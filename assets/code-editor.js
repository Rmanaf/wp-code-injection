; (() => {
    "user strict"

    var editor = null;
    var parent = null;

    function ready(fn) {
        if (document.readyState != 'loading') {
            fn();
        } else if (document.addEventListener) {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            document.attachEvent('onreadystatechange', function () {
                if (document.readyState != 'loading')
                    fn();
            });
        }
    }

    function hide(el) {
        el.forEach(element => {
            document.querySelector(element).style.display = 'none';
        });
    }

    function init() {

        parent = document.getElementById('postdivrich');

        var textarea = document.querySelector('.wp-editor-area');

        var toolbar = document.createElement('DIV');
        var container = document.createElement('DIV');
        var fullscreen = document.createElement('BUTTUN');

        addClass(toolbar , 'dcp-ci-toolbar');
        addClass(container ,'dcp-ci-editor' );
        addClass(fullscreen , 'full-screen');

        parent.insertBefore(container, parent.firstChild);
        parent.insertBefore(toolbar, parent.firstChild);
        toolbar.appendChild(fullscreen);

        hide(['.quicktags-toolbar', '#wp-content-editor-tools', '#post-status-info', '.wp-editor-area', '#wp-content-wrap']);

        require(['vs/editor/editor.main'], () => {
            editor = monaco.editor.create(container, {
                value: textarea.textContent,
                theme: 'vs-dark',
                language: 'php'
            });
        });

        toolbar.onclick = toggleFullScreen;

        window.onresize = () => {
            if (editor) {
                editor.layout();
            }
        }

    }

    function toggleFullScreen() {
        if(hasClass(parent , 'fullscreen')){
            removeClass(parent , 'fullscreen');
        }else{
            addClass(parent , 'fullscreen');
        }
        editor.layout();
    }

    function hasClass(el, className) {
        if (el.classList) {
            el.classList.contains(className);
        } else {
            new RegExp('(^| )' + className + '( |$)', 'gi').test(el.className);
        }
    }

    function removeClass(el, className) {
        if (el.classList) {
            el.classList.remove(className);
        } else {
            el.className = el.className.replace(new RegExp('(^|\\b)' + className.split(' ').join('|') + '(\\b|$)', 'gi'), ' ');
        }
    }

    function addClass(el, className) {
        if (el.classList) {
            el.classList.add(className);
        } else {
            el.className += ' ' + className;
        }
    }

    ready(init);


})();