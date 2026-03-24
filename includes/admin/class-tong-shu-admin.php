<?php
/**
 * Admin Settings & License Management
 */

if (!defined('ABSPATH')) exit;

class WPTS_Tong_Shu_Admin {

    const LICENSE_OPTION = 'wpts_license_key';
    const LICENSE_STATUS = 'wpts_license_status';

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
        register_setting('wpts_settings', 'wpts_free_mode_notice');
    }

    public static function render_settings_page() {
        $license_key = get_option(self::LICENSE_OPTION, '');
        $license_status = get_option(self::LICENSE_STATUS, 'inactive');
        ?>
        <div class="wrap">
            <h1>🏮 Tong Shu — Einstellungen</h1>

            <div style="display:flex;gap:30px;margin-top:20px;">
                <!-- Free Features -->
                <div style="flex:1;background:#fff;padding:20px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.1);">
                    <h2>✅ Kostenlose Funktionen</h2>
                    <ul>
                        <li>📅 <strong>Monatskalender</strong> — Grundlegende Tong Shu Übersicht</li>
                        <li>🏮 <strong>Himmlische Stämme & Irdische Zweige</strong> (天干地支)</li>
                        <li>🐲 <strong>Tierkreiszeichen</strong> — Tages-Tierkreis</li>
                        <li>🌳 <strong>Fünf Elemente</strong> (五行) Anzeige</li>
                        <li>🗓️ <strong>[tong_shu_calendar]</strong> Shortcode</li>
                    </ul>
                    <p style="color:#666;">Diese Funktionen sind frei ohne Lizenz verfügbar.</p>
                </div>

                <!-- Premium Features -->
                <div style="flex:1;background:#fff;padding:20px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.1);border-left:4px solid #f59e0b;">
                    <h2>⭐ Premium Funktionen</h2>
                    <ul>
                        <li>✅ <strong>Günstige Aktivitäten</strong> — Detaillierte Empfehlungen</li>
                        <li>❌ <strong>Ungünstige Aktivitäten</strong> — Warnungen</li>
                        <li>🧭 <strong>Glücksrichtungen</strong> — Tägliche Orientierung</li>
                        <li>⚔️ <strong>Konflikt-Tierkreiszeichen</strong> (冲)</li>
                        <li>📊 <strong>Tagesdetail-Ansicht</strong> — Vollständige Analyse</li>
                        <li>📖 <strong>[tong_shu_day]</strong> Shortcode</li>
                        <li>🔮 <strong>Monats-/Jahresprognosen</strong></li>
                        <li>📈 <strong>API-Zugriff</strong> — Für Entwickler</li>
                    </ul>
                    <p style="color:#666;">Premium erfordert eine aktive Lizenz.</p>
                </div>
            </div>

            <!-- License Section -->
            <div style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 1px 3px rgba(0,0,0,0.1);margin-top:20px;max-width:600px;">
                <h2>🔑 Lizenz</h2>
                <form method="post" action="options.php">
                    <?php settings_fields('wpts_settings'); ?>
                    <table class="form-table">
                        <tr>
                            <th><label for="wpts_license_key">Lizenzschlüssel</label></th>
                            <td>
                                <input type="text" id="wpts_license_key" name="<?php echo self::LICENSE_OPTION; ?>"
                                       value="<?php echo esc_attr($license_key); ?>"
                                       class="regular-text" placeholder="XXXX-XXXX-XXXX-XXXX">
                                <p class="description">Gib deinen Lizenzschlüssel ein, um Premium-Funktionen freizuschalten.</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td>
                                <?php if ($license_status === 'active') : ?>
                                    <span style="color:#16a34a;font-weight:600;">✅ Aktiv</span>
                                <?php else : ?>
                                    <span style="color:#dc2626;">❌ Inaktiv</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                    <?php submit_button('Lizenz speichern'); ?>
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * Check if premium features are available
     */
    public static function is_premium() {
        $status = get_option(self::LICENSE_STATUS, 'inactive');
        return $status === 'active';
    }
}
