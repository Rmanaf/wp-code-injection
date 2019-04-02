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
            document.querySelector(el).style.display = 'none';
        });
    }

    function init(){

        hide(['.quicktags-toolbar','#wp-content-editor-tools','#post-status-info']);

        require(['vs/editor/editor.main'], function() { 
            var editor = monaco.editor.create(document.querySelector('.wp-editor-area') , {
                language: 'php'
            });
        });

    }
    

    ready(init);

})();