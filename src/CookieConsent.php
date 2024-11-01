<?php

namespace UbkCookieConsent;

use DateTime;

/**
 * Class Init
 *
 * @package UbkCookieConsent\Model
 * @author  kamil.pesek
 * Date: 6. 9. 2021
 */
class CookieConsent
{
    private array $languagesSettings = [];

    public function __construct()
    {
        add_action('plugins_loaded', [$this, 'checkInstallUpgrade']);

        // Register Languages
        add_action('init', function () {
            load_plugin_textdomain('ubk-cookie-consent', false, UBK_COOKIE_CONSENT_BASENAME . '/languages');

            $cookieConsentSettings = new CookieConsentSettings();
            $this->languagesSettings = $cookieConsentSettings->getConsentSettings(true);
        });

        add_action('wp_enqueue_scripts', function () {
            if ($this->languagesSettings) {
                wp_enqueue_script('orestbida-cookie-consent-js', UBK_COOKIE_CONSENT_URL . 'assets/dist/lib/cookieconsent.js', [], 1.0, true);
                wp_enqueue_script('ubk-cookie-consent', UBK_COOKIE_CONSENT_URL . 'assets/dist/ubk_cookie_consent.min.js', [], 1.0, false);
                wp_enqueue_style('ubk-cookie-consent', UBK_COOKIE_CONSENT_URL . 'assets/dist/ubk_cookie_consent.min.css', [], 1.0);

                $inlineJs = $this->initConsentJavascript($this->languagesSettings);
                wp_add_inline_script('ubk-cookie-consent', $inlineJs, 'before');
            }
        });

        add_action('wp_head', function () {
            if ($this->languagesSettings) {
                do_action('ubk_cookie_consent/add_app_scripts');
            }
        });

        // make it deferred
        add_filter('script_loader_tag', static function ($tag, $handle) {
            if ($handle === 'orestbida-cookie-consent-js') {
                $tag = str_replace('></script>', ' defer></script>', $tag);
            }

            return $tag;
        }, 11, 2);

        add_action('wp_ajax_nopriv_cookies-accepted', [$this, 'cookiesAcceptedAction']);
        add_action('wp_ajax_cookies-accepted', [$this, 'cookiesAcceptedAction']);
    }


    /**
     * Cookie has been accepted
     */
    public function cookiesAcceptedAction(): void
    {
        $actionName = 'ajax-cookies-accepted';
        $nonceName = $actionName . '-nonce';

        if (!isset($_POST[$nonceName]) || !wp_verify_nonce($_POST[$nonceName], $actionName)) {
            wp_send_json(['msg' => __('Nonce is not valid')], 403);
            wp_die();
        }

        $ip = $_SERVER['HTTP_CLIENT_IP'] ?? ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR']);
        $userAgent = $_SERVER['HTTP_USER_AGENT'];

        $cookieData = json_decode(stripslashes(sanitize_text_field($_POST['cookie-data'])), true);
        $cookieData['ip'] = $ip;
        $cookieData['user_agent'] = $userAgent;

        $level = $cookieData['level'];
        $cookieExpiresInDays = (int) $cookieData['cookie_expires_in_days'];

        unset($cookieData['level'], $cookieData['cookie_expires_in_days']);

        global $wpdb;
        $wpdb->insert(self::getTableName(), [
            'level' => json_encode($level),
            'timestamp' => (new DateTime('now'))->format('Y-m-d_H:i:s'),
            'cookie_expires_in_days' => $cookieExpiresInDays,
            'data' => serialize($cookieData),
        ], ['%s', '%s', '%d', '%s']);

        wp_send_json([
            'msg' => __('ok'),
        ], 200);
        wp_die();
    }

