<?php

namespace UbkCookieConsent;

/**
 * Class CookieConsentSettings
 *
 * @package UbkCookieConsent\Model
 * @author  kamil.pesek
 * Date: 11. 1. 2022
 */
class CookieConsentSettings
{

    public const COOKIE_CATEGORY_MARKETING = 'marketing';
    public const COOKIE_CATEGORY_ANALYTICS = 'analytics';
    public const COOKIE_CONSENT_SETTINGS_KEY = 'ubk-cookie-consent-settings';

    /**
     * @var array
     */
    private array $savedSettings;

    /**
     * @var array
     */
    private array $availableLanguages;

    public function __construct()
    {
        add_action('admin_enqueue_scripts', static function () {
            wp_enqueue_style('ubk-cookie-consent-admin', UBK_COOKIE_CONSENT_URL . 'assets/dist/ubk_cookie_consent_admin.min.css', [], 1.0);
        });

        add_filter('plugin_action_links_ubk-cookie-consent/ubk-cookie-consent.php', static function ($links) {
            $links[] = '<a href="' . admin_url('options-general.php?page=ubk-cookie-consent-settings') . '">' . __('Settings') . '</a>';
            return $links;
        });

        add_action('admin_menu', function () {
            $tab = sanitize_text_field($_GET['tab'] ?? 'texts');
            $optionsCapability = 'manage_options';
            $optionsCapability = apply_filters('ubk_cookie_consent/options_capability', $optionsCapability);

            add_options_page(__('Cookie consent', 'ubk-cookie-consent'), __('Cookie consent', 'ubk-cookie-consent'), $optionsCapability, 'ubk-cookie-consent-settings', function () use ($tab) {
                ?>
                <nav class="nav-tab-wrapper">
                    <a href="<?php echo admin_url('options-general.php?page=ubk-cookie-consent-settings'); ?>&amp;tab=texts"
                       class="nav-tab <?php if ($tab === 'texts'): ?>nav-tab-active<?php endif; ?>"><?php _e('Texts', 'ubk-cookie-consent'); ?>
                    </a>
                    <a href="<?php echo admin_url('options-general.php?page=ubk-cookie-consent-settings'); ?>&amp;tab=settings"
                       class="nav-tab <?php if ($tab === 'settings'): ?>nav-tab-active<?php endif; ?>"><?php _e('Settings', 'ubk-cookie-consent'); ?>
                    </a>
                    <a href="<?php echo admin_url('options-general.php?page=ubk-cookie-consent-settings'); ?>&amp;tab=statistics"
                       class="nav-tab <?php if ($tab === 'statistics'): ?>nav-tab-active<?php endif; ?>"><?php _e('Statistics', 'ubk-cookie-consent'); ?>
                    </a>
                </nav>
                <div class="wrap">
                    <?php
                    if ('texts' === $tab) {
                        $this->tabTexts();
                    }
                    if ('settings' === $tab) {
                        $this->tabSettings();
                    }

                    if ('statistics' === $tab) {
                        $this->tabStatistics();
                    }
                    ?>
                </div>
                <?php
                //todo design, domain ...
            });
        });
    }

