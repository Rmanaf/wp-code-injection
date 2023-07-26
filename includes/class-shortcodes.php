<?php

/**
 * Licensed under MIT (https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE)
 * Copyright (c) 2018 Rmanaf <me@rmanaf.com>
 */

namespace ci;

use Exception;

class Shortcodes
{

    /**
     * @since 2.4.12
     */
    static function init()
    {
        add_shortcode('inject', array(__CLASS__, '_ci_shortcode'));
        add_shortcode('unsafe', array(__CLASS__, '_unsafe_shortcode'));

        add_filter("no_texturize_shortcodes", array(__CLASS__, '_no_texturize_shortcodes'));
    }



    /**
     * @since 2.4.12
     * @access private
     */
    static function _no_texturize_shortcodes($shortcodes){
        $shortcodes[] = 'inject';
        $shortcodes[] = 'unsafe';
        return $shortcodes;
    }



    /**
     * @since 2.4.12
     */
    private static function get_shortcode_by_name($text, $name)
    {

        $result     = array();
        $shortcodes = array();

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
     * @since 2.4.12
     * @access private
     */
    static function _unsafe_shortcode($atts = [], $content = null)
    {

        $use_php = get_option('ci_unsafe_widgets_php', false);

        if (!$use_php) {

            Database::record_activity(1, null, 1);

            return;
        }

        $ignore_keys = get_option('ci_unsafe_ignore_keys', false);

        if (!$ignore_keys) {

            extract(shortcode_atts(['key' => ''], $atts));

            $keys = Options::get_keys();

            if (empty($keys) || !in_array($key, $keys)) {

                Database::record_activity(1, $key, 5);

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
                Database::record_activity(1, $key, 4);
                return;
            }

            ob_end_clean();
        }

        return $html;
    }


    /**
     * @since 2.4.12
     * @access private
     */
    static function _ci_shortcode($atts = [], $content = null)
    {

        if (!is_array($atts)) {
            $atts  = array();
        }

        if (!isset($atts["id"]) && !empty($atts)) {
            $atts["slug"] = isset($atts['slug']) ? $atts['slug'] : array_values($atts)[0];
        }

        extract(shortcode_atts([
            'id' => '',
            'slug' => ''
        ], $atts));


        if (empty($id) && empty($slug)) {
            Database::record_activity(0, null, 2);
            return;
        }

        if (!empty($id)) {
            $code = get_page_by_title($id, OBJECT, 'code');
        } else {
            $code = Database::get_code_by_slug($slug);
        }

        if (!is_object($code)) {
            return;
        }


        if (!CodeType::check_code_status($code)) {

            // Unauthorized Request
            Database::record_activity(0, $id, 6, $code->ID);

            return;
        }

        $co = Metabox::get_code_options($code);

        $is_plugin =  isset($co['code_is_plugin']) && $co['code_is_plugin'] == '1';

        if ($co['code_enabled'] == false || $is_plugin) {
            return;
        }


        $render_shortcodes = get_option('ci_code_injection_allow_shortcode', false);

        $nested_injections = self::get_shortcode_by_name($code->post_content, 'inject');

        foreach ($nested_injections as $i) {

            $params = $i['params'];

            if (isset($params['id']) && $params['id'] == $id) {
                Database::record_activity(0, $id, 3, $code->ID);
                return;
            }
        }

        Database::record_activity(0, $id, 0, $code->ID);

        if ($render_shortcodes) {
            return do_shortcode($code->post_content);
        } 

        return $code->post_content;
        
    }
}
