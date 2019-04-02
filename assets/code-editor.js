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

        console.log('init');

        hide(['.quicktags-toolbar','#wp-content-editor-tools','#post-status-info']);

        require(['vs/editor/editor.main'], function() { 
            var editor = monaco.editor.create(document.querySelector('.wp-editor-area') , {
                language: 'php'
            });
        });

    }

    ready(init);

})();