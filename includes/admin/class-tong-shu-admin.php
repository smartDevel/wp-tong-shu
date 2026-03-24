<?php
/**
 * Admin Settings & License Management
 */

if (!defined('ABSPATH')) exit;

class WPTS_Tong_Shu_Admin {

    const LICENSE_OPTION = 'wpts_license_key';
    const LICENSE_STATUS = 'wpts_license_status';
    const LICENSE_SERVER = 'wpts_license_server';

    // ⚠️ HIER die URL deines Lizenz-Servers eintragen!
    const DEFAULT_SERVER = 'https://buchversteher.de/tong-shu-license/api';

    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }

    public static function add_menu() {
        add_options_page(
            'Tong Shu Einstellungen',
            'Tong Shu',
            'manage_options',
            'tong-shu-settings',
            [__CLASS__, 'render_settings_page']
        );
    }

    public static function register_settings() {
        register_setting('wpts_settings', self::LICENSE_OPTION);
        register_setting('wpts_settings', self::LICENSE_SERVER);
    }

    /**
     * Verify license against remote server
     */
    public static function verify_license($license_key = null) {
        if (!$license_key) {
            $license_key = get_option(self::LICENSE_OPTION, '');
        }
        if (empty($license_key)) return false;

        $server = get_option(self::LICENSE_SERVER, self::DEFAULT_SERVER);
        $site_url = home_url();

        $response = wp_remote_post($server . '/verify.php', [
            'body' => json_encode([
                'license_key' => $license_key,
                'site_url' => $site_url,
            ]),
            'headers' => ['Content-Type' => 'application/json'],
            'timeout' => 10,
        ]);

        if (is_wp_error($response)) {
            // Server nicht erreichbar — Cache nutzen
            return get_option(self::LICENSE_STATUS, '') === 'active';
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($body['status'] === 'active') {
            update_option(self::LICENSE_STATUS, 'active');
            return true;
        } else {
            update_option(self::LICENSE_STATUS, $body['status']);
            return false;
        }
    }

    public static function is_premium() {
        return self::verify_license();
    }

    public static function render_settings_page() {
        $license_key = get_option(self::LICENSE_OPTION, '');
        $license_server = get_option(self::LICENSE_SERVER, self::DEFAULT_SERVER);
        $license_status = get_option(self::LICENSE_STATUS, 'inactive');

        // Handle verify button
        if (isset($_POST['wpts_verify']) && check_admin_referer('wpts_verify_action')) {
            $key = sanitize_text_field($_POST[self::LICENSE_OPTION] ?? '');
            if ($key && self::verify_license($key)) {
                $license_status = 'active';
                update_option(self::LICENSE_OPTION, $key);
                $msg = '✅ Lizenz erfolgreich verifiziert!';
            } else {
                $license_status = 'invalid';
                $msg = '❌ Lizenz konnte nicht verifiziert werden.';
            }
        }

        // Handle deactivate
        if (isset($_POST['wpts_deactivate']) && check_admin_referer('wpts_verify_action')) {
            $server = get_option(self::LICENSE_SERVER, self::DEFAULT_SERVER);
            wp_remote_post($server . '/deactivate.php', [
                'body' => json_encode([
                    'license_key' => $license_key,
                    'site_url' => home_url(),
                ]),
                'headers' => ['Content-Type' => 'application/json'],
            ]);
            update_option(self::LICENSE_STATUS, 'inactive');
            $license_status = 'inactive';
            $msg = '✅ Lizenz deaktiviert.';
        }

        ?>
        <div class="wrap">
            <h1>🏮 Tong Shu — Einstellungen</h1>

            <?php if (!empty($msg)): ?>
                <div class="notice notice-info"><p><?= $msg ?></p></div>
            <?php endif; ?>

            <div style="display:flex;gap:30px;margin-top:20px;">
                <!-- Free -->
                <div style="flex:1;background:#fff;padding:20px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h2>🆓 Kostenlos</h2>
                    <ul>
                        <li>📅 Monatskalender</li>
                        <li>🏮 天干地支</li>
                        <li>🐲 Tierkreiszeichen</li>
                        <li>🌳 Fünf Elemente</li>
                        <li>🗓️ [tong_shu_calendar]</li>
                    </ul>
                </div>
                <!-- Premium -->
                <div style="flex:1;background:#fff;padding:20px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.1);border-left:4px solid #f59e0b;">
                    <h2>⭐ Premium</h2>
                    <ul>
                        <li>✅ Günstige Aktivitäten</li>
                        <li>❌ Ungünstige Aktivitäten</li>
                        <li>🧭 Glücksrichtungen</li>
                        <li>⚔️ Konflikt-Tierkreiszeichen</li>
                        <li>📊 Tagesdetail [tong_shu_day]</li>
                        <li>🔮 Prognosen & API</li>
                    </ul>
                </div>
            </div>

            <!-- License Section -->
            <div style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.1);margin-top:20px;max-width:600px;">
                <h2>🔑 Lizenz</h2>
                <form method="post">
                    <?php wp_nonce_field('wpts_verify_action'); ?>

                    <table class="form-table">
                        <tr>
                            <th><label>Lizenzschlüssel</label></th>
                            <td>
                                <input type="text" name="<?= self::LICENSE_OPTION ?>"
                                       value="<?= esc_attr($license_key) ?>"
                                       class="regular-text" placeholder="XXXX-XXXX-XXXX-XXXX">
                            </td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                <?php if ($license_status === 'active'): ?>
                                    <span style="color:#16a34a;font-weight:600;">✅ Aktiv</span>
                                <?php elseif ($license_status === 'expired'): ?>
                                    <span style="color:#f59e0b;font-weight:600;">⚠️ Abgelaufen</span>
                                <?php else: ?>
                                    <span style="color:#dc2626;">❌ Inaktiv</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if (current_user_can('manage_options')): ?>
                        <tr>
                            <th><label>Lizenz-Server</label></th>
                            <td>
                                <input type="url" name="<?= self::LICENSE_SERVER ?>"
                                       value="<?= esc_attr($license_server) ?>"
                                       class="regular-text" placeholder="https://...">
                                <p class="description">Nur ändern wenn eigener Server genutzt wird.</p>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </table>

                    <p>
                        <input type="submit" name="wpts_verify" class="button button-primary" value="Lizenz verifizieren">
                        <?php if ($license_status === 'active'): ?>
                            <input type="submit" name="wpts_deactivate" class="button" value="Deaktivieren" onclick="return confirm('Lizenz deaktivieren?')">
                        <?php endif; ?>
                    </p>
                </form>
            </div>
        </div>
        <?php
    }
}
