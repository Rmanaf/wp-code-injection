<?php

/**
 * Licensed under MIT (https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE)
 * Copyright (c) 2018 Rmanaf <me@rmanaf.com>
 */

if (!class_exists('Wp_Code_Injection_Plugin_Widget')) {


    class Wp_Code_Injection_Plugin_Widget extends WP_Widget
    {

        function __construct()
        {

            parent::__construct(
                'wp_code_injection_plugin_widget',
                esc_html__('Code Injection', 'code-injection'),
                ['description' => esc_html__('This plugin allows you to inject code snippets into the pages.', 'code-injection')]
            );

        }

        public function widget($args, $instance)
        {

            $title = apply_filters('widget_title', $instance['title']);

            if($title == '0')
            {
                return;
            }

            //output
            echo do_shortcode("[inject id='$title']");

        }

        public function form($instance)
        {

            if (isset($instance['title'])) {

                $title = $instance['title'];

            } else {

                $title = 'code-#########';

            }

            $codes = WP_CI_Database::get_codes();

            $codes = array_filter($codes , function($item){
                return $item->post_status == 'publish';
            });
            
            ?>

             <p>
                <label for="<?php echo $this->get_field_id('title'); ?>"><?php esc_html_e('Code ID:' , 'code-injection'); ?></label>
                <select style="width:100%;" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>">
                <option value="0">— <?php esc_html_e("Select" , "code-injection"); ?> —</option>
            <?php

            foreach ($codes as $code) {

                $codeTitle = get_post_meta( $code->ID, "code_slug", true );

                $codeTitle = $codeTitle ?: $code->post_title;

                ?>
                    <option <?php selected( $code->post_title , $title ) ?> value="<?php echo esc_attr($code->post_title); ?>" ><?php echo $codeTitle; ?></option>
                <?php
            }

            echo '</select></p>';

            wp_reset_query();
            
        }

        public function update($new_instance, $old_instance)
        {

            $instance = array();

            $instance['title'] = (!empty($new_instance['title'])) ? strip_tags($new_instance['title']) : '';

            return $instance;

        }

    }

}