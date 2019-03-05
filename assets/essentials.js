;(($)=>{

    window.ci = {};

    window.ci.ctc = function(element) {

        var text = $(element).text();
        var $temp = $(`<input type="hidden" value="${text}" />`);

        $("body").append($temp);

        $temp.select();
        document.execCommand("copy");
        //$temp.remove();
        
    }

})(jQuery);
