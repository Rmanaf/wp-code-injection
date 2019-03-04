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

if (!class_exists('WP_Package_Manager')) 
{

    class WP_Package_Manager
    {

        function __construct()
        {

            add_action('admin_menu', [$this, 'admin_menu']);

            add_action('dcp_settings_tab', [$this, 'dcp_packages_tab'], 60);

        }

        /**
         * Adds the "Packages" tab into the Control panel
         * @since 1.0.0
         */
        public function dcp_packages_tab()
        {

            global $_DCP_ACTIVE_TAB;

            $class = $_DCP_ACTIVE_TAB == "packages" ? 'nav-tab-active' : '';

            $href = admin_url('options-general.php?page=dcp-settings&tab=packages');

            echo "<a class=\"nav-tab $class\" href=\"$href\"><span class=\"dcp-package\"></span>Packages</a>";

        }

        /**
         * adds the packages menu item into the admin menu
         * @since 1.0.0
         */
        public function admin_menu()
        {

            add_menu_page(
                __("Packages", 'code-injection'),
                __("Packages", 'code-injection'),
                'manage_options',
                'dcp-package-manager',
                [$this, 'packages_content'],
                'dashicons-cloud'
            );

        }

    }

}