    /**
     * @param string $name
     * @param string $label
     * @param string $type
     *
     * @return void
     */
    private function printFieldRow(string $name, string $label, string $type = 'text'): void
    {
        foreach ($this->availableLanguages as $language):
            $fieldName = $language . $name;
            $id = str_replace([']', '['], ['', '-'], $fieldName);

            //get array indexes from field name - [consent_modal][title] => consent_modal::title
            $settingsIndexParts = explode('::', str_replace(['][', '[', ']'], ['::', '', ''], $name));
            $fieldValue = $this->savedSettings[$language] ?? null;
            foreach ($settingsIndexParts as $settingsIndexPart) {
                $fieldValue = $fieldValue[$settingsIndexPart] ?? null;
            }

            if (!is_array($fieldValue)) {
                $fieldValue = htmlspecialchars($fieldValue);
            }
            ?>
            <tr>
                <th scope="row">
                    <label for="<?php echo esc_attr($id) ?>"><?php echo esc_html($label) ?> - <?php echo esc_html($language) ?></label>
                </th>
                <td>
                    <?php if ('text' === $type): ?>
                        <input type="text" value="<?php echo esc_attr($fieldValue) ?>" class="regular-text" name="<?php echo esc_attr($fieldName) ?>" id="<?php echo esc_attr($id) ?>">
                    <?php elseif ('textarea' === $type): ?>
                        <textarea rows="5" style="max-width: 700px;width: 100%;" name="<?php echo esc_attr($fieldName) ?>" id="<?php echo esc_attr($id) ?>"><?php echo esc_html($fieldValue) ?></textarea>
                    <?php else: ?>
                        <?php $fieldValue = $fieldValue ?: [['col1' => '', 'col2' => '', 'col3' => '', 'col4' => '']]; ?>
                        <button type="button" onclick="addCookieTableRow(this)" style="margin-bottom: 10px;"><?php _e('Add row', 'ubk-cookie-consent') ?></button>
                        <table class="field-collection">
                            <tr>
                                <th><?php _e('Name', 'ubk-cookie-consent'); ?></th>
                                <th><?php _e('Domain', 'ubk-cookie-consent'); ?></th>
                                <th><?php _e('Expiration', 'ubk-cookie-consent'); ?></th>
                                <th><?php _e('Description', 'ubk-cookie-consent'); ?></th>
                                <th></th>
                            </tr>
                            <?php foreach ($fieldValue as $rowIdx => $rowValues):
                                $rowValues = array_map('htmlspecialchars', $rowValues);
                                ?>
                                <tr>
                                    <td>
                                        <input type="text" value="<?php echo esc_attr($rowValues['col1']) ?>" name="<?php echo esc_attr($fieldName) ?>[<?php echo esc_attr($rowIdx); ?>][col1]"
                                               id="<?php echo esc_attr($id) ?>">
                                    </td>
                                    <td>
                                        <input type="text" value="<?php echo esc_attr($rowValues['col2']) ?>" name="<?php echo esc_attr($fieldName) ?>[<?php echo esc_attr($rowIdx); ?>][col2]" aria-label="false">
                                    </td>
                                    <td>
                                        <input type="text" value="<?php echo esc_attr($rowValues['col3']) ?>" name="<?php echo esc_attr($fieldName) ?>[<?php echo esc_attr($rowIdx); ?>][col3]" aria-label="false">
                                    </td>
                                    <td style="padding-right: 15px;">
                                        <textarea class="" name="<?php echo esc_attr($fieldName) ?>[<?php echo esc_attr($rowIdx); ?>][col4]" rows="3"
                                                  aria-label="false"><?php echo esc_html($rowValues['col4']) ?></textarea>
                                    </td>
                                    <td>
                                        <button type="button" onclick="removeCookieTableRow(this)"><?php _e('Remove row', 'ubk-cookie-consent') ?></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php
    }

