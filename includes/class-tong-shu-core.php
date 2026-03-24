<?php
/**
 * Core Tong Shu calculations
 */

if (!defined('ABSPATH')) exit;

class WPTS_Tong_Shu_Core {

    /** Heavenly Stems (天干) */
    const HEAVENLY_STEMS = ['甲','乙','丙','丁','戊','己','庚','辛','壬','癸'];

    /** Earthly Branches (地支) */
    const EARTHLY_BRANCHES = ['子','丑','寅','卯','辰','巳','午','未','申','酉','戌','亥'];

    /** Chinese Zodiac Animals */
    const ZODIAC_ANIMALS = ['Rat','Ox','Tiger','Rabbit','Dragon','Snake','Horse','Goat','Monkey','Rooster','Dog','Pig'];

    /** Five Elements (五行) */
    const FIVE_ELEMENTS = ['Wood','Fire','Earth','Metal','Water'];

    /** Activities */
    const ACTIVITIES = [
        'auspicious' => [
            '结婚' => 'Heiraten',
            '搬家' => 'Umziehen',
            '开业' => 'Geschäftseröffnung',
            '出行' => 'Reisen',
            '签约' => 'Verträge unterzeichnen',
            '动土' => 'Bau beginnen',
            '安床' => 'Bett aufstellen',
            '祭祀' => 'Opfer bringen',
            '求嗣' => 'Kinderwunsch',
            '开市' => 'Markt eröffnen',
            '交易' => 'Handel treiben',
            '纳财' => 'Reichtum anziehen',
            '入学' => 'Studium beginnen',
            '赴任' => 'Amt antreten',
            '裁衣' => 'Kleidung nähen',
        ],
        'inauspicious' => [
            '破土' => 'Grund graben',
            '安葬' => 'Beerdigung',
            '诉讼' => 'Klage führen',
            '探病' => 'Kranke besuchen',
            '针灸' => 'Akupunktur',
            '剃头' => 'Haare schneiden',
            '伐木' => 'Bäume fällen',
            '纳畜' => 'Vieh kaufen',
        ],
    ];

    public static function init() {
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }

    public static function enqueue_assets() {
        wp_enqueue_style('wpts-calendar', WPTS_PLUGIN_URL . 'assets/css/calendar.css', [], WPTS_VERSION);
        wp_enqueue_script('wpts-calendar', WPTS_PLUGIN_URL . 'assets/js/calendar.js', ['jquery'], WPTS_VERSION, true);
    }

    /**
     * Get Tong Shu data for a specific date
     */
    public static function get_day_data($year, $month, $day) {
        $timestamp = mktime(0, 0, 0, $month, $day, $year);

        // Heavenly Stem & Earthly Branch for the day
        $stem_index = ($timestamp / 86400 + 9) % 10;
        $branch_index = ($timestamp / 86400 + 1) % 12;

        $heavenly_stem = self::HEAVENLY_STEMS[$stem_index];
        $earthly_branch = self::EARTHLY_BRANCHES[$branch_index];
        $zodiac = self::ZODIAC_ANIMALS[$branch_index];

        // Five Element for the day
        $element_index = floor($stem_index / 2) % 5;
        $element = self::FIVE_ELEMENTS[$element_index];

        // Determine auspicious/inauspicious activities
        $auspicious = self::get_auspicious_activities($stem_index, $branch_index);
        $inauspicious = self::get_inauspicious_activities($stem_index, $branch_index);

        // Lucky direction
        $lucky_direction = self::get_lucky_direction($branch_index);

        // Clash (冲)
        $clash_branch = ($branch_index + 6) % 12;
        $clash_zodiac = self::ZODIAC_ANIMALS[$clash_branch];

        return [
            'date' => date('Y-m-d', $timestamp),
            'heavenly_stem' => $heavenly_stem,
            'earthly_branch' => $earthly_branch,
            'stem_branch' => $heavenly_stem . $earthly_branch,
            'zodiac' => $zodiac,
            'element' => $element,
            'lucky_direction' => $lucky_direction,
            'clash_zodiac' => $clash_zodiac,
            'auspicious' => $auspicious,
            'inauspicious' => $inauspicious,
        ];
    }

    private static function get_auspicious_activities($stem, $branch) {
        $activities = [];
        $seed = ($stem * 12 + $branch) % count(self::ACTIVITIES['auspicious']);
        $keys = array_keys(self::ACTIVITIES['auspicious']);
        for ($i = 0; $i < 5; $i++) {
            $idx = ($seed + $i * 3) % count($keys);
            $activities[] = [
                'chinese' => $keys[$idx],
                'german' => self::ACTIVITIES['auspicious'][$keys[$idx]],
            ];
        }
        return $activities;
    }

    private static function get_inauspicious_activities($stem, $branch) {
        $activities = [];
        $seed = ($stem * 12 + $branch + 5) % count(self::ACTIVITIES['inauspicious']);
        $keys = array_keys(self::ACTIVITIES['inauspicious']);
        for ($i = 0; $i < 3; $i++) {
            $idx = ($seed + $i * 2) % count($keys);
            $activities[] = [
                'chinese' => $keys[$idx],
                'german' => self::ACTIVITIES['inauspicious'][$keys[$idx]],
            ];
        }
        return $activities;
    }

    private static function get_lucky_direction($branch_index) {
        $directions = ['N','NE','E','SE','S','SW','W','NW'];
        return $directions[$branch_index % 8];
    }
}
