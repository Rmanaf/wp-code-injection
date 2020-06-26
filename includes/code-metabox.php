<?php

/**
 * MIT License <https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE>
 * Copyright (c) 2020 Arman Afzal <rman.afzal@gmail.com>
 */

if (!class_exists('WP_CI_Code_Metabox')) {

        class WP_CI_Code_Metabox
        {

            private static $default_values = [
                'code_description' => '',
                'code_tracking' => true,
                'code_enabled' => true,
                'code_is_plugin' => false,
                'code_activator_key' => '',
                'code_is_template' => false
            ];

            private static $text_domain;

            /**
             * @since 2.4.2
             */
            static function init($text_domain)
            {
                self::$text_domain = $text_domain;
                add_action('add_meta_boxes',  'WP_CI_Code_Metabox::add_meta_box');
                add_action('save_post',  'WP_CI_Code_Metabox::save_post');

            }


            /**
             * @since 2.2.8
             */
            static function add_meta_box()
            {

                add_meta_box(
                    'code_options_metabox',
                    __('Code Options', self::$text_domain),
                    'WP_CI_Code_Metabox::code_options_meta_box_cb',
                    'code',
                    'side'
                );

            }


            /**
             * @since 2.2.8
             */
            static function save_post($id)
            {

                if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
                {
                    return;
                }

                if (!isset($_POST['code_meta_box_nonce']) || !wp_verify_nonce($_POST['code_meta_box_nonce'], 'code-settings-nonce'))
                {
                    return;
                }

                if (!current_user_can('edit_post' , $id))
                {
                    return;
                }

                $value = [];

                foreach(array_keys(self::$default_values) as $p){

                    if(isset($_REQUEST[$p]))
                    {
                        
                        $value[$p] = esc_html( $_REQUEST[$p] );

                    }

                }

                update_post_meta($id, 'code_options',  $value);

            }


            /**
             * @since 2.2.8
             */
            static function get_code_options($code)
            {
                
                $ID = $code;

                if(isset($code->ID))
                {
                    $ID = $code->ID;
                }

                $code_options = get_post_meta($ID, 'code_options', true);

                if(!is_array($code_options) || empty($code_options))
                {

                    $code_options = self::$default_values;

                }

                foreach(array_keys(self::$default_values) as $key) {

                    if(!isset($code_options[$key])){

                        $code_options[$key] = false;

                    }

                }

                return $code_options;

            }


            /**
             * @since 2.2.8
             */
            static function code_options_meta_box_cb($code)
            {

                $code_options = self::get_code_options($code);

                extract( $code_options );
                wp_nonce_field('code-settings-nonce', 'code_meta_box_nonce');

                $ignore_keys = get_option('wp_dcp_unsafe_ignore_keys', false);
                $use_php = get_option('wp_dcp_unsafe_widgets_php', false);

                ?>

                <!-- 'description' section -->
                <p><b><?php _e("Description" , self::$text_domain) ?></b></p>

                <textarea rows="5" style="width:100%;" id="code_description" name="code_description"><?php echo $code_description; ?></textarea>
                <!-- 'description' section -->

                <!-- 'tracking' section -->
                <p>
                    <label>
                        <input <?php checked($code_tracking , true); ?> type="checkbox" id="code_tracking" name="code_tracking" value="1" />
                        <?php _e("Tracking" , self::$text_domain); ?>
                    </label>  

                    <?php if($code_is_plugin): ?>

                    <p class="description">
                        <?php _e("<span class=\"dashicons dashicons-info\"></span> Plugins are not able to be tracked." , self::$text_domain); ?>
                    </p>

                    <?php endif; ?>

                </p>
                <!-- 'tracking' section -->


                <!-- 'plugin' section -->
                <p>
                    
                    <label>
                        <input <?php checked($code_is_plugin , true); ?> type="checkbox" id="code_is_plugin" name="code_is_plugin" value="1" />
                        <?php _e("As Plugin" , self::$text_domain); ?>
                    </label> 

                    <?php if(!$use_php) : ?>
                    
                    <p class="description">
                        <?php _e("<span class=\"dashicons dashicons-info\"></span> Running PHP codes has disabled by the administrator." , self::$text_domain); ?>
                    </p>

                    <?php endif; ?>

                    <?php if($code_is_plugin): ?>

                    <p class="description">
                        <?php _e("<span class=\"dashicons dashicons-info\"></span> The <code>[inject]</code> shortcode ignores the code that is marked as plugin." , self::$text_domain); ?>
                    </p>

                    <?php endif; ?>

                    <?php if(!$ignore_keys) : ?>
                    
                    <p><strong><?php _e("Activator Key:" , self::$text_domain); ?></strong></p>
  
                    <input type="text" style="width:100%;"  id="code_activator_key" name="code_activator_key" value="<?php echo $code_activator_key; ?>" />              
                    
                    <?php else: ?>
                    
                    <input type="hidden"  id="code_activator_key" name="code_activator_key" value="<?php echo $code_activator_key; ?>" />              
                    
                    <?php endif; ?>

                </p>
                <!-- 'plugin' section -->



                <!-- 'enable' section -->
                <p>
                    <label>
                        <input <?php checked($code_enabled , true); ?> type="checkbox" id="code_enabled" name="code_enabled" value="1" />
                        <?php _e("Enabled" , self::$text_domain); ?>
                    </label>               
                </p>
                <!-- 'enable' section -->


                <?php

            }

        }
    }
