<?php

/**
 * MIT License <https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE>
 * Copyright (c) 2018 Arman Afzal <rman.afzal@gmail.com>
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

            $texts = [
                "The File is too large. Do you want to proceed?",
                "Are you sure? You are about to replace the current code with the selected file content.",
                "The selected file type is not supported."
            ];

            
            wp_register_style('ci-custom-code-editor', self::get_asset_url('/css/code-editor.css'), [], $ver, 'all');

            wp_register_script('ci-monaco-editor-loader', self::get_asset_url('/monaco-editor/vs/loader.js'), ['jquery'], $ver, true);
            
            wp_register_script('ci-code-injection-editor', self::get_asset_url('/js/code-editor.js'), [], $ver, false);


            wp_enqueue_script('ci-code-injection-essentials', self::get_asset_url('/js/essentials.js'), ['jquery'] , $ver, true);
            
            wp_localize_script( 'ci-code-injection-essentials', "_ci", [
                "i18n" => [
                    "code-injection" => [
                        "texts" => $texts,
                        "translates" => array_map(function($item){
                            return esc_html__( $item ,"code-injection" );
                        } , $texts)
                    ]
                ]
            ]);

            wp_enqueue_style('ci-code-injection', self::get_asset_url('/css/wp-code-injection-admin.css'), [], $ver, 'all');


            if(self::is_settings_page()) {  

                // tag-editor styles
                wp_enqueue_style('ci-tag-editor' , self::get_asset_url('/css/jquery.tag-editor.css'), [], $ver, 'all');

                // tag-editor scripts
                wp_enqueue_script('ci-caret' , self::get_asset_url('/js/jquery.caret.min.js'), ['jquery'], $ver, false);
                wp_enqueue_script('ci-tag-editor' , self::get_asset_url('/js/jquery.tag-editor.min.js'), ['jquery','ci-caret'], $ver, false);


                // admin settings scripts
                wp_enqueue_script('ci-code-injection', self::get_asset_url('/js/wp-ci-general-settings.js'), ['jquery'], $ver, true);

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


        /**
         * @since 2.4.3
         */
        static function get_asset_url($path){
            return plugins_url("/assets/" . rtrim(ltrim($path , "/") , "/"), self::$plugin);
        }


    }

}