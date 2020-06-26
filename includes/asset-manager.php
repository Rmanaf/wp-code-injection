<?php

/**
 * MIT License <https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE>
 * Copyright (c) 2020 Arman Afzal <rman.afzal@gmail.com>
 */

if (!class_exists('WP_CI_Assets_Manager')) 
{

    class WP_CI_Asset_Manager
    {

        private static $version;
        private static $plugin;


        /**
         * @since 2.4.2
         */
        static function init($plugin , $version)
        {
            self::$version = $version;
            self::$plugin  = $plugin;
            add_action( 'admin_enqueue_scripts', 'WP_CI_Asset_Manager::enqueue_scripts' , 50);
        }



        /**
         * @since 2.2.8
         */
        static function enqueue_scripts()
        {

            $ver = self::$version;

            $plugin = self::$plugin;

            
            wp_register_style('ci-custom-code-editor', plugins_url('assets/css/code-editor.css', $plugin), [], $ver, 'all');

            wp_register_script('ci-monaco-editor-loader', plugins_url('assets/monaco-editor/vs/loader.js', $plugin), ['jquery'], $ver, true);
            
            wp_register_script('ci-code-injection-editor', plugins_url('assets/js/code-editor.js', $plugin), [], $ver, false);


            wp_enqueue_script('ci-code-injection-essentials', plugins_url('assets/js/essentials.js', $plugin), ['jquery'] , $ver, true);
            
            wp_enqueue_style('ci-code-injection', plugins_url('assets/css/wp-code-injection-admin.css', $plugin), [], $ver, 'all');


            if(self::is_settings_page()) {  

                // tag-editor styles
                wp_enqueue_style('ci-tag-editor' , plugins_url('assets/css/jquery.tag-editor.css', $plugin), [], $ver, 'all');

                // tag-editor scripts
                wp_enqueue_script('ci-caret' , plugins_url('assets/js/jquery.caret.min.js', $plugin), ['jquery'], $ver, false);
                wp_enqueue_script('ci-tag-editor' , plugins_url('assets/js/jquery.tag-editor.min.js', $plugin), ['jquery','dcp-caret'], $ver, false);


                // admin settings scripts
                wp_enqueue_script('ci-code-injection', plugins_url('assets/js/wp-ci-general-settings.js', $plugin), ['jquery'], $ver, true);

            }

        }


        /**
         * @since 2.4.2
         */
        static function enqueue_editor_scripts(){

            wp_enqueue_style('ci-custom-code-editor');

            wp_enqueue_script('ci-monaco-editor-loader');

            wp_enqueue_script('ci-code-injection-editor');

        }


        /**
         * @since 2.2.8
         */
        private static function is_settings_page()
        {

            $screen = get_current_screen();

            return $screen->id == 'options-general';

        }


    }

}