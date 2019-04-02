;(() => {
    "user strict"

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
        var container = document.createElement('DIV');

        container.classList.add('dcp-ci-editor');

        parent.insertBefore(container, parent.firstChild);

        hide(['.quicktags-toolbar','#wp-content-editor-tools','#post-status-info','.wp-editor-area']);

        require(['vs/editor/editor.main'], function() { 
            var editor = monaco.editor.create(container , {
                value: textarea.innerHTML,
                theme: 'vs-dark',
                language: 'php'
            });
        });

    }

    ready(init);

})();