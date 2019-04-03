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
 */

if (!class_exists('WP_CI_Assets_Manager')) 
{

    class WP_CI_Assets_Manager
    {

        private $version;
        private $plugin;

        function __construct($plugin , $version)
        {

            $this->version = $version;

            $this->plugin  = $plugin;

            add_action( 'admin_enqueue_scripts', [$this , 'print_scripts'] , 50);

        }



        function register_styles()
        {

            $ver = $this->version;

            // monaco editor
            wp_register_style('dcp-monaco-editor', plugins_url('assets/monaco-editor/vs/editor/editor.main.css', $this->plugin), [], $ver, 'all');
            
            // tagEditor
            wp_register_style('dcp-tag-editor', plugins_url('assets/jquery.tag-editor.css', $this->plugin), [], $ver, 'all');
              
            wp_register_style('dcp-custom-code-editor', plugins_url('assets/code-editor.css', $this->plugin), [], $ver, 'all');
            

        }



        function register_scripts()
        {

            $ver = $this->version;

            // monaco-editor
            wp_register_script('dcp-monaco-editor-loader', plugins_url('assets/monaco-editor/vs/loader.js', $this->plugin), ['jquery'], $ver, true);
            //wp_register_script('dcp-monaco-editor-nls', plugins_url('assets/monaco-editor/vs/editor/editor.main.nls.js', $this->plugin), ['jquery'], $ver, true);
            //wp_register_script('dcp-monaco-editor', plugins_url('assets/monaco-editor/vs/editor/editor.main.js', $this->plugin), ['jquery'], $ver, true);
            

            // tagEditor
            wp_register_script('dcp-caret', plugins_url('assets/jquery.caret.min.js', $this->plugin), ['jquery'], $ver, false);
            wp_register_script('dcp-tag-editor', plugins_url('assets/jquery.tag-editor.min.js', $this->plugin), ['jquery','dcp-caret'], $ver, false);


            wp_register_script('dcp-code-injection-editor', plugins_url('assets/code-editor.js', $this->plugin), [], $ver, false);

        }


        /**
         * register assets
         * @since 2.2.8
         */
        private function register_assets()
        {

            $this->register_styles();
            $this->register_scripts();

        }


        /**
         * print scripts
         * @since 2.2.8
         */
        function print_scripts()
        {

            $ver = $this->version;

            $this->register_assets();

            wp_enqueue_script('dcp-code-injection-essentials', plugins_url('assets/essentials.js', $this->plugin), ['jquery'] , $ver, true);
            
            wp_enqueue_style('dcp-code-injection', plugins_url('assets/wp-code-injection-admin.css', $this->plugin), [], $ver, 'all');

            if($this->is_settings_page()) {  

                wp_enqueue_style('dcp-tag-editor');

                wp_enqueue_script('dcp-caret');
                wp_enqueue_script('dcp-tag-editor');
                wp_enqueue_script('dcp-code-injection', plugins_url('assets/wp-ci-general-settings.js', $this->plugin), ['jquery'], $ver, true);
            
            }

        }


         /**
         * checks if is in the General settings page
         * @since 2.2.8
         */
        private function is_settings_page()
        {

            $screen = get_current_screen();

            if(defined('DIVAN_CONTROL_PANEL'))
            {

                if(isset($_GET['page']) && isset($_GET['tab'])){

                    return  $_GET['page'] == 'dcp-settings' &&  $_GET['tab'] == 'general';

                }
                
            } 

            return $screen->id == 'options-general';

        }


    }

}