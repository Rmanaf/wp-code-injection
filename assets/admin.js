; (($) => {

    $(document).ready(() => {

        $('.quicktags-toolbar').remove();

        $('#wp-content-editor-tools').remove();

        $('#post-status-info').remove();

        var textarea = $('.wp-editor-area')[0];

        var editor = CodeMirror.fromTextArea(textarea, {
            lineNumbers: true,
            autoCloseBrackets: true,
            matchBrackets: true,
            matchTags: true,
            autoCloseTags: true,
            mode: "application/x-httpd-php",
            theme: 'dracula',
            keyMap: 'sublime',
            viewportMargin: Infinity
        });

    })

})(jQuery);