<?php

/**
 * Apache License, Version 2.0
 * 
 * Copyright (C) 2018 Arman Afzal <arman.afzal@gmail.com>
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
 */

 /*
    Plugin Name: Code Injection
    Plugin URI: https://wordpress.org/plugins/code-injection
    Description: Allows You to inject code snippets into the pages by just using the Wordpress shortcode
    Version: 2.4.0
    Author: Arman Afzal
    Author URI: https://github.com/Rmanaf
    License: Apache License, Version 2.0
    Text Domain: code-injection
 */

defined('ABSPATH') or die;

require_once __DIR__ . '/wp-code-injection-plugin-widget.php';

require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/calendar-heatmap.php';
require_once __DIR__ . '/includes/code-metabox.php';
require_once __DIR__ . '/includes/code-type.php';
require_once __DIR__ . '/includes/assets-manager.php';


if (!class_exists('WP_Code_Injection_Plugin')) {

    class WP_Code_Injection_Plugin
    {

        private $code_meta_box;
        private $database;
        private $custom_post_type;
        private $assets_manager;
      
        public static $text_domain = 'code-injection';

        private static $role_version = '1.0.0';

        private static $client_version = '2.4.0';


        function __construct()
        {

            /**
             * initialize custom post type
             * @since 2.2.8
             */
            $this->custom_post_type = new WP_CI_Code_Type(__FILE__);


            /**
             * initialize database
             * @since 2.2.6
             */
            $this->database = new WP_CI_Database();


            /**
             * initialize the meta box component
             * @since 2.2.8
             */
            $this->code_meta_box = new WP_CI_Code_Metabox(self::$text_domain);


            
            /**
             * initialize the assets manager component
             * @since 2.2.8
             */
            $this->assets_manager = new WP_CI_Assets_Manager(__FILE__ , self::$client_version);



            // check "Unsafe" settings
            $use_shortcode = get_option('wp_dcp_unsafe_widgets_shortcodes', 0);


            if ($use_shortcode) {

                add_filter('widget_text', 'shortcode_unautop');

                add_filter('widget_text', 'do_shortcode');

            }


            add_shortcode('inject', [$this, 'ci_shortcode']);

            add_shortcode('unsafe', [$this, 'unsafe_shortcode']);
            
            add_action('admin_init', [$this, 'admin_init']);

            add_action('widgets_init', [$this, 'widgets_init']);    
        
            add_action('plugins_loaded', [$this, 'load_extra_plugins'] );
            
            add_filter('dcp_shortcodes_list', [&$this, 'add_shortcode_to_list']);



        }


        /**
         * "Unsafe" shortcode
         * @since 2.2.6
         */
        public function unsafe_shortcode($atts = [], $content = null)
        {

            $use_php = get_option('wp_dcp_unsafe_widgets_php', false);

            if (!$use_php) {

                $this->database->record_activity(1 , null , 1);

                return;

            }

            $ignore_keys = get_option('wp_dcp_unsafe_ignore_keys', false);

            if(!$ignore_keys){

                extract(shortcode_atts(['key' => ''], $atts));

                $keys = $this->extract_keys(get_option('wp_dcp_unsafe_keys', ''));

                if (empty($keys) || !in_array($key, $keys)) {

                    $this->database->record_activity(1 , $key , 5);

                    return;

                }

            }

            $html = $content;

            if (strpos($html, "<" . "?php") !== false) {

                ob_start();

                eval("?" . ">" . $html);

                try
                {

                    $html = ob_get_contents();

                }
                catch(Exception $ex)
                {

                    $this->database->record_activity(1 , $key , 4);

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

            if (empty($id)) 
            {

                $this->database->record_activity(0 , null , 2);

                return;

            }
            

            $code = get_page_by_title($id, OBJECT, 'code');

            if (is_object($code)) 
            {


                /**
                 * checks if code is enabled
                 * @since 2.2.8
                 */
                $co = WP_CI_Code_Metabox::get_code_options($code);

                if($co['code_enabled'] == false)
                {
                    return;
                }





                $render_shortcodes = get_option('wp_dcp_code_injection_allow_shortcode', false);

                $nested_injections = $this->get_shortcode_by_name($code->post_content, 'inject');

                foreach ($nested_injections as $i) {

                    $params = $i['params'];

                    if (isset($params['id']) && $params['id'] == $id) {

                        $this->database->record_activity(0 , $id , 3, $code->ID);

                        return;

                    }

                }

                $post = $temp_post;

                if ($render_shortcodes) {

                    $this->database->record_activity(0 , $id, 0, $code->ID);

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
         * Settings fields callback 
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


            }

        }


        public function widgets_init()
        {

            register_widget('Wp_Code_Injection_Plugin_Widget');

        }


        /**
         * loads codes as plugin
         * @since 2.2.9
         */
        public function load_extra_plugins()
        {

            global $wpdb;

            $instance = $this;

            $use_php = get_option('wp_dcp_unsafe_widgets_php', false);

            if(!$use_php)
            {
                return;
            }

            $ignore_keys = get_option('wp_dcp_unsafe_ignore_keys', false);

            $keys = get_option('wp_dcp_unsafe_keys' , '');

            $query = "
                     SELECT $wpdb->posts.*, $wpdb->postmeta.*
                     FROM $wpdb->posts, $wpdb->postmeta
                     WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id 
                     AND $wpdb->postmeta.meta_key = 'code_options' 
                     AND $wpdb->posts.post_status = 'publish' 
                     AND $wpdb->posts.post_type = 'code'
                     AND $wpdb->posts.post_date < NOW()
                     ORDER BY $wpdb->posts.post_date DESC
                     ";

            $codes = $wpdb->get_results($query, OBJECT);


            $plugins = array_filter($codes , function($element) use ($instance, $ignore_keys, $keys) {

                $options = maybe_unserialize($element->meta_value);

                extract($options);

                $is_plugin =  isset($code_is_plugin) && $code_is_plugin == '1';

                if(!isset($code_enabled))
                {
                   $code_enabled = false; 
                }

                if(!$is_plugin || $code_enabled == false)
                {
                    return false;
                }

                if($ignore_keys)
                {
                    return true;
                }
                
                return isset($code_activator_key) && in_array($code_activator_key , $instance->extract_keys($keys));

            });

            foreach($plugins as $p)
            {    

                $code_options = WP_CI_Code_Metabox::get_code_options($p->ID);

                $code_options['code_enabled'] = false;

                update_post_meta( $p->ID, "code_options", $code_options);

                eval("?" . ">" . $p->post_content );

                $code_options['code_enabled'] = true;

                update_post_meta( $p->ID, "code_options", $code_options);
    
            }

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
         * generates random unique ID
         * @since 2.2.8
         */
        public static function generate_id($prefix = '')
        {

            return $prefix . md5(uniqid(rand(0,1), true));

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
        public static function get_version()
        {

            return get_plugin_data(__FILE__)['Version'];

        }

    }
}


$CODE_INJECTION_PLUGIN_INSTANCE = new WP_Code_Injection_Plugin();

register_activation_hook(__FILE__, [$CODE_INJECTION_PLUGIN_INSTANCE, 'activate']);

register_deactivation_hook(__FILE__, [$CODE_INJECTION_PLUGIN_INSTANCE, 'deactivate']);