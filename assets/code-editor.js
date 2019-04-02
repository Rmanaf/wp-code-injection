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

    function hide(...selectors) {
        selectors.forEach(s => {
            document.querySelector(s).style.display = 'none';
        });
    }

    function init() {

        parent = document.getElementById('postdivrich');
        
        hide('.quicktags-toolbar', '#wp-content-editor-tools', '#post-status-info', '.wp-editor-area', '#wp-content-wrap');

        var textarea = document.querySelector('.wp-editor-area');

        var toolbar = createElement('div', 'quicktags-toolbar' , 'dcp-ci-toolbar');
        var container = createElement('div', 'dcp-ci-editor');
        var fullscreen = createElement('button', 'full-screen','ed_button', 'qt-dfw');

        parent.insertBefore(container, parent.firstChild);
        parent.insertBefore(toolbar, parent.firstChild);
        toolbar.appendChild(fullscreen);

      
        require(['vs/editor/editor.main'], () => {
            editor = monaco.editor.create(container, {
                value: textarea.textContent,
                theme: 'vs-dark',
                language: 'php'
            });
        });

        fullscreen.onclick = toggleFullScreen;

        window.onresize = () => {
            if (editor) {
                editor.layout();
            }
        }

    }

    function createElement(t, ...className) {
        var res = document.createElement(t);
        addClass(res, ...className);
        return res;
    }

    function toggleFullScreen(e) {
        
        e.preventDefault();

        var fs = 'fullscreen';

        if (hasClass(parent , fs)) {
            removeClass(parent , fs);
        } else {
            addClass(parent ,fs);
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
        console.log(className);
        if (el.classList) {
            el.classList.remove(className);
        } else {
            el.className = el.className.replace(new RegExp('(^|\\b)' + className.split(' ').join('|') + '(\\b|$)', 'gi'), ' ');
        }
    }

    function addClass(el, ...className) {
        if (el.classList) {
            className.forEach(c => {
                el.classList.add(c);
            });
        } else {
            className.forEach(c => {
                el.className += ' ' + c;
            });
        }
    }

    ready(init);


})();