<?php
/** 
 * @wordpress-plugin
 * Plugin Name: Prayer Times Widget
 * Description: Display prayer times from Masjidi API for one or two masjids
 * Author: Masjidal
 * Author URI: https://icfbayarea.com/
 * Version: 2.0.0
 */

error_reporting(0);

/**
 * Fetch prayer times from Masjidi API
 */
if (!function_exists("mptsi_fetch_masjidi_data")) {
    function mptsi_fetch_masjidi_data($masjid_id) {
        if (empty($masjid_id)) {
            return null;
        }

        // Fetch from Masjidi API (no caching - always fetch fresh data)
        $url = "https://api.masjidiapp.com/v2/masjids/{$masjid_id}";

        // API key - get from options or use default test key
        $api_key = get_option('masjidi_api_key', '123-test-key');
        $api_key = apply_filters('masjidi_api_key', $api_key);

        $response = wp_remote_get($url, array(
            'timeout' => 15,
            'headers' => array(
                'Accept' => 'application/json',
                'apikey' => $api_key,
            )
        ));

        if (is_wp_error($response)) {
            error_log('Masjidi API Error: ' . $response->get_error_message());
            return null;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($response_code == 200) {
            $data = json_decode($body);
            if ($data) {
                return $data;
            }
        } else {
            error_log('Masjidi API Error: HTTP ' . $response_code . ' - ' . $body);
        }

        return null;
    }
}

/**
 * Format time to 12-hour format
 */
if (!function_exists("mptsi_format_time")) {
    function mptsi_format_time($time_str) {
        if (empty($time_str) || $time_str === '-' || $time_str === '') {
            return '-';
        }
        
        $timestamp = strtotime($time_str);
        if ($timestamp === false) {
            return $time_str;
        }
        
        return date("g:i A", $timestamp);
    }
}

/**
 * Get upcoming iqamah changes for a masjid (returns array, not HTML)
 */
if (!function_exists("mptsi_get_iqamah_changes")) {
    function mptsi_get_iqamah_changes($data, $masjid_name) {
        if (!isset($data->next_iqamah_change) || !isset($data->next_iqamah_change->change_date)) {
            return null;
        }

        $next_change = $data->next_iqamah_change;
        $change_date = strtotime($next_change->change_date);
        $today = strtotime('today');

        // Only include if change date is today or in the future
        if ($change_date < $today) {
            return null;
        }

        // Compare current times with next times to find what's changing
        $prayer_fields = [
            'Fajr' => ['current' => 'fajr_iqama_time', 'next' => 'fajr_iqama'],
            'Dhuhr' => ['current' => 'zuhr_iqama_time', 'next' => 'zuhr_iqama'],
            'Asr' => ['current' => 'asr_iqama_time', 'next' => 'asr_iqama'],
            'Isha' => ['current' => 'isha_iqama_time', 'next' => 'isha_iqama']
        ];

        $changes = [];
        foreach ($prayer_fields as $prayer => $fields) {
            $current_field = $fields['current'];
            $next_field = $fields['next'];

            $current_time = isset($data->$current_field) ? $data->$current_field : '';
            $next_time = isset($next_change->$next_field) ? $next_change->$next_field : '';

            // Show if time is different
            if (!empty($next_time) && $current_time !== $next_time) {
                $changes[] = $prayer . ' ' . mptsi_format_time($current_time) . ' → ' . mptsi_format_time($next_time);
            }
        }

        if (!empty($changes)) {
            return [
                'masjid_name' => $masjid_name,
                'change_date' => $change_date,
                'change_date_str' => date('D, M j', $change_date),
                'changes' => $changes
            ];
        }

        return null;
    }
}

/**
 * Build merged iqamah changes banner for one or two masjids
 */
if (!function_exists("mptsi_build_merged_changes_banner")) {
    function mptsi_build_merged_changes_banner($changes1, $changes2 = null, $text_color = '#ffffff') {
        if (!$changes1 && !$changes2) {
            return '';
        }

        // Use inline styles to override Elementor/theme CSS
        $strong_style = 'style="color: ' . esc_attr($text_color) . ' !important;"';
        $span_style = 'style="color: ' . esc_attr($text_color) . ' !important;"';

        $banner_content = '';

        // Check if both have changes on the same date
        if ($changes1 && $changes2 && $changes1['change_date_str'] === $changes2['change_date_str']) {
            // Same date - merge into one banner
            $banner_content = '<span ' . $span_style . '>Iqamah changing ' . esc_html($changes1['change_date_str']) . ':</span><br>';
            $banner_content .= '<strong ' . $strong_style . '>' . esc_html($changes1['masjid_name']) . '</strong>: <span ' . $span_style . '>' . esc_html(implode(', ', $changes1['changes'])) . '</span><br>';
            $banner_content .= '<strong ' . $strong_style . '>' . esc_html($changes2['masjid_name']) . '</strong>: <span ' . $span_style . '>' . esc_html(implode(', ', $changes2['changes'])) . '</span>';
        } else {
            // Different dates or only one masjid - show separately
            $parts = [];
            if ($changes1) {
                $parts[] = '<strong ' . $strong_style . '>' . esc_html($changes1['masjid_name']) . '</strong> <span ' . $span_style . '>changing ' . esc_html($changes1['change_date_str']) . ': ' . esc_html(implode(', ', $changes1['changes'])) . '</span>';
            }
            if ($changes2) {
                $parts[] = '<strong ' . $strong_style . '>' . esc_html($changes2['masjid_name']) . '</strong> <span ' . $span_style . '>changing ' . esc_html($changes2['change_date_str']) . ': ' . esc_html(implode(', ', $changes2['changes'])) . '</span>';
            }
            $banner_content = implode('<br>', $parts);
        }

        return $banner_content;
    }
}

/**
 * Determine the active prayer based on current time
 */
if (!function_exists("mptsi_get_active_prayer")) {
    function mptsi_get_active_prayer($data) {
        // Get timezone from masjid data
        $timezone = 'America/Los_Angeles';
        if (isset($data->timezone_for_masjid)) {
            $timezone = $data->timezone_for_masjid;
        } elseif (isset($data->timezoneId)) {
            $timezone = $data->timezoneId;
        }
        
        try {
            $now = new DateTime("now", new DateTimeZone($timezone));
            $current_time = strtotime($now->format("H:i"));
        } catch (Exception $e) {
            $current_time = strtotime(date("H:i"));
        }
        
        $prayers = [
            'fajr' => 'fajr_iqama_time',
            'zuhr' => 'zuhr_iqama_time', 
            'asr' => 'asr_iqama_time',
            'maghrib' => 'magrib_iqama_time',
            'isha' => 'isha_iqama_time'
        ];
        
        foreach ($prayers as $key => $field) {
            if (isset($data->$field) && !empty($data->$field)) {
                $iqamah_time = strtotime(date("H:i", strtotime($data->$field)));
                if ($current_time < $iqamah_time) {
                    return $key;
                }
            }
        }
        
        return '';
    }
}

/**
 * Main shortcode for prayer times widget
 */
if (!function_exists("mptsi_masjidi_prayer_times_shortcode")) {
    function mptsi_masjidi_prayer_times_shortcode($atts) {
        // Get settings
        $masjid_id_1 = get_option('masjid_id_1', get_option('masjid_id', '3443'));
        $masjid_id_2 = get_option('masjid_id_2', '');
        $masjid_name_1 = get_option('masjid_name_1', 'Masjid 1');
        $masjid_name_2 = get_option('masjid_name_2', 'Masjid 2');
        $highlighted_color = get_option('highlighted_color', '#1e7b34');
        $highlighted_text_color = get_option('highlighted_text_color', '#ffffff');
        $ramadan_timetable_url = get_option('ramadan_timetable_url', '');

        // Fallback to defaults if empty
        if (empty($highlighted_color)) {
            $highlighted_color = '#1e7b34';
        }
        if (empty($highlighted_text_color)) {
            $highlighted_text_color = '#ffffff';
        }
        
        // Fetch data for both masjids
        $data1 = mptsi_fetch_masjidi_data($masjid_id_1);
        $data2 = !empty($masjid_id_2) ? mptsi_fetch_masjidi_data($masjid_id_2) : null;
        
        if (!$data1) {
            return '<div style="padding: 20px; text-align: center; color: #666;">Unable to load prayer times. Please check your Masjid ID in settings.</div>';
        }
        
        // Get timezone and date
        $timezone = $data1->timezone_for_masjid ?? $data1->timezoneId ?? 'America/Los_Angeles';
        try {
            $date = new DateTime("now", new DateTimeZone($timezone));
        } catch (Exception $e) {
            $date = new DateTime("now");
        }
        
        $today_view_date = $date->format("l, M d");
        $hijri_date = $data1->hijri_date ?? '';
        
        // Get active prayer
        $active_prayer = mptsi_get_active_prayer($data1);
        
        // Check if we have two masjids
        $has_two_masjids = !empty($masjid_id_2) && $data2 !== null;
        
        // Use masjid name from API if not set
        if (empty($masjid_name_1) || $masjid_name_1 === 'Masjid 1') {
            $masjid_name_1 = $data1->title ?? 'Masjid 1';
            // Shorten if too long
            if (strlen($masjid_name_1) > 20) {
                $masjid_name_1 = $data1->masjid_preferences->short_name ?? substr($masjid_name_1, 0, 15) . '...';
            }
        }
        if ($has_two_masjids && (empty($masjid_name_2) || $masjid_name_2 === 'Masjid 2')) {
            $masjid_name_2 = $data2->title ?? 'Masjid 2';
            if (strlen($masjid_name_2) > 20) {
                $masjid_name_2 = $data2->masjid_preferences->short_name ?? substr($masjid_name_2, 0, 15) . '...';
            }
        }
        
        ob_start();
        ?>
        <style>
            .mptsi-widget { font-family: 'Poppins', -apple-system, BlinkMacSystemFont, sans-serif; background: #fff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); max-width: 420px; overflow: hidden; margin: 0 auto; }
            .mptsi-header { text-align: center; padding: 14px 15px 10px; border-bottom: 1px solid #eee; }
            .mptsi-header h2 { font-size: 20px; font-weight: 600; color: #1a1a1a; margin: 0 0 4px 0; }
            .mptsi-header .date { font-size: 13px; color: #555; margin: 0; }
            .mptsi-table { padding: 6px 15px 10px; }
            .mptsi-row { display: grid; grid-template-columns: <?php echo $has_two_masjids ? '95px 1fr 1fr 1fr' : '100px 1fr 1fr'; ?>; padding: 10px 0; border-bottom: 1px solid #f5f5f5; align-items: center; gap: 4px; }
            .mptsi-row.header { border-bottom: 2px solid #eee; padding: 6px 0 8px; }
            .mptsi-row.header span { font-size: 10px; font-weight: 600; text-transform: uppercase; color: #666; letter-spacing: 0.3px; }
            .mptsi-row:last-child { border-bottom: none; }
            .mptsi-row.active { background: <?php echo esc_attr($highlighted_color); ?>; border-radius: 6px; margin: 4px -10px; padding: 10px; }
            .mptsi-row.active * { color: <?php echo esc_attr($highlighted_text_color); ?> !important; }
            .mptsi-name { display: flex; align-items: center; gap: 8px; font-weight: 500; color: #333; font-size: 14px; }
            .mptsi-name .icon { font-size: 18px; width: 22px; text-align: center; }
            .mptsi-time { text-align: center; font-size: 13px; color: #666; line-height: 1.3; }
            .mptsi-iqamah { text-align: center; font-size: 14px; font-weight: 600; color: #1a1a1a; line-height: 1.3; }
            .mptsi-alert { background: <?php echo esc_attr($highlighted_color); ?>; color: <?php echo esc_attr($highlighted_text_color); ?>; text-align: center; padding: 10px 14px; margin: 6px 15px 12px; border-radius: 20px; font-size: 12px; font-weight: 500; }
            .mptsi-alert, .mptsi-alert * { color: <?php echo esc_attr($highlighted_text_color); ?> !important; }
            .mptsi-header .mptsi-alert { margin: 10px 0 0 0; padding: 8px 12px; font-size: 11px; border-radius: 6px; }
            .mptsi-jumuah { padding: 12px 15px; border-top: 1px solid #eee; }
            .mptsi-jumuah-grid { display: flex; justify-content: center; gap: 0; flex-wrap: nowrap; }
            .mptsi-jumuah-item { text-align: center; padding: 4px 14px; flex-shrink: 0; }
            .mptsi-jumuah-item:not(:last-child) { border-right: 1px solid #ddd; }
            .mptsi-jumuah-time { font-size: 16px; font-weight: 600; color: #1a1a1a; }
            .mptsi-jumuah-label { font-size: 10px; text-transform: uppercase; color: #888; letter-spacing: 0.3px; margin-top: 2px; }
            .mptsi-sun { display: flex; justify-content: space-between; padding: 10px 15px; background: #f8f9fa; border-top: 1px solid #eee; }
            .mptsi-sun-item { display: flex; align-items: center; gap: 6px; font-size: 13px; color: #333; }
            .mptsi-link { display: block; text-align: center; padding: 12px; background: #f0f7ff; color: #0066cc; text-decoration: none; font-size: 13px; font-weight: 500; border-top: 1px solid #e0e0e0; }
            .mptsi-link:hover { background: #e0efff; }
            .mptsi-link-ramadan { display: block; text-align: center; padding: 12px; background: #1a1a2e; color: #c8a86b; text-decoration: none; font-size: 13px; font-weight: 500; border-top: 1px solid #2d2d4e; border-radius: 0 0 12px 12px; }
            .mptsi-link-ramadan:hover { background: #16213e; color: #d4b97a; }
            .mptsi-link.mptsi-link-last { border-radius: 0 0 12px 12px; }
            
            /* Tablet/iPad styles */
            @media (min-width: 481px) and (max-width: 1024px) {
                .mptsi-widget { max-width: 400px; }
                .mptsi-row { grid-template-columns: <?php echo $has_two_masjids ? '85px 1fr 1fr 1fr' : '95px 1fr 1fr'; ?>; }
                .mptsi-name { font-size: 13px; }
                .mptsi-name .icon { font-size: 16px; width: 20px; }
                .mptsi-time { font-size: 12px; }
                .mptsi-iqamah { font-size: 13px; }
                .mptsi-header h2 { font-size: 18px; }
                .mptsi-header .date { font-size: 12px; }
            }
            
            /* Mobile styles */
            @media (max-width: 480px) { 
                .mptsi-widget { max-width: 100%; border-radius: 10px; }
                .mptsi-row { grid-template-columns: <?php echo $has_two_masjids ? '75px 1fr 1fr 1fr' : '85px 1fr 1fr'; ?>; padding: 8px 0; }
                .mptsi-name { font-size: 12px; }
                .mptsi-name .icon { font-size: 14px; width: 18px; }
                .mptsi-time { font-size: 11px; }
                .mptsi-iqamah { font-size: 12px; }
                .mptsi-header { padding: 12px 12px 10px; }
                .mptsi-header h2 { font-size: 17px; }
                .mptsi-header .date { font-size: 11px; }
                .mptsi-table { padding: 8px 12px; }
            }
        </style>
        
        <?php
        // Build merged changes banner for both masjids
        $changes_1 = mptsi_get_iqamah_changes($data1, $masjid_name_1);
        $changes_2 = $data2 ? mptsi_get_iqamah_changes($data2, $masjid_name_2) : null;
        $changes_banner = mptsi_build_merged_changes_banner($changes_1, $changes_2, $highlighted_text_color);
        ?>
        <div class="mptsi-widget">
            <div class="mptsi-header">
                <h2>Prayer Timings</h2>
                <div class="date"><?php echo esc_html($today_view_date); ?><?php if (!empty($hijri_date)) echo ' | ' . esc_html($hijri_date); ?></div>
                <?php if (!empty($changes_banner)): ?>
                <div class="mptsi-alert"><?php echo $changes_banner; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="mptsi-table">
                <div class="mptsi-row header">
                    <span></span>
                    <span style="text-align: center;">Azan</span>
                    <span style="text-align: center;"><?php echo esc_html($has_two_masjids ? $masjid_name_1 : 'Iqamah'); ?></span>
                    <?php if ($has_two_masjids): ?>
                    <span style="text-align: center;"><?php echo esc_html($masjid_name_2); ?></span>
                    <?php endif; ?>
                </div>
                
                <?php
                $prayers = [
                    'fajr' => ['label' => 'Fajr', 'icon' => '🌅', 'start' => 'fajr_start_time', 'iqama' => 'fajr_iqama_time'],
                    'zuhr' => ['label' => 'Dhuhr', 'icon' => '☀️', 'start' => 'zuhr_start_time', 'iqama' => 'zuhr_iqama_time'],
                    'asr' => ['label' => 'Asr', 'icon' => '🌤️', 'start' => 'asr_start_time', 'iqama' => 'asr_iqama_time'],
                    'maghrib' => ['label' => 'Maghrib', 'icon' => '🌇', 'start' => 'magrib_start_time', 'iqama' => 'magrib_iqama_time'],
                    'isha' => ['label' => 'Isha', 'icon' => '🌙', 'start' => 'isha_start_time', 'iqama' => 'isha_iqama_time'],
                ];
                
                foreach ($prayers as $key => $prayer):
                    $start = $prayer['start'];
                    $iqama = $prayer['iqama'];

                    $start_time = isset($data1->$start) ? mptsi_format_time($data1->$start) : '-';
                    $iqama_time_1 = isset($data1->$iqama) ? mptsi_format_time($data1->$iqama) : '-';

                    // For Maghrib, use the Azan time as the Iqamah time for both masjids
                    if ($key === 'maghrib') {
                        $iqama_time_1 = $start_time;
                        $iqama_time_2 = $start_time;
                    } else {
                        $iqama_time_2 = ($data2 && isset($data2->$iqama)) ? mptsi_format_time($data2->$iqama) : '-';
                    }

                    $is_active = ($active_prayer === $key);
                ?>
                <div class="mptsi-row <?php echo $is_active ? 'active' : ''; ?>">
                    <div class="mptsi-name">
                        <span class="icon"><?php echo $prayer['icon']; ?></span>
                        <span><?php echo esc_html($prayer['label']); ?></span>
                    </div>
                    <div class="mptsi-time"><?php echo esc_html($start_time); ?></div>
                    <div class="mptsi-iqamah"><?php echo esc_html($iqama_time_1); ?></div>
                    <?php if ($has_two_masjids): ?>
                    <div class="mptsi-iqamah"><?php echo esc_html($iqama_time_2); ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            
            <?php 
            // Iqamah change alert
            $iqamah_change = $data1->last_iqamah_time_change ?? '';
            if (!empty($iqamah_change)):
            ?>
            <div class="mptsi-alert"><?php echo esc_html($iqamah_change); ?></div>
            <?php endif; ?>
            
            <?php
            // Jumuah times - get prayer (iqama) and talk (azan) times from both masjids
            // Prayer times (iqama)
            $j1_prayer_m1 = isset($data1->jumma1_iqama) && !empty($data1->jumma1_iqama) ? mptsi_format_time($data1->jumma1_iqama) : '';
            $j2_prayer_m1 = isset($data1->jumma2_iqama) && !empty($data1->jumma2_iqama) ? mptsi_format_time($data1->jumma2_iqama) : '';
            $j3_prayer_m1 = isset($data1->jumma3_iqama) && !empty($data1->jumma3_iqama) ? mptsi_format_time($data1->jumma3_iqama) : '';

            // Talk times (azan) - try jumma_azan first, then jumma_start_time
            $j1_talk_m1 = '';
            if (isset($data1->jumma1_azan) && !empty($data1->jumma1_azan)) {
                $j1_talk_m1 = mptsi_format_time($data1->jumma1_azan);
            } elseif (isset($data1->jumma1_start_time) && !empty($data1->jumma1_start_time)) {
                $j1_talk_m1 = mptsi_format_time($data1->jumma1_start_time);
            }

            $j2_talk_m1 = '';
            if (isset($data1->jumma2_azan) && !empty($data1->jumma2_azan)) {
                $j2_talk_m1 = mptsi_format_time($data1->jumma2_azan);
            } elseif (isset($data1->jumma2_start_time) && !empty($data1->jumma2_start_time)) {
                $j2_talk_m1 = mptsi_format_time($data1->jumma2_start_time);
            }

            $j3_talk_m1 = '';
            if (isset($data1->jumma3_azan) && !empty($data1->jumma3_azan)) {
                $j3_talk_m1 = mptsi_format_time($data1->jumma3_azan);
            } elseif (isset($data1->jumma3_start_time) && !empty($data1->jumma3_start_time)) {
                $j3_talk_m1 = mptsi_format_time($data1->jumma3_start_time);
            }

            $j1_prayer_m2 = ($data2 && isset($data2->jumma1_iqama) && !empty($data2->jumma1_iqama)) ? mptsi_format_time($data2->jumma1_iqama) : '';
            $j2_prayer_m2 = ($data2 && isset($data2->jumma2_iqama) && !empty($data2->jumma2_iqama)) ? mptsi_format_time($data2->jumma2_iqama) : '';
            $j3_prayer_m2 = ($data2 && isset($data2->jumma3_iqama) && !empty($data2->jumma3_iqama)) ? mptsi_format_time($data2->jumma3_iqama) : '';

            // Build array of all jumuah times with labels
            $jumuah_times = [];

            // Jumu'ah 1
            $j1_m1_valid = !empty($j1_prayer_m1) && $j1_prayer_m1 !== '-';
            $j1_m2_valid = !empty($j1_prayer_m2) && $j1_prayer_m2 !== '-';
            if ($j1_m1_valid || $j1_m2_valid) {
                if ($j1_m1_valid && $j1_m2_valid) {
                    $jumuah_times[] = ['talk' => $j1_talk_m1, 'prayer' => $j1_prayer_m1, 'label' => "Jumu'ah 1", 'masjid' => ''];
                } elseif ($j1_m1_valid) {
                    $jumuah_times[] = ['talk' => $j1_talk_m1, 'prayer' => $j1_prayer_m1, 'label' => "Jumu'ah 1", 'masjid' => $masjid_name_1];
                } else {
                    $jumuah_times[] = ['talk' => '', 'prayer' => $j1_prayer_m2, 'label' => "Jumu'ah 1", 'masjid' => $masjid_name_2];
                }
            }

            // Jumu'ah 2
            $j2_m1_valid = !empty($j2_prayer_m1) && $j2_prayer_m1 !== '-';
            $j2_m2_valid = !empty($j2_prayer_m2) && $j2_prayer_m2 !== '-';
            if ($j2_m1_valid || $j2_m2_valid) {
                if ($j2_m1_valid && $j2_m2_valid) {
                    $jumuah_times[] = ['talk' => $j2_talk_m1, 'prayer' => $j2_prayer_m1, 'label' => "Jumu'ah 2", 'masjid' => ''];
                } elseif ($j2_m1_valid) {
                    $jumuah_times[] = ['talk' => $j2_talk_m1, 'prayer' => $j2_prayer_m1, 'label' => "Jumu'ah 2", 'masjid' => $masjid_name_1];
                } else {
                    $jumuah_times[] = ['talk' => '', 'prayer' => $j2_prayer_m2, 'label' => "Jumu'ah 2", 'masjid' => $masjid_name_2];
                }
            }

            // Jumu'ah 3
            $j3_m1_valid = !empty($j3_prayer_m1) && $j3_prayer_m1 !== '-';
            $j3_m2_valid = !empty($j3_prayer_m2) && $j3_prayer_m2 !== '-';
            if ($j3_m1_valid || $j3_m2_valid) {
                if ($j3_m1_valid && $j3_m2_valid) {
                    $jumuah_times[] = ['talk' => $j3_talk_m1, 'prayer' => $j3_prayer_m1, 'label' => "Jumu'ah 3", 'masjid' => ''];
                } elseif ($j3_m1_valid) {
                    $jumuah_times[] = ['talk' => $j3_talk_m1, 'prayer' => $j3_prayer_m1, 'label' => "Jumu'ah 3", 'masjid' => $masjid_name_1];
                } else {
                    $jumuah_times[] = ['talk' => '', 'prayer' => $j3_prayer_m2, 'label' => "Jumu'ah 3", 'masjid' => $masjid_name_2];
                }
            }

            if (!empty($jumuah_times)):
            ?>
            <div class="mptsi-jumuah">
                <div class="mptsi-jumuah-grid">
                    <?php foreach ($jumuah_times as $j): ?>
                    <div class="mptsi-jumuah-item">
                        <?php if (!empty($j['talk']) && $j['talk'] !== '-'): ?>
                        <div class="mptsi-jumuah-time"><span style="font-size: 11px; font-weight: 400; color: #666;">Talk</span> <?php echo esc_html($j['talk']); ?></div>
                        <?php endif; ?>
                        <div class="mptsi-jumuah-time"><span style="font-size: 11px; font-weight: 400; color: #666;">Prayer</span> <?php echo esc_html($j['prayer']); ?></div>
                        <div class="mptsi-jumuah-label"><?php echo esc_html($j['label']); ?></div>
                        <?php if (!empty($j['masjid'])): ?>
                        <div style="font-size: 9px; color: #0066cc; margin-top: 2px;"><?php echo esc_html($j['masjid']); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php
            // Sunrise/Sunset
            $sunrise = isset($data1->shuruq) ? mptsi_format_time($data1->shuruq) : '';
            $sunset = isset($data1->magrib_start_time) ? mptsi_format_time($data1->magrib_start_time) : '';
            
            if (!empty($sunrise) || !empty($sunset)):
            ?>
            <div class="mptsi-sun">
                <div class="mptsi-sun-item">
                    <span>🌅</span>
                    <span><strong>Sunrise</strong> <?php echo esc_html($sunrise); ?></span>
                </div>
                <div class="mptsi-sun-item">
                    <span><strong>Sunset</strong> <?php echo esc_html($sunset); ?></span>
                    <span>🌇</span>
                </div>
            </div>
            <?php endif; ?>
            
            <a href="https://www.masjidiapp.com/IqamaCalculator/Customized_UI_Yearly-3.htm?download=1&masjid_id=<?php echo urlencode($masjid_id_1); ?>" class="mptsi-link<?php echo empty($ramadan_timetable_url) ? ' mptsi-link-last' : ''; ?>" target="_blank">
                📅 View Monthly Prayer Schedule
            </a>
            <?php if (!empty($ramadan_timetable_url)): ?>
            <a href="<?php echo esc_url($ramadan_timetable_url); ?>" class="mptsi-link-ramadan" target="_blank">
                🌙 View Ramadan Timetable
            </a>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
}
add_shortcode("masjidi_prayer_times", "mptsi_masjidi_prayer_times_shortcode");

/**
 * WordPress Widget Class
 */
class MPSTI_wpb_widget extends WP_Widget {
    
    function __construct() {
        parent::__construct(
            "MPSTI_wpb_widget",
            esc_html__("Prayer Times Widget", "masjidi"),
            array("description" => esc_html__("Display prayer times from Masjidi API", "masjidi"))
        );
    }

    public function widget($args, $instance) {
        $title = apply_filters("widget_title", $instance["title"] ?? '');
        
        echo wp_kses_post($args["before_widget"]);
        if (!empty($title)) {
            echo wp_kses_post($args["before_title"]) . esc_html($title) . wp_kses_post($args["after_title"]);
        }
        
        echo do_shortcode('[masjidi_prayer_times]');
        
        echo wp_kses_post($args["after_widget"]);
    }

    public function form($instance) {
        $title = $instance["title"] ?? __("Prayer Times", "masjidi");
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id("title")); ?>">
                <?php esc_html_e("Title:", "masjidi"); ?>
            </label>
            <input class="widefat" 
                   id="<?php echo esc_attr($this->get_field_id("title")); ?>" 
                   name="<?php echo esc_attr($this->get_field_name("title")); ?>" 
                   type="text" 
                   value="<?php echo esc_attr($title); ?>" />
        </p>
        <p><small><?php esc_html_e("Configure masjid IDs in Settings → Masjidi.", "masjidi"); ?></small></p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        return array("title" => !empty($new_instance["title"]) ? strip_tags($new_instance["title"]) : "");
    }
}

// Register widget
add_action("widgets_init", function() {
    register_widget("MPSTI_wpb_widget");
});

// Legacy shortcode support
add_shortcode("single_view_calendar", function() {
    return do_shortcode('[masjidi_prayer_times]');
});
