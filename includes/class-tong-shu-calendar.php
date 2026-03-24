<?php
/**
 * Calendar rendering — Free vs Premium
 */

if (!defined('ABSPATH')) exit;

class WPTS_Tong_Shu_Calendar {

    public static function render_month($year, $month) {
        $first_day = mktime(0, 0, 0, $month, 1, $year);
        $days_in_month = date('t', $first_day);
        $start_weekday = date('N', $first_day);
        $is_premium = WPTS_Tong_Shu_Admin::is_premium();

        $html = '<div class="wpts-calendar">';
        $html .= '<div class="wpts-calendar-header">';
        $html .= '<button class="wpts-nav wpts-prev" data-year="' . ($month == 1 ? $year - 1 : $year) . '" data-month="' . ($month == 1 ? 12 : $month - 1) . '">&laquo;</button>';
        $html .= '<h3>' . date('F Y', $first_day) . '</h3>';
        $html .= '<button class="wpts-nav wpts-next" data-year="' . ($month == 12 ? $year + 1 : $year) . '" data-month="' . ($month == 12 ? 1 : $month + 1) . '">&raquo;</button>';
        $html .= '</div>';

        $html .= '<div class="wpts-weekdays">';
        foreach (['Mo','Di','Mi','Do','Fr','Sa','So'] as $day_name) {
            $html .= '<div class="wpts-weekday">' . $day_name . '</div>';
        }
        $html .= '</div>';

        $html .= '<div class="wpts-days">';
        for ($i = 1; $i < $start_weekday; $i++) {
            $html .= '<div class="wpts-day wpts-day-empty"></div>';
        }

        for ($day = 1; $day <= $days_in_month; $day++) {
            $data = WPTS_Tong_Shu_Core::get_day_data($year, $month, $day);
            $is_today = (date('Y-m-d') === $data['date']);
            $class = 'wpts-day' . ($is_today ? ' wpts-today' : '') . (!$is_premium ? ' wpts-free' : '');

            $html .= '<div class="' . $class . '" data-date="' . $data['date'] . '">';
            $html .= '<div class="wpts-day-number">' . $day . '</div>';
            $html .= '<div class="wpts-stem-branch">' . $data['stem_branch'] . '</div>';
            $html .= '<div class="wpts-element">' . self::element_icon($data['element']) . '</div>';
            $html .= '<div class="wpts-zodiac">' . self::zodiac_emoji($data['zodiac']) . '</div>';

            if (!$is_premium) {
                $html .= '<div class="wpts-premium-badge" title="Premium">⭐</div>';
            }

            $html .= '</div>';
        }

        $html .= '</div>';

        if (!$is_premium) {
            $html .= '<div class="wpts-upgrade-notice">';
            $html .= '⭐ <strong>Premium freischalten:</strong> Günstige/ungünstige Aktivitäten, Glücksrichtungen, Konflikt-Tierkreiszeichen und mehr! ';
            $html .= '<a href="' . admin_url('options-general.php?page=tong-shu-settings') . '">Lizenz eingeben →</a>';
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    public static function render_day($year, $month, $day) {
        $data = WPTS_Tong_Shu_Core::get_day_data($year, $month, $day);
        $is_premium = $data['premium'];

        $html = '<div class="wpts-day-detail">';
        $html .= '<h3>' . date('d.m.Y', mktime(0, 0, 0, $month, $day, $year)) . '</h3>';
        $html .= '<div class="wpts-stem-branch-large">' . $data['stem_branch'] . '</div>';

        $html .= '<table class="wpts-info-table">';
        $html .= '<tr><td>Element</td><td>' . self::element_icon($data['element']) . ' ' . $data['element'] . '</td></tr>';
        $html .= '<tr><td>Tierkreis</td><td>' . self::zodiac_emoji($data['zodiac']) . ' ' . $data['zodiac'] . '</td></tr>';

        if ($is_premium) {
            $html .= '<tr><td>Glücksrichtung</td><td>' . $data['lucky_direction'] . '</td></tr>';
            $html .= '<tr><td>Konflikt</td><td>' . self::zodiac_emoji($data['clash_zodiac']) . ' ' . $data['clash_zodiac'] . '</td></tr>';
        }
        $html .= '</table>';

        if ($is_premium) {
            $html .= '<h4>✅ Günstig</h4><ul class="wpts-auspicious">';
            foreach ($data['auspicious'] as $a) {
                $html .= '<li>' . $a['german'] . ' <small>(' . $a['chinese'] . ')</small></li>';
            }
            $html .= '</ul>';

            $html .= '<h4>❌ Ungünstig</h4><ul class="wpts-inauspicious">';
            foreach ($data['inauspicious'] as $a) {
                $html .= '<li>' . $a['german'] . ' <small>(' . $a['chinese'] . ')</small></li>';
            }
            $html .= '</ul>';
        } else {
            $html .= '<div class="wpts-premium-locked">';
            $html .= '<p>⭐ <strong>Premium-Inhalt</strong></p>';
            $html .= '<p>Günstige & ungünstige Aktivitäten, Glücksrichtungen und Konflikt-Tierkreiszeichen sind Premium-Features.</p>';
            $html .= '<a href="' . admin_url('options-general.php?page=tong-shu-settings') . '" class="wpts-upgrade-btn">Premium freischalten →</a>';
            $html .= '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    private static function element_icon($element) {
        $icons = ['Wood' => '🌳', 'Fire' => '🔥', 'Earth' => '🌍', 'Metal' => '⚙️', 'Water' => '💧'];
        return $icons[$element] ?? '';
    }

    private static function zodiac_emoji($zodiac) {
        $emojis = [
            'Rat' => '🐀', 'Ox' => '🐂', 'Tiger' => '🐅', 'Rabbit' => '🐇',
            'Dragon' => '🐉', 'Snake' => '🐍', 'Horse' => '🐴', 'Goat' => '🐐',
            'Monkey' => '🐒', 'Rooster' => '🐓', 'Dog' => '🐕', 'Pig' => '🐖',
        ];
        return $emojis[$zodiac] ?? '';
    }
}
