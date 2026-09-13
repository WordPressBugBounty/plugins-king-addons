<?php
/**
 * Shared chrome for King Addons admin screens.
 *
 * The dashboard, settings and wishlist screens each hand-rolled the same
 * header, stylesheet link and theme bootstrap, and the extension screens did
 * none of it - which is why they rendered as bare WordPress tables. Everything
 * goes through here instead.
 *
 * Usage:
 *   require_once KING_ADDONS_PATH . 'includes/admin/shared/page-shell.php';
 *   ka_admin_page_open([...]);
 *   ... cards ...
 *   ka_admin_page_close();
 *
 * @package King_Addons
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('ka_admin_theme_mode')) {
    /**
     * The theme the current user picked for King Addons screens.
     *
     * @return string One of dark, light, auto.
     */
    function ka_admin_theme_mode(): string
    {
        $mode = get_user_meta(get_current_user_id(), 'king_addons_theme_mode', true);

        return in_array($mode, ['dark', 'light', 'auto'], true) ? $mode : 'auto';
    }
}

if (!function_exists('ka_admin_theme_switch')) {
    /**
     * The Light / Dark / Auto control.
     *
     * The Settings screen had this in its own markup, so every other screen was
     * stuck on whatever was chosen there. It lives in the shell now, which puts
     * it on all of them.
     *
     * @param string $mode Current mode.
     *
     * @return void
     */
    function ka_admin_theme_switch(string $mode): void
    {
        $options = [
            'light' => ['☀︎', esc_html__('Light', 'king-addons')],
            'dark' => ['☾', esc_html__('Dark', 'king-addons')],
            'auto' => ['◐', esc_html__('Auto', 'king-addons')],
        ];
        ?>
        <div class="ka-v3-segmented" id="ka-v3-theme-segment" role="radiogroup"
            aria-label="<?php esc_attr_e('Theme', 'king-addons'); ?>"
            data-active="<?php echo esc_attr($mode); ?>"
            data-nonce="<?php echo esc_attr(wp_create_nonce('king_addons_dashboard_ui')); ?>">
            <span class="ka-v3-segmented-indicator" aria-hidden="true"></span>
            <?php foreach ($options as $value => $option) : ?>
                <button type="button" class="ka-v3-segmented-btn" data-theme="<?php echo esc_attr($value); ?>"
                    aria-pressed="<?php echo $mode === $value ? 'true' : 'false'; ?>">
                    <span class="ka-v3-segmented-icon" aria-hidden="true"><?php echo esc_html($option[0]); ?></span>
                    <?php echo esc_html($option[1]); ?>
                </button>
            <?php endforeach; ?>
        </div>

        <script>
        (function () {
            var segment = document.getElementById('ka-v3-theme-segment');
            if (!segment || segment.dataset.kaReady === '1') {
                return;
            }
            segment.dataset.kaReady = '1';

            var mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

            function paint(mode) {
                var isDark = mode === 'auto' ? !!(mql && mql.matches) : mode === 'dark';
                document.body.classList.toggle('ka-v3-dark', isDark);
                document.body.classList.toggle('ka-dark-theme', isDark);
                document.documentElement.classList.toggle('ka-v3-dark', isDark);
            }

            segment.addEventListener('click', function (event) {
                var button = event.target.closest('.ka-v3-segmented-btn');
                if (!button) {
                    return;
                }

                var mode = button.dataset.theme || 'dark';
                segment.setAttribute('data-active', mode);
                segment.querySelectorAll('.ka-v3-segmented-btn').forEach(function (btn) {
                    btn.setAttribute('aria-pressed', btn === button ? 'true' : 'false');
                });
                paint(mode);

                var body = new URLSearchParams();
                body.append('action', 'king_addons_save_dashboard_ui');
                body.append('nonce', segment.dataset.nonce || '');
                body.append('key', 'theme_mode');
                body.append('value', mode);

                fetch(<?php echo wp_json_encode(admin_url('admin-ajax.php')); ?>, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                    body: body.toString()
                });
            });

            // "Auto" has to keep following the operating system.
            if (mql && mql.addEventListener) {
                mql.addEventListener('change', function () {
                    if ('auto' === segment.getAttribute('data-active')) {
                        paint('auto');
                    }
                });
            }
        })();
        </script>
        <?php
    }
}

if (!function_exists('ka_admin_page_open')) {
    /**
     * Open a King Addons admin screen.
     *
     * @param array{
     *     title:string,
     *     subtitle?:string,
     *     icon?:string,
     *     icon_color?:string,
     *     saved?:bool,
     *     saved_message?:string,
     *     notices?:array<int,array{type:string,text:string}>,
     *     actions?:string
     * } $args Screen options.
     *
     * @return void
     */
    function ka_admin_page_open(array $args): void
    {
        $args = wp_parse_args($args, [
            'title' => '',
            'subtitle' => '',
            'icon' => 'dashicons-admin-generic',
            'icon_color' => 'purple',
            'saved' => false,
            'saved_message' => esc_html__('Settings saved.', 'king-addons'),
            'notices' => [],
            'actions' => '',
        ]);

        $mode = ka_admin_theme_mode();
        $css_path = KING_ADDONS_PATH . 'includes/admin/layouts/shared/admin-v3-styles.css';
        $version = file_exists($css_path) ? filemtime($css_path) : KING_ADDONS_VERSION;

        wp_enqueue_style(
            'king-addons-admin-v3',
            KING_ADDONS_URL . 'includes/admin/layouts/shared/admin-v3-styles.css',
            [],
            $version
        );

        $colors = ['purple', 'green', 'orange', 'pink', 'red'];
        $icon_color = in_array($args['icon_color'], $colors, true) ? $args['icon_color'] : 'purple';
        ?>
        <script>
        (function () {
            var mode = <?php echo wp_json_encode($mode); ?>;
            var mql = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
            var isDark = mode === 'auto' ? !!(mql && mql.matches) : mode === 'dark';
            function apply() {
                document.body.classList.add('ka-admin-v3');
                document.body.classList.toggle('ka-v3-dark', isDark);
                document.body.classList.toggle('ka-dark-theme', isDark);
                document.documentElement.classList.toggle('ka-v3-dark', isDark);
            }
            if (document.body) {
                apply();
            } else {
                document.addEventListener('DOMContentLoaded', apply, { once: true });
            }
        })();
        </script>

        <div class="ka-admin-wrap">
            <div class="ka-admin-header">
                <div class="ka-admin-header-left">
                    <div class="ka-admin-header-icon <?php echo esc_attr($icon_color); ?>">
                        <span class="dashicons <?php echo esc_attr($args['icon']); ?>"></span>
                    </div>
                    <div>
                        <h1 class="ka-admin-title"><?php echo esc_html($args['title']); ?></h1>
                        <?php if ('' !== $args['subtitle']) : ?>
                            <p class="ka-admin-subtitle"><?php echo esc_html($args['subtitle']); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="ka-admin-header-actions">
                    <?php ka_admin_theme_switch($mode); ?>
                    <?php echo $args['actions']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- caller markup. ?>
                </div>
            </div>

            <?php if ($args['saved']) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo esc_html($args['saved_message']); ?></p>
                </div>
            <?php endif; ?>

            <?php foreach ((array) $args['notices'] as $notice) : ?>
                <?php
                $type = in_array(($notice['type'] ?? ''), ['error', 'warning', 'info', 'success'], true)
                    ? $notice['type']
                    : 'info';
                ?>
                <div class="notice notice-<?php echo esc_attr($type); ?>">
                    <p><?php echo wp_kses_post($notice['text'] ?? ''); ?></p>
                </div>
            <?php endforeach; ?>
        <?php
    }
}

if (!function_exists('ka_admin_page_close')) {
    /**
     * Close a King Addons admin screen.
     *
     * @return void
     */
    function ka_admin_page_close(): void
    {
        echo '</div>';
    }
}

if (!function_exists('ka_admin_card_open')) {
    /**
     * Open a settings card.
     *
     * @param string $title Card title.
     * @param string $icon  Dashicon class.
     *
     * @return void
     */
    function ka_admin_card_open(string $title, string $icon = 'dashicons-admin-settings'): void
    {
        ?>
        <div class="ka-card">
            <div class="ka-card-header">
                <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
                <h2><?php echo esc_html($title); ?></h2>
            </div>
            <div class="ka-card-body">
        <?php
    }
}

if (!function_exists('ka_admin_card_close')) {
    /**
     * Close a settings card.
     *
     * @return void
     */
    function ka_admin_card_close(): void
    {
        echo '</div></div>';
    }
}

if (!function_exists('ka_admin_row_open')) {
    /**
     * Open one labelled row inside a card.
     *
     * @param string $label Row label.
     * @param string $for   Optional input id the label points at.
     *
     * @return void
     */
    function ka_admin_row_open(string $label, string $for = ''): void
    {
        ?>
        <div class="ka-row">
            <div class="ka-row-label">
                <?php if ('' !== $for) : ?>
                    <label for="<?php echo esc_attr($for); ?>"><?php echo esc_html($label); ?></label>
                <?php else : ?>
                    <?php echo esc_html($label); ?>
                <?php endif; ?>
            </div>
            <div class="ka-row-field">
        <?php
    }
}

if (!function_exists('ka_admin_row_close')) {
    /**
     * Close a row.
     *
     * @param string $description Optional help text under the field.
     *
     * @return void
     */
    function ka_admin_row_close(string $description = ''): void
    {
        if ('' !== $description) {
            echo '<p class="ka-row-desc">' . wp_kses_post($description) . '</p>';
        }

        echo '</div></div>';
    }
}
