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
 * @package WP_Divan_Control_Panel
 * @version 2.2.8
 */


if (!class_exists('WP_Code_Metabox')) {

    class WP_Code_Metabox
    {

        function __construct()
        {

            add_action('add_meta_boxes',       [$this, 'add_meta_box']);
            add_action('save_post',            [$this, 'save_post']);

        }

        public function add_meta_box()
        {

            add_meta_box(
                'code_config_metabox',
                __('Configs', 'code-injection'),
                [$this, 'code_config_meta_box_cb'],
                'codes',
                'side'
            );

        }

        public function code_config_meta_box_cb($code)
        {

            $data = get_post_meta($code->ID, 'empty_page_data', true);
        }

        public function save_post($id)
        {

            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
                return;

            if (!isset($_POST['meta_box_nonce']) || !wp_verify_nonce($_POST['meta_box_nonce'], 'page-configs-nonce'))
                return;

            if (!current_user_can('edit_post'))
                return;

            update_post_meta($id, 'empty_page_data', array(
                'scripts' => $_POST['scripts'],
                'styles' => $_POST['styles'],
            ));
        }
    }
}