    /**
     * Init consent JS
     *
     * @param array $languagesSettings
     *
     * @return string
     */
    private function initConsentJavascript(array $languagesSettings): string
    {
        $cookieConsentOptions = [
            'autorun' => apply_filters('ubk_cookie_consent/autorun', true),
            'autoclear_cookies' => true,
            'theme_css' => UBK_COOKIE_CONSENT_URL . 'assets/dist/lib/cookieconsent.css',
            'page_scripts' => true,
            'cookie_expiration' => 182,
            'current_lang' => get_bloginfo('language'),
            'gui_options' => [
                'consent_modal' => [
                    'layout' => 'cloud',               // box/cloud/bar
                    'position' => 'bottom center',     // bottom/middle/top + left/right/center
                    'transition' => 'zoom'            // zoom/slide
                ],
                'settings_modal' => [
                    'layout' => 'box',                 // box/bar
                    'transition' => 'zoom'            // zoom/slide
                ],
            ],
            'languages' => $languagesSettings,
        ];

        $cookieConsentOptions = apply_filters('ubk_cookie_consent/javascript_options', $cookieConsentOptions);

        $inlineJs = "const cookieConsentOptions = " . json_encode($cookieConsentOptions) . ";";
        $inlineJs .= "const cookieCategoryAnalytics = '" . CookieConsentSettings::COOKIE_CATEGORY_ANALYTICS . "';";
        $inlineJs .= "const cookieCategoryMarketing = '" . CookieConsentSettings::COOKIE_CATEGORY_MARKETING . "';";
        $inlineJs .= "const cookiesAcceptedNonce = '" . wp_create_nonce('ajax-cookies-accepted') . "';";
        $inlineJs .= "const adminUrl = '" . esc_url(admin_url('admin-ajax.php')) . "';";
        $inlineJs .= "const placeholderButtonText = '" . sanitize_text_field($languagesSettings[substr(get_bloginfo('language'), 0, 2)]['iframe']['placeholder_button']) . "';";
        $inlineJs .= "const cookieName = 'accepted-cookie-consent-id';";
        if (get_option('ubk-cookie-consent-gtm-id')) {
            $inlineJs .= "const gtmId = '" . wp_kses(get_option('ubk-cookie-consent-gtm-id'), []) . "';";
        } else {
            $inlineJs .= "const gtmId = null;";
        }

        return $inlineJs;
    }


    /**
     * Activation hook
     * Must be static!
     */
    public static function activate(): void
    {
        // Check PHP Version and deactivate & die if it doesn't meet minimum requirements.
        if (version_compare(PHP_VERSION, UBK_COOKIE_CONSENT_MIN_PHP, '<')) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(sprintf(__('Plugin requires at least PHP version %s.', 'ubk-cookie-consent'), UBK_COOKIE_CONSENT_MIN_PHP));
        }
    }

    /**
     * Deactivation hook
     * Must be static!
     */
    public static function deactivate(): void
    {
        // empty for now
    }

    /**
     *
     * @return void
     */
    public function checkInstallUpgrade(): void
    {
        if (function_exists('get_sites')) {
            $currentBlogId = get_current_blog_id();
            foreach (get_sites() as $site) {
                switch_to_blog($site->blog_id);
                $this->installUpgrade();
            }

            switch_to_blog($currentBlogId);
        } else {
            $this->installUpgrade();
        }
    }

    /**
     * Install/upgrade plugin hook
     *
     * @return void
     */
    private function installUpgrade(): void
    {
        // install v1.0.0
        if (!get_option('ubk-cookie-consent-version')) {
            global $wpdb;
            $charsetCollate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE IF NOT EXISTS " . self::getTableName() . " (
                    id mediumint(9) NOT NULL AUTO_INCREMENT,
                    level varchar(100) NOT NULL,
                    timestamp datetime NOT NULL,
                    cookie_expires_in_days INT(4) UNSIGNED NOT NULL,
                    data text NOT NULL,
                    PRIMARY KEY (id),
                    INDEX (level)
                ) $charsetCollate;";

            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);

            update_option('ubk-cookie-consent-version', '1.0.0');
        }
    }


    /**
     * Get table name
     *
     * @return string
     */
    public static function getTableName(): string
    {
        global $wpdb;
        return $wpdb->prefix . 'ubk_cookie_consent';
    }

}
