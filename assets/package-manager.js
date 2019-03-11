;(($) => {
    $(document).ready(() => {
        $("pkg_add").click(() => {
            uri = $("pkg_add").val();
            type = $("pkg_type").val();
            type_name = type == 0 ? "style" : "script";

            $("pkgs_container").append(`
                <li>
                    <span class="pkg-${type_name}">
                        ${uri}
                        <i>x</i> 
                    </span>
                </li>
            `);
        });
    });
})(jQuery);