<?php

/**
 * Plugin Name: Code Injection
 * Plugin URI: https://github.com/Rmanaf/wp-code-injection
 * Description: This plugin allows you to inject code snippets into the pages.
 * Version: 2.4.9
 * Author: Rmanaf
 * Author URI: https://profiles.wordpress.org/rmanaf/
 * License: MIT License
 * License URI: https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE
 * Text Domain: code-injection
 * Domain Path: /languages
 */

defined('ABSPATH') or die;

require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/plugin-widget.php';
require_once __DIR__ . '/includes/calendar-heatmap.php';
require_once __DIR__ . '/includes/barchart.php';
require_once __DIR__ . '/includes/code-metabox.php';
require_once __DIR__ . '/includes/code-type.php';
require_once __DIR__ . '/includes/asset-manager.php';


if (!class_exists('WP_Code_Injection_Plugin')) {

    class WP_Code_Injection_Plugin
    {

        private $database;

        private static $role_version = '1.0.0';

        private static $version = '2.4.9';

        function __construct()
        {


            WP_CI_Code_Type::init(__FILE__);

            $this->database = new WP_CI_Database();

            WP_CI_Code_Metabox::init();

            WP_CI_Asset_Manager::init(__FILE__, self::$version);

            // check "Unsafe" settings
            $use_shortcode = get_option('wp_dcp_unsafe_widgets_shortcodes', 0);


            if ($use_shortcode) {

                add_filter('widget_text', 'shortcode_unautop');

                add_filter('widget_text', 'do_shortcode');
            }


            add_shortcode('inject', [$this, 'ci_shortcode']);

            add_shortcode('unsafe', [$this, 'unsafe_shortcode']);

            add_filter("no_texturize_shortcodes" , function($shortcodes) {
                $shortcodes[] = 'inject';
                $shortcodes[] = 'unsafe';
                return $shortcodes;
            });

            add_action('admin_init', [$this, 'admin_init']);

            add_action('widgets_init', [$this, 'widgets_init']);

            add_action('plugins_loaded', [$this, 'load_plugin_textdomain']);

            add_action('plugins_loaded', [$this, 'load_plugins']);

            add_action( "template_redirect" , [$this , "check_raw_content"]);

        }

        /**
         * @since 2.4.3
         */
        function load_plugin_textdomain() {
            load_plugin_textdomain( "code-injection", FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
        }


        /**
         * @since 2.2.6
         */
        function unsafe_shortcode($atts = [], $content = null)
        {

            $use_php = get_option('wp_dcp_unsafe_widgets_php', false);

            if (!$use_php) {

                $this->database->record_activity(1, null, 1);

                return;
            }

            $ignore_keys = get_option('wp_dcp_unsafe_ignore_keys', false);

            if (!$ignore_keys) {

                extract(shortcode_atts(['key' => ''], $atts));

                $keys = $this->extract_keys(get_option('wp_dcp_unsafe_keys', ''));

                if (empty($keys) || !in_array($key, $keys)) {

                    $this->database->record_activity(1, $key, 5);

                    return;
                }
            }

            $html = $content;

            if (strpos($html, "<" . "?php") !== false) {

                ob_start();

                eval("?" . ">" . $html);

                try {

                    $html = ob_get_contents();
                } catch (Exception $ex) {

                    $this->database->record_activity(1, $key, 4);

                    return;
                }

                ob_end_clean();
            }

            return $html;
        }


        /**
         * @since 1.0.0
         */
        function ci_shortcode($atts = [], $content = null)
        {

            if(!is_array($atts)){
                $atts  = [];
            }
    
            if(!isset($atts["id"]) && !empty($atts) ){
                $atts["slug"] = $atts['slug'] ?? array_values($atts)[0];
            }

            extract(shortcode_atts([
                'id' => '',
                'slug' => ''
            ], $atts));


            if (empty($id) && empty($slug)) {
                $this->database->record_activity(0, null, 2);
                return;
            }

            if(!empty($id)){
                $code = get_page_by_title($id, OBJECT, 'code');
            } else {
                $code = WP_CI_Database::get_code_by_slug($slug);
            }

            if (!is_object($code)) {
                return;
            }


            if(!self::check_code_status($code))
            {
                 // Unauthorized Request
                 $this->database->record_activity(0, $id, 6 , $code->ID);

                return;
            }
            

            $co = WP_CI_Code_Metabox::get_code_options($code);

            $is_plugin =  isset($co['code_is_plugin']) && $co['code_is_plugin'] == '1';

            if ($co['code_enabled'] == false || $is_plugin) {
                return;
            }


            $render_shortcodes = get_option('wp_dcp_code_injection_allow_shortcode', false);

            $nested_injections = $this->get_shortcode_by_name($code->post_content, 'inject');

            foreach ($nested_injections as $i) {

                $params = $i['params'];

                if (isset($params['id']) && $params['id'] == $id) {

                    $this->database->record_activity(0, $id, 3, $code->ID);

                    return;
                }
            }

            $this->database->record_activity(0, $id, 0, $code->ID);

            if ($render_shortcodes) {

                return do_shortcode($code->post_content);

            } else {

                return $code->post_content;

            }
            
        }

        
        /**
         * @since 2.2.6
         */
        private function update_caps()
        {

            $roles = ['developer', 'administrator'];

            foreach ($roles as $role) {

                $ur = get_role($role);

                if (!isset($ur)) {
                    continue;
                }

                foreach ([
                    'publish',  'delete',  'delete_others', 'delete_private',
                    'delete_published',  'edit', 'edit_others',  'edit_private',
                    'edit_published', 'read_private'
                ] as $cap) {
                    $ur->add_cap("{$cap}_code");
                    $ur->add_cap("{$cap}_codes");
                }
            }
        }

        /**
         * @since 1.0.0
         */
        function admin_init()
        {

            $this->register_roles();

            $this->update_caps();

            $group = 'general';

            $title_version = "<span class=\"gdcp-version-box wp-ui-notification\">v" . self::$version . "</span>";

            $title = esc_html__('Code Injection', "code-injection");
            


            // settings section
            add_settings_section(
                'wp_code_injection_plugin',
                is_rtl() ? $title_version . $title : $title . $title_version,
                [$this, 'settings_section_cb'],
                $group
            );


            // register code settings
            register_setting($group, 'wp_dcp_code_injection_cache_max_age', ['default' => '84600']);

            // register "CI" settings
            register_setting($group, 'wp_dcp_code_injection_allow_shortcode', ['default' => false]);

            // register "Unsafe" settings
            register_setting($group, 'wp_dcp_unsafe_widgets_shortcodes', ['default' => false]);
            register_setting($group, 'wp_dcp_unsafe_keys', ['default' => '']);
            register_setting($group, 'wp_dcp_unsafe_widgets_php', ['default' => false]);
            register_setting($group, 'wp_dcp_unsafe_ignore_keys', ['default' => false]);


             // code settings fields
             add_settings_field(
                'wp_dcp_code_injection_cache_max_age',
                esc_html__("Code Options", "code-injection"),
                [$this, 'settings_field_cb'],
                $group,
                'wp_code_injection_plugin',
                ['label_for' => 'wp_dcp_code_injection_cache_max_age']
            );


            // "CI" fields
            add_settings_field(
                'wp_dcp_code_injection_allow_shortcode',
                esc_html__("Shortcodes", "code-injection"),
                [$this, 'settings_field_cb'],
                $group,
                'wp_code_injection_plugin',
                ['label_for' => 'wp_dcp_code_injection_allow_shortcode']
            );


            // "Unsafe fields"
            add_settings_field(
                'wp_dcp_unsafe_widgets_shortcodes',
                "",
                [$this, 'settings_field_cb'],
                $group,
                'wp_code_injection_plugin',
                ['label_for' => 'wp_dcp_unsafe_widgets_shortcodes']
            );

            add_settings_field(
                'wp_dcp_unsafe_widgets_php',
                "",
                [$this, 'settings_field_cb'],
                $group,
                'wp_code_injection_plugin',
                ['label_for' => 'wp_dcp_unsafe_widgets_php']
            );

            add_settings_field(
                'wp_dcp_unsafe_ignore_keys',
                esc_html__("Activator Keys", "code-injection"),
                [$this, 'settings_field_cb'],
                $group,
                'wp_code_injection_plugin',
                ['label_for' => 'wp_dcp_unsafe_ignore_keys']
            );

            add_settings_field(
                'wp_dcp_unsafe_keys',
                "",
                [$this, 'settings_field_cb'],
                $group,
                'wp_code_injection_plugin',
                ['label_for' => 'wp_dcp_unsafe_keys']
            );
        }


        /**
         * @since 1.0.0
         */
        function settings_section_cb() {  }


        /**
         * @since 2.2.6
         */
        private function extract_keys($text)
        {
            return array_filter(explode(',', $text), function ($elem) {
                return preg_replace('/\s/', '', $elem);
            });
        }

        
        /** 
         * @since 1.0.0
         */
        function settings_field_cb($args)
        {

            switch ($args['label_for']) {

                case 'wp_dcp_code_injection_cache_max_age':

                    $cache_max_age = get_option('wp_dcp_code_injection_cache_max_age', '84600');

                    ?>
                        <p>
                            <?php esc_html_e("Cache max-age (Seconds)", "code-injection"); ?>
                        </p>
                        <input class="regular-text" type="number" value="<?php echo $cache_max_age; ?>" id="wp_dcp_code_injection_cache_max_age" name="wp_dcp_code_injection_cache_max_age" />
                        <dl>
                            <dd>
                                <p class="description">
                                    e.g.&nbsp;&nbsp;&nbsp;&nbsp;84600
                                </p>
                            </dd>
                        </dl>
                    <?php

                break;

                case 'wp_dcp_code_injection_allow_shortcode':

                    $nested_shortcode = get_option('wp_dcp_code_injection_allow_shortcode', false);

                    ?>
                        <label>
                            <input type="checkbox" value="1" id="wp_dcp_code_injection_allow_shortcode" name="wp_dcp_code_injection_allow_shortcode" <?php checked($nested_shortcode, true); ?> />
                            <?php esc_html_e("Allow nested shortcodes", "code-injection"); ?>
                        </label>
                    <?php
                    break;

                case 'wp_dcp_unsafe_keys':

                    $keys = get_option('wp_dcp_unsafe_keys', '');

                    ?>

                    <p class="ack-head-wrapper">
                        <span class="ack-header">
                            <strong><?php esc_html_e("Keys:", "code-injection"); ?></strong>
                        </span>
                        <a class="button ack-new" href="javascript:void(0);" id="wp_dcp_generate_key">
                            <?php esc_html_e("Generate Key" , "code-injection");  ?>
                        </a>
                    </p>
                    <p>
                        <textarea data-placeholder="<?php esc_html_e("Enter Keys:" , "code-injection")?>" class="large-text code" id="wp_dcp_unsafe_keys" name="wp_dcp_unsafe_keys"><?php echo $keys; ?></textarea>
                        <dl>
                            <dd>
                                <p class="description">
                                    e.g.&nbsp;&nbsp;&nbsp;&nbsp;key-2im2a5ex4,&nbsp;&nbsp;key-6dp7mwt05 ...
                                </p>
                            </dd>
                        </dl>
                    </p>
                    <?php
                    break;

                    case 'wp_dcp_unsafe_ignore_keys':

                        $ignore_keys = get_option('wp_dcp_unsafe_ignore_keys', false);

                        ?>
                            <label>
                                <input type="checkbox" value="1" id="wp_dcp_unsafe_ignore_keys" name="wp_dcp_unsafe_ignore_keys" <?php checked($ignore_keys, true); ?> />
                                <?php _e("Ignore activator keys", "code-injection"); ?>
                            </label>
                        <?php
                        break;

                    case 'wp_dcp_unsafe_widgets_shortcodes':

                        $shortcodes_enabled = get_option('wp_dcp_unsafe_widgets_shortcodes', false);

                        ?>
                            <label>
                                <input type="checkbox" value="1" id="wp_dcp_unsafe_widgets_shortcodes" name="wp_dcp_unsafe_widgets_shortcodes" <?php checked($shortcodes_enabled, true); ?> />
                                <?php esc_html_e("Allow shortcodes in the Custom HTML widget", "code-injection"); ?>
                            </label>
                        <?php
                        break;

                    case 'wp_dcp_unsafe_widgets_php':

                        $php_enabled = get_option('wp_dcp_unsafe_widgets_php', false);

                        ?>
                            <label>
                                <input type="checkbox" value="1" id="wp_dcp_unsafe_widgets_php" name="wp_dcp_unsafe_widgets_php" <?php checked($php_enabled, true); ?> />
                                <?php printf( esc_html__("Enable %s shortcode", "code-injection") , "<code>[unsafe key='']</code>"); ?>
                            </label>
                            <dl>
                                <dd>
                                    <p class="description">
                                        <?php
                                            printf(
                                                esc_html__('See %s for more information.' , "code-injection"),
                                                sprintf(
                                                    '<a target="_blank" href="%1$s">%2$s</a>' , 
                                                    esc_url('https://github.com/Rmanaf/wp-code-injection/blob/master/README.md'), 
                                                    esc_html__("Readme" , "code-injection")
                                                )
                                            );
                                        ?>
                                    </p>
                                </dd>
                            </dl>
                        <?php
                        break;
                }
            }

            /**
             * @since 1.0.0
             */
            function widgets_init()
            {
                register_widget('Wp_Code_Injection_Plugin_Widget');
            }


            /**
             * @since 2.4.3
             */
            static function check_code_status($code){

                $status = get_post_status( $code );

                if($status == "private" && !is_user_logged_in())
                {
                    return false;
                }

                if($status != "private" && $status != "publish")
                {
                    return false;
                }


                return true;
            }



            /**
             * @since 2.2.9
             */
            function load_plugins()
            {

                global $wpdb;

                $instance = $this;

                $use_php = get_option('wp_dcp_unsafe_widgets_php', false);

                if (!$use_php) {
                    return;
                }

                $ignore_keys = get_option('wp_dcp_unsafe_ignore_keys', false);

                $keys = get_option('wp_dcp_unsafe_keys', '');

                $codes = WP_CI_Database::get_codes();

                $plugins = array_filter($codes, function ($element) use ($instance, $ignore_keys, $keys) {

                    $options = maybe_unserialize($element->meta_value);

                    extract($options);

                    $is_plugin = isset($code_is_plugin) && $code_is_plugin == '1';

                    $is_public = isset($code_is_publicly_queryable) && $code_is_publicly_queryable == '1';


                    if (!isset($code_enabled)) {
                        $code_enabled = false;
                    }


                    if(!self::check_code_status($element))
                    {
                        return false;
                    }


                    if($is_public){
                        return false;
                    }


                    if (!$is_plugin || $code_enabled == false) {
                        return false;
                    }

                    if ($ignore_keys) {
                        return true;
                    }

                    return isset($code_activator_key) && in_array($code_activator_key, $instance->extract_keys($keys));
                });

                foreach ($plugins as $p) {

                    $code_options = WP_CI_Code_Metabox::get_code_options($p->ID);

                    $code_options['code_enabled'] = false;

                    update_post_meta($p->ID, "code_options", $code_options);

                    eval("?" . ">" . $p->post_content);

                    $code_options['code_enabled'] = true;

                    update_post_meta($p->ID, "code_options", $code_options);
                }
            }


            /**
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
             * @since 2.2.8
             */
            static function generate_id($prefix = '')
            {
                return $prefix . md5(uniqid(rand(0, 1), true));
            }



            /**
             * @since 2.2.6
             */
            private function register_roles()
            {

                $developer = get_role('developer');


                $role_version = get_option('wp_dcp_code_injection_role_version', '');


                if ($role_version == self::$role_version && isset($developer)) {
                    return;
                }

               
                remove_role('developer');
                

                add_role(
                    'developer',
                    esc_html__('Developer', "code-injection"),
                    [
                        'read' => true,
                        'edit_posts' => false,
                        'delete_posts' => false,
                        'publish_posts' => false,
                        'upload_files' => true,
                    ]
                );


                update_option('wp_dcp_code_injection_role_version', self::$role_version);
            }


            /**
             * @since 2.4.3
             */
            function check_raw_content() {

                global $wpdb;

                if(!is_home() && !is_front_page()){
                    return;
                }

                if(!isset($_GET["raw"])){
                    return;
                }

                $id = $_GET["raw"];

                $query = "SELECT $wpdb->posts.*, $wpdb->postmeta.*
                    FROM $wpdb->posts, $wpdb->postmeta
                    WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id 
                    AND $wpdb->postmeta.meta_key = 'code_options' 
                    AND $wpdb->posts.post_type = 'code'
                    AND $wpdb->posts.post_title = '$id'";


                $results = $wpdb->get_results($query, OBJECT);


                if(empty($results)){

                    // Code not found
                    $this->database->record_activity(0, null, 2);

                    return;

                }


                $code = $results[0];


                if(!self::check_code_status($code)){

                    // Unauthorized Request
                    $this->database->record_activity(0, $id, 6 , $code->ID);

                    return;

                }


                $options = maybe_unserialize($code->meta_value);

                extract($options);

                $active = isset($code_enabled) && $code_enabled == '1';

                $is_plugin =  isset($code_is_plugin) && $code_is_plugin == '1';

                $is_public =  isset($code_is_publicly_queryable) && $code_is_publicly_queryable == '1';

                $no_cache = isset($code_no_cache) && $code_no_cache == '1';

                if(!$active || $is_plugin || !$is_public){
                    return;
                }

                $render_shortcodes = get_option('wp_dcp_code_injection_allow_shortcode', false);

                $this->database->record_activity(0, $id, 0, $code->ID);


                header("Content-Type: $code_content_type; charset=UTF-8" , true);


                if($no_cache){

                    header("Pragma: no-cache" , true);
                    
                    header("Cache-Control: no-cache, must-revalidate, max-age=0" , true);
                    
                    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT" , true);

                }else{

                    $cache_max_age = get_option('wp_dcp_code_injection_cache_max_age', '84600');

                    header("Pragma: public" , true);

                    header("Cache-Control: max-age=$cache_max_age, public, no-transform" , true);

                    header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cache_max_age) . ' GMT' , true);

                }


                if ($render_shortcodes) {

                    exit(do_shortcode($code->post_content));

                } else {

                    exit($code->post_content);

                }

            }


            /**
             * @since 1.0.0
             */
            function activate()
            {
                flush_rewrite_rules();
            }


            /**
             * @since 1.0.0
             */
            function deactivate()
            {

                flush_rewrite_rules();

                delete_option('wp_dcp_code_injection_role_version');

                remove_role('developer');

            }
        }
    }


    $CODE_INJECTION_PLUGIN_INSTANCE = new WP_Code_Injection_Plugin();

    register_activation_hook(__FILE__, [$CODE_INJECTION_PLUGIN_INSTANCE, 'activate']);

    register_deactivation_hook(__FILE__, [$CODE_INJECTION_PLUGIN_INSTANCE, 'deactivate']);
