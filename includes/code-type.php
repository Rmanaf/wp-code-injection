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
 * @version 2.2.9
 */


if (!class_exists('WP_CI_Code_Type')) {

    class WP_CI_Code_Type
    {

        private static $text_domain;

        private static $not_ready_states = ['private', 'draft', 'trash', 'pending'];

        function __construct()
        {

            self::$text_domain = WP_Code_Injection_Plugin::$text_domain;

            add_action('init', [$this, 'create_posttype']);

            add_action('admin_head', [$this, 'admin_head']);

            add_filter('title_save_pre', [$this, 'auto_generate_post_title']);

            add_filter('user_can_richedit', [$this, 'disable_wysiwyg']);

            add_filter('post_row_actions', [$this, 'custom_row_actions'], 10, 2);

            add_filter('manage_code_posts_columns', [$this, 'manage_code_posts_columns']);

            add_action('manage_code_posts_custom_column' , [$this, 'manage_code_posts_custom_column'], 10, 2 );

            add_action('admin_enqueue_scripts', [$this, 'print_scripts'], 50);

            add_action( 'restrict_manage_posts',  [$this, 'filter_codes_by_taxonomies'] , 10, 2);

        }


        public function print_scripts()
        {

            if (!$this->is_code_page()) {
                return;
            }

            $ver = WP_Code_Injection_Plugin::get_version();

            wp_enqueue_style('dcp-codemirror');
            wp_enqueue_style('dcp-codemirror-dracula');
            wp_enqueue_style('code-editor');

            //codemirror
            foreach(WP_CI_Assets_Manager::$codemirror_bundle as $script)
            {
                wp_enqueue_script($script);
            }
            
            wp_enqueue_script('dcp-code-injection-editor');

        }


        public function filter_codes_by_taxonomies( $post_type, $which ) {

            if ( 'code' !== $post_type )
                return;
        
            $taxonomies = ['code_category'];
        
            foreach ( $taxonomies as $taxonomy_slug ) {
        
                // Retrieve taxonomy data
                $taxonomy_obj = get_taxonomy( $taxonomy_slug );
                $taxonomy_name = $taxonomy_obj->labels->name;
        
                // Retrieve taxonomy terms
                $terms = get_terms( $taxonomy_slug );
        
                // Display filter HTML
                echo "<select name='{$taxonomy_slug}' id='{$taxonomy_slug}' class='postform'>";
                echo '<option value="">' . sprintf( esc_html__( 'Show All %s', self::$text_domain ), $taxonomy_name ) . '</option>';
                foreach ( $terms as $term ) {
                    printf(
                        '<option value="%1$s" %2$s>%3$s (%4$s)</option>',
                        $term->slug,
                        ( ( isset( $_GET[$taxonomy_slug] ) && ( $_GET[$taxonomy_slug] == $term->slug ) ) ? ' selected="selected"' : '' ),
                        $term->name,
                        $term->count
                    );
                }
                echo '</select>';
            }
        
        }


        public function admin_head()
        {

            $this->hide_post_title_input();

            $this->remove_mediabuttons();

        }

        /**
         * Disable quick edit button
         * @since 1.0.0
         */
        public function custom_row_actions($actions, $post)
        {

            if (isset($_GET['post_type']) && $_GET['post_type'] == 'code') {
                
                unset($actions['inline hide-if-no-js']);

                $status = get_post_status($post);

                $needles = [$post->post_title, '”' , '“'];

                if(isset($actions['edit']))
                {
                    $actions['edit'] = str_replace($needles , '' , $actions['edit']);
                }

                if(isset($actions['trash']))
                {
                    $actions['trash'] = str_replace($needles , '' , $actions['trash']);
                }


                if(!in_array($status , self::$not_ready_states))
                {

                    $cid_title = __("Copy the Code ID into the Clipboard", self::$text_domain);
                    $cid_text = __("Copy CID" , self::$text_domain);

                    $actions['copy_cid'] = "<a href=\"javascript:window.ci.ctc('#cid');\" title=\"$cid_title\" rel=\"permalink\">$cid_text</a>";

                }

            }

            return $actions;

        }

        /**
         * Generate title
         * @since 1.0.0
         */
        public function auto_generate_post_title($title)
        {

            global $post;

            if (isset($post->ID)) {

                if (empty($_POST['post_title']) && 'code' == get_post_type($post->ID)) {

                    $title = WP_Code_Injection_Plugin::generate_id('code-');

                }
            }

            return $title;

        }


        /**
         * Disable visual editor
         * @since 1.0.0
         */
        public function disable_wysiwyg($default)
        {

            if ($this->is_code_page()) {
                return false;
            }

            return $default;

        }

        /**
         * Hide post title input
         * @since 1.0.0
         */
        public function hide_post_title_input()
        {

            if ($this->is_code_page()) :
            ?>
                <style>#titlediv{display:none;}</style>
            <?php
            endif;

        }


        /**
         * disable media button
         * @since 1.0.0
         */
        private function remove_mediabuttons()
        {

            if ($this->is_code_page()) {

                remove_action('media_buttons', 'media_buttons');

            }

        }


         /**
         * Checks if is in post edit page
         * @since 1.0.0
         */
        private function is_edit_page($new_edit = null)
        {

            global $pagenow;


            if (!is_admin()) return false;


            if ($new_edit == "edit")
                return in_array($pagenow, array('post.php'));
            elseif ($new_edit == "new")
                return in_array($pagenow, array('post-new.php'));
            else
                return in_array($pagenow, array('post.php', 'post-new.php'));

        }


        /**
         * Checks if is in code edit/new page
         * @since 1.0.0
         */
        private function is_code_page()
        {

            if ($this->is_edit_page('new')) {
                if (isset($_GET['post_type']) && $_GET['post_type'] == 'code') {
                    return true;
                }
            }

            if ($this->is_edit_page('edit')) {

                global $post;

                if ('code' == get_post_type($post)) {
                    return true;
                }

            }

            return false;

        }
        



        /**
         * create code post type
         * @since 1.0.0
         */
        public function create_posttype()
        {

            $this->create_category_tax();

            // $this->create_directory_tax();


            $code_lables = [
                'name' => __('Codes', self::$text_domain),
                'singular_name' => __('Code', self::$text_domain),
                'add_new_item' => __('Add New Code', self::$text_domain),
                'edit_item' => __('Edit Code', self::$text_domain),
                'new_item' => __('New Code', self::$text_domain),
                'search_items ' => __('Search Codes', self::$text_domain),
                'not_found' => __('No codes found', self::$text_domain),
                'not_found_in_trash ' => __('No codes found in Trash', self::$text_domain),
                'all_items' => __('All Codes', self::$text_domain)
            ];


            register_post_type(
                'Code',
                [
                    'menu_icon' => 'dashicons-editor-code',
                    'labels' => $code_lables,
                    'public' => false,
                    'show_ui' => true,
                    'rewrite' => false,
                    'query_var' => false,
                    'exclude_from_search' => true,
                    'publicly_queryable' => false,
                    'supports' => ['author', 'revisions', 'title', 'editor'],
                    'taxonomies' => ['directory'],
                    'capability_type' => ['code','codes'],
                    'can_export' => true,
                    'map_meta_cap' => true
                ]
            );


        }
        
        
        /**
         * create category taxonomy
         * @since 2.2.8
         */
        private function create_category_tax(){

            register_taxonomy( 
                'code_category', 
                'code', 
                [
                   'show_admin_column' => true,
                   'public' => false,
                   'show_ui' => true,
                   'rewrite' => false,
                   'hierarchical' => true
                ]
            );

        }

         /**
         * create directory taxonomy
         * @since 2.2.8
         */
        private function create_directory_tax(){

            $lables = [
                'name' => __('Directories' , self::$text_domain),
                'menu_name' => __('Directories' , self::$text_domain),
                'singular_name' => __('Directory', self::$text_domain),
                'add_new_item' => __('Add New Directory', self::$text_domain),
                'edit_item' => __('Edit Directory', self::$text_domain),
                'new_item_name' => __('New Directory Name', self::$text_domain),
                'parent_item' => __('Parent Directory', self::$text_domain),
                'parent_item_colon' => __('Parent Directory:', self::$text_domain),
                'search_items ' => __('Search Directories', self::$text_domain),
                'not_found' => __('No directories found', self::$text_domain),
                'all_items' => __('All Directories', self::$text_domain),
                'popular_items' => __('Popular Directories', self::$text_domain),
                'choose_from_most_used' => __('Choose from the most used directories', self::$text_domain),
                'add_or_remove_items' => __('Add or remove directories', self::$text_domain),
                'back_to_items' => __('← Back to directories', self::$text_domain)
            ];

            register_taxonomy( 
                'directory', 
                'code', 
                [
                   'labels' => $lables,
                   'show_admin_column' => true,
                   'public' => false,
                    'show_ui' => true,
                    'rewrite' => false,
                   'hierarchical' => true
                ]
            );

        }


         /**
         * Rename header of title column to ID
         * @since 1.0.0
         */
        public function manage_code_posts_columns($columns)
        {

            $columns = [];

            $columns['id'] = __("Code" , self::$text_domain);
            $columns['statistics'] = __("Hits", self::$text_domain) . " — " . WP_CI_Calendar_Heatmap::map();
            $columns['info'] = __("Info", self::$text_domain);

            return $columns;

        }

        public function manage_code_posts_custom_column( $column, $post_id ){

            switch ( $column ) {
                case 'info':

                    $code = get_post($post_id);

                    $categories = get_the_terms( $code, 'code_category' );

                    ?>

                    <dl>

                        <?php if(is_array($categories) && count($categories) > 0) : ?>
                            <dt>
                                <strong><?php _e("Categories") ?></strong>
                            <dt>
                            <dd>
                                <?php 
                                    foreach($categories as $c){
                                        echo "<span>$c->name<span>,";
                                    }
                                ?>
                            <dd>
                        <?php endif; ?>

                        <dt>
                            <strong><?php _e("Author") ?></strong>
                        <dt>
                        <dd>
                            <?php  
                                echo esc_html(get_the_author_meta('display_name' , $code->post_author) . 
                                " — <" . get_the_author_meta('user_email' , $code->post_author) . ">"); 
                            ?>
                        <dd>
                        <dt>
                            <strong><?php _e("Date") ?></strong>
                        <dt>
                        <dd>
                            <?php echo date_i18n( 'F j, Y - g:i a' , strtotime($code->post_modified) ); ?>
                        <dd>
                    </dl>

                    <?php

                    break;
                case 'id':

                    $code = get_post($post_id);

                    $status = get_post_status($post_id);
                    
                    $code_options = WP_CI_Code_Metabox::get_code_options($code);
                 
                    ?>
                        <p style="text-align: justify;">
                            <?php echo $code_options['code_description']; ?>  —  <strong><?php echo ucwords($status); ?></strong>
                        </p>
                        
                        <?php 
                            /**
                             * prevents the showing of the code IDs in the following states
                             * private, draft, trash, pending
                             */
                            if(in_array($status , self::$not_ready_states)) 
                            {
                                break;
                            } 
                        ?>

                        <dl>
                            <dt>
                                <strong><?php _e("Code ID") ?></strong>
                            <dt>
                            <dd>
                                <code id='cid' style="font-size:11px;"><?php echo $code->post_title; ?></code>
                            <dd>
                        </dl>
                    <?php

                    break;

                case 'statistics':

                    // get GMT
                    $cdate = current_time( 'mysql' , 1 );

                    // start from 6 days ago
                    $start = new DateTime($cdate);
                    $start->sub(new DateInterval('P6D')); 

                    // today
                    $end = new DateTime($cdate);
                    
                    $heatmap = new WP_CI_Calendar_Heatmap();
                    $heatmap->load(WP_CI_Database::$table_activities_name , $post_id, $start, $end);
                    $heatmap->render();

                break;
            }
        }

    }

}