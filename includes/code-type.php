<?php

/**
 * MIT License <https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE>
 * Copyright (c) 2020 Arman Afzal <rman.afzal@gmail.com>
 */

if (!class_exists('WP_CI_Code_Type')) {

    class WP_CI_Code_Type
    {

        private static $plugin;

        private static $text_domain;

        private static $not_ready_states = ['private', 'draft', 'trash', 'pending'];


        /**
         * @since 2.4.2
         */
        static function init($plugin)
        {

            self::$plugin = $plugin;

            self::$text_domain = WP_Code_Injection_Plugin::$text_domain;

            add_action('init', 'WP_CI_Code_Type::create_posttype');

            add_action('admin_head', 'WP_CI_Code_Type::admin_head');

            add_action('admin_enqueue_scripts', 'WP_CI_Code_Type::print_scripts', 51);

            add_filter('title_save_pre', 'WP_CI_Code_Type::auto_generate_post_title');

            add_filter('user_can_richedit', 'WP_CI_Code_Type::disable_wysiwyg');

            add_filter('post_row_actions', 'WP_CI_Code_Type::custom_row_actions', 10, 2);

            add_filter('manage_code_posts_columns', 'WP_CI_Code_Type::manage_code_posts_columns');

            add_action('manage_code_posts_custom_column', 'WP_CI_Code_Type::manage_code_posts_custom_column', 10, 2);

            add_action('restrict_manage_posts',  'WP_CI_Code_Type::filter_codes_by_taxonomies', 10, 2);

        }


        /**
         * @since 2.2.8
         */
        public function print_scripts()
        {

            if (!self::is_code_page()) {
                return;
            }

            WP_CI_Asset_Manager::enqueue_editor_scripts();
        }


        /**
         * @since 2.2.8
         */
        static function filter_codes_by_taxonomies($post_type, $which)
        {

            if ('code' !== $post_type)
                return;

            $taxonomies = ['code_category'];

            foreach ($taxonomies as $taxonomy_slug) {

                // Retrieve taxonomy data
                $taxonomy_obj = get_taxonomy($taxonomy_slug);
                $taxonomy_name = $taxonomy_obj->labels->name;

                // Retrieve taxonomy terms
                $terms = get_terms($taxonomy_slug);

                // Display filter HTML
                echo "<select name='{$taxonomy_slug}' id='{$taxonomy_slug}' class='postform'>";
                echo '<option value="">' . sprintf(esc_html__('Show All %s', self::$text_domain), $taxonomy_name) . '</option>';
                foreach ($terms as $term) {
                    printf(
                        '<option value="%1$s" %2$s>%3$s (%4$s)</option>',
                        $term->slug,
                        ((isset($_GET[$taxonomy_slug]) && ($_GET[$taxonomy_slug] == $term->slug)) ? ' selected="selected"' : ''),
                        $term->name,
                        $term->count
                    );
                }
                echo '</select>';
            }
        }


        /**
         * @since 2.2.8
         */
        static function admin_head()
        {

            self::hide_post_title_input();

            self::remove_mediabuttons();

            if (!self::is_code_page()) {
                return;
            }

?>

            <script>
                var require = {
                    paths: {
                        'vs': '<?php echo plugins_url('assets/monaco-editor/vs', self::$plugin) ?>',
                        'js': '<?php echo plugins_url('assets/js', self::$plugin) ?>'
                    }
                };
            </script>

            <?php

        }

        /**
         * @since 2.2.8
         */
        static function custom_row_actions($actions, $post)
        {

            if (isset($_GET['post_type']) && $_GET['post_type'] == 'code') {

                unset($actions['inline hide-if-no-js']);

                $status = get_post_status($post);

                $needles = [$post->post_title, '”', '“'];

                if (isset($actions['edit'])) {
                    $actions['edit'] = str_replace($needles, '', $actions['edit']);
                }

                if (isset($actions['trash'])) {
                    $actions['trash'] = str_replace($needles, '', $actions['trash']);
                }


                if (!in_array($status, self::$not_ready_states)) {

                    $cid_title = __("Copy the Code ID into the Clipboard", self::$text_domain);
                    $cid_text = __("Copy CID", self::$text_domain);

                    $actions['copy_cid'] = "<a href=\"javascript:window.ci.ctc('#cid-$post->ID');\" title=\"$cid_title\" rel=\"permalink\">$cid_text</a>";
                }
            }

            return $actions;
        }

        /**
         * @since 2.2.8
         */
        static function auto_generate_post_title($title)
        {

            global $post;

            if (wp_is_post_autosave($post)) {
                return $title;
            }

            if (wp_is_post_revision($post)) {
                return $title;
            }


            if (isset($post->ID)) {

                if (empty($_POST['post_title'])) {

                    if (!empty($title)) {
                        return $title;
                    }

                    if ('code' == get_post_type($post->ID)) {
                        return WP_Code_Injection_Plugin::generate_id('code-');
                    }
                }
            }

            return $title;
        }


        /**
         * @since 2.2.8
         */
        static function disable_wysiwyg($default)
        {

            if (self::is_code_page()) {
                return false;
            }

            return $default;
        }

        /**
         * @since 2.2.8
         */
        private static function hide_post_title_input()
        {

            if (self::is_code_page()) :
            ?>
                <style>
                    #titlediv {
                        display: none;
                    }
                </style>
                <?php
            endif;
        }


        /**
         * @since 2.2.8
         */
        private static function remove_mediabuttons()
        {

            if (self::is_code_page()) {

                remove_action('media_buttons', 'media_buttons');
            }
        }


        /**
         * @since 2.2.8
         */
        private static function is_edit_page($new_edit = null)
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
         * @since 2.2.8
         */
        private static function is_code_page()
        {

            if (self::is_edit_page('new')) {
                if (isset($_GET['post_type']) && $_GET['post_type'] == 'code') {
                    return true;
                }
            }

            if (self::is_edit_page('edit')) {

                global $post;

                if ('code' == get_post_type($post)) {
                    return true;
                }
            }

            return false;
        }


        /**
         * @since 2.2.8
         */
        static function create_posttype()
        {

            self::create_category_tax();

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
                    'capability_type' => ['code', 'codes'],
                    'can_export' => true,
                    'map_meta_cap' => true
                ]
            );
        }


        /**
         * @since 2.2.8
         */
        private static function create_category_tax()
        {

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
         * @since 2.2.8
         */
        static function manage_code_posts_columns($columns)
        {

            $columns = [];

            $columns['id'] = __("Code", self::$text_domain);
            $columns['statistics'] = __("Hits", self::$text_domain) . " — " . WP_CI_Calendar_Heatmap::map();
            $columns['info'] = __("Info", self::$text_domain);

            return $columns;
        }



        /**
         * @since 2.2.8
         */
        static function manage_code_posts_custom_column($column, $post_id)
        {

            switch ($column) {
                case 'info':

                    $code = get_post($post_id);

                    $categories = get_the_terms($code, 'code_category');

                ?>

                    <dl>

                        <?php if (is_array($categories) && count($categories) > 0) : ?>
                            <dt>
                                <strong><?php _e("Categories") ?></strong>
                            <dt>
                            <dd>
                                <?php
                                foreach ($categories as $c) {
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
                                echo esc_html(get_the_author_meta('display_name', $code->post_author) .
                                    " — <" . get_the_author_meta('user_email', $code->post_author) . ">");
                                ?>
                            <dd>
                            <dt>
                                <strong><?php _e("Date") ?></strong>
                            <dt>
                            <dd>
                                <?php echo date_i18n('F j, Y - g:i a', strtotime($code->post_modified)); ?>
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
                        <?php echo $code_options['code_description']; ?> — <strong><?php echo ucwords($status); ?></strong>
                    </p>

                    <?php
                    if (in_array($status, self::$not_ready_states)) {
                        break;
                    }
                    ?>

                    <dl>
                        <dt>
                            <strong><?php _e("Code ID") ?></strong>
                        <dt>
                        <dd>
                            <code id="<?php echo "cid-$code->ID"; ?>" style="font-size:11px;"><?php echo $code->post_title; ?></code>
                        <dd>
                    </dl>
<?php

                    break;

                case 'statistics':

                    // get GMT
                    $cdate = current_time('mysql', 1);

                    // start from 6 days ago
                    $start = new DateTime($cdate);
                    $start->sub(new DateInterval('P6D'));

                    // today
                    $end = new DateTime($cdate);

                    $heatmap = new WP_CI_Calendar_Heatmap();
                    $heatmap->load(WP_CI_Database::$table_activities_name, $post_id, $start, $end);
                    $heatmap->render();

                    break;
            }
        }
    }
}
