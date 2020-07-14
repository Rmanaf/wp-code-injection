/**
 * MIT License <https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE>
 * Copyright (c) 2018 Arman Afzal <rman.afzal@gmail.com>
 */
;(function($) {


    window.ci = {};

    window.ci.ctc = function(element) {

        var text = $(element).text();
        var $temp = $(`<input value="${text}" />`).css({
            'position' : 'absolute',
            'top'   : '-1000px'
        });

        $("body").append($temp);

        $temp.select();
        document.execCommand("copy");
        $temp.remove();
        
    }

})(jQuery);
