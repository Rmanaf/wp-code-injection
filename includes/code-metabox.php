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
 * 
 * 
 * Third Party Licenses :
 * 
 * tagEditor :
 * 
 * MIT License
 *
 * 
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

/**
 * @author Arman Afzal <rman.afzal@gmail.com>
 * @package WP_Divan_Control_Panel
 * @version 2.2.8
 */


if (!class_exists('WP_CI_Code_Metabox')) {

        class WP_CI_Code_Metabox
        {

            private static $default_values = [
                'description' => '',
                'code_tracking' => true,
                'code_enabled' => true,
            ];

            private static $text_domain;

            function __construct($text_domain)
            {

                self::$text_domain = $text_domain;

                add_action('add_meta_boxes',       [$this, 'add_meta_box']);

                add_action('save_post',            [$this, 'save_post']);

            }

            /**
             * adds the options meta box
             * @since 2.2.8
             */
            public function add_meta_box()
            {

                add_meta_box(
                    'code_options_metabox',
                    __('Code Options', self::$text_domain),
                    [$this, 'code_options_meta_box_cb'],
                    'code',
                    'side'
                );
            }

            /**
             * handle the code options
             * @since 2.2.8
             */
            public function save_post($id)
            {

                if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
                {
                    return;
                }

                if (!isset($_POST['code_meta_box_nonce']) || !wp_verify_nonce($_POST['code_meta_box_nonce'], 'code-settings-nonce'))
                {
                    return;
                }

                if (!current_user_can('edit_post'))
                {
                    return;
                }

                $value = [];

                foreach(array_keys(self::$default_values) as $p){

                    if(!isset($_REQUEST[$p]))
                    {

                        $value[$p] =  self::$default_values[$p];

                    } else {

                        $value[$p] = esc_html( $_REQUEST[$p] );

                    }

                }

                update_post_meta($id, 'code_options',  $value);

            }


            /**
             * returns the code options
             * @since 2.2.8
             */
            public static function get_code_options($code)
            {

                $code_options = get_post_meta($code->ID, 'code_options', true);

                if(!is_array($code_options) || empty($code_options))
                {

                    $code_options = self::$default_values;

                }

                return $code_options;

            }



            public function code_options_meta_box_cb($code)
            {

                $code_options = self::get_code_options($code);

                extract( $code_options );

                wp_nonce_field('code-settings-nonce', 'code_meta_box_nonce');

                ?>

                <!-- 'description' section -->
                <p><b><?php _e("Description" , self::$text_domain) ?></b></p>

                <textarea rows="5" style="width:100%;" id="description" name="description"><?php echo $description; ?></textarea>
                <!-- 'description' section -->

                <!-- 'tracking' section -->
                <p>
                    <label>
                        <input <?php checked($code_tracking , true); ?> type="checkbox" class="regular-text" id="code_tracking" name="code_tracking" value="1" />
                        <?php _e("Tracking" , self::$text_domain); ?>
                    </label>  
                </p>
                <!-- 'tracking' section -->

                <!-- 'enable' section -->
                <p>
                    <label>
                        <input <?php checked($code_enabled , true); ?> type="checkbox" class="regular-text" id="code_enabled" name="code_enabled" value="1" />
                        <?php _e("Enabled" , self::$text_domain); ?>
                    </label>               
                </p>
                <!-- 'enable' section -->

                <?php

            }

        }
    }
