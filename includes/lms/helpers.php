<?php

if(!defined('ABSPATH')) exit;



/**
 * Convert Persian/Arabic digits to latin digits
 */
function nasim_latin_digits($value) {

    return strtr((string) $value, [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ]);
}


/**
 * Split a textarea value into non-empty trimmed lines
 */
function nasim_split_lines($value) {

    return array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $value))));
}


/**
 * Format a price in Toman with Persian digits
 */
function nasim_format_price($toman) {

    return nasim_fa_digits(number_format((int) $toman)) . ' تومان';
}


/**
 * Gregorian to Jalali (Solar Hijri) conversion
 */
function nasim_gregorian_to_jalali($gy, $gm, $gd) {

    $g_d_m = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
    $gy2   = ($gm > 2) ? ($gy + 1) : $gy;
    $days  = 355666 + (365 * $gy) + intdiv($gy2 + 3, 4) - intdiv($gy2 + 99, 100) + intdiv($gy2 + 399, 400) + $gd + $g_d_m[$gm - 1];
    $jy    = -1595 + (33 * intdiv($days, 12053));
    $days %= 12053;
    $jy   += 4 * intdiv($days, 1461);
    $days %= 1461;

    if ($days > 365) {
        $jy  += intdiv($days - 1, 365);
        $days = ($days - 1) % 365;
    }

    if ($days < 186) {
        $jm = 1 + intdiv($days, 31);
        $jd = 1 + ($days % 31);
    } else {
        $jm = 7 + intdiv($days - 186, 30);
        $jd = 1 + (($days - 186) % 30);
    }

    return [$jy, $jm, $jd];
}


/**
 * Jalali date (Y/m/d, Persian digits) of a unix timestamp in the site timezone
 */
function nasim_jdate($timestamp, $with_time = false) {

    $date = (new DateTimeImmutable('@' . (int) $timestamp))->setTimezone(wp_timezone());

    [$jy, $jm, $jd] = nasim_gregorian_to_jalali((int) $date->format('Y'), (int) $date->format('n'), (int) $date->format('j'));

    $output = sprintf('%04d/%02d/%02d', $jy, $jm, $jd);

    if ($with_time) {
        $output .= ' - ' . $date->format('H:i');
    }

    return nasim_fa_digits($output);
}


/**
 * Parse "m:ss" or "h:mm:ss" (latin or Persian digits) into seconds
 */
function nasim_parse_duration($text) {

    $text = trim(nasim_latin_digits($text));

    if (!preg_match('/^(\d+):(\d{1,2})(?::(\d{1,2}))?$/', $text, $parts)) {
        return 0;
    }

    if (isset($parts[3])) {
        return ((int) $parts[1] * 3600) + ((int) $parts[2] * 60) + (int) $parts[3];
    }

    return ((int) $parts[1] * 60) + (int) $parts[2];
}


/**
 * Human readable total duration, e.g. "حدود ۵۵ دقیقه" or "۲ ساعت و ۱۰ دقیقه"
 */
function nasim_format_total_duration($seconds) {

    $minutes = (int) round($seconds / 60);

    if ($minutes < 1) {
        return '';
    }

    if ($minutes < 60) {
        return 'حدود ' . nasim_fa_digits($minutes) . ' دقیقه';
    }

    $hours   = intdiv($minutes, 60);
    $minutes = $minutes % 60;

    return nasim_fa_digits($hours) . ' ساعت' . ($minutes ? ' و ' . nasim_fa_digits($minutes) . ' دقیقه' : '');
}


/**
 * Small inline lock icon used for locked lessons
 */
function nasim_lock_icon() {

    return '<svg class="lock-icon" viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="11" width="14" height="10" rx="2"></rect><path d="M8 11V8a4 4 0 0 1 8 0v3"></path></svg>';
}
