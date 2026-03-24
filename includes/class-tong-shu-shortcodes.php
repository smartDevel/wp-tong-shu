<?php
/**
 * Shortcodes — Free & Premium
 */

if (!defined('ABSPATH')) exit;

class WPTS_Tong_Shu_Shortcodes {

    public static function init() {
        add_shortcode('tong_shu_calendar', [__CLASS__, 'calendar_shortcode']);
        add_shortcode('tong_shu_day', [__CLASS__, 'day_shortcode']);
        add_action('wp_ajax_wpts_navigate', [__CLASS__, 'ajax_navigate']);
        add_action('wp_ajax_nopriv_wpts_navigate', [__CLASS__, 'ajax_navigate']);
    }

    /**
     * [tong_shu_calendar] — FREE
     */
    public static function calendar_shortcode($atts) {
        $atts = shortcode_atts(['year' => date('Y'), 'month' => date('n')], $atts);
        return WPTS_Tong_Shu_Calendar::render_month(intval($atts['year']), intval($atts['month']));
    }

    /**
     * [tong_shu_day] — PREMIUM
     */
    public static function day_shortcode($atts) {
        if (!WPTS_Tong_Shu_Admin::is_premium()) {
            return '<div class="wpts-premium-locked"><p>⭐ Der <code>[tong_shu_day]</code> Shortcode ist ein Premium-Feature. <a href="' . admin_url('options-general.php?page=tong-shu-settings') . '">Lizenz eingeben →</a></p></div>';
        }
        $atts = shortcode_atts(['year' => date('Y'), 'month' => date('n'), 'day' => date('j')], $atts);
        return WPTS_Tong_Shu_Calendar::render_day(intval($atts['year']), intval($atts['month']), intval($atts['day']));
    }

    public static function ajax_navigate() {
        $year = intval($_GET['year'] ?? date('Y'));
        $month = intval($_GET['month'] ?? date('n'));
        echo WPTS_Tong_Shu_Calendar::render_month($year, $month);
        wp_die();
    }
}
