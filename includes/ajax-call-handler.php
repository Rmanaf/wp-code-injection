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

if (!class_exists('WP_CI_AJAX_Call_Handler')) {

    class WP_CI_AJAX_Call_Handler
    {

        public function __construct()
        {
            
            $this->active_ajax_calls();

            add_action('wp_enqueue_scripts', [$this , 'enqueue_ajax_scripts']);

        }


        public function enqueue_ajax_scripts(){

            wp_localize_script( 'ci-ajax-script', 'ci_ajax_object', ['ajax_url' => admin_url( 'admin-ajax.php' )]);

        }


        private function active_ajax_calls(){

            global $wpdb;

           
            $querystr = "
                SELECT $wpdb->posts.ID, $wpdb->posts.post_title, $wpdb->posts.post_content, $wpdb->postmeta.meta_value
                FROM $wpdb->posts, $wpdb->postmeta
                WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id 
                AND $wpdb->postmeta.meta_key = 'code_options'
                AND $wpdb->posts.post_status = 'publish' 
                AND $wpdb->posts.post_type = 'code'
            ";

            $codes = $wpdb->get_results($querystr, OBJECT);

            
            foreach($codes as $c)
            {

                $code_options = maybe_unserialize( $c->meta_value );

                $acn = $code_options['action_name'];

                $allow = $code_options['allow_ajax_call'];

                if(!empty($acn) && $allow)
                {

                    add_action("wp_ajax_$acn" , "handle_ajax_action_call");

                }

            }
            
        }


        public function handle_ajax_action_call()
        {

            return $_POST['action'];

        }

    }

}