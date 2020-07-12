<?php

/**
 * MIT License <https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE>
 * Copyright (c) 2020 Arman Afzal <rman.afzal@gmail.com>
 */

if (!class_exists('WP_CI_Database')) {

    class WP_CI_Database
    {

        public static $table_activities_name = 'dcp_ci_activities';

        private static $db_errors = [
            '',                                   // 0 no error
            'PHP scripts are disabled',           // 1
            'Code not found',                     // 2
            'Infinite Loop',                      // 3
            'An unexpected error occurred',       // 4
            'Key not found',                      // 5
            'Unauthorized Request',               // 6
        ];

        private static $db_shortcodes_types = ['HTML', 'PHP'];

        private static $db_version  = '1.0.0';

        function __construct()
        {

            $this->check_db();

        }

         /**
         * @since 2.2.6
         */
        private function check_db()
        {

            global $wpdb;

            $dbv = get_option('wp_dcp_code_injection_db_version', '');

            if($dbv == self::$db_version){

                return;

            }

            $table_name = self::$table_activities_name;

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

            update_option('wp_dcp_code_injection_db_version', self::$db_version);

        }


         /**
         * @since 2.2.6
         */
        public function record_activity($type = 0, $code = null, $error = 0, $id = null)
        {

            global $wpdb, $post;


            if($code != null && $type == 0 && $id != null)
            {

                $co = WP_CI_Code_Metabox::get_code_options($id);

                if($co['code_tracking'] == false)
                {
                    return;
                }

            }



            $ip =  $this->get_ip_address();


            if($ip === null){
                //return;
            }



            $table_name = self::$table_activities_name;

            $time = current_time('mysql' , 1);


            $start = new DateTime($time);

            $start->sub(new DateInterval("PT10S"));

            $start_date = $start->format('Y-m-d H:i:s');


            $blog = get_current_blog_id();

            $user = get_current_user_id();

            $post_param = isset($post->ID) && is_single() ? $post->ID : null;

            $post_query_param = is_null($post_param)  ? "`post` IS NULL" : "`post` = '$post_param'";
           
            $ip_query_param =  is_null($ip)  ? "`ip` IS NULL" : "`ip` = '$ip'";


            $query =  "SELECT COUNT(*) FROM `$table_name` WHERE 
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
                    self::$table_activities_name,
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
         * @since 1.0.0
         */
        public function get_ip_address()
        {

            foreach (['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'] as $key) {

                if (array_key_exists($key,(array) $_SERVER) === true) {

                    foreach (explode(',', $_SERVER[$key]) as $ip) {
                        $ip = trim($ip);

                        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                            return $ip;
                        }

                    }

                }

            }

        }

    }

}