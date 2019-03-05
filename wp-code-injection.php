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

 /*
    Plugin Name: Code Injection
    Plugin URI: https://wordpress.org/plugins/code-injection
    Description: Allows You to inject code snippets into the pages by just using the Wordpress shortcode
    Version: 2.2.8
    Author: Arman Afzal
    Author URI: https://github.com/Rmanaf
    License: Apache License, Version 2.0
    Text Domain: code-injection
 */

/**
 * @author Arman Afzal <rman.afzal@gmail.com>
 * @package WP_Divan_Control_Panel
 * @version 2.2.8
 */

defined('ABSPATH') or die;

require_once __DIR__ . '/wp-code-injection-plugin-widget.php';

require_once __DIR__ . '/includes/calendar-heatmap.php';

require_once __DIR__ . '/includes/package-manager.php';

require_once __DIR__ . '/includes/code-metabox.php';




if (!class_exists('WP_Code_Injection_Plugin')) {

    class WP_Code_Injection_Plugin
    {

        private $code_meta_box;

        private $package_manager;

        private static $text_domain = 'code-injection';

        private static $db_version  = '1.0.0';

        private static $role_version = '1.0.0';

        private static $db_shortcodes_types = ['HTML', 'PHP'];

        private static $db_errors = [
            '',                                   // 0 no error
            'Rendering of PHP code is disabled',  // 1
            'Code not founds',                    // 2
            'Infinity loop ignored',              // 3
            'An unexpected error occurred',       // 4
        ];

        function __construct()
        {

            $this->check_db();

            $this->init_meta_box();

            $this->init_package_manager();


            // check "Unsafe" settings
            $use_shortcode = get_option('wp_dcp_unsafe_widgets_shortcodes', 0);

            if ($use_shortcode) {

                add_filter('widget_text', 'shortcode_unautop');
                add_filter('widget_text', 'do_shortcode');

            }

            // create CPT
            add_shortcode('inject', [$this, 'ci_shortcode']);
            add_shortcode('unsafe', [$this, 'unsafe_shortcode']);

            add_action('init', [$this, 'create_posttype']);
            add_action('admin_init', [$this, 'admin_init']);
            add_action('admin_head', [$this, 'hide_post_title_input']);
            add_action('admin_head', [$this, 'remove_mediabuttons']);
            
            add_action('admin_enqueue_scripts', [$this, 'print_scripts']);
            add_action('widgets_init', [$this, 'widgets_init']);
            
            add_filter('title_save_pre', [$this, 'auto_generate_post_title']);
            add_filter('user_can_richedit', [$this, 'disable_wysiwyg']);
            add_filter('post_row_actions', [$this, 'remove_quick_edit'], 10, 2);
            add_filter('manage_code_posts_columns', [$this, 'manage_code_posts_columns']);
            add_action('manage_code_posts_custom_column' , [$this, 'manage_code_posts_custom_column'], 10, 2 );

            add_filter('dcp_shortcodes_list', [&$this, 'add_shortcode_to_list']);


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

            $table_name = self::table_name();

            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE $table_name (
                id mediumint(9) NOT NULL AUTO_INCREMENT,
                blog smallint,
                time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                ip tinytext NOT NULL,
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
        private function record_activity($type = 0, $code = null, $error = 0)
        {

            global $wpdb, $post;

            /**
             * type 0 for HTML, CSS and, javascript
             * type 1 for PHP
             */
            $wpdb->insert(
                self::table_name(),
                [
                    'time'  => current_time('mysql' , 1),
                    'ip'    => $this->get_ip_address(),
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
        private function get_ip_address()
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

        /**
         * Prints admin scripts
         * @since 1.0.0
         */
        public function print_scripts()
        {

            $ver = $this->get_version();

            wp_enqueue_style('dcp-code-injection', plugins_url('assets/wp-code-injection-admin.css', __FILE__), [], $ver, 'all');
            
            if( $this->is_settings_page() ) {

                
                wp_enqueue_style('dcp-tag-editor', plugins_url('assets/jquery.tag-editor.css', __FILE__), [], $ver, 'all');

                wp_enqueue_script('dcp-caret', plugins_url('assets/jquery.caret.min.js', __FILE__), ['jquery'], $ver, true);
                wp_enqueue_script('dcp-tag-editor', plugins_url('assets/jquery.tag-editor.min.js', __FILE__), [], $ver, true);
                

                wp_enqueue_script('dcp-code-injection', plugins_url('assets/wp-code-injection-admin.js', __FILE__), [], $ver, false);

            }

            // "CI" assets
            if (!$this->is_code_page()) {
                return;
            }


            wp_enqueue_style('dcp-codemirror', plugins_url('assets/codemirror/lib/codemirror.css', __FILE__), [], $ver, 'all');
            wp_enqueue_style('dcp-codemirror-dracula', plugins_url('assets/codemirror/theme/dracula.css', __FILE__), [], $ver, 'all');

            //codemirror
            wp_enqueue_script('dcp-codemirror', plugins_url('assets/codemirror/lib/codemirror.js', __FILE__), ['jquery'], $ver, false);

            // addons
            wp_enqueue_script('dcp-codemirror-addon-fold', plugins_url('assets/codemirror/addons/fold/xml-fold.js', __FILE__), [], $ver, false);
            wp_enqueue_script('dcp-codemirror-addon-closebrackets', plugins_url('assets/codemirror/addons/edit/closebrackets.js', __FILE__), [], $ver, false);
            wp_enqueue_script('dcp-codemirror-addon-matchbrackets', plugins_url('assets/codemirror/addons/edit/matchbrackets.js', __FILE__), [], $ver, false);
            wp_enqueue_script('dcp-codemirror-addon-matchtags', plugins_url('assets/codemirror/addons/edit/matchtags.js', __FILE__), [], $ver, false);
            wp_enqueue_script('dcp-codemirror-addon-closetag', plugins_url('assets/codemirror/addons/edit/closetag.js', __FILE__), [], $ver, false);
            wp_enqueue_script('dcp-codemirror-addon-search', plugins_url('assets/codemirror/addons/search/match-highlighter.js', __FILE__), [], $ver, false);
            wp_enqueue_script('dcp-codemirror-addon-fullscreen', plugins_url('assets/codemirror/addons/display/fullscreen.js', __FILE__), [], $ver, false);

            //keymap
            wp_enqueue_script('dcp-codemirror-keymap', plugins_url('assets/codemirror/keymap/sublime.js', __FILE__), [], $ver, false);

            //mode
            wp_enqueue_script('dcp-codemirror-mode-xml', plugins_url('assets/codemirror/mode/xml/xml.js', __FILE__), [], $ver, false);
            wp_enqueue_script('dcp-codemirror-mode-js', plugins_url('assets/codemirror/mode/javascript/javascript.js', __FILE__), [], $ver, false);
            wp_enqueue_script('dcp-codemirror-mode-css', plugins_url('assets/codemirror/mode/css/css.js', __FILE__), [], $ver, false);
            wp_enqueue_script('dcp-codemirror-mode-htmlmixed', plugins_url('assets/codemirror/mode/htmlmixed/htmlmixed.js', __FILE__), [], $ver, false);
            wp_enqueue_script('dcp-codemirror-mode-clike', plugins_url('assets/codemirror/mode/clike/clike.js', __FILE__), [], $ver, false);
            wp_enqueue_script('dcp-codemirror-mode-php', plugins_url('assets/codemirror/mode/php/php.js', __FILE__), [], $ver, false);

            wp_enqueue_script('dcp-code-injection', plugins_url('assets/code-editor.js', __FILE__), [], $ver, false);

        }


        public function init_meta_box(){

            $this->code_meta_box = new WP_Code_Metabox();

        }

        public function init_package_manager(){

            $this->package_manager = new WP_Package_Manager();

        }


        /**
         * "Unsafe" shortcode
         * @since 2.2.6
         */
        public function unsafe_shortcode($atts = [], $content = null)
        {

            $use_php = get_option('wp_dcp_unsafe_widgets_php', false);

            $debug = get_option('wp_dcp_unsafe_debug', false);

            if (!$use_php) {

                $this->record_activity(1 , null , 1);

                if($debug){

                    return self::$db_errors[1];

                }

                return;

            }

            $ignore_keys = get_option('wp_dcp_unsafe_ignore_keys', false);

            if(!$ignore_keys){

                extract(shortcode_atts(['key' => ''], $atts));

                $keys = $this->extract_keys(get_option('wp_dcp_unsafe_keys', ''));

                if (!empty($keys) && !in_array($key, $keys)) {

                    $this->record_activity(1 , $key , 3);

                    if($debug){

                        return self::$db_errors[3];
    
                    }

                    return;

                }

            }

            $html = $content;

            if (strpos($html, "<" . "?php") !== false) {

                ob_start();

                eval("?" . ">" . $html);

                try{

                    $html = ob_get_contents();

                }
                catch(Exception $ex)
                {

                    $this->record_activity(1 , $key , 4);

                    if($debug) {

                        throw $ex;

                    }

                    return;

                }

                ob_end_clean();

            }

            return $html;

        }


        /**
         * "CI" shortcode renderer 
         * @since 1.0.0
         */
        public function ci_shortcode($atts = [], $content = null)
        {

            global $post;

            $temp_post = $post;

            extract(shortcode_atts(['id' => ''], $atts));

            if (empty($id)) {

                $this->record_activity(0 , null , 2);

                return;

            }
            

            $code = get_page_by_title($id, OBJECT, 'codes');

            if (is_object($code)) {

                $render_shortcodes = get_option('wp_dcp_code_injection_allow_shortcode', false);

                $nested_injections = $this->get_shortcode_by_name($code->post_content, 'inject');

                foreach ($nested_injections as $i) {

                    $params = $i['params'];

                    if (isset($params['id']) && $params['id'] == $id) {

                        $this->record_activity(0 , $id , 3);

                        return;

                    }

                }

                $post = $temp_post;

                if ($render_shortcodes) {

                    $this->record_activity(0 , $id, 0);

                    return do_shortcode($code->post_content);

                } else {

                    return $code->post_content;

                }

            }

        }

        /**
         * Add shortcodes to the DCP shortcodes list
         * @since 1.0.0
         */
        public function add_shortcode_to_list($list)
        {

            $list[] = [
                'template' => "[inject id='#']",
                'description' => __("Injects code snippets into the content", self::$text_domain),
                'readme' =>  __DIR__ . '/README.md',
                'example' => __DIR__ . '/EXAMPLE.md'
            ];

            $list[] = [
                'template' => "[unsafe key='#']",
                'description' => __("Allows to use of PHP syntaxes", self::$text_domain),
                'readme' =>  __DIR__ . '/README.md',
                'example' => __DIR__ . '/EXAMPLE.md'
            ];

            return $list;

        }


        /**
         * Update users capabilities
         * @since 2.2.6
         */
        private function update_caps(){

            $roles = ['developer' , 'administrator'];

            foreach($roles as $role){

                $ur = get_role($role);

                if(!isset($ur)){

                    continue;

                }

                foreach ( [ 'publish',  'delete',  'delete_others', 'delete_private',
                        'delete_published',  'edit', 'edit_others',  'edit_private',
                        'edit_published', 'read_private'
                    ] as $cap ) {
    
                    $ur->add_cap( "{$cap}_code" );
                    $ur->add_cap( "{$cap}_codes" );
    
                }

            }

        }

        /**
         * register settings
         * @since 1.0.0
         */
        public function admin_init()
        {

            $this->register_roles();

		    $this->update_caps();

            // checks for control panel plugin 

            if (defined('DIVAN_CONTROL_PANEL')) {

                global $_DCP_PLUGINS;

                // control panel will take setting section

                $group = 'dcp-settings-general';

                array_push($_DCP_PLUGINS, ['slug' => self::$text_domain, 'version' => $this->get_version()]);

            } else {

                $group = 'general';

            }


            // settings section
            add_settings_section(
                'wp_code_injection_plugin',
                __('Code Injection', self::$text_domain) . "<span class=\"gdcp-version-box wp-ui-notification\">" . $this->get_version() . "<span>",
                [&$this, 'settings_section_cb'],
                $group
            );



            // register "CI" settings
            register_setting($group, 'wp_dcp_code_injection_allow_shortcode', ['default' => false]);

            // register "Unsafe" settings
            register_setting($group, 'wp_dcp_unsafe_debug', ['default' => false]);
            register_setting($group, 'wp_dcp_unsafe_widgets_shortcodes', ['default' => false]);
            register_setting($group, 'wp_dcp_unsafe_keys', ['default' => '']);
            register_setting($group, 'wp_dcp_unsafe_widgets_php', ['default' => false]);
            register_setting($group, 'wp_dcp_unsafe_ignore_keys', ['default' => false]);



            // "CI" fields
            add_settings_field(
                'wp_dcp_code_injection_allow_shortcode',
                __("Shortcodes", self::$text_domain),
                [&$this, 'settings_field_cb'],
                $group,
                'wp_code_injection_plugin',
                ['label_for' => 'wp_dcp_code_injection_allow_shortcode']
            );


            // "Unsafe fields"
            add_settings_field(
                'wp_dcp_unsafe_widgets_shortcodes',
                "",
                [&$this, 'settings_field_cb'],
                $group,
                'wp_code_injection_plugin',
                ['label_for' => 'wp_dcp_unsafe_widgets_shortcodes']
            );

            add_settings_field(
                'wp_dcp_unsafe_widgets_php',
                "",
                [&$this, 'settings_field_cb'],
                $group,
                'wp_code_injection_plugin',
                ['label_for' => 'wp_dcp_unsafe_widgets_php']
            );

            add_settings_field(
                'wp_dcp_unsafe_debug',
                "",
                [&$this, 'settings_field_cb'],
                $group,
                'wp_code_injection_plugin',
                ['label_for' => 'wp_dcp_unsafe_debug']
            );
            
            add_settings_field(
                'wp_dcp_unsafe_ignore_keys',
                __("Activator Keys", self::$text_domain),
                [&$this, 'settings_field_cb'],
                $group,
                'wp_code_injection_plugin',
                ['label_for' => 'wp_dcp_unsafe_ignore_keys']
            );

            add_settings_field(
                'wp_dcp_unsafe_keys',
                "",
                [&$this, 'settings_field_cb'],
                $group,
                'wp_code_injection_plugin',
                ['label_for' => 'wp_dcp_unsafe_keys']
            );

           

        }


        /**
         * Settings section header
         * @since 1.0.0
         */
        public function settings_section_cb()
        {
        
            ?>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label>
                                <?php _e("Bug & Issues Reporting"); ?>
                            </label>
                        </th>
                        <td>
                            <?php _e("<p>If you faced any issues, please tell us on <strong><a target=\"_blank\" href=\"https://github.com/Rmanaf/wp-code-injection/issues/new\">Github</a></strong>"); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
            <?php
        }


        /**
         * Retrieve keys from string
         * @since 2.2.6
         */
        private function extract_keys($text)
        {

            return array_filter(explode(',', $text), function ($elem) {
                
                return preg_replace('/\s/', '', $elem);

            });

        }

        /** 
         * Settings section 
         * @since 1.0.0
         */
        public function settings_field_cb($args)
        {

            switch ($args['label_for']) {

                case 'wp_dcp_code_injection_allow_shortcode':

                    $nested_shortcode = get_option('wp_dcp_code_injection_allow_shortcode', false);

                    ?>
                    <label>
                        <input type="checkbox" value="1" id="wp_dcp_code_injection_allow_shortcode" name="wp_dcp_code_injection_allow_shortcode" <?php checked($nested_shortcode, true); ?> />
                        <?php _e("Render nested shortcodes in <code>[inject]</code>", self::$text_domain); ?>
                    </label>
                    <?php
                    break;

                case 'wp_dcp_unsafe_keys':

                    $keys = get_option('wp_dcp_unsafe_keys', '');

                    ?>
                        <p class="ack-head-wrapper"><span class="ack-header"><strong><?php _e("Keys:", "code-injection"); ?></strong></span><a class="button ack-new" href="javascript:void(0);" id="wp_dcp_generate_key">Generate</a><p>
                        <textarea data-placeholder="Enter Keys:" class="large-text code" id="wp_dcp_unsafe_keys" name="wp_dcp_unsafe_keys"><?php echo $keys; ?></textarea>
                        <dl>
                            <dd>
                                <p class="description">
                                    <?php _e("Enter an unique and strong key that contains digits and characters.", self::$text_domain); ?>
                                </p>
                            </dd>   
                        </dl>
                    <?php
                    break;

                case 'wp_dcp_unsafe_ignore_keys':

                    $ignore_keys = get_option('wp_dcp_unsafe_ignore_keys', false);

                    ?>
                    <label>
                        <input type="checkbox" value="1" id="wp_dcp_unsafe_ignore_keys" name="wp_dcp_unsafe_ignore_keys" <?php checked($ignore_keys, true); ?> />
                        <?php _e("Ignore activator keys", self::$text_domain); ?>
                    </label>
                    <dl>
                        <dd>
                            <p class="description">
                                <?php _e("Please consider that ignoring the activator keys, will result in the injection of malicious codes into your website.", self::$text_domain); ?>
                            </p>
                        </dd>   
                    </dl>
                    <?php
                    break;

                case 'wp_dcp_unsafe_widgets_shortcodes':

                    $shortcodes_enabled = get_option('wp_dcp_unsafe_widgets_shortcodes', false);

                    ?>
                    <label>
                        <input type="checkbox" value="1" id="wp_dcp_unsafe_widgets_shortcodes" name="wp_dcp_unsafe_widgets_shortcodes" <?php checked($shortcodes_enabled, true); ?> />
                        <?php _e("Render shortcodes in <strong>Custom HTML</strong> widget", self::$text_domain); ?>
                    </label>
                    <?php
                    break;

                case 'wp_dcp_unsafe_widgets_php':

                    $php_enabled = get_option('wp_dcp_unsafe_widgets_php', false);

                    ?>
                    <label>
                        <input type="checkbox" value="1" id="wp_dcp_unsafe_widgets_php" name="wp_dcp_unsafe_widgets_php" <?php checked($php_enabled, true); ?> />
                        <?php _e("Enable <code>[unsafe key='']</code> shortcode", self::$text_domain); ?>
                    </label>
                    <dl>
                        <dd>
                            <p class="description">
                                <?php _e("By default, <code>[inject]</code> just renders HTML content.", self::$text_domain); ?>
                            </p>
                        </dd>
                        <dd>
                            <p class="description">
                                <?php _e("In order to run PHP codes, You have to enable <code>[unsafe]</code> shortcode.</li>", self::$text_domain); ?>
                            </p>
                        </dd>
                        <dd>
                            <p class="description">
                                <?php _e("Please notice that each unsafe section require an <strong>Activator Key</strong> for security purposes.</li>", self::$text_domain); ?>
                            </p>
                        </dd>      
                    </dl>
                    <?php
                    break;

                case 'wp_dcp_unsafe_debug':

                    $debug = get_option('wp_dcp_unsafe_debug', false);

                    ?>
                    <label>
                        <input type="checkbox" value="1" id="wp_dcp_unsafe_debug" name="wp_dcp_unsafe_debug" <?php checked($debug, true); ?> />
                        <?php _e("Show PHP errors (Experimental)", self::$text_domain); ?>
                    </label>
                    <dl>
                        <dd>
                            <p class="description">
                                <?php _e("Overrides <code>WP_DEBUG</code> value.", self::$text_domain); ?>
                            </p>
                        </dd>   
                    </dl>
                    <?php

                    break;

            }

        }



        /**
         * Rename header of title column to ID
         * @since 1.0.0
         */
        public function manage_code_posts_columns($columns)
        {
            $columns = [];

            $columns['id'] = __("Code" , self::$text_domain);
            $columns['statistics'] = __("Hits", self::$text_domain) . " — " . WP_Calendar_Heatmap::map();
            $columns['info'] = __("Info", self::$text_domain);

            return $columns;

        }

        public function manage_code_posts_custom_column( $column, $post_id ){

           

            switch ( $column ) {
                case 'info':

                    $code = get_post($post_id);

                    $categories = get_the_terms( $code, 'code_category' );

                    ?>

                    <dl>
                        <dt>
                            <strong>Categories</strong>
                        <dt>
                        <dd>
                            <?php 
                                foreach($categories as $c){
                                    echo "<span>$c->name<span>,";
                                }
                            ?>
                        <dd>
                        <dt>
                            <strong>Author</strong>
                        <dt>
                        <dd>
                            <?php  echo get_the_author_meta('display_name' , $code->post_author) . " — <" . get_the_author_meta('user_email' , $code->post_author) . ">"; ?>
                        <dd>
                        <dt>
                            <strong>Date</strong>
                        <dt>
                        <dd>
                            <?php echo date_i18n( 'F j, Y - g:i a' , strtotime($code->post_modified) ); ?>
                        <dd>
                    </dl>

                    <?php

                    break;
                case 'id':

                    $code = get_post($post_id);

                    $status = get_post_status($post_id);
                    
                    $code_options = WP_Code_Metabox::get_code_options($code);
                 
                    ?>
                        <p style="text-align: justify;">
                            <?php echo $code_options['description']; ?>  —  <strong><?php echo ucwords($status); ?></strong>
                        </p>
                        
                        <?php 
                            /**
                             * prevents the showing of the code IDs in private mode
                             */
                            if('private' == $status) {
                                break;
                            } 
                        ?>

                        <dl>
                            <dt>
                                <strong>Code ID</strong>
                            <dt>
                            <dd>
                                <code style="font-size:11px;"><?php echo $code->post_title; ?></code>
                            <dd>
                            <dt>
                                <strong>Action ID</strong>
                            <dt>
                            <dd>
                                <?php if(!empty($code_options['action_name'])) : ?>
                                <code style="font-size:11px;"><?php echo $code_options['action_name']; ?></code>
                                <?php 
                                      else :
                                        _e("In order to see the AID, You have to publish the Code.");
                                      endif;
                                ?>
                            <dd>
                        </dl>
                    <?php

                    break;

                case 'statistics':

                    // get GMT
                    $cdate = current_time( 'mysql' , 1 );

                    // start from 6 days ago
                    $start = new DateTime($cdate);
                    $start->sub(new DateInterval('P6D')); 

                    // today
                    $end = new DateTime($cdate);
                    
                    $heatmap = new WP_Calendar_Heatmap();
                    $heatmap->load(self::table_name() , $post_id, $start, $end);
                    $heatmap->render();

                break;
            }
        }


        /**
         * Disable quick edit button
         * @since 1.0.0
         */
        public function remove_quick_edit($actions, $post)
        {

            if (isset($_GET['post_type']) && $_GET['post_type'] == 'code') {
                
                unset($actions['inline hide-if-no-js']);

                $title = __("Copy the code ID into the clipboard", self::$text_domain);

                $text = __("Copy the ID" , self::$text_domain);

                $actions['edit'] = $post->post_title;//str_replace("“{$post->post_title}”" , '' , $actions['edit']);

                $actions['trash'] = str_replace("“{$post->post_title}”" , '' , $actions['trash']);

                $actions['copy_to_clipboard'] = "<a href=\"javascript:void(0);\" title=\"$title\" rel=\"permalink\">$text</a>";

            }

            return $actions;

        }


        /**
         * Hide post title input
         * @since 1.0.0
         */
        public function hide_post_title_input()
        {

            if ($this->is_code_page()) :
            ?>
                <style>#titlediv{display:none;}</style>
            <?php
            endif;

        }


        public function widgets_init()
        {

            register_widget('Wp_Code_Injection_Plugin_Widget');

        }



        /**
         * Checks if is in post edit page
         * @since 1.0.0
         */
        private function is_edit_page($new_edit = null)
        {

            global $pagenow;


            if (!is_admin()) return false;


            if ($new_edit == "edit")
                return in_array($pagenow, array('post.php'));
            elseif ($new_edit == "new")
                return in_array($pagenow, array('post-new.php'));
            else
                return in_array($pagenow, array('post.php', 'post-new.php'));

        }

        /**
         * Checks if is in code edit/new page
         * @since 2.2.6
         */
        private function is_settings_page()
        {

            global $pagenow;

            if(!in_array($pagenow , ['options-general.php']))
            {
                return false;
            }

            if(defined('DIVAN_CONTROL_PANEL'))
            {

                if(isset($_GET['page']) && isset($_GET['tab'])){

                    return  $_GET['page'] == 'dcp-settings' &&  $_GET['tab'] == 'general';

                }

                return false;
            }

            return true;

        }

        /**
         * Checks if is in code edit/new page
         * @since 1.0.0
         */
        private function is_code_page()
        {

            if ($this->is_edit_page('new')) {
                if (isset($_GET['post_type']) && $_GET['post_type'] == 'code') {
                    return true;
                }
            }

            if ($this->is_edit_page('edit')) {

                global $post;

                if ('code' == get_post_type($post)) {
                    return true;
                }

            }

            return false;

        }



        /**
         * Disable Media button
         * @since 1.0.0
         */
        public function remove_mediabuttons()
        {

            if ($this->is_code_page()) {

                remove_action('media_buttons', 'media_buttons');

            }

        }


        /**
         * Disable visual editor
         * @since 1.0.0
         */
        public function disable_wysiwyg($default)
        {

            if ($this->is_code_page()) {
                return false;
            }

            return $default;

        }



        /**
         * finds shortcode, and its parameters from the string
         * @since 1.0.1
         */
        private function get_shortcode_by_name($text, $name)
        {

            $result = [];

            $shortcodes = [];

            preg_match("/\[" . $name . " (.+?)\]/", $text, $shortcodes);

            foreach ($shortcodes as $sh) {

                $params = [];

                $data = explode(" ", $sh);

                unset($data[0]);

                foreach ($data as $d) {

                    list($opt, $val) = explode("=", $d);

                    $params[$opt] = trim($val, "[\"]'");

                }

                array_push($result, [
                    'params' => $params
                ]);

            }

            return $result;

        }


        /**
         * Generate title
         * @since 1.0.0
         */
        public function auto_generate_post_title($title)
        {

            global $post;

            if (isset($post->ID)) {

                if (empty($_POST['post_title']) && 'code' == get_post_type($post->ID)) {

                    $title = 'code-' . md5(uniqid(rand(0,1), true));

                }
            }

            return $title;

        }


        /**
         * create directory taxonomy
         * @since 2.2.8
         */
        private function create_directory_tax(){

            $lables = [
                'name' => __('Directories' , self::$text_domain),
                'menu_name' => __('Directories' , self::$text_domain),
                'singular_name' => __('Directory', self::$text_domain),
                'add_new_item' => __('Add New Directory', self::$text_domain),
                'edit_item' => __('Edit Directory', self::$text_domain),
                'new_item_name' => __('New Directory Name', self::$text_domain),
                'parent_item' => __('Parent Directory', self::$text_domain),
                'parent_item_colon' => __('Parent Directory:', self::$text_domain),
                'search_items ' => __('Search Directories', self::$text_domain),
                'not_found' => __('No directories found', self::$text_domain),
                'all_items' => __('All Directories', self::$text_domain),
                'popular_items' => __('Popular Directories', self::$text_domain),
                'choose_from_most_used' => __('Choose from the most used directories', self::$text_domain),
                'add_or_remove_items' => __('Add or remove directories', self::$text_domain),
                'back_to_items' => __('← Back to directories', self::$text_domain)
            ];

            register_taxonomy( 
                'directory', 
                'code', 
                [
                   'labels' => $lables,
                   'show_admin_column' => true,
                   'public' => false,
                    'show_ui' => true,
                    'rewrite' => false,
                   'hierarchical' => true
                ]
            );

        }

        
        /**
         * create category taxonomy
         * @since 2.2.8
         */
        private function create_category_tax(){

            register_taxonomy( 
                'code_category', 
                'code', 
                [
                   'show_admin_column' => true,
                   'public' => false,
                   'show_ui' => true,
                   'rewrite' => false,
                   'hierarchical' => true
                ]
            );

        }



        /**
         * create code post type
         * @since 1.0.0
         */
        public function create_posttype()
        {

            $this->create_category_tax();

            $this->create_directory_tax();


            $code_lables = [
                'name' => __('Codes', self::$text_domain),
                'singular_name' => __('Code', self::$text_domain),
                'add_new_item' => __('Add New Code', self::$text_domain),
                'edit_item' => __('Edit Code', self::$text_domain),
                'new_item' => __('New Code', self::$text_domain),
                'search_items ' => __('Search Codes', self::$text_domain),
                'not_found' => __('No codes found', self::$text_domain),
                'not_found_in_trash ' => __('No codes found in Trash', self::$text_domain),
                'all_items' => __('All Codes', self::$text_domain)
            ];


            register_post_type(
                'Code',
                [
                    'menu_icon' => 'dashicons-editor-code',
                    'labels' => $code_lables,
                    'public' => false,
                    'show_ui' => true,
                    'rewrite' => false,
                    'query_var' => false,
                    'exclude_from_search' => true,
                    'publicly_queryable' => false,
                    'supports' => ['author', 'revisions', 'title', 'editor'],
                    'taxonomies' => ['directory'],
                    'capability_type' => ['code','codes'],
                    'can_export' => true,
                    'map_meta_cap' => true
                ]
            );


        }


        /**
         * Register developer role
         * @since 2.2.6
         */
        private function register_roles() 
        {

            $role_version = get_option( 'wp_dcp_code_injection_role_version', '' );

            if($role_version == self::$role_version){

                return;

            }

            $developer = get_role( 'developer' );
            
            if(isset($developer)){

                remove_role( 'developer' );
                
            }

            add_role('developer',
                __('Developer' , self::$text_domain),
                [
                    'read' => true,
                    'edit_posts' => false,
                    'delete_posts' => false,
                    'publish_posts' => false,
                    'upload_files' => true,
                ]
            );


            update_option( 'wp_dcp_code_injection_role_version', self::$role_version );

        }


        /**
         * returns table name
         * @since 1.0.0
         */
        public static function table_name()
        {

            global $wpdb;

            $dbname = "dcp_code_injection";

            //return $wpdb->prefix . $dbname;

            return $dbname;

        }


        /**
         * Activation hook
         * @since 1.0.0
         */
        public function activate()
        {

            flush_rewrite_rules();

        }

        /**
         * Deactivation hook
         * @since 1.0.0
         */
        public function deactivate()
        {

            flush_rewrite_rules();

            remove_role('developer');

        }

        /**
         * Returns plugin version
         * @since 1.0.0
         */
        private function get_version()
        {

            return get_plugin_data(__FILE__)['Version'];

        }
    }
}


$CODE_INJECTION_PLUGIN_INSTANCE = new WP_Code_Injection_Plugin();

register_activation_hook(__FILE__, [$CODE_INJECTION_PLUGIN_INSTANCE, 'activate']);

register_deactivation_hook(__FILE__, [$CODE_INJECTION_PLUGIN_INSTANCE, 'deactivate']);