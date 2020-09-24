<?php

/**
 * Licensed under MIT (https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE)
 * Copyright (c) 2018 Arman Afzal (https://rmanaf.com)
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
                'code_is_template' => false,
                'code_is_publicly_queryable' => false,
                'code_content_type' => "text/plain",
                'code_no_cache' => false
            ];

            /**
             * @since 2.4.2
             */
            static function init()
            {
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
                    esc_html__('Code Settings', "code-injection"),
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

                $is_plugin = isset($code_is_plugin) && $code_is_plugin == '1';

                $is_public = isset($code_is_publicly_queryable) && $code_is_publicly_queryable == '1';

                if (!isset($code_enabled)) {
                    $code_enabled = false;
                }

                $messages = [
                    [
                        "show" => !$code_enabled,
                        "message" => "The Code is Suspended."
                    ],
                    [
                        "show" => !$use_php,
                        "message" => "You are not able to run PHP scripts."
                    ],
                    [
                        "show" => $is_plugin && !$is_public,
                        "message" => "You are not able to inject this code into the posts."
                    ],
                    [
                        "show" => $is_plugin && !$is_public,
                        "message" => "You are not able to track this code."
                    ],
                ];

                $logo = WP_CI_Asset_Manager::get_asset_url("/image/ci.svg");

                ?>


                <!-- 'header'  section -->

                <div class="ci-metabox-group ci-header" >

                    <img class="ci-logo" src="<?php echo $logo; ?>" />
                    <p><?php 

                        $url = "https://github.com/Rmanaf/ci-library";
                        printf(
                            esc_html__('You can find useful codes in the CI %1$s.' , "code-injection" ) ,
                            sprintf(
                                '<a target="_blank" href="%s">%s</a>',
                                $url,
                                esc_html__( 'Library', 'code-injection' )
                            ));
    
                     ?></p>
                
                </div>

                <!-- 'header'  section -->



                <!-- 'info' section -->

                <div class="ci-metabox-group" >

                <?php foreach($messages as $m) : if($m["show"]) : ?>

                    <p class="ci-description">
                        <span class="dashicons dashicons-info"></span>
                        <?php esc_html_e($m["message"] , "code-injection"); ?>
                    </p>

                <?php endif; endforeach; ?>

                </div>

                <!-- 'info' section -->




                <!-- 'description' section -->

                <div class="ci-metabox-group" >
                    <p><strong><?php esc_html_e("Description" , "code-injection") ?></strong></p>

                    <textarea placeholder="<?php esc_html_e("Write something about your code..." , "code-injection"); ?>" rows="2" style="width:100%;" id="code_description" name="code_description"><?php echo $code_description; ?></textarea>
                </div>

                <!-- 'description' section -->



                <!-- 'tracking' section -->

                <div class="ci-metabox-group" id="code_tracking_group" >
                    <label>
                        <input <?php checked($code_tracking , true); ?> type="checkbox" id="code_tracking" name="code_tracking" value="1" />
                        <?php esc_html_e("Tracking" , "code-injection"); ?>
                    </label>  

                </div>

                <!-- 'tracking' section -->

                

                <!-- 'publicly_queryable' section -->

                <div class="ci-metabox-group">
                    <label>
                        <input data-checkbox-activator data-show-targets="code_no_cache_group,code_content_type_group" data-hide-targets="code_is_plugin_group" <?php checked($code_is_publicly_queryable , true); ?> type="checkbox" id="code_is_publicly_queryable" name="code_is_publicly_queryable" value="1" />
                        <?php esc_html_e("Publicly Queryable" , "code-injection"); ?>
                    </label>  
                </div>

                <!-- 'publicly_queryable' section -->


                <!-- 'no_cache' section -->

                <div id="code_no_cache_group" class="ci-metabox-group">
                    <label>
                        <input <?php checked($code_no_cache , true); ?> type="checkbox" id="code_no_cache" name="code_no_cache" value="1" />
                        <?php esc_html_e("No-Cache" , "code-injection"); ?>
                    </label>  
                </div>

                <!-- 'no_cache' section -->



                <!-- 'content_type' section -->
               
                <div class="ci-metabox-group" id="code_content_type_group">
                    <p><strong><?php esc_html_e("Content-Type" , "code-injection"); ?></strong></p>

                    <select style="width: 100%;" id="code_content_type" name="code_content_type" >
                        
                        <?php foreach([
                            "text/plain",
                            "text/css",
                            "text/html",
                            "text/xml",
                            "text/javascript",
                            "image/svg+xml",
                            "application/json",
                            "application/xml",
                            "application/octet-stream"
                        ] as $type) : ?>
                                <option value="<?php echo $type; ?>" <?php selected( $code_content_type, $type); ?> ><?php echo $type; ?></option>
                        <?php endforeach; ?>
                        
                    </select>

                </div>

                <!-- 'content_type' section -->
               

                <!-- 'plugin' section -->
                
                <div class="ci-metabox-group" id="code_is_plugin_group">
                    
                    <label>
                        <input data-checkbox-activator  data-show-targets="code_activator_key,code_activator_key_label" <?php checked($code_is_plugin , true); ?> type="checkbox" id="code_is_plugin" name="code_is_plugin" value="1" />
                        <?php esc_html_e("As Plugin" , "code-injection"); ?>
                    </label> 
                    
                    <?php if(!$ignore_keys) : ?>
                    
                    <p id="code_activator_key_label"><strong><?php esc_html_e("Activator key" , "code-injection"); ?></strong></p>
  
                    <input type="text" placeholder="<?php esc_html_e("Enter key..." , "code-injection"); ?>" style="width:100%;"  id="code_activator_key" name="code_activator_key" value="<?php echo $code_activator_key; ?>" />              
                    
                    <?php else: ?>
                    
                    <input type="hidden"  id="code_activator_key" name="code_activator_key" value="<?php echo $code_activator_key; ?>" />              
                    
                    <?php endif; ?>

                </div>

                <!-- 'plugin' section -->
                



                <!-- 'from file' section -->
                <div class="ci-metabox-group" >
                    <p><b><?php esc_html_e("From File" , "code-injection") ?></b></p>
                    
                    <button id="fileInputDelegate" class="button button-primary" style="width:100%;" ><?php _e("Select File" , "code-injection"); ?></button>
                    
                    <input style="display: none;" type="file" id="fileInput" />
                </div>
                <!-- 'from file' section -->


                <!-- 'enable' section -->
                <p>
                    <label>
                        <input <?php checked($code_enabled , true); ?> type="checkbox" id="code_enabled" name="code_enabled" value="1" />
                        <?php esc_html_e("Active" , "code-injection"); ?>
                    </label>               
                </p>
                <!-- 'enable' section -->


                <?php

            }

        }
    }
