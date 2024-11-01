=== ubk-cookie-consent ===
Contributors: kamilpesekubkcz
Requires at least: 5.3
Tested up to: 5.8
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Stable tag: 1.0.0

This plugin implements orestbida/cookieconsent solution and stores consents in custom database table. Google Tag Manager consent mode is supported by default.

== Description ==

# Filters/hooks

Set for which Wordpress role cookie settings will be available
`
add_filter('ubk_cookie_consent/options_capability', static function ($optionsCapability) {
    return 'custom_capability';
});
`

Adjust options passed to Javascript. See [https://github.com/orestbida/cookieconsent/#all-configuration-options](https://github.com/orestbida/cookieconsent/#all-configuration-options/)
`
add_filter('ubk_cookie_consent/javascript_options', static function ($cookieConsentOptions) {
    $cookieConsentOptions['cookie_domain'] = '.example.com';
    return $cookieConsentOptions;
});
`

Disable autorun
`
add_filter('ubk_cookie_consent/autorun', static function ($autorun) {
    return false;
});


// call ubk_show_cookie_consent() when needed
if (function_exists('ubk_show_cookie_consent')) {
    ubk_show_cookie_consent();
}
`

---

# Actions

Add custom/service Javascripts to page and control them by cookie consent
`
add_action('ubk_cookie_consent/add_app_scripts', static function () {
    if (wp_get_environment_type() === 'production') {
        ?>
        <script>
            ...
        </script>

        <script type="text/script-template" data-cookiecategory="analytics">
            ...
        </script>

        <script type="text/script-template" data-cookiecategory="marketing">
            ...
        </script>
        <?php
    }
});
`
---
# iframes

Change `<iframe>` tag to `<div>` and prefix attributes with `data-`
- data-iframe-cookie-needed - cookie category needed for iframe
- data-iframe-placeholder-url - url to placeholder image which will be used instead for iframe content

Example:
`
<div
   title="Google Map"
   data-iframe-cookie-needed="analytics"
   data-iframe-placeholder-url="https://www.example.com/map_placeholder.png"
   style="width:100%;min-height:400px"
   data-frameborder="0"
   data-src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d82507.32058738587!2d13.30188385000431!3d49.74178702400929!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x470af1e5133d11b7%3A0x31b9406e3fc10b83!2sPilsen!5e0!3m2!1sen!2scz!4v1641891569357!5m2!1sen!2scz"
   >
</div>
`

== Installation ==
1. Go to settings -> Cookie consent
2. Fill all texts and cookies table for defined languages (Polylang plugin is supported)
3. Set Google Tag Manager ID under *Settings* tab

== Changelog ==
1.0.0 GTM consent mode supported