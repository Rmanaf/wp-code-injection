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
    Plugin URI: https://wordpress.org/plugins/wp-code-injection
    Description: Allows You to inject code snippets into the pages by just using the Wordpress shortcode
    Version: 2.1.2
    Author: Arman Afzal
    Author URI: https://github.com/Rmanaf
    License: Apache License, Version 2.0
    Text Domain: wp-code-injection
 */

/**
 * @package WP_Code_Injection_Plugin
 * @version 2.1.2
 */

defined('ABSPATH') or die;

require_once __DIR__ . '/wp-code-injection-plugin-widget.php';

if (!class_exists('WP_Code_Injection_Plugin')) {

    class WP_Code_Injection_Plugin
    {

        function __construct()
        {


            add_action('init', [&$this, 'create_posttype']);
            add_action('admin_init', [&$this, 'admin_init']);
            add_action('admin_head', [&$this, 'hide_post_title_input']);
            add_action('admin_head', [&$this, 'remove_mediabuttons']);
            add_action('admin_enqueue_scripts', [$this, 'print_scripts']);
            add_action('widgets_init', [$this, 'widgets_init']);
            add_filter('title_save_pre', [&$this, 'auto_generate_post_title']);
            add_filter('user_can_richedit', [&$this, 'disable_wysiwyg']);
            add_filter('post_row_actions', [&$this, 'remove_quick_edit'], 10, 1);
            add_filter('manage_codes_posts_columns', [&$this, 'manage_codes_columns']);


            add_shortcode('inject', [&$this, 'shortcode']);

            add_filter('dcp_shortcodes_list', [&$this, 'add_shortcode_to_list']);

        }


        /**
         * Prints admin scripts
         * @since 1.0.0
         */
        public function print_scripts()
        {

            if (!$this->is_code_page()) {

                return;

            }

            $ver = $this->get_version();


            wp_enqueue_style('dcp-codemirror', plugins_url('assets/codemirror/lib/codemirror.css', __FILE__), [], $ver, 'all');
            wp_enqueue_style('dcp-codemirror-dracula', plugins_url('assets/codemirror/theme/dracula.css', __FILE__), [], $ver, 'all');
            wp_enqueue_style('dcp-code-injection', plugins_url('assets/wp-code-injection-admin.css', __FILE__), [], $ver, 'all');

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

            wp_enqueue_script('dcp-code-injection', plugins_url('assets/wp-code-injection-admin.js', __FILE__), [], $ver, false);

        }



        /**
         * Add shortcode to the DCP shortcodes list
         * @since 1.0.0
         */
        public function add_shortcode_to_list($list)
        {

            $item = [
                'template' => "[inject id='']",
                'description' => __("Injects code snippets into the content", 'wp-code-injection')
            ];

            if (!is_array($list)) {
                return [$item];
            }

            return array_merge($list, [$item]);

        }



        public function admin_init()
        {

            if (!is_super_admin()) {
                return;
            }

            if (defined('DIVAN_CONTROL_PANEL')) {

                global $_DCP_PLUGINS;

                $group = 'dcp-settings-general';

                array_push($_DCP_PLUGINS, ['slug' => 'unsafe', 'version' => $this->get_version()]);

            } else {

                $group = 'general';

            }

            register_setting($group, 'wp_dcp_code_injection_allow_shortcode', ['default' => false]);


            add_settings_section(
                'wp_code_injection_plugin',
                __('Code Injection Plugin', 'wp-code-injection') . "<span class=\"gdcp-version-box wp-ui-notification\">" . ($group != 'general' ? $this->get_version() : '') . "<span>",
                [&$this, 'settings_section_cb'],
                $group
            );

            add_settings_field(
                'wp_dcp_code_injection_allow_shortcode',
                __("Accessibility", 'wp-code-injection'),
                [&$this, 'settings_field_cb'],
                $group,
                'wp_code_injection_plugin',
                ['label_for' => 'wp_dcp_code_injection_allow_shortcode']
            );

        }

        public function settings_section_cb()
        {

            echo "<p>" . __("Code Injection Plugin Settings", 'wp-code-injection') . "</p>";

        }

        public function settings_field_cb($args)
        {

            switch ($args['label_for']) {
                case 'wp_dcp_code_injection_allow_shortcode':
                    ?>
                    <label>
                        <input type="checkbox" value="1" id="wp_dcp_code_injection_allow_shortcode" name="wp_dcp_code_injection_allow_shortcode" <?php checked(get_option('wp_dcp_code_injection_allow_shortcode', false), true); ?> />
                        <?php _e("Allow rendering nested shortcodes", 'wp-code-injection'); ?>
                    </label>
                    <?php
                    break;
            }

        }



        /**
         * Renames Title Column to ID
         * @since 1.0.0
         */
        public function manage_codes_columns($columns)
        {

            $columns['title'] = "ID";

            return $columns;

        }


        /**
         * Disable quick edit button
         * @since 1.0.0
         */
        public function remove_quick_edit($actions)
        {

            if (isset($_GET['post_type']) && $_GET['post_type'] == 'codes') {
                unset($actions['inline hide-if-no-js']);
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

            if (current_user_can('edit_theme_options')) {

                register_widget('Wp_Code_Injection_Plugin_Widget');

            }

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
                return in_array($pagenow, array('post.php', ));
            elseif ($new_edit == "new")
                return in_array($pagenow, array('post-new.php'));
            else
                return in_array($pagenow, array('post.php', 'post-new.php'));

        }


        /**
         * Checks if is in code edit/new page
         * @since 1.0.0
         */
        private function is_code_page()
        {

            if ($this->is_edit_page('new')) {
                if (isset($_GET['post_type']) && $_GET['post_type'] == 'codes') {
                    return true;
                }
            }

            if ($this->is_edit_page('edit')) {

                global $post;

                if ('codes' == get_post_type($post)) {
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
         * Shortcode renderer 
         * @since 1.0.0
         */
        public function shortcode($atts = [], $content = null)
        {

            extract(shortcode_atts(['id' => ''], $atts));

            if (empty($id)) {

                return;

            }

            $code = get_page_by_title($id, OBJECT, 'codes');

            if (is_object($code)) {

                $render_shortcodes = get_option('wp_dcp_code_injection_allow_shortcode', false);

                $nested_injections = $this->get_shortcode_by_name($code->post_content, 'inject');

                foreach ($nested_injections as $i) {

                    $params = $i['params'];

                    if (isset($params['id']) && $params['id'] == $id) {

                        return '';

                    }

                }

                if ($render_shortcodes) {

                    return do_shortcode($code->post_content);

                } else {

                    return $code->post_content;

                }

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
         * Generate title
         * @since 1.0.0
         */
        public function auto_generate_post_title($title)
        {

            global $post;

            if (isset($post->ID)) {

                if (empty($_POST['post_title']) && 'codes' == get_post_type($post->ID)) {

                    $title = 'code-' . md5(uniqid(rand(), true));

                }
            }

            return $title;

        }



        /**
         * Create CPT
         * @since 1.0.0
         */
        public function create_posttype()
        {

            $lables = [
                'name' => __('Codes', 'wp-code-injection'),
                'singular_name' => __('Code', 'wp-code-injection'),
                'add_new_item' => __('Add New Code', 'wp-code-injection'),
                'edit_item' => __('Edit Code', 'wp-code-injection'),
                'new_item' => __('New Code', 'wp-code-injection'),
                'search_items ' => __('Search Codes', 'wp-code-injection'),
                'not_found' => __('No codes found', 'wp-code-injection'),
                'not_found_in_trash ' => __('No codes found in Trash', 'wp-code-injection'),
                'all_items' => __('All Codes', 'wp-code-injection')
            ];

            register_post_type(
                'Codes',
                [
                    'menu_icon' => 'dashicons-editor-code',
                    'labels' => $lables,
                    'public' => false,
                    'show_ui' => true,
                    'rewrite' => false,
                    'query_var' => false,
                    'exclude_from_search' => true,
                    'publicly_queryable' => false,
                    'supports' => ['author', 'revisions', 'title', 'editor'],
                    'capabilities' => [
                        'edit_post'          => 'update_core',
                        'read_post'          => 'update_core',
                        'delete_post'        => 'update_core',
                        'edit_posts'         => 'update_core',
                        'edit_others_posts'  => 'update_core',
                        'delete_posts'       => 'update_core',
                        'publish_posts'      => 'update_core',
                        'read_private_posts' => 'update_core'
                    ],
                    'can_export' => true
                ]
            );


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