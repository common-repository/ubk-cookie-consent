<?php

use UbkCookieConsent\CookieConsent;

/**
 * The plugin bootstrap file
 *
 * Plugin Name: UBK - cookie consent
 * Description: Plugin implements orestbida/cookieconsent cookie consent solution and stores consents in custom database table.
 * Author: UBK s.r.o.
 * Author URI: https://www.ubk.cz/
 * Version: 1.0.0
 * Text Domain: ubk-cookie-consent
 * Domain Path: /languages
 * Requires at least: 5.3
 * Requires PHP: 7.4
 * License: GPLv2
 *
 * @package UBK
 * @author  Kamil Pešek <kamil.pesek@ubk.cz>
 */


// exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Define constants
 */
define('UBK_COOKIE_CONSENT_MIN_PHP', '7.4');
define('UBK_COOKIE_CONSENT_DIR', plugin_dir_path(__FILE__));
define('UBK_COOKIE_CONSENT_URL', plugin_dir_url(__FILE__));
define('UBK_COOKIE_CONSENT_BASENAME', basename(__DIR__));

/**
 * Autoload classes
 */
spl_autoload_register(static function ($class) {
    if (0 === strpos($class, 'UbkCookieConsent')) {
        require(__DIR__ . '/src/' . str_replace('UbkCookieConsent', '', str_replace('\\', '/', $class)) . '.php');
    }
});

/**
 * Register activation & deactivation hooks
 */
register_activation_hook(__FILE__, [CookieConsent::class, 'activate']);
register_deactivation_hook(__FILE__, [CookieConsent::class, 'deactivate']);

/**
 * Check the minimum required PHP version and run the plugin
 */
if (version_compare(PHP_VERSION, UBK_COOKIE_CONSENT_MIN_PHP, '>=')) {
    new CookieConsent();
} else {
    add_action('admin_notices', static function () {
        echo '<div class="notice notice-error is-dismissible"><p>' . sprintf(__('Plugin "UBK s.r.o. - cookie consent" requires at least PHP version %s.'), UBK_COOKIE_CONSENT_MIN_PHP) . '</p></div>';
    });
}

/**
 * Show cookie consent programmatically
 *
 * @return void
 */
function ubk_show_cookie_consent()
{
    ?>
    <script>
        if (typeof orestbidaCookieConsent === 'undefined') {
            window.addEventListener('orestbida-consent-loaded', function () {
                orestbidaCookieConsent.show();
            });
        } else {
            orestbidaCookieConsent.show();
        }
    </script>
    <?php
}
