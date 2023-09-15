<?php

/**
 * Licensed under MIT (https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE)
 * Copyright (c) 2018 Rmanaf <me@rmanaf.com>
 */


namespace ci;


/**
 * The Helpers class provides utility functions for various tasks within the code tracking system.
 *
 * This class offers a collection of static utility functions that are used across the code tracking system.
 * These functions assist in tasks such as determining the type of page being displayed, checking if a specific
 * page is an edit page, identifying settings pages, retrieving the user's IP address, and obtaining asset URLs.
 *
 * @namespace ci
 * @since 2.4.12
 */
class Helpers
{


    /**
     * Checks if the current page is related to managing code snippets.
     *
     * This static function determines whether the current page is associated with code snippet management.
     * It checks if the current page is either a new code creation page or an edit page for an existing code snippet.
     * The function examines the page type using the internal `is_edit_page` function, and additionally checks
     * if the post type matches 'code' to confirm that it's a code management page.
     *
     * @return bool True if the current page is a code management page, false otherwise.
     *
     * @since 2.4.12
     */
    static function is_code_page()
    {

        // Check if it's a new code creation page
        if (self::is_edit_page('new')) {
            if (isset($_GET['post_type']) && $_GET['post_type'] == 'code') {
                return true;
            }
        }

        // Check if it's an edit page for an existing code snippet
        if (self::is_edit_page('edit')) {
            global $post;
            if ('code' == get_post_type($post)) {
                return true;
            }
        }

        // If neither edit nor new code page, or post type is not 'code', it's not a code management page
        return false;

    }



    /**
     * Checks if the current page is an edit or new post creation page within the admin area.
     *
     * This static function determines whether the current page is an edit page or a new post creation page
     * within the WordPress admin area. It accepts an optional parameter to specify the type of page to check:
     * 'edit' for edit page and 'new' for new post creation page. The function examines the global variable `$pagenow`
     * to identify the current page and compares it with relevant page slugs to determine if the page matches the type.
     *
     * @param string|null $new_edit Specifies the type of page to check ('edit' for edit page, 'new' for new post creation page).
     * @return bool True if the current page is an edit or new post creation page, false otherwise.
     *
     * @since 2.4.12
     */
    static function is_edit_page($new_edit = null)
    {

        global $pagenow;

        // Check if it's within the admin area
        if (!is_admin()) {
            return false;
        }
    
        switch ($new_edit) {
            // Check if it's an edit page
            case "edit": 
                return in_array($pagenow, array('post.php'));

            // Check if it's a new post creation page
            case "new":
                return in_array($pagenow, array('post-new.php'));

            // Check if it's either an edit or new post creation page
            default:
                return in_array($pagenow, array('post.php', 'post-new.php'));
        }

    }


    /**
     * Checks if the current page is a settings page within the WordPress admin area.
     *
     * This static function determines if the current page is a settings page within the WordPress admin area.
     * It checks if the `get_current_screen` function exists, and if not, assumes it's not a settings page.
     * If the function exists, it retrieves the current screen object and examines the screen ID to determine
     * if it corresponds to the target settings screen ID, 'ci-general'.
     *
     * @return bool True if the current page is a settings page, false otherwise.
     *
     * @since 2.4.12
     */
    static function is_settings_page()
    {
        // Define the target settings screen ID
        $TARGET_SCREEN_ID = 'ci-general';

        // Check if the required function exists
        if (!function_exists('get_current_screen')) {
            return false;
        }

        // Retrieve the current screen object
        $screen = get_current_screen();

        // Check if the screen ID matches the target settings screen ID
        return $screen->id === $TARGET_SCREEN_ID;
    }




    /**
     * Retrieves the user's IP address from various possible sources.
     *
     * This static function retrieves the user's IP address from a list of potential headers and server variables
     * that might contain the IP address. It iterates through these sources, validates the IP addresses,
     * and returns the first valid IP address found. If no valid IP address is found, it returns "Unknown".
     *
     * @return string The user's IP address if found, otherwise "Unknown".
     *
     * @since 2.4.12
     */
    static function get_ip_address()
    {
        // List of possible headers and server variables containing the IP address
        $ip_sources = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        // Iterate through each source to find the user's IP address
        foreach ($ip_sources as $source) {
            if (array_key_exists($source, $_SERVER)) {
                foreach (explode(',', $_SERVER[$source]) as $ip) {
                    $ip = trim($ip);

                    // Validate the IP address and exclude private and reserved ranges
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip ?: "Unknown"; // Return the IP address or "Unknown"
                    }
                }
            }
        }

        return "Unknown"; // Return "Unknown" if no valid IP address is found
    }



    /**
     * Retrieves the URL of an asset using the provided relative path.
     *
     * This static function constructs the URL for an asset using the provided relative path within the plugin's assets directory.
     * It appends the provided path to the assets directory path, ensuring proper formatting by utilizing the `trailingslashit` function.
     * The resulting URL is based on the WordPress `plugins_url` function and the plugin's main file constant `__CI_FILE__`.
     *
     * @param string $relative_path The relative path of the asset within the plugin's assets directory.
     * @return string The complete URL of the asset.
     *
     * @since 2.4.12
     */
    static function get_asset_url($relative_path)
    {
        // Construct the URL by appending the relative path to the assets directory path
        $assets_dir_path = trailingslashit("/assets/");
        $asset_url = plugins_url($assets_dir_path . ltrim($relative_path, "/"), __CI_FILE__);

        return $asset_url;
    }
    

}
