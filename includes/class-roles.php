<?php

/**
 * Licensed under MIT (https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE)
 * Copyright (c) 2018 Rmanaf <me@rmanaf.com>
 */

namespace ci;

class Roles
{

    /**
     * @since 2.4.12
     */
    static function init(){
        add_action('admin_init' , array(__CLASS__ , '_register_roles'));
        add_action('admin_init' , array(__CLASS__ , '_update_caps'));
    }


    /**
     * @since 2.4.12
     * @access private
     */
    static function _register_roles()
    {

        $developer      = get_role('developer');
        $role_version   = get_option('ci_role_version', '');


        if ($role_version == __CI_VERSION__ && isset($developer)) {
            return;
        }


        remove_role('developer');


        add_role(
            'developer',
            esc_html__('Developer', "code-injection"),
            [
                'read' => true,
                'edit_posts' => false,
                'delete_posts' => false,
                'publish_posts' => false,
                'upload_files' => true,
            ]
        );


        update_option('ci_role_version', __CI_VERSION__);
    }


    /**
     * @since 2.2.6
     * @access private
     */
    static function _update_caps()
    {

        $roles = array('developer', 'administrator');

        foreach ($roles as $role) {

            $ur = get_role($role);

            if (!isset($ur)) {
                continue;
            }

            foreach (array(
                'publish',  'delete',  'delete_others', 'delete_private',
                'delete_published',  'edit', 'edit_others',  'edit_private',
                'edit_published', 'read_private'
            ) as $cap) {
                $ur->add_cap("{$cap}_code");
                $ur->add_cap("{$cap}_codes");
            }
        }
    }


}
