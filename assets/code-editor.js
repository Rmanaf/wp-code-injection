;(() => {
    "user strict"

    var editor = null;

    function ready(fn) {
        if (document.readyState != 'loading'){
          fn();
        } else if (document.addEventListener) {
          document.addEventListener('DOMContentLoaded', fn);
        } else {
          document.attachEvent('onreadystatechange', function() {
            if (document.readyState != 'loading')
              fn();
          });
        }
    }

    function hide(el){
        el.forEach(element => {
            document.querySelector(element).style.display = 'none';
        });
    }

    function init(){

        var textarea = document.querySelector('.wp-editor-area');
        var parent = document.getElementById('postdivrich');
        var toolbar =  document.createElement('DIV');
        var container = document.createElement('DIV');

        toolbar.classList.add('dcp-ci-toolbar');
        container.classList.add('dcp-ci-editor');
        parent.classList.add('fullscreen');

        parent.insertBefore(container, parent.firstChild);
        parent.insertBefore(toolbar , parent.firstChild);

        hide(['.quicktags-toolbar','#wp-content-editor-tools','#post-status-info','.wp-editor-area']);

        require(['vs/editor/editor.main'], () => { 
            editor = monaco.editor.create(container , {
                value: textarea.textContent,
                theme: 'vs-dark',
                language: 'php'
            });
        });

    }

    ready(init);

    window.onresize = () => {
        if(editor){
            editor.layout();
        }
    }

})();