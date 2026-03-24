<?php
/**
 * Core Tong Shu calculations
 */

if (!defined('ABSPATH')) exit;

class WPTS_Tong_Shu_Core {

    const HEAVENLY_STEMS = ['甲','乙','丙','丁','戊','己','庚','辛','壬','癸'];
    const EARTHLY_BRANCHES = ['子','丑','寅','卯','辰','巳','午','未','申','酉','戌','亥'];
    const ZODIAC_ANIMALS = ['Rat','Ox','Tiger','Rabbit','Dragon','Snake','Horse','Goat','Monkey','Rooster','Dog','Pig'];
    const FIVE_ELEMENTS = ['Wood','Fire','Earth','Metal','Water'];

    const ACTIVITIES = [
        'auspicious' => [
            '结婚' => 'Heiraten', '搬家' => 'Umziehen', '开业' => 'Geschäftseröffnung',
            '出行' => 'Reisen', '签约' => 'Verträge unterzeichnen', '动土' => 'Bau beginnen',
            '安床' => 'Bett aufstellen', '祭祀' => 'Opfer bringen', '求嗣' => 'Kinderwunsch',
            '开市' => 'Markt eröffnen', '交易' => 'Handel treiben', '纳财' => 'Reichtum anziehen',
            '入学' => 'Studium beginnen', '赴任' => 'Amt antreten', '裁衣' => 'Kleidung nähen',
        ],
        'inauspicious' => [
            '破土' => 'Grund graben', '安葬' => 'Beerdigung', '诉讼' => 'Klage führen',
            '探病' => 'Kranke besuchen', '针灸' => 'Akupunktur', '剃头' => 'Haare schneiden',
            '伐木' => 'Bäume fällen', '纳畜' => 'Vieh kaufen',
        ],
    ];

    public static function init() {
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);
    }

    public static function enqueue_assets() {
        wp_enqueue_style('wpts-calendar', WPTS_PLUGIN_URL . 'assets/css/calendar.css', [], WPTS_VERSION);
        wp_enqueue_script('wpts-calendar', WPTS_PLUGIN_URL . 'assets/js/calendar.js', ['jquery'], WPTS_VERSION, true);
        wp_localize_script('wpts-calendar', 'wpts_ajax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'premium' => WPTS_Tong_Shu_Admin::is_premium(),
        ]);
    }

    /**
     * Get Tong Shu data for a specific date
     */
    public static function get_day_data($year, $month, $day) {
        $timestamp = mktime(0, 0, 0, $month, $day, $year);
        $stem_index = ($timestamp / 86400 + 9) % 10;
        $branch_index = ($timestamp / 86400 + 1) % 12;

        $data = [
            'date' => date('Y-m-d', $timestamp),
            'heavenly_stem' => self::HEAVENLY_STEMS[$stem_index],
            'earthly_branch' => self::EARTHLY_BRANCHES[$branch_index],
            'stem_branch' => self::HEAVENLY_STEMS[$stem_index] . self::EARTHLY_BRANCHES[$branch_index],
            'zodiac' => self::ZODIAC_ANIMALS[$branch_index],
            'element' => self::FIVE_ELEMENTS[floor($stem_index / 2) % 5],
            'premium' => WPTS_Tong_Shu_Admin::is_premium(),
        ];

        // Premium-only data
        if (WPTS_Tong_Shu_Admin::is_premium()) {
            $data['lucky_direction'] = self::get_lucky_direction($branch_index);
            $data['clash_branch'] = self::EARTHLY_BRANCHES[($branch_index + 6) % 12];
            $data['clash_zodiac'] = self::ZODIAC_ANIMALS[($branch_index + 6) % 12];
            $data['auspicious'] = self::get_activities('auspicious', $stem_index, $branch_index);
            $data['inauspicious'] = self::get_activities('inauspicious', $stem_index, $branch_index);
        } else {
            $data['lucky_direction'] = null;
            $data['clash_zodiac'] = null;
            $data['auspicious'] = null;
            $data['inauspicious'] = null;
        }

        return $data;
    }

    private static function get_activities($type, $stem, $branch) {
        $activities = [];
        $seed = ($stem * 12 + $branch + ($type === 'inauspicious' ? 5 : 0)) % count(self::ACTIVITIES[$type]);
        $keys = array_keys(self::ACTIVITIES[$type]);
        $count = $type === 'auspicious' ? 5 : 3;
        for ($i = 0; $i < $count; $i++) {
            $idx = ($seed + $i * ($type === 'auspicious' ? 3 : 2)) % count($keys);
            $activities[] = [
                'chinese' => $keys[$idx],
                'german' => self::ACTIVITIES[$type][$keys[$idx]],
            ];
        }
        return $activities;
    }

    private static function get_lucky_direction($branch_index) {
        $directions = ['N','NE','E','SE','S','SW','W','NW'];
        return $directions[$branch_index % 8];
    }
}
