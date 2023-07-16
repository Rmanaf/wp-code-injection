<?php

/**
 * Licensed under MIT (https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE)
 * Copyright (c) 2018 Rmanaf <me@rmanaf.com>
 */

namespace ci;

class AssetManager
{


    /**
     * @since 2.4.2
     */
    static function init()
    {
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_scripts'), 50);
    }



    /**
     * @since 2.2.8
     */
    static function enqueue_scripts()
    {

        $texts = array(
            "The File is too large. Do you want to proceed?",
            "Are you sure? You are about to replace the current code with the selected file content.",
            "The selected file type is not supported.",
            "Copy"
        );

        $i18n = array(
            'code-injection' => array(
                'texts'         => $texts , 
                'translates'    => array_map(function ($item) {
                    return esc_html__($item, "code-injection");
                }, $texts)
            )
        );

        wp_register_style('ci-custom-code-editor', Helpers::get_asset_url('/css/code-editor.css'), array(), __CI_VERSION__, 'all');

        wp_register_script('ci-monaco-editor-loader', 'https://cdnjs.cloudflare.com/ajax/libs/monaco-editor/0.21.2/min/vs/loader.min.js', array('jquery'), null, true);
        wp_register_script('ci-editor', Helpers::get_asset_url('/js/code-editor.js'), array(), __CI_VERSION__, false);

        wp_enqueue_script('ci-essentials', Helpers::get_asset_url('/js/essentials.js'), array('jquery'), __CI_VERSION__, true);

        wp_localize_script('ci-essentials', "_ci", array(
            'ajax_url'      => admin_url('admin-ajax.php'),
            "ajax_nonce"    => wp_create_nonce("code-injection-ajax-nonce"),
            "is_rtl"        => is_rtl() ? "true" : "false",
            "i18n"          => $i18n
        ));

        wp_enqueue_style('ci-styles', Helpers::get_asset_url('/css/wp-code-injection-admin.css'), array(), __CI_VERSION__, 'all');

        if (Helpers::is_settings_page()) {

            // tag-editor styles
            wp_enqueue_style('ci-tag-editor', Helpers::get_asset_url('/css/jquery.tag-editor.css'), array(), __CI_VERSION__, 'all');

            // tag-editor scripts
            wp_enqueue_script('ci-caret', Helpers::get_asset_url('/js/jquery.caret.min.js'), array('jquery'), __CI_VERSION__, false);
            wp_enqueue_script('ci-tag-editor', Helpers::get_asset_url('/js/jquery.tag-editor.min.js'), array('jquery', 'ci-caret'), __CI_VERSION__, false);

            // admin settings scripts
            wp_enqueue_script('ci-code-injection', Helpers::get_asset_url('/js/wp-ci-general-settings.js'), array('jquery'), __CI_VERSION__, true);
        }
    }


    /**
     * @since 2.4.2
     */
    static function enqueue_editor_scripts()
    {
        wp_enqueue_style('ci-custom-code-editor');

        wp_enqueue_script('ci-monaco-editor-loader');
        wp_enqueue_script('ci-editor');
    }
}
