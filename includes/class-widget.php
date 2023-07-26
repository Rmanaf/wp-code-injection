<?php

/**
 * Licensed under MIT (https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE)
 * Copyright (c) 2018 Rmanaf <me@rmanaf.com>
 */

namespace ci;

use WP_Widget;

class Widget extends WP_Widget
{

    /**
     *  @since 0.9.0
     */
    function __construct()
    {
        parent::__construct(
            'wp_code_injection_plugin_widget',
            esc_html__('Code Injection', 'code-injection'),
            ['description' => esc_html__("This plugin allows you to effortlessly create custom ads for your website. Inject code snippets in HTML, CSS, and JavaScript, write and run custom plugins on-the-fly, and take your website's capabilities to the next level.", 'code-injection')]
        );
    }


    /**
     * @since 0.9.0
     */
    function widget($args, $instance)
    {
        $title = apply_filters('widget_title', $instance['title']);

        if ($title == '0') {
            return;
        }

        //output
        echo do_shortcode("[inject id='$title']");
    }


    /**
     * @since 0.9.0
     */
    function form($instance)
    {

        $label     = esc_html__('Code ID:', 'code-injection');
        $fieldId   = $this->get_field_id('title');
        $fieldName = $this->get_field_name('title');

        if (isset($instance['title'])) {
            $title = $instance['title'];
        } else {
            $title = 'code-#########';
        }

        $codes = Database::get_codes();

        $codes = array_filter($codes, function ($item) {
            return $item->post_status == 'publish';
        });

        ob_start();

        printf('<option value="0">— %1$s —</option>', esc_html__("Select", "code-injection"));

        foreach ($codes as $code) {
            $codeTitle = get_post_meta($code->ID, "code_slug", true);
            $codeTitle = $codeTitle ?: $code->post_title;

            printf('<option %1$s value="%2$s">%3$s</option>', selected($code->post_title, $title, false), esc_attr($code->post_title), $codeTitle);
        }

        $options = ob_get_clean();

        printf('<p><label for="%1$s">%2$s</label><select style="width:100%;" id="%1$s" name="%2$s">%3$s</select></p>', $fieldId, $label , $fieldId, $fieldName, $options);

        wp_reset_query();

    }


    
    /**
     * @since 0.9.0
     */
    function update($new_instance, $old_instance)
    {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';
        return $instance;
    }
    
}
