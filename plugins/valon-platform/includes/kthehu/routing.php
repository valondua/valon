<?php
/** Keep the Albanian initiative at its announced root URL with Polylang active. */
defined("ABSPATH") || exit();
function vp_kt_is_route()
{
    $path = rtrim(
        (string) wp_parse_url($_SERVER["REQUEST_URI"] ?? "", PHP_URL_PATH),
        "/",
    );
    return in_array($path, ["/kthehu", "/sq/kthehu"], true) &&
        (int) get_option("vp_kt_page_id") > 0;
}
add_filter("pll_get_current_language", function ($language) {
    if (vp_kt_is_route() && function_exists("PLL")) {
        return PLL()->model->get_language("sq") ?: $language;
    }
    return $language;
});
add_filter(
    "_get_page_link",
    function ($url, $id) {
        if (
            (int) $id === (int) get_option("vp_kt_page_id") &&
            get_page_template_slug($id) === "page-kthehu.php" &&
            !str_contains($url, "preview=true")
        ) {
            return home_url("/kthehu/");
        }
        return $url;
    },
    99,
    2,
);
add_filter("pll_check_canonical_url", function ($url) {
    if (
        vp_kt_is_route() &&
        is_page((int) get_option("vp_kt_page_id")) &&
        !is_preview()
    ) {
        return home_url("/kthehu/");
    }
    return $url;
});
