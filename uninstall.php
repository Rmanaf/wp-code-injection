<?php

/**
 * MIT License <https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE>
 * Copyright (c) 2018 Arman Afzal <rman.afzal@gmail.com>
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

// "CI" options
delete_option('wp_dcp_code_injection_db_version');

delete_option('wp_dcp_code_injection_allow_shortcode');

delete_option('wp_dcp_code_injection_role_version');


// "Unsafe" options
delete_option('wp_dcp_unsafe_widgets_shortcodes');

delete_option('wp_dcp_unsafe_widgets_php');

delete_option('wp_dcp_unsafe_ignore_keys');


if (empty(get_option('wp_dcp_unsafe_keys', ''))) {

    delete_option('wp_dcp_unsafe_keys');

}