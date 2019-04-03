; (($) => {
    "user strict"

    var parent, editor, textarea, toolbar, fullscreen;

    $(document).ready(() => {

        $('.quicktags-toolbar').hide();
        $('.wp-editor-area').hide();
        $('#wp-content-editor-tools').hide();
        $('#wp-content-wrap').hide();

        $('#post-status-info').remove();

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


        require([
            'vs/editor/editor.main.nls' , 
            'vs/editor/editor.main'
        ], () => {
            editor = monaco.editor.create(container[0], {
                value: textarea.text(),
                theme: 'vs-dark',
                language: 'php'
            });
        });

        require([
            'js/jquery.hotkeys'
        ] , () => {
            $(document).bind('ctrl+f' , () => {
                console.log("formatting");
                editor.getAction('editor.action.format').run();
            });
        });

        $(window).on( 'resize' , () => {
            if (editor) {
                editor.layout();
            }
        });

    });

})(jQuery);