    private function tabTexts(): void
    {
        $this->savedSettings = $this->getConsentSettings();
        $this->handleTextsForm();
        ?>
        <script>
            function addCookieTableRow(button) {
                let table = button.closest('td').querySelector('table');
                let firstDiv = table.querySelector('tr:nth-child(2)');
                let clonedDiv = firstDiv.cloneNode(true);
                clonedDiv.querySelectorAll('input, textarea').forEach(element => element.value = '');

                let lastDiv = table.querySelector('tr:last-child');
                lastDiv.after(clonedDiv);

                reindexCookieTableRows(table);
            }

            function removeCookieTableRow(button) {
                let rows = button.closest('table').querySelectorAll('tr');
                if (rows.length > 2) { // +1 for header
                    let table = button.closest('table');
                    button.closest('tr').remove();
                    reindexCookieTableRows(table);
                }
            }

            function reindexCookieTableRows(table) {
                // reindex fields
                table.querySelectorAll('tr').forEach(function (element, index) {
                    element.querySelectorAll('input, textarea').forEach(function (input) {
                        let nameParts = input.name.split('][')
                        let rowIndex = nameParts[nameParts.length - 2];
                        input.name = input.name.replace('[' + rowIndex + ']', '[' + (index - 1) + ']')
                    });
                });
            }
        </script>
        <form action="" method="POST">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="status"><?php _e('Status', 'ubk-cookie-consent') ?></label>
                    </th>
                    <td>
                        <select name="status" id="status">
                            <option value="disabled" <?php if (($this->savedSettings['status'] ?? '') === 'disabled'): ?>selected<?php endif; ?>>
                                <?php _e('Disabled', 'ubk-cookie-consent') ?>
                            </option>
                            <option value="enabled" <?php if (($this->savedSettings['status'] ?? '') === 'enabled'): ?>selected<?php endif; ?>>
                                <?php _e('Enabled', 'ubk-cookie-consent') ?>
                            </option>
                            <option value="only_admins" <?php if (($this->savedSettings['status'] ?? '') === 'only_admins'): ?>selected<?php endif; ?>>
                                <?php _e('Show only for admins', 'ubk-cookie-consent') ?>
                            </option>
                        </select>
                    </td>
                </tr>
            </table>
            <hr>
            <h2><?php _e('Consent modal', 'ubk-cookie-consent'); ?></h2>
            <table class="form-table">
                <?php $this->printFieldRow('[consent_modal][title]', __('Header', 'ubk-cookie-consent')) ?>
                <?php $this->printFieldRow('[consent_modal][description]', __('Description', 'ubk-cookie-consent'), 'textarea'); ?>
                <?php $this->printFieldRow('[consent_modal][primary_btn][text]', __('Accept all button', 'ubk-cookie-consent')); ?>
                <?php $this->printFieldRow('[consent_modal][secondary_btn][text]', __('Reject all button', 'ubk-cookie-consent')); ?>
            </table>
            <hr>
            <h2><?php _e('Settings modal', 'ubk-cookie-consent'); ?></h2>
            <table class="form-table">
                <?php $this->printFieldRow('[settings_modal][title]', __('Header', 'ubk-cookie-consent')); ?>
                <?php $this->printFieldRow('[settings_modal][save_settings_btn]', __('Save settings button', 'ubk-cookie-consent')); ?>
                <?php $this->printFieldRow('[settings_modal][accept_all_btn]', __('Accept all button', 'ubk-cookie-consent')); ?>
                <?php $this->printFieldRow('[settings_modal][reject_all_btn]', __('Reject all button', 'ubk-cookie-consent')); ?>
                <?php $this->printFieldRow('[settings_modal][close_btn_label]', __('Close button', 'ubk-cookie-consent')); ?>

                <?php $this->printFieldHeader(__('Info block', 'ubk-cookie-consent')); ?>
                <?php $this->printFieldRow('[settings_modal][blocks][0][title]', __('Header', 'ubk-cookie-consent')); ?>
                <?php $this->printFieldRow('[settings_modal][blocks][0][description]', __('Description', 'ubk-cookie-consent'), 'textarea'); ?>

                <?php $this->printFieldHeader(__('Necessary cookies', 'ubk-cookie-consent')); ?>
                <?php $this->printFieldRow('[settings_modal][blocks][1][title]', __('Header', 'ubk-cookie-consent')); ?>
                <?php $this->printFieldRow('[settings_modal][blocks][1][description]', __('Description', 'ubk-cookie-consent'), 'textarea'); ?>
                <?php $this->printFieldRow('[settings_modal][blocks][1][cookie_table]', __('Cookie table', 'ubk-cookie-consent'), 'cookietable'); ?>

                <?php $this->printFieldHeader(__('Analytics cookies', 'ubk-cookie-consent')); ?>
                <?php $this->printFieldRow('[settings_modal][blocks][2][title]', __('Header', 'ubk-cookie-consent')); ?>
                <?php $this->printFieldRow('[settings_modal][blocks][2][description]', __('Description', 'ubk-cookie-consent'), 'textarea'); ?>
                <?php $this->printFieldRow('[settings_modal][blocks][2][cookie_table]', __('Cookie table', 'ubk-cookie-consent'), 'cookietable'); ?>

                <?php $this->printFieldHeader(__('Marketing cookies', 'ubk-cookie-consent')); ?>
                <?php $this->printFieldRow('[settings_modal][blocks][3][title]', __('Header', 'ubk-cookie-consent')); ?>
                <?php $this->printFieldRow('[settings_modal][blocks][3][description]', __('Description', 'ubk-cookie-consent'), 'textarea'); ?>
                <?php $this->printFieldRow('[settings_modal][blocks][3][cookie_table]', __('Cookie table', 'ubk-cookie-consent'), 'cookietable'); ?>
            </table>
            <hr>
            <h2><?php _e('Iframe placeholder', 'ubk-cookie-consent'); ?></h2>
            <table class="form-table">
                <?php $this->printFieldRow('[iframe][placeholder_button]', __('Placeholder', 'ubk-cookie-consent')); ?>
            </table>
            <input type="submit" name="save-settings" class="button button-primary" value="<?php _e('Save', 'ubk-cookie-consent') ?>">
        </form>
        <?php
    }

