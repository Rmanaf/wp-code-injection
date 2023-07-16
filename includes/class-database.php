<?php

/**
 * Licensed under MIT (https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE)
 * Copyright (c) 2018 Rmanaf <me@rmanaf.com>
 */

namespace ci;

use DateInterval;
use DateTime;

class Database
{

    const table_activities_name = 'ci_activities';
    const db_version_option     = 'ci_db_version';
    const db_errors             = array(
            '',                                   // 0 no error
            'PHP scripts are disabled',           // 1
            'Code not found',                     // 2
            'Infinite Loop',                      // 3
            'An unexpected error occurred',       // 4
            'Key not found',                      // 5
            'Unauthorized Request',               // 6
    );


    private static $db_shortcodes_types = ['HTML', 'PHP'];


    /**
     * @since 2.4.12
     */
    static function init()
    {
        self::check_db();
    }



    /**
     * @since 2.2.6
     */
    private static function check_db()
    {

        global $wpdb;

        $dbv = get_option(self::db_version_option, '');

        if ($dbv == __CI_VERSION__) {
            return;
        }

        $table_name = self::table_activities_name;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                blog smallint,
                time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                ip tinytext,
                post smallint,
                user smallint,
                code tinytext,
                type smallint,
                error smallint,
                PRIMARY KEY  (id)
                ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($sql);

        update_option(self::db_version_option , __CI_VERSION__);
    }



    /**
     * @since 2.4.8
     */
    static function get_codes()
    {

        global $wpdb;

        $query = "SELECT $wpdb->posts.*, $wpdb->postmeta.*
                FROM $wpdb->posts, $wpdb->postmeta
                WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id 
                AND $wpdb->postmeta.meta_key = 'code_options' 
                AND $wpdb->posts.post_type = 'code'
                AND $wpdb->posts.post_date < NOW()
                ORDER BY $wpdb->posts.post_date DESC";

        return $wpdb->get_results($query, OBJECT);
    }

    /**
     * @since 2.4.8
     */
    static function get_code_by_slug($slug)
    {

        global $wpdb;

        $query = "SELECT $wpdb->posts.*, $wpdb->postmeta.*
                FROM $wpdb->posts, $wpdb->postmeta
                WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id 
                AND $wpdb->postmeta.meta_key = 'code_slug' 
                AND $wpdb->postmeta.meta_value = '$slug'
                AND $wpdb->posts.post_type = 'code'
                LIMIT 1";

        $posts = $wpdb->get_results($query, ARRAY_A);

        if (empty($posts)) {
            return null;
        }

        return $posts[0];
    }


    /**
     * @since 2.2.6
     */
    static function record_activity($type = 0, $code = null, $error = 0, $id = null)
    {

        global $wpdb, $post;


        if ($code != null && $type == 0 && $id != null) {

            $co = Metabox::get_code_options($id);

            if ($co['code_tracking'] == false) {
                return;
            }
        }

        $ip         = Helpers::get_ip_address();
        $table_name = self::table_activities_name;
        $time       = current_time('mysql', 1);
        $start      = new DateTime($time);
        $blog       = get_current_blog_id();
        $user       = get_current_user_id();

        $start->sub(new DateInterval("PT10S"));

        $start_date       = $start->format('Y-m-d H:i:s');
        $post_param       = isset($post->ID) && is_single() ? $post->ID : null;
        $post_query_param = is_null($post_param)  ? "`post` IS NULL" : "`post` = '$post_param'";
        $ip_query_param   =  is_null($ip)  ? "`ip` IS NULL" : "`ip` = '$ip'";
        
        
        $query  =  "SELECT COUNT(*) FROM `$table_name` WHERE 
                            $ip_query_param AND 
                            `type` = '$type' AND 
                            `blog` = '$blog' AND
                            `user` = '$user' AND
                            $post_query_param AND
                            `code` = '$code' AND
                            `time` BETWEEN '$start_date' AND '$time'";

        $count = $wpdb->get_var($query);

        if ($count == 0) {

            /**
             * type 0 for HTML, CSS and, javascript
             * type 1 for PHP
             */
            $wpdb->insert(
                self::table_activities_name,
                [
                    'time'  => $time,
                    'ip'    => $ip,
                    'post'  => $post_param,
                    'blog'  => $blog,
                    'user'  => $user,
                    'type'  => $type,
                    'code'  => $code,
                    'error' => $error
                ]
            );
        }
    }



    /**
     * @since 2.4.5
     */
    static function get_weekly_report_query($post_id, $start, $end)
    {

        $table_name = self::table_activities_name;

        // get post title
        $post = get_post($post_id);
        $code = $post->post_title;

        // convert dates to mysql format
        $start = $start->format('Y-m-d H:i:s');
        $end   = $end->format('Y-m-d H:i:s');

        return "SELECT time, WEEKDAY(time) weekday, HOUR(time) hour,
                        COUNT(DISTINCT ip) unique_hits,
                        COUNT(*) total_hits,
                        SUM(case when error = '' then 0 else 1 end) total_errors
                        FROM $table_name
                        WHERE code='$code' AND (time BETWEEN '$start' AND '$end')
                        GROUP BY weekday, hour";
    }

    /**
     * @since 2.4.5
     */
    static function get_monthly_report_query($post_id, $start, $end)
    {

        $table_name = self::table_activities_name;

        // get post title
        $post = get_post($post_id);
        $code = $post->post_title;

        // convert dates to mysql format
        $start = $start->format('Y-m-d H:i:s');
        $end   = $end->format('Y-m-d H:i:s');

        return "SELECT time, MONTHNAME(time) month, DAYOFMONTH(time) day,
                        COUNT(DISTINCT ip) unique_hits,
                        COUNT(*) total_hits,
                        SUM(case when error = '' then 0 else 1 end) total_errors
                        FROM $table_name
                        WHERE code='$code' AND (time BETWEEN '$start' AND '$end')
                        GROUP BY month, day";
    }


}
