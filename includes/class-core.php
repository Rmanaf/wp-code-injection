<?php

/**
 * Licensed under MIT (https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE)
 * Copyright (c) 2018 Rmanaf <me@rmanaf.com>
 */

namespace ci;

class Core
{

    /**
     * @since 2.4.12
     */
    static function setup()
    {

        CodeType::init();
        Database::init();
        Roles::init();
        Metabox::init();
        AssetManager::init();
        Shortcodes::init();
        Options::init();
        

        add_action('plugins_loaded', array(__CLASS__, '_load_plugins'));
        add_action("template_redirect", array(__CLASS__, '_check_raw_content'));
        add_action('widgets_init', array(__CLASS__, 'widgets_init'));
        add_action('plugins_loaded', array(__CLASS__, 'load_plugin_textdomain'));

        // check "Unsafe" settings
        if (get_option('ci_unsafe_widgets_shortcodes', 0)) {
            add_filter('widget_text', 'shortcode_unautop');
            add_filter('widget_text', 'do_shortcode');
        }

        register_activation_hook(__CI_FILE__, array(__CLASS__, 'activate'));
        register_deactivation_hook(__CI_FILE__, array(__CLASS__, 'deactivate'));
    }



    /**
     * @since 2.4.12
     */
    static function load_plugin_textdomain()
    {
        load_plugin_textdomain("code-injection", FALSE, basename(dirname(__CI_FILE__)) . '/languages/');
    }


    /**
     * @since 2.2.9
     * @access private
     */
    static function _load_plugins()
    {

        global $wpdb;

        $use_php = get_option('ci_unsafe_widgets_php', false);

        if (!$use_php) {
            return;
        }

        $ignore_keys = get_option('ci_unsafe_ignore_keys', false);

        $keys = get_option('ci_unsafe_keys', '');

        $codes = Database::get_codes();

        $plugins = array_filter($codes, function ($element) use ($ignore_keys, $keys) {

            $options = maybe_unserialize($element->meta_value);

            extract($options);

            $is_plugin = isset($code_is_plugin) && $code_is_plugin == '1';

            $is_public = isset($code_is_publicly_queryable) && $code_is_publicly_queryable == '1';


            if (!isset($code_enabled)) {
                $code_enabled = false;
            }


            if (!CodeType::check_code_status($element)) {
                return false;
            }


            if ($is_public) {
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

            $code_options = Metabox::get_code_options($p->ID);

            $code_options['code_enabled'] = false;

            update_post_meta($p->ID, "code_options", $code_options);

            eval("?" . ">" . $p->post_content);

            $code_options['code_enabled'] = true;

            update_post_meta($p->ID, "code_options", $code_options);
        }
    }


    /**
     * @since 2.4.12
     * @access private
     */
    static function _check_raw_content()
    {

        global $wpdb;

        if (!is_home() && !is_front_page()) {
            return;
        }

        if (!isset($_GET["raw"])) {
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


        if (empty($results)) {

            // Code not found
            Database::record_activity(0, null, 2);

            return;
        }


        $code = $results[0];


        if (!CodeType::check_code_status($code)) {

            // Unauthorized Request
            Database::record_activity(0, $id, 6, $code->ID);

            return;
        }


        $options = maybe_unserialize($code->meta_value);

        extract($options);

        $active = isset($code_enabled) && $code_enabled == '1';

        $is_plugin =  isset($code_is_plugin) && $code_is_plugin == '1';

        $is_public =  isset($code_is_publicly_queryable) && $code_is_publicly_queryable == '1';

        $no_cache = isset($code_no_cache) && $code_no_cache == '1';

        if (!$active || $is_plugin || !$is_public) {
            return;
        }

        $render_shortcodes = get_option('ci_code_injection_allow_shortcode', false);

        Database::record_activity(0, $id, 0, $code->ID);


        header("Content-Type: $code_content_type; charset=UTF-8", true);


        if ($no_cache) {

            header("Pragma: no-cache", true);

            header("Cache-Control: no-cache, must-revalidate, max-age=0", true);

            header("Expires: Sat, 26 Jul 1997 05:00:00 GMT", true);
        } else {

            $cache_max_age = get_option('ci_code_injection_cache_max_age', '84600');

            header("Pragma: public", true);

            header("Cache-Control: max-age=$cache_max_age, public, no-transform", true);

            header('Expires: ' . gmdate('D, d M Y H:i:s', time() + $cache_max_age) . ' GMT', true);
        }


        if ($render_shortcodes) {

            exit(do_shortcode($code->post_content));
        } else {

            exit($code->post_content);
        }
    }


    /**
     * @since 2.4.12
     */
    static function widgets_init()
    {
        register_widget(Widget::class);
    }


    /**
     * @since 2.4.12
     */
    static function activate()
    {
        flush_rewrite_rules();
    }


    /**
     * @since 2.4.12
     */
    static function deactivate()
    {
        flush_rewrite_rules();

        delete_option('ci_code_injection_db_version');
        delete_option('ci_code_injection_role_version');

        remove_role('developer');
    }
}
