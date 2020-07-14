/**
 * MIT License <https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE>
 * Copyright (c) 2018 Arman Afzal <rman.afzal@gmail.com>
 */
; (($) => {

    $(document).ready(() => {

        $usafe_keys = $('#wp_dcp_unsafe_keys');

        $usafe_keys.tagEditor({
            placeholder: $usafe_keys.data('placeholder') || ''
        });

        $('#wp_dcp_generate_key').click(() => {
            const id = Math.random().toString(36).substr(2, 9);
            $('#wp_dcp_unsafe_keys').tagEditor('addTag', `key-${id}`);
        });

    })

})(jQuery);