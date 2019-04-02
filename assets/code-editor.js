; (($) => {

    $(document).ready(() => {

        $('.quicktags-toolbar').hide();

        $('#wp-content-editor-tools').hide();

        $('#post-status-info').hide();

    });

    require(['vs/editor/editor.main'], function() { 
        var editor = monaco.editor.create(e, {
            value: $('.wp-editor-area').val(),
            language: 'php'
        });
    });

})(jQuery);