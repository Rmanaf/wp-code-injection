; (($) => {

    $(document).ready(() => {

        $('.quicktags-toolbar').hide();

        $('#wp-content-editor-tools').hide();

        $('#post-status-info').hide();

    });

    var e = $('.wp-editor-area').first()[0];

    require(['vs/editor/editor.main'], function() { 
        var editor = monaco.editor.create(e, {
            value: $(e).val(),
            language: 'php'
        });
    });

})(jQuery);