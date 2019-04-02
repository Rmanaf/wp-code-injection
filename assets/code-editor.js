; (($) => {

    $(document).ready(() => {

        $('.quicktags-toolbar').remove();

        $('#wp-content-editor-tools').remove();

        $('#post-status-info').remove();

        $('.wp-editor-area').each((i, e) => {
            var editor = monaco.editor.create(e, {
                value: $(e).val(),
                language: 'php'
            });
        });

    })

})(jQuery);