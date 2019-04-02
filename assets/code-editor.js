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
            document.querySelector(element).style.display = 'none!important';
        });
    }

    function init(){

        console.log('init');

        var textarea = document.querySelector('.wp-editor-area');

        var parent = document.getElementById('postdivrich');

        var container = document.createElement('DIV');

        parent.insertBefore(container, parent.firstChild);

        hide(['.quicktags-toolbar','#wp-content-editor-tools','#post-status-info']);

        require(['vs/editor/editor.main'], function() { 
            var editor = monaco.editor.create(container , {
                language: 'php'
            });
        });

    }

    ready(init);

})();