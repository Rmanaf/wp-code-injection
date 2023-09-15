<?php

/**
 * Licensed under MIT (https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE)
 * Copyright (c) 2018 Rmanaf <me@rmanaf.com>
 */

namespace ci;


use DateInterval,  DateTime;


/**
 * The Database class handles interactions with the database for tracking and reporting activities.
 *
 * This class is responsible for managing database interactions related to tracking and reporting
 * activities within the context of the code snippet tracking system. It encapsulates various
 * functions that generate SQL queries for retrieving activity reports and recording activity events.
 * The class utilizes DateInterval and DateTime objects for handling time calculations and formatting.
 *
 * @namespace ci
 * @since 2.2.6
 */
class Database
{

    /**
     * The name of the database table used for storing activity records.
     *
     * This constant property stores the name of the database table where activity records
     * are stored for tracking purposes. The table is used to record various details about
     * activity events, such as timestamps, IP addresses, post IDs, and error statuses.
     *
     * @since 2.2.6
     */
    const table_activities_name = 'ci_activities';


    /**
     * The option name for storing the database version.
     *
     * This constant property represents the option name used to store the version of the
     * database schema. It is utilized to keep track of the database schema version and
     * facilitate updates when needed.
     *
     * @since 2.2.6
     */
    const db_version_option     = 'ci_db_version';


    /**
     * An array of error messages for different error codes.
     *
     * This constant property holds an array of error messages associated with specific
     * error codes. The array provides human-readable explanations for different types
     * of errors that can occur during activity tracking and reporting.
     *
     * @since 2.2.6
     */
    const db_errors             = array(
        '',                                   // 0: No error
        'PHP scripts are disabled',           // 1
        'Code not found',                     // 2
        'Infinite Loop',                      // 3
        'An unexpected error occurred',       // 4
        'Key not found',                      // 5
        'Unauthorized Request',               // 6
    );


    private static $db_shortcodes_types = array('HTML', 'PHP');


    /**
     * Initializes the plugin by performing necessary setup actions.
     *
     * This static function serves as the initialization point for the plugin. It is called
     * upon plugin activation or when required during the plugin's lifecycle. The primary
     * purpose of this function is to ensure the proper setup of the plugin's components.
     * It accomplishes this by invoking the 'check_db' method, which verifies and updates
     * the plugin's database schema as needed to maintain compatibility.
     *
     * @since 2.4.12
     */
    static function init()
    {
        // Perform the necessary setup actions, including checking and updating the database
        self::check_db();
    }




    /**
     * Checks and updates the custom database table used by the plugin.
     *
     * This private function is responsible for verifying and updating the database
     * schema to ensure compatibility with the plugin's version. It compares the stored
     * database version with the current version of the plugin. If the versions match,
     * no action is taken. If they differ, the function creates or updates a custom
     * database table with necessary columns. This table is used to store activity log
     * entries associated with various events triggered by the plugin.
     *
     * @since 2.2.6
     */
    private static function check_db()
    {
        global $wpdb;

        // Retrieve the stored database version from options
        $stored_db_version = get_option(self::db_version_option, '');

        // Compare database version with the current plugin version
        if ($stored_db_version === __CI_VERSION__) {
            return; // No action needed if versions match
        }

        // Define the custom table name for storing activity log entries
        $table_name = self::table_activities_name;

        // Get charset collation for creating the table
        $charset_collate = $wpdb->get_charset_collate();

        // SQL query for creating the custom table with required columns
        $sql = "
            CREATE TABLE $table_name (
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
            ) $charset_collate;
        ";

        // Include necessary WordPress upgrade functions
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Execute the SQL query and update the table schema
        dbDelta($sql);

        // Update the stored database version option with the current version
        update_option(self::db_version_option, __CI_VERSION__);
    }





