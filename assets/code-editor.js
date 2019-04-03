; (($) => {
    "user strict"

    var parent, editor, textarea, toolbar, fullscreen;

    $(document).ready(() => {

        $('.quicktags-toolbar').hide();
        $('.wp-editor-area').hide();
        $('#wp-content-editor-tools').hide();
        $('#post-status-info').remove();
        $('#wp-content-wrap').hide();


        parent = $('#postdivrich');

        textarea = $('.wp-editor-area');

        toolbar = $('<div>')
            .addClass('quicktags-toolbar dcp-ci-toolbar')
            .appendTo(parent);

        container = $('<div>')
            .addClass('dcp-ci-editor')
            .appendTo(parent);
        
        fullscreen = $('<div>')
            .addClass('full-screen ed_button qt-dfw')
            .appendTo(toolbar)
            .click((e)=>{
                e.preventDefault();
                parent.toggleClass('fullscreen');
                editor.layout();
            });


        require(['vs/editor/editor.main'], () => {
            editor = monaco.editor.create(container[0], {
                value: textarea.text(),
                theme: 'vs-dark',
                language: 'php'
            });
        });

        $(window).on( 'resize' , () => {
            if (editor) {
                editor.layout();
            }
        });

    });

})(jQuery);