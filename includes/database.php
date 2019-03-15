<?php
/**
 * Apache License, Version 2.0
 * 
 * Copyright (C) 2018 Arman Afzal <rman.afzal@gmail.com>
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *     http://www.apache.org/licenses/LICENSE-2.0
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 * 
 * Third Party Licenses :
 * 
 * tagEditor :
 * 
 * MIT License
 *
 * 
 * 
 * CodeMirror :
 * 
 * MIT License
 *
 * Copyright (C) 2017 by Marijn Haverbeke <marijnh@gmail.com> and others
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
 
 /**
 * @author Arman Afzal <rman.afzal@gmail.com>
 * @package WP_Divan_Control_Panel
 * @version 2.2.8
 */

if (!class_exists('WP_CI_Database')) {

    class WP_CI_Database
    {

        public static $table_activities_name = 'dcp_ci_activities';

        private static $db_errors = [
            '',                                   // 0 no error
            'Rendering of PHP code is disabled',  // 1
            'Code not founds',                    // 2
            'Infinity loop ignored',              // 3
            'An unexpected error occurred',       // 4
            'Key not founds',                     // 5
        ];

        private static $db_shortcodes_types = ['HTML', 'PHP'];

        private static $db_version  = '1.0.0';

        function __construct()
        {

            $this->check_db();

        }

         /**
         * Checks database
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
         * Records activity into the database
         * @since 2.2.6
         */
        public function record_activity($type = 0, $code = null, $error = 0)
        {

            global $wpdb, $post;


            if($code != null)
            {

                $co = WP_CI_Code_Metabox::get_code_options($code);

                if($co['code_tracking'] === false)
                {
                    return;
                }

            }



            $ip =  $this->get_ip_address();

            if($ip === null){

                //return;

            }



            /**
             * type 0 for HTML, CSS and, javascript
             * type 1 for PHP
             */
            $wpdb->insert(
                self::$table_activities_name,
                [
                    'time'  => current_time('mysql' , 1),
                    'ip'    => $ip,
                    'post'  => isset($post->ID) && is_single() ? $post->ID : null,
                    'blog'  => get_current_blog_id(),
                    'user'  => get_current_user_id(),
                    'type'  => $type,
                    'code'  => $code,
                    'error' => $error
                ]
            );

            

        }

        /**
         * Returns client IP address
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