    /**
     * Retrieves an array of code snippets with associated metadata.
     *
     * This static function queries the WordPress database to retrieve an array of code
     * snippet posts along with their associated metadata. It retrieves posts that have
     * the 'code' post type, have a meta key 'code_options', and have a post date earlier
     * than the current date and time. The retrieved results are ordered in descending order
     * based on the post date.
     *
     * @since 2.4.8
     *
     * @return array An array of objects containing code snippet post data and metadata.
     */
    static function get_codes()
    {

        global $wpdb;

        // Define the table names and placeholders
        $posts_table = $wpdb->posts;
        $postmeta_table = $wpdb->postmeta;
        $code_options_meta_key = 'code_options';
        $code_post_type = 'code';

        // Construct the SQL query to retrieve code snippets
        $query = $wpdb->prepare(
            "SELECT $posts_table.*, $postmeta_table.*
            FROM $posts_table, $postmeta_table
            WHERE $posts_table.ID = $postmeta_table.post_id
            AND $postmeta_table.meta_key = %s
            AND $posts_table.post_type = %s
            AND $posts_table.post_date < NOW()
            ORDER BY $posts_table.post_date DESC",
            $code_options_meta_key,
            $code_post_type
        );

        // Execute the query and fetch results as an array of objects
        return $wpdb->get_results($query);
    }




    /**
     * Retrieves a code snippet by its title.
     *
     * This function queries the WordPress database to retrieve a code snippet
     * post based on the provided title. It fetches both post and postmeta
     * information associated with the given title.
     *
     * @since 2.4.14
     *
     * @param string $title The title of the code snippet to retrieve.
     * @return object|null An object containing the code snippet's post and postmeta data as properties,
     *                    or null if no matching snippet is found.
     */
    static function get_code_by_title($title)
    {
        global $wpdb;

        // Construct the SQL query to retrieve the code snippet by title
        $query = "SELECT $wpdb->posts.*, $wpdb->postmeta.*
                FROM $wpdb->posts, $wpdb->postmeta
                WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id 
                AND $wpdb->posts.post_title = '$title'
                AND $wpdb->posts.post_type = 'code'
                LIMIT 1";

        // Execute the query and fetch a single row as an object
        $row = $wpdb->get_row($query);

        // If no matching snippet is found, return null
        if (!$row) {
            return null;
        }

        return $row;
    }



    /**
     * Retrieves a code snippet by its unique slug.
     *
     * This function queries the WordPress database to retrieve a code snippet
     * post based on the provided unique slug. It fetches both post and postmeta
     * information associated with the given slug.
     *
     * @since 2.4.8
     *
     * @param string $slug The unique slug of the code snippet to retrieve.
     * @return object|null An object containing the code snippet's post and postmeta data as properties,
     *                    or null if no matching snippet is found.
     */
    static function get_code_by_slug($slug)
    {
        global $wpdb;

        // Construct the SQL query to retrieve the code snippet by slug
        $query = "SELECT $wpdb->posts.*, $wpdb->postmeta.*
                FROM $wpdb->posts, $wpdb->postmeta
                WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id 
                AND $wpdb->postmeta.meta_key = 'code_slug' 
                AND $wpdb->postmeta.meta_value = '$slug'
                AND $wpdb->posts.post_type = 'code'
                LIMIT 1";

        // Execute the query and fetch a single row as an object
        $row = $wpdb->get_row($query);

        // If no matching snippet is found, return null
        if (!$row) {
            return null;
        }

        return $row;
    }




    /**
     * Records an activity event in the database for tracking purposes.
     *
     * This static function is responsible for recording activity events in the database
     * for tracking purposes. It records various details such as the event type, associated
     * code snippet, error status, and relevant identifiers like IP address, post ID, blog ID,
     * and user ID. The function first checks whether the event should be tracked based on
     * provided conditions. If tracking is enabled, the function inserts a new activity record
     * into the database table, capturing key information about the event.
     *
     * @since 2.2.6
     *
     * @param int $type The type of activity event (0 for HTML, CSS, JavaScript, 1 for PHP, etc.).
     * @param string|null $code The code snippet associated with the activity.
     * @param int $error The error status (0 for no error, 1 for error).
     * @param int|null $id The post ID or identifier associated with the event (used in specific cases).
     */
    static function record_activity($type = 0, $code = null, $error = 0, $id = null)
    {
        global $wpdb, $post;

        // Check if tracking is required based on provided conditions
        if ($code !== null && $type === 0 && $id !== null) {
            $co = Metabox::get_code_options($id);
            if (!$co['code_tracking']) {
                return; // No tracking needed
            }
        }

        // Capture essential information for the activity record
        $ip = Helpers::get_ip_address();
        $table_name = self::table_activities_name;
        $time = current_time('mysql', 1);
        $start = new DateTime($time);
        $blog = get_current_blog_id();
        $user = get_current_user_id();
        $start->sub(new DateInterval("PT10S"));
        $start_date = $start->format('Y-m-d H:i:s');
        $post_param = isset($post->ID) && is_single() ? $post->ID : null;
        $post_query_param = is_null($post_param) ? "`post` IS NULL" : "`post` = '$post_param'";
        $ip_query_param = is_null($ip) ? "`ip` IS NULL" : "`ip` = '$ip'";

        // Construct and execute query to check if similar event was recorded recently
        $query = $wpdb->prepare("
            SELECT COUNT(*)
            FROM `$table_name`
            WHERE $ip_query_param 
            AND `type` = %d 
            AND `blog` = %d 
            AND `user` = %d 
            AND $post_query_param 
            AND `code` = %s 
            AND `time` BETWEEN %s AND %s",
            $type, $blog, $user, $code, $start_date, $time
        );
        $count = $wpdb->get_var($query);

        // If no similar event was recently recorded, insert the activity record
        if ($count === 0) {
            // Insert the activity record into the database
            $wpdb->insert(
                self::table_activities_name,
                array(
                    'time'  => $time,
                    'ip'    => $ip,
                    'post'  => $post_param,
                    'blog'  => $blog,
                    'user'  => $user,
                    'type'  => $type,
                    'code'  => $code,
                    'error' => $error
                )
            );
        }
    }




    /**
     * Generates a SQL query for retrieving a weekly activity report for a specific code snippet.
     *
     * This static function constructs a SQL query for retrieving a weekly activity report
     * for a given code snippet within a specified time range. The query calculates metrics
     * such as unique hits, total hits, and total errors for each day of the week and each hour
     * of the day within the specified range. The report is organized by weekday and hour,
     * providing insights into the snippet's activity patterns.
     *
     * @since 2.4.5
     *
     * @param int $post_id The ID of the code snippet post.
     * @param DateTime $start The start date of the report's time range.
     * @param DateTime $end The end date of the report's time range.
     * @return string A SQL query for generating the weekly activity report.
     */
    static function get_weekly_report_query($post_id, $start, $end)
    {

        global $wpdb;

        $table_name = self::table_activities_name;

        // Convert the start and end dates to MySQL format
        $start = $start->format('Y-m-d H:i:s');
        $end   = $end->format('Y-m-d H:i:s');

        // Get the title of the code snippet based on its post ID
        $post = get_post($post_id);
        $code = $post->post_title;

        // Construct and return the SQL query for generating the weekly report
        return $wpdb->prepare(
            "
            SELECT time, DAYOFWEEK(time) AS weekday, HOUR(time) AS hour,
                COUNT(DISTINCT ip) AS unique_hits,
                COUNT(*) AS total_hits,
                SUM(CASE WHEN error != '' THEN 1 ELSE 0 END) AS total_errors
            FROM $table_name
            WHERE code = %s AND (time BETWEEN %s AND %s)
            GROUP BY weekday, hour",
            $code,
            $start,
            $end
        );
    }





    /**
     * Generates a SQL query for retrieving a monthly activity report for a specific code snippet.
     *
     * This static function constructs a SQL query for retrieving a monthly activity report
     * for a given code snippet within a specified time range. The query calculates metrics
     * such as unique hits, total hits, and total errors for each day within the specified range.
     * The report is organized by month and day, providing insights into the snippet's activity.
     *
     * @since 2.4.5
     *
     * @param int $post_id The ID of the code snippet post.
     * @param DateTime $start The start date of the report's time range.
     * @param DateTime $end The end date of the report's time range.
     * @return string A SQL query for generating the monthly activity report.
     */
    static function get_monthly_report_query($post_id, $start, $end)
    {

        global $wpdb;

        $table_name = self::table_activities_name;

        // Convert the start and end dates to MySQL format
        $start = $start->format('Y-m-d H:i:s');
        $end   = $end->format('Y-m-d H:i:s');

        // Construct and return the SQL query for generating the monthly report
        return $wpdb->prepare(
            "
            SELECT time, MONTHNAME(time) AS month, DAYOFMONTH(time) AS day,
                COUNT(DISTINCT ip) AS unique_hits,
                COUNT(*) AS total_hits,
                SUM(CASE WHEN error != '' THEN 1 ELSE 0 END) AS total_errors
            FROM $table_name
            WHERE code = (SELECT post_title FROM $wpdb->posts WHERE ID = %d) AND (time BETWEEN %s AND %s)
            GROUP BY month, day",
            $post_id,
            $start,
            $end
        );
    }
}