    /**
     * @return void
     */
    private function tabSettings(): void
    {
        $this->handleJavascriptsForm();
        ?>
        <form action="" method="POST">
            <h2>
                <label for="app-scripts"><?php _e('App javascripts', 'ubk-cookie-consent'); ?></label>
            </h2>
            <pre style="background: #f0f0f1; background: rgba(0,0,0,.07);"><code
                        style="background: transparent;white-space: pre-line;">&lt;script type="text/script-template" data-cookiecategory="<?php echo self::COOKIE_CATEGORY_ANALYTICS; ?>"&gt;
                    ...
                    &lt;/script&gt;

                    &lt;script type="text/script-template" data-cookiecategory="<?php echo self::COOKIE_CATEGORY_MARKETING; ?>"&gt;
                    ...
                    &lt;/script&gt;</code></pre>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="gtm-id"><?php _e('Google Tag Manager ID', 'ubk-cookie-consent') ?></label>
                    </th>
                    <td>
                        GTM-<input type="text" name="gtm_id" id="gtm-id" value="<?php echo esc_attr(get_option('ubk-cookie-consent-gtm-id')); ?>">
                        <br>
                        <small>
                            <?php _e('Google Tag Manager will be added automatically with default analytics_storage=denied & ad_storage=denied ', 'ubk-cookie-consent') ?>
                        </small>
                    </td>
                </tr>
            </table>
            <input type="submit" name="save-settings" class="button button-primary" value="<?php _e('Save', 'ubk-cookie-consent') ?>">
        </form>
        <?php
    }

    /**
     * @return void
     */
    private function tabStatistics(): void
    {
        global $wpdb;
        $consents = $wpdb->get_results('SELECT level FROM ' . CookieConsent::getTableName());
        $countsByCategory = [];
        $totalConsents = count($consents);
        foreach ($consents as $consent) {
            foreach (json_decode($consent->level, true) as $level) {
                $countsByCategory[$level] = isset($countsByCategory[$level]) ? $countsByCategory[$level] + 1 : 1;
            }
        }

        if (0 === count($countsByCategory)) {
            ?><?php _e('No data', 'ubk-cookie-consent'); ?><?php
        }

        foreach ($countsByCategory as $categoryName => $consentsCount) {
            $percents = round(($consentsCount / $totalConsents) * 100);
            $label = __('Necessary', 'ubk-cookie-consent');
            if ('analytics' === $categoryName) {
                $label = __('Analytics', 'ubk-cookie-consent');
            } elseif ('marketing' === $categoryName) {
                $label = __('Marketing', 'ubk-cookie-consent');
            }
            ?>
            <div style="width:33%; min-width: 200px; float: left; text-align:center;">
                <div class="pie" style="--p:<?php echo esc_html($percents); ?>;"><?php echo esc_html($percents); ?>%</div>
                <h2><?php echo esc_html($label); ?> - <?php echo esc_html($consentsCount); ?>/<?php echo esc_html($totalConsents); ?></h2>
            </div>
            <?php
        }
    }

    /**
     * @param string $text
     *
     * @return void
     */
    private function printFieldHeader(string $text): void
    {
        ?>
        <tr>
            <th colspan="2"><h3><?php echo esc_html($text); ?></h3></th>
        </tr>
        <?php
    }

    /**
     * @return void
     */
    private function handleTextsForm(): void
    {
        if (isset($_POST['save-settings'])) {
            $data = $_POST;
            unset($data['save-settings']);

            $allowedAttributes = [
                'class' => [],
                'id' => [],
                'style' => [],
                'role' => [],
                'aria-live' => [],
                'aria-describedby' => [],
                'aria-details' => [],
                'aria-label' => [],
                'aria-labelledby' => [],
                'aria-hidden' => [],
                'aria-required' => [],
                'aria-invalid' => [],
                'aria-haspopup' => [],
                'disabled' => [],
                'readonly' => [],
                'title' => [],
            ];

            array_walk_recursive($data, static function (&$item) use ($allowedAttributes) {
                $item = stripslashes($item);
                $item = wp_kses($item, [
                    'a' => array_merge(['href' => [], 'data-cc' => []], $allowedAttributes),
                    'button' => array_merge(['type' => [], 'data-cc' => []], $allowedAttributes),
                    'br' => $allowedAttributes,
                    'p' => $allowedAttributes,
                    'em' => $allowedAttributes,
                    'i' => $allowedAttributes,
                    'b' => $allowedAttributes,
                    'strong' => $allowedAttributes,
                    'ol' => $allowedAttributes,
                    'ul' => $allowedAttributes,
                    'li' => $allowedAttributes,
                    'span' => $allowedAttributes,
                ]);
            });

            foreach ($this->availableLanguages as $availableLanguage) {
                $data[$availableLanguage]['consent_modal']['primary_btn']['role'] = 'accept_all';
                $data[$availableLanguage]['consent_modal']['secondary_btn']['role'] = 'accept_necessary';

                $data[$availableLanguage]['settings_modal']['blocks'][1]['toggle'] = [
                    'value' => 'necessary',
                    'enabled' => true,
                    'readonly' => true,
                ];

                $data[$availableLanguage]['settings_modal']['blocks'][2]['toggle'] = [
                    'value' => self::COOKIE_CATEGORY_ANALYTICS,
                    'enabled' => false,
                    'readonly' => false,
                ];

                $data[$availableLanguage]['settings_modal']['blocks'][3]['toggle'] = [
                    'value' => self::COOKIE_CATEGORY_MARKETING,
                    'enabled' => false,
                    'readonly' => false,
                ];
            }

            update_option('ubk-cookie-consent-version', '1.0.0');

            update_option(self::COOKIE_CONSENT_SETTINGS_KEY, $data);
            $this->savedSettings = $this->getConsentSettings();
        }
    }

    /**
     * @return void
     */
    private function handleJavascriptsForm(): void
    {
        if (isset($_POST['save-settings'])) {
            update_option('ubk-cookie-consent-gtm-id', sanitize_text_field($_POST['gtm_id']));
        }
    }

    /**
     * @param bool $prepareForFrontend
     *
     * @return array
     */
    public function getConsentSettings(bool $prepareForFrontend = false): array
    {
        $this->availableLanguages = [mb_substr(get_locale(), 0, 2)];
        if (function_exists('pll_languages_list')) {
            $this->availableLanguages = pll_languages_list();
        }

        $savedSettings = [];
        if (get_option(self::COOKIE_CONSENT_SETTINGS_KEY)) {
            $savedSettings = get_option(self::COOKIE_CONSENT_SETTINGS_KEY);
        }

        if ($prepareForFrontend && $savedSettings) {
            foreach ($this->availableLanguages as $availableLanguage) {
                $savedSettings[$availableLanguage]['settings_modal']['cookie_table_headers'] = [
                    ['col1' => __('Name', 'ubk-cookie-consent'),],
                    ['col2' => __('Domain', 'ubk-cookie-consent'),],
                    ['col3' => __('Expiration', 'ubk-cookie-consent'),],
                    ['col4' => __('Description', 'ubk-cookie-consent'),],
                ];

                /* remove empty cookies tables */
                foreach ($savedSettings[$availableLanguage]['settings_modal']['blocks'] as $blockIdx => $block) {
                    if (isset($block['cookie_table'])) {
                        $filledRowExists = false;
                        foreach ($block['cookie_table'] as $rows) {
                            if (!empty(array_filter($rows))) {
                                $filledRowExists = true;
                                break;
                            }
                        }
                        if (!$filledRowExists) {
                            unset($savedSettings[$availableLanguage]['settings_modal']['blocks'][$blockIdx]['cookie_table']);
                        }
                    }
                }
            }

            if (!isset($savedSettings['status']) ||
                'disabled' === $savedSettings['status'] ||
                ('only_admins' === $savedSettings['status'] && !current_user_can('administrator'))) {
                $savedSettings = [];
            }
        }

        return $savedSettings;
    }


}
