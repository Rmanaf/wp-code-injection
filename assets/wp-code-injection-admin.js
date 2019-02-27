; (($) => {

    $(document).ready(() => {

        $('.quicktags-toolbar').remove();

        $('#wp-content-editor-tools').remove();

        $('#post-status-info').remove();

        $('.wp-editor-area').each((i, e) => {

            CodeMirror.fromTextArea(e, {
                lineNumbers: true,
                autoCloseBrackets: true,
                matchBrackets: true,
                matchTags: true,
                autoCloseTags: true,
                mode: "application/x-httpd-php",
                theme: 'dracula',
                keyMap: 'sublime',
                viewportMargin: Infinity
            })

        });


        // unsafe

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