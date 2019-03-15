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
            
            <h2><?php _e("Packages", self::$text_domain); ?></h2>

            <h2>Add menu items</h2>
            <div id="side-sortables" class="accordion-container">
		<ul class="outer-border">
						<li class="control-section accordion-section  add-post-type-page" id="add-post-type-page">
						<h3 class="accordion-section-title hndle" tabindex="0">
							Pages							<span class="screen-reader-text">Press return or enter to open this section</span>
						</h3>
						<div class="accordion-section-content " style="display: none;">
							<div class="inside">
									<div id="posttype-page" class="posttypediv">
		<ul id="posttype-page-tabs" class="posttype-tabs add-menu-item-tabs">
			<li class="tabs">
				<a class="nav-tab-link" data-type="tabs-panel-posttype-page-most-recent" href="#">
					Most Recent				</a>
			</li>
			<li>
				<a class="nav-tab-link" data-type="page-all" href="#">
					View All				</a>
			</li>
			<li>
				<a class="nav-tab-link" data-type="tabs-panel-posttype-page-search" href="#">
					Search				</a>
			</li>
		</ul><!-- .posttype-tabs -->

		<div id="tabs-panel-posttype-page-most-recent" class="tabs-panel tabs-panel-active">
			<ul id="pagechecklist-most-recent" class="categorychecklist form-no-clear">
				<li><label class="menu-item-title"><input type="checkbox" class="menu-item-checkbox" name="menu-item[-1][menu-item-object-id]" value="2"> Sample Page</label><input type="hidden" class="menu-item-db-id" name="menu-item[-1][menu-item-db-id]" value="0"><input type="hidden" class="menu-item-object" name="menu-item[-1][menu-item-object]" value="page"><input type="hidden" class="menu-item-parent-id" name="menu-item[-1][menu-item-parent-id]" value="0"><input type="hidden" class="menu-item-type" name="menu-item[-1][menu-item-type]" value="post_type"><input type="hidden" class="menu-item-title" name="menu-item[-1][menu-item-title]" value="Sample Page"><input type="hidden" class="menu-item-url" name="menu-item[-1][menu-item-url]" value="http://divan.local.com/sample-page/"><input type="hidden" class="menu-item-target" name="menu-item[-1][menu-item-target]" value=""><input type="hidden" class="menu-item-attr_title" name="menu-item[-1][menu-item-attr_title]" value=""><input type="hidden" class="menu-item-classes" name="menu-item[-1][menu-item-classes]" value=""><input type="hidden" class="menu-item-xfn" name="menu-item[-1][menu-item-xfn]" value=""></li>
			</ul>
		</div><!-- /.tabs-panel -->

		<div class="tabs-panel tabs-panel-inactive" id="tabs-panel-posttype-page-search">
						<p class="quick-search-wrap">
				<label for="quick-search-posttype-page" class="screen-reader-text">Search</label>
				<input type="search" class="quick-search" value="" name="quick-search-posttype-page" id="quick-search-posttype-page">
				<span class="spinner"></span>
				<input type="submit" name="submit" id="submit-quick-search-posttype-page" class="button button-small quick-search-submit hide-if-js" value="Search">			</p>

			<ul id="page-search-checklist" data-wp-lists="list:page" class="categorychecklist form-no-clear">
						</ul>
		</div><!-- /.tabs-panel -->

		<div id="page-all" class="tabs-panel tabs-panel-view-all tabs-panel-inactive">
						<ul id="pagechecklist" data-wp-lists="list:page" class="categorychecklist form-no-clear">
				<li><label class="menu-item-title"><input type="checkbox" class="menu-item-checkbox add-to-top" name="menu-item[-2][menu-item-object-id]" value="-2"> Home</label><input type="hidden" class="menu-item-db-id" name="menu-item[-2][menu-item-db-id]" value="0"><input type="hidden" class="menu-item-object" name="menu-item[-2][menu-item-object]" value=""><input type="hidden" class="menu-item-parent-id" name="menu-item[-2][menu-item-parent-id]" value=""><input type="hidden" class="menu-item-type" name="menu-item[-2][menu-item-type]" value="custom"><input type="hidden" class="menu-item-title" name="menu-item[-2][menu-item-title]" value="Home"><input type="hidden" class="menu-item-url" name="menu-item[-2][menu-item-url]" value="http://divan.local.com/"><input type="hidden" class="menu-item-target" name="menu-item[-2][menu-item-target]" value=""><input type="hidden" class="menu-item-attr_title" name="menu-item[-2][menu-item-attr_title]" value=""><input type="hidden" class="menu-item-classes" name="menu-item[-2][menu-item-classes]" value=""><input type="hidden" class="menu-item-xfn" name="menu-item[-2][menu-item-xfn]" value=""></li>
<li><label class="menu-item-title"><input type="checkbox" class="menu-item-checkbox" name="menu-item[-4][menu-item-object-id]" value="2"> Sample Page</label><input type="hidden" class="menu-item-db-id" name="menu-item[-4][menu-item-db-id]" value="0"><input type="hidden" class="menu-item-object" name="menu-item[-4][menu-item-object]" value="page"><input type="hidden" class="menu-item-parent-id" name="menu-item[-4][menu-item-parent-id]" value="0"><input type="hidden" class="menu-item-type" name="menu-item[-4][menu-item-type]" value="post_type"><input type="hidden" class="menu-item-title" name="menu-item[-4][menu-item-title]" value="Sample Page"><input type="hidden" class="menu-item-url" name="menu-item[-4][menu-item-url]" value="http://divan.local.com/sample-page/"><input type="hidden" class="menu-item-target" name="menu-item[-4][menu-item-target]" value=""><input type="hidden" class="menu-item-attr_title" name="menu-item[-4][menu-item-attr_title]" value=""><input type="hidden" class="menu-item-classes" name="menu-item[-4][menu-item-classes]" value=""><input type="hidden" class="menu-item-xfn" name="menu-item[-4][menu-item-xfn]" value=""></li>
			</ul>
					</div><!-- /.tabs-panel -->

		<p class="button-controls wp-clearfix">
			<span class="list-controls">
				<a href="#" class="select-all aria-button-if-js" role="button">Select All</a>
			</span>

			<span class="add-to-menu">
				<input type="submit" disabled="disabled" class="button submit-add-to-menu right" value="Add to Menu" name="add-post-type-menu-item" id="submit-posttype-page">
				<span class="spinner"></span>
			</span>
		</p>

	</div><!-- /.posttypediv -->
								</div><!-- .inside -->
						</div><!-- .accordion-section-content -->
					</li><!-- .accordion-section -->
										<li class="control-section accordion-section   add-post-type-post" id="add-post-type-post">
						<h3 class="accordion-section-title hndle" tabindex="0">
							Posts							<span class="screen-reader-text">Press return or enter to open this section</span>
						</h3>
						<div class="accordion-section-content " style="display: none;">
							<div class="inside">
									<div id="posttype-post" class="posttypediv">
		<ul id="posttype-post-tabs" class="posttype-tabs add-menu-item-tabs">
			<li class="tabs">
				<a class="nav-tab-link" data-type="tabs-panel-posttype-post-most-recent" href="#">
					Most Recent				</a>
			</li>
			<li>
				<a class="nav-tab-link" data-type="post-all" href="#">
					View All				</a>
			</li>
			<li>
				<a class="nav-tab-link" data-type="tabs-panel-posttype-post-search" href="#">
					Search				</a>
			</li>
		</ul><!-- .posttype-tabs -->

		<div id="tabs-panel-posttype-post-most-recent" class="tabs-panel tabs-panel-active">
			<ul id="postchecklist-most-recent" class="categorychecklist form-no-clear">
				<li><label class="menu-item-title"><input type="checkbox" class="menu-item-checkbox" name="menu-item[-5][menu-item-object-id]" value="1"> Hello world!</label><input type="hidden" class="menu-item-db-id" name="menu-item[-5][menu-item-db-id]" value="0"><input type="hidden" class="menu-item-object" name="menu-item[-5][menu-item-object]" value="post"><input type="hidden" class="menu-item-parent-id" name="menu-item[-5][menu-item-parent-id]" value="0"><input type="hidden" class="menu-item-type" name="menu-item[-5][menu-item-type]" value="post_type"><input type="hidden" class="menu-item-title" name="menu-item[-5][menu-item-title]" value="Hello world!"><input type="hidden" class="menu-item-url" name="menu-item[-5][menu-item-url]" value="http://divan.local.com/2019/03/03/hello-world/"><input type="hidden" class="menu-item-target" name="menu-item[-5][menu-item-target]" value=""><input type="hidden" class="menu-item-attr_title" name="menu-item[-5][menu-item-attr_title]" value=""><input type="hidden" class="menu-item-classes" name="menu-item[-5][menu-item-classes]" value=""><input type="hidden" class="menu-item-xfn" name="menu-item[-5][menu-item-xfn]" value=""></li>
			</ul>
		</div><!-- /.tabs-panel -->

		<div class="tabs-panel tabs-panel-inactive" id="tabs-panel-posttype-post-search">
						<p class="quick-search-wrap">
				<label for="quick-search-posttype-post" class="screen-reader-text">Search</label>
				<input type="search" class="quick-search" value="" name="quick-search-posttype-post" id="quick-search-posttype-post">
				<span class="spinner"></span>
				<input type="submit" name="submit" id="submit-quick-search-posttype-post" class="button button-small quick-search-submit hide-if-js" value="Search">			</p>

			<ul id="post-search-checklist" data-wp-lists="list:post" class="categorychecklist form-no-clear">
						</ul>
		</div><!-- /.tabs-panel -->

		<div id="post-all" class="tabs-panel tabs-panel-view-all tabs-panel-inactive">
						<ul id="postchecklist" data-wp-lists="list:post" class="categorychecklist form-no-clear">
				<li><label class="menu-item-title"><input type="checkbox" class="menu-item-checkbox" name="menu-item[-6][menu-item-object-id]" value="1"> Hello world!</label><input type="hidden" class="menu-item-db-id" name="menu-item[-6][menu-item-db-id]" value="0"><input type="hidden" class="menu-item-object" name="menu-item[-6][menu-item-object]" value="post"><input type="hidden" class="menu-item-parent-id" name="menu-item[-6][menu-item-parent-id]" value="0"><input type="hidden" class="menu-item-type" name="menu-item[-6][menu-item-type]" value="post_type"><input type="hidden" class="menu-item-title" name="menu-item[-6][menu-item-title]" value="Hello world!"><input type="hidden" class="menu-item-url" name="menu-item[-6][menu-item-url]" value="http://divan.local.com/2019/03/03/hello-world/"><input type="hidden" class="menu-item-target" name="menu-item[-6][menu-item-target]" value=""><input type="hidden" class="menu-item-attr_title" name="menu-item[-6][menu-item-attr_title]" value=""><input type="hidden" class="menu-item-classes" name="menu-item[-6][menu-item-classes]" value=""><input type="hidden" class="menu-item-xfn" name="menu-item[-6][menu-item-xfn]" value=""></li>
			</ul>
					</div><!-- /.tabs-panel -->

		<p class="button-controls wp-clearfix">
			<span class="list-controls">
				<a href="#" class="select-all aria-button-if-js" role="button">Select All</a>
			</span>

			<span class="add-to-menu">
				<input type="submit" disabled="disabled" class="button submit-add-to-menu right" value="Add to Menu" name="add-post-type-menu-item" id="submit-posttype-post">
				<span class="spinner"></span>
			</span>
		</p>

	</div><!-- /.posttypediv -->
								</div><!-- .inside -->
						</div><!-- .accordion-section-content -->
					</li><!-- .accordion-section -->
										<li class="control-section accordion-section   add-custom-links" id="add-custom-links">
						<h3 class="accordion-section-title hndle" tabindex="0">
							Custom Links							<span class="screen-reader-text">Press return or enter to open this section</span>
						</h3>
						<div class="accordion-section-content " style="display: none;">
							<div class="inside">
									<div class="customlinkdiv" id="customlinkdiv">
		<input type="hidden" value="custom" name="menu-item[-7][menu-item-type]">
		<p id="menu-item-url-wrap" class="wp-clearfix">
			<label class="howto" for="custom-menu-item-url">URL</label>
			<input id="custom-menu-item-url" name="menu-item[-7][menu-item-url]" type="text" class="code menu-item-textbox" value="http://">
		</p>

		<p id="menu-item-name-wrap" class="wp-clearfix">
			<label class="howto" for="custom-menu-item-name">Link Text</label>
			<input id="custom-menu-item-name" name="menu-item[-7][menu-item-title]" type="text" class="regular-text menu-item-textbox">
		</p>

		<p class="button-controls wp-clearfix">
			<span class="add-to-menu">
				<input type="submit" disabled="disabled" class="button submit-add-to-menu right" value="Add to Menu" name="add-custom-menu-item" id="submit-customlinkdiv">
				<span class="spinner"></span>
			</span>
		</p>

	</div><!-- /.customlinkdiv -->
								</div><!-- .inside -->
						</div><!-- .accordion-section-content -->
					</li><!-- .accordion-section -->
										<li class="control-section accordion-section   add-category" id="add-category">
						<h3 class="accordion-section-title hndle" tabindex="0">
							Categories							<span class="screen-reader-text">Press return or enter to open this section</span>
						</h3>
						<div class="accordion-section-content " style="display: none;">
							<div class="inside">
									<div id="taxonomy-category" class="taxonomydiv">
		<ul id="taxonomy-category-tabs" class="taxonomy-tabs add-menu-item-tabs">
			<li class="tabs">
				<a class="nav-tab-link" data-type="tabs-panel-category-pop" href="#">
					Most Used				</a>
			</li>
			<li>
				<a class="nav-tab-link" data-type="tabs-panel-category-all" href="#">
					View All				</a>
			</li>
			<li>
				<a class="nav-tab-link" data-type="tabs-panel-search-taxonomy-category" href="#">
					Search				</a>
			</li>
		</ul><!-- .taxonomy-tabs -->

		<div id="tabs-panel-category-pop" class="tabs-panel tabs-panel-active ">
			<ul id="categorychecklist-pop" class="categorychecklist form-no-clear">
				<li><label class="menu-item-title"><input type="checkbox" class="menu-item-checkbox" name="menu-item[-8][menu-item-object-id]" value="1"> Uncategorized</label><input type="hidden" class="menu-item-db-id" name="menu-item[-8][menu-item-db-id]" value="0"><input type="hidden" class="menu-item-object" name="menu-item[-8][menu-item-object]" value="category"><input type="hidden" class="menu-item-parent-id" name="menu-item[-8][menu-item-parent-id]" value="0"><input type="hidden" class="menu-item-type" name="menu-item[-8][menu-item-type]" value="taxonomy"><input type="hidden" class="menu-item-title" name="menu-item[-8][menu-item-title]" value="Uncategorized"><input type="hidden" class="menu-item-url" name="menu-item[-8][menu-item-url]" value="http://divan.local.com/category/uncategorized/"><input type="hidden" class="menu-item-target" name="menu-item[-8][menu-item-target]" value=""><input type="hidden" class="menu-item-attr_title" name="menu-item[-8][menu-item-attr_title]" value=""><input type="hidden" class="menu-item-classes" name="menu-item[-8][menu-item-classes]" value=""><input type="hidden" class="menu-item-xfn" name="menu-item[-8][menu-item-xfn]" value=""></li>
			</ul>
		</div><!-- /.tabs-panel -->

		<div id="tabs-panel-category-all" class="tabs-panel tabs-panel-view-all tabs-panel-inactive ">
						<ul id="categorychecklist" data-wp-lists="list:category" class="categorychecklist form-no-clear">
				<li><label class="menu-item-title"><input type="checkbox" class="menu-item-checkbox" name="menu-item[-9][menu-item-object-id]" value="1"> Uncategorized</label><input type="hidden" class="menu-item-db-id" name="menu-item[-9][menu-item-db-id]" value="0"><input type="hidden" class="menu-item-object" name="menu-item[-9][menu-item-object]" value="category"><input type="hidden" class="menu-item-parent-id" name="menu-item[-9][menu-item-parent-id]" value="0"><input type="hidden" class="menu-item-type" name="menu-item[-9][menu-item-type]" value="taxonomy"><input type="hidden" class="menu-item-title" name="menu-item[-9][menu-item-title]" value="Uncategorized"><input type="hidden" class="menu-item-url" name="menu-item[-9][menu-item-url]" value="http://divan.local.com/category/uncategorized/"><input type="hidden" class="menu-item-target" name="menu-item[-9][menu-item-target]" value=""><input type="hidden" class="menu-item-attr_title" name="menu-item[-9][menu-item-attr_title]" value=""><input type="hidden" class="menu-item-classes" name="menu-item[-9][menu-item-classes]" value=""><input type="hidden" class="menu-item-xfn" name="menu-item[-9][menu-item-xfn]" value=""></li>
			</ul>
					</div><!-- /.tabs-panel -->

		<div class="tabs-panel tabs-panel-inactive" id="tabs-panel-search-taxonomy-category">
						<p class="quick-search-wrap">
				<label for="quick-search-taxonomy-category" class="screen-reader-text">Search</label>
				<input type="search" class="quick-search" value="" name="quick-search-taxonomy-category" id="quick-search-taxonomy-category">
				<span class="spinner"></span>
				<input type="submit" name="submit" id="submit-quick-search-taxonomy-category" class="button button-small quick-search-submit hide-if-js" value="Search">			</p>

			<ul id="category-search-checklist" data-wp-lists="list:category" class="categorychecklist form-no-clear">
						</ul>
		</div><!-- /.tabs-panel -->

		<p class="button-controls wp-clearfix">
			<span class="list-controls">
				<a href="#" class="select-all aria-button-if-js" role="button">Select All</a>
			</span>

			<span class="add-to-menu">
				<input type="submit" disabled="disabled" class="button submit-add-to-menu right" value="Add to Menu" name="add-taxonomy-menu-item" id="submit-taxonomy-category">
				<span class="spinner"></span>
			</span>
		</p>

	</div><!-- /.taxonomydiv -->
								</div><!-- .inside -->
						</div><!-- .accordion-section-content -->
					</li><!-- .accordion-section -->
										<li class="control-section accordion-section   add-post_tag" id="add-post_tag">
						<h3 class="accordion-section-title hndle" tabindex="0">
							Tags							<span class="screen-reader-text">Press return or enter to open this section</span>
						</h3>
						<div class="accordion-section-content " style="display: none;">
							<div class="inside">
								<p>No items.</p>							</div><!-- .inside -->
						</div><!-- .accordion-section-content -->
					</li><!-- .accordion-section -->
							</ul><!-- .outer-border -->
	</div>

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
