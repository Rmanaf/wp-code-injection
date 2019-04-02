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
                lint: true,
                mode: "css",
                theme: 'dracula',
                keyMap: 'sublime',
                viewportMargin: Infinity,
                gutters: ["CodeMirror-lint-markers"]
            })

        });

    })

})(jQuery);