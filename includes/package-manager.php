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

if (!class_exists('WP_CI_Package_Manager')) 
{

    class WP_CI_Package_Manager
    {

        private static $text_domain;

        function __construct($text_domain)
        {

            self::$text_domain = $text_domain;

            add_action('admin_menu', [$this, 'admin_menu']);

            add_action('dcp_settings_tab', [$this, 'dcp_packages_tab'], 60);

        }

        /**
         * Adds the "Packages" tab into the Control panel
         * @since 2.2.8
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
         * @since 2.2.8
         */
        public function admin_menu()
        {

            add_menu_page(
                __("Packages", self::$text_domain),
                __("Packages", self::$text_domain),
                'manage_options',
                'dcp-package-manager',
                [$this, 'packages_content'],
                'dashicons-cloud'
            );

        }

        /**
         * package manager template
         * @since 2.2.8
         */
        public function packages_content()
        {

            $packages = get_option( 'wp_dcp_pm_packages', [] );

            ?>
            <style>
                #pkg_type{
                    margin: 0px !important;
                    height: inherit !important;
                    vertical-align: unset !important;
                }
            </style>

            <h2><?php _e("Packages", self::$text_domain); ?></h2>

            <ul class="packages-list" id="pkgs_container">

                <li>

                    <span><strong>URI: </strong><input type="text" class="regular-text" id="pkg_uri" /></span>
                    
                    <span>
                      <strong>Type: </strong>
                      <select id="pkg_type">
                        <option>Style</option>
                        <option>Script</option>
                      </select>
                    </span>

                    <button id="pkg_add" class="button"><?php _e("Add" , self::$text_domain); ?></button>

                </li>

            <?php foreach($packages as $p): ?>

               <li>
                    <span class="<?php echo "pkg-$p->type" ?>">
                        <input type="hidden" id="<?php echo $p->id; ?>" />
                        <?php echo $p->uri; ?> 
                        <i>x</i> 
                    </span>
                </li>

            <?php

            endforeach;

        }


        /**
         * reset all settings
         * @since 2.2.8
         */
        public static function reset()
        {

            delete_option( 'wp_dcp_pm_packages' );

        }

    }

}
