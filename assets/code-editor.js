; (($) => {
    "user strict"
    var parent, editor, textarea, toolbar, fullscreen, code;
    $(document).ready(() => {

        

        // fix jquery ui conflict
        $('body').removeClass('wp-core-ui');

        $('.postbox-container').each((i,e) => {
            $(e).addClass("wp-core-ui");
        })

        // hide unneeded elements
        $('.quicktags-toolbar').hide();
        $('.wp-editor-area').hide();
        $('#wp-content-editor-tools').hide();
        $('#wp-content-wrap').hide();

        $('#post-status-info').remove();

        // create new elements
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

        // store initial value
        code = textarea.text();

        require([ 'vs/editor/editor.main' ], () => {

            // create editor
            editor = monaco.editor.create(container[0], {
                value: textarea.text(),
                theme: 'vs-dark',
                language: 'php'
            });

            // update code
            editor.getModel().onDidChangeContent((event) => {
                textarea.text(editor.getModel().getValue());
            });

            editor.addCommand(monaco.KeyMod.CtrlCmd | monaco.KeyCode.KEY_S, () => {

                // prevent submission if the code has no changes
                if(textarea.text() === code)
                {
                    return;
                }

                window.onunload = window.onbeforeunload = null;

                document.forms.post.submit();

            });

            editor.addCommand(monaco.KeyMod.Alt | monaco.KeyMod.Shift | monaco.KeyCode.KEY_F, () => {
                
                // TODO: add auto format
                
            });

        });

        $(window).on( 'resize' , () => {
            if (editor) {
                editor.layout();
            }
        });

    });

})(jQuery);