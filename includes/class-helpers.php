<?php

/**
 * Licensed under MIT (https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE)
 * Copyright (c) 2018 Rmanaf <me@rmanaf.com>
 */

namespace ci;

class Helpers
{


    /**
     * @since 2.4.12
     */
    static function is_code_page()
    {

        if (self::is_edit_page('new')) {
            if (isset($_GET['post_type']) && $_GET['post_type'] == 'code') {
                return true;
            }
        }

        if (self::is_edit_page('edit')) {

            global $post;

            if ('code' == get_post_type($post)) {
                return true;
            }

        }

        return false;
    }



    /**
     * @since 2.4.12
     */
    static function is_edit_page($new_edit = null)
    {

        global $pagenow;

        if (!is_admin()) {
            return false;
        }

        if ($new_edit == "edit") {
            return in_array($pagenow, array('post.php'));
        }

        if ($new_edit == "new") {
            return in_array($pagenow, array('post-new.php'));
        }

        return in_array($pagenow, array('post.php', 'post-new.php'));
    }


    /**
     * @since 2.4.12
     */
    static function is_settings_page()
    {

        if (!function_exists('get_current_screen')) {
            return false;
        }

        $screen = get_current_screen();

        return strpos($screen->id , 'ci-general') !== false;
    }



    /**
     * @since 2.4.12
     */
    static function get_ip_address()
    {

        foreach ([
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ] as $key) {

            if (array_key_exists($key, $_SERVER) === true) {

                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);

                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip ?: "Unknown";
                    }
                }
            }
        }
    }


    /**
     * @since 2.4.12
     */
    static function get_asset_url($path)
    {
        return plugins_url("/assets/" . rtrim(ltrim($path, "/"), "/"), __CI_FILE__);
    }
}
