/**
 * Licensed under MIT (https://github.com/Rmanaf/wp-code-injection/blob/master/LICENSE)
 * Copyright (c) 2018 Arman Afzal (https://rmanaf.com)
 */
; (function ($) {


    window.ci = {};

    window.ci.ctc = function (element) {

        var text = $(element).text();
        var $temp = $(`<input value="${text}" />`).css({
            'position': 'absolute',
            'top': '-1000px'
        });

        $("body").append($temp);

        $temp.select();
        document.execCommand("copy");
        $temp.remove();

    }

    window.ci.gcs = function (id, target) {
        $.get(_ci.ajax_url, {
            action: "code_stats",
            id: id
        }, function (result) {
            target.parent().html(result);
        }).fail(function () {
            console.error("Faild");
        });
    }


    $(document).ready(function () {

        $(".ci-codes__chart-placeholder").each(function (i, e) {
            var target = $(e);
            var id = $(e).attr("data-post");
            window.ci.gcs(id, target);
        });

    });



})(jQuery);
