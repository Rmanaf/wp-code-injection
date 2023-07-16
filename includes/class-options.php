<?php

/**
 * Licensed under MIT (https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE)
 * Copyright (c) 2018 Rmanaf <me@rmanaf.com>
 */

namespace ci;

class Options
{

    const option_group = 'ci-general';


    /**
     * @since 2.4.12
     */
    static function init()
    {
        add_action('admin_init', array( __CLASS__ , '_admin_init'));
        add_action('admin_menu', array( __CLASS__ , '_admin_menu'));
    }


    /**
     * @since 2.4.12
     */
    static function get_keys()
    {
        return self::extract_keys(get_option('ci_unsafe_keys', ''));
    }


    /**
     * @since 2.4.12
     */
    private static function extract_keys($text)
    {
        return array_filter(explode(',', $text), function ($elem) {
            return preg_replace('/\s/', '', $elem);
        });
    }


    /**
     * @since 2.4.12
     * @access private
     */
    static function _admin_menu(){

        add_submenu_page(
            'options-general.php',
            __('Code Injection', 'code-injection'),
            __('Code Injection', 'code-injection'),
            'manage_options',
            self::option_group ,
            array(__CLASS__ , '_settings_page_cb')
        );

    }


    /**
     * @since 2.4.12
     * @access private
     */
    static function _admin_init()
    {

        // Settings Section
        add_settings_section('wp_code_injection_plugin', "", "__return_false", self::option_group);

        // register code settings
        register_setting(self::option_group, 'ci_code_injection_cache_max_age', ['default' => '84600']);

        // register "CI" settings
        register_setting(self::option_group, 'ci_code_injection_allow_shortcode', ['default' => false]);

        // register "Unsafe" settings
        register_setting(self::option_group, 'ci_unsafe_widgets_shortcodes', ['default' => false]);
        register_setting(self::option_group, 'ci_unsafe_keys', ['default' => '']);
        register_setting(self::option_group, 'ci_unsafe_widgets_php', ['default' => false]);
        register_setting(self::option_group, 'ci_unsafe_ignore_keys', ['default' => false]);

        self::add_settings_field('ci_code_injection_cache_max_age', '84600',  esc_html__("Code Options", "code-injection"));

        self::add_settings_field('ci_code_injection_allow_shortcode', false, esc_html__("Shortcodes", "code-injection"));

        self::add_settings_field('ci_unsafe_widgets_shortcodes', false);
        self::add_settings_field('ci_unsafe_widgets_php', false);
        self::add_settings_field('ci_unsafe_ignore_keys', false,  esc_html__("Activator Keys", "code-injection"));
        self::add_settings_field('ci_unsafe_keys');
    }



    /**
     * @since 2.4.12
     * @access private
     */
    static function _settings_section_cb()
    {
    }


    /**
     * @since 2.4.12
     * @access private
     */
    static function _settings_page_cb()
    {

        $title  = esc_html__('Code Injection', "code-injection");

        ob_start();

        settings_fields(self::option_group);

        do_settings_sections(self::option_group);

        submit_button();

        $content = ob_get_clean();

        $template = '<div class="wrap"><h1 class="title">%1$s</h1><form method="POST" action="options.php">%2$s</form></div>';

        printf( $template, $title , $content );

    }



    /**
     * @since 2.4.12
     */
    private static function add_settings_field($id, $default = '', $title = '', $section = 'wp_code_injection_plugin', $page = self::option_group)
    {
        add_settings_field($id, $title, array(__CLASS__, '_settings_field_cb'),  $page, $section, array('label_for' => $id,  'default'   => $default));
    }


    /**
     * @since 2.4.12
     */
    private static function checkbox($key, $value, $description)
    {
        printf('<label><input type="checkbox" value="1" id="%1$s" name="%1$s" %2$s />%3$s</label>', $key, checked($value, true, false), $description);
    }


    /** 
     * @since 2.4.12
     * @access private
     */
    static function _settings_field_cb($args)
    {

        $key        = $args['label_for'];
        $default    = isset($args['default']) ? $args['default'] : '';
        $value      = get_option($key, $default);

        switch ($key) {

            case 'ci_code_injection_cache_max_age':

                printf('<p>%1$s</p>', esc_html__('Cache max-age (Seconds)', 'code-injection'));
                printf('<input class="regular-text" type="number" value="%1$s" id="%2$s" name="%2$s" />', $value, $key);
                printf('<p class="description">e.g.&nbsp;&nbsp;&nbsp;&nbsp;%1$s</p>', $default);

                break;

            case 'ci_unsafe_keys':

                printf('<p class="ack-head-wrapper"><span class="ack-header"><strong>%1$s</strong></span><a class="button ack-new" href="javascript:void(0);" id="ci_generate_key">%2$s</a></p>', esc_html__("Keys:", "code-injection"), esc_html__("Generate Key", "code-injection"));
                printf('<p><textarea data-placeholder="%1$s" class="large-text code" id="%2$s" name="%2$s">%3$s</textarea></p>', esc_html__("Enter Keys:", "code-injection"), $key, $value);
                printf('<p class="description">e.g.&nbsp;&nbsp;&nbsp;&nbsp;key-2im2a5ex4,&nbsp;&nbsp;key-6dp7mwt05 ...</p>', $default);

                break;

            case 'ci_code_injection_allow_shortcode':

                self::checkbox($key, $value, esc_html__("Allow nested shortcodes", "code-injection"));

                break;

            case 'ci_unsafe_ignore_keys':

                self::checkbox($key, $value, esc_html__("Ignore activator keys", "code-injection"));

                break;

            case 'ci_unsafe_widgets_shortcodes':

                self::checkbox($key, $value, esc_html__("Allow shortcodes in the Custom HTML widget", "code-injection"));

                break;

            case 'ci_unsafe_widgets_php':

                self::checkbox($key, $value, sprintf(esc_html__("Enable %s shortcode", "code-injection"), "<code>[unsafe key='']</code>"));

                printf(
                    '<p class="description">%1$s</p>',
                    sprintf(
                        esc_html__('See %1$s for more information.', "code-injection"),
                        sprintf(
                            '<a target="_blank" href="%1$s">%2$s</a>',
                            esc_url('https://github.com/Rmanaf/wp-code-injection/blob/master/README.md'),
                            esc_html__("Readme", "code-injection")
                        )
                    )
                );

                break;
        }
    }
}
