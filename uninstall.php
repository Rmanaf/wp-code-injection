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