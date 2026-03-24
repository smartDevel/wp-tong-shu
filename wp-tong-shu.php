<?php
/**
 * Plugin Name: WP Tong Shu — Chinese Almanac
 * Plugin URI: https://github.com/smartDevel/wp-tong-shu
 * Description: Generates Tong Shu (Chinese Almanac) calendar data — daily favorable/unfavorable activities, Heavenly Stems & Earthly Branches, lucky guidance.
 * Version: 0.1.0
 * Author: Herbert Sablotny
 * License: GPL v2 or later
 * Text Domain: wp-tong-shu
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) exit;

define('WPTS_VERSION', '0.1.0');
define('WPTS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPTS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Autoload
require_once WPTS_PLUGIN_DIR . 'includes/class-tong-shu-core.php';
require_once WPTS_PLUGIN_DIR . 'includes/class-tong-shu-calendar.php';
require_once WPTS_PLUGIN_DIR . 'includes/class-tong-shu-shortcodes.php';

// Initialize
add_action('plugins_loaded', function () {
    WPTS_Tong_Shu_Core::init();
    WPTS_Tong_Shu_Shortcodes::init();
});
