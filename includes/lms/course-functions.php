<?php

if(!defined('ABSPATH')) exit;



/**
 * Course curriculum: list of sections, each with its lessons
 *
 * Lesson keys: id, title, type, duration, description, overview, embed, is_free, resource_title, resource_url
 */
function nasim_get_curriculum($course_id) {

    $curriculum = get_post_meta($course_id, '_nasim_curriculum', true);

    return is_array($curriculum) ? $curriculum : [];
}


/**
 * Flat list of lessons with their section title and 1-based number
 */
function nasim_get_course_lessons($course_id) {

    static $cache = [];

    if (isset($cache[$course_id])) {
        return $cache[$course_id];
    }

    $lessons = [];

    foreach (nasim_get_curriculum($course_id) as $section) {
        foreach ($section['lessons'] ?? [] as $lesson) {
            $lesson['section'] = $section['title'] ?? '';
            $lesson['number']  = count($lessons) + 1;
            $lessons[]         = $lesson;
        }
    }

    return $cache[$course_id] = $lessons;
}


function nasim_find_lesson($course_id, $lesson_id) {

    foreach (nasim_get_course_lessons($course_id) as $lesson) {
        if ($lesson['id'] === $lesson_id) {
            return $lesson;
        }
    }

    return null;
}


function nasim_course_total_seconds($course_id) {

    return array_sum(array_map(function ($lesson) {
        return nasim_parse_duration($lesson['duration'] ?? '');
    }, nasim_get_course_lessons($course_id)));
}


/**
 * Player URL of a lesson, with or without pretty permalinks
 */
function nasim_lesson_url($course_id, $lesson_id) {

    $course_url = get_permalink($course_id);

    if (get_option('permalink_structure')) {
        return trailingslashit($course_url) . 'learn/' . rawurlencode($lesson_id) . '/';
    }

    return add_query_arg('learn', rawurlencode($lesson_id), $course_url);
}


/**
 * How a course is sold / unlocked.
 *
 * - free: open for everyone (no paywall)
 * - subscription: unlocked only with an active subscription
 * - purchase: must buy this course separately (Zibal plan / Spot)
 *
 * @return string free|subscription|purchase
 */
function nasim_course_access_mode($course_id) {

    $mode = sanitize_key((string) get_post_meta($course_id, '_nasim_course_access_mode', true));

    if (in_array($mode, ['free', 'subscription', 'purchase'], true)) {
        return $mode;
    }

    // Legacy inference before meta was set.
    $delivery = function_exists('nasim_course_delivery') ? nasim_course_delivery($course_id) : 'lms';
    $plan_id  = (int) get_post_meta($course_id, '_nasim_linked_plan_id', true);
    $price    = $plan_id ? (int) get_post_meta($plan_id, '_nasim_plan_price', true) : 0;

    if ('spot' === $delivery || $price > 0) {
        return 'purchase';
    }

    return 'subscription';
}


/**
 * Short badge / label for course cards.
 */
function nasim_course_access_label($course_id) {

    switch (nasim_course_access_mode($course_id)) {
        case 'free':
            return 'رایگان';
        case 'subscription':
            return 'رایگان با اشتراک';
        default:
            return 'خرید جداگانه';
    }
}


/**
 * CSS modifier for access badge.
 */
function nasim_course_access_badge_class($course_id) {

    return 'course-access-badge course-access-badge--' . nasim_course_access_mode($course_id);
}


/**
 * Full course access: mode-aware (subscription only unlocks subscription courses).
 */
function nasim_user_has_course_access($course_id, $user_id = null) {

    $user_id = $user_id ?? get_current_user_id();
    $mode    = nasim_course_access_mode($course_id);

    if ('free' === $mode) {
        return true;
    }

    if (!$user_id) {
        return false;
    }

    if (user_can($user_id, 'edit_post', $course_id)) {
        return true;
    }

    if (in_array((int) $course_id, nasim_get_course_grants($user_id), true)) {
        return true;
    }

    if ('subscription' === $mode && nasim_has_active_subscription($user_id)) {
        return true;
    }

    return false;
}


function nasim_can_watch_lesson($course_id, $lesson, $user_id = null) {

    return !empty($lesson['is_free']) || nasim_user_has_course_access($course_id, $user_id);
}


/**
 * Checkout / CTA URL for a locked course.
 */
function nasim_course_checkout_url($course_id) {

    $mode    = nasim_course_access_mode($course_id);
    $plan_id = (int) get_post_meta($course_id, '_nasim_linked_plan_id', true);

    if ('purchase' === $mode && $plan_id) {
        return nasim_subscription_url(['tab' => 'plans', 'highlight' => $plan_id, 'course' => $course_id]);
    }

    if ('subscription' === $mode) {
        return nasim_subscription_url(['tab' => 'plans']);
    }

    return get_permalink($course_id);
}


/**
 * Primary CTA label when the user lacks access.
 */
function nasim_course_cta_label($course_id) {

    switch (nasim_course_access_mode($course_id)) {
        case 'purchase':
            return 'خرید این دوره';
        case 'subscription':
            return 'خرید اشتراک و شروع';
        default:
            return 'شروع دوره';
    }
}


/**
 * Lesson ids stored in a per-course user list (progress / bookmarks), limited to lessons that still exist
 */
function nasim_get_user_lesson_list($course_id, $list, $user_id = null) {

    $user_id = $user_id ?? get_current_user_id();

    if (!$user_id) {
        return [];
    }

    $stored     = (array) get_user_meta($user_id, "_nasim_{$list}_{$course_id}", true);
    $lesson_ids = wp_list_pluck(nasim_get_course_lessons($course_id), 'id');

    return array_values(array_intersect($stored, $lesson_ids));
}


/**
 * Add or remove a lesson from a per-course user list; returns whether the lesson is now in the list
 */
function nasim_toggle_user_lesson($course_id, $list, $lesson_id, $user_id = null) {

    $user_id = $user_id ?? get_current_user_id();
    $items   = nasim_get_user_lesson_list($course_id, $list, $user_id);
    $exists  = in_array($lesson_id, $items, true);

    $items = $exists ? array_values(array_diff($items, [$lesson_id])) : array_merge($items, [$lesson_id]);

    update_user_meta($user_id, "_nasim_{$list}_{$course_id}", $items);

    return !$exists;
}


function nasim_course_progress($course_id, $user_id = null) {

    $total     = count(nasim_get_course_lessons($course_id));
    $completed = count(nasim_get_user_lesson_list($course_id, 'progress', $user_id));

    return [
        'completed' => $completed,
        'total'     => $total,
        'percent'   => $total ? (int) round(($completed / $total) * 100) : 0,
    ];
}


/**
 * First lesson the user has not completed yet (or the first lesson)
 */
function nasim_next_lesson_to_watch($course_id, $user_id = null) {

    $lessons   = nasim_get_course_lessons($course_id);
    $completed = nasim_get_user_lesson_list($course_id, 'progress', $user_id);

    foreach ($lessons as $lesson) {
        if (!in_array($lesson['id'], $completed, true)) {
            return $lesson;
        }
    }

    return $lessons[0] ?? null;
}


function nasim_get_lesson_note($course_id, $lesson_id, $user_id = null) {

    $user_id = $user_id ?? get_current_user_id();
    $notes   = (array) get_user_meta($user_id, "_nasim_notes_{$course_id}", true);

    return $notes[$lesson_id] ?? '';
}


function nasim_save_lesson_note($course_id, $lesson_id, $note, $user_id = null) {

    $user_id = $user_id ?? get_current_user_id();
    $notes   = (array) get_user_meta($user_id, "_nasim_notes_{$course_id}", true);

    if ('' === $note) {
        unset($notes[$lesson_id]);
    } else {
        $notes[$lesson_id] = $note;
    }

    update_user_meta($user_id, "_nasim_notes_{$course_id}", $notes);
}


/**
 * Allowed markup for lesson video embeds: iframe or self-hosted video
 */
function nasim_embed_allowed_html() {

    return [
        'iframe' => [
            'src'                   => true,
            'width'                 => true,
            'height'                => true,
            'title'                 => true,
            'allow'                 => true,
            'allowfullscreen'       => true,
            'webkitallowfullscreen' => true,
            'mozallowfullscreen'    => true,
            'frameborder'           => true,
            'referrerpolicy'        => true,
            'loading'               => true,
        ],
        'video'  => [
            'src'      => true,
            'controls' => true,
            'width'    => true,
            'height'   => true,
            'poster'   => true,
            'preload'  => true,
            'playsinline' => true,
            'style'    => true,
        ],
        'source' => [
            'src'  => true,
            'type' => true,
        ],
    ];
}


/**
 * Convert a bare Aparat / YouTube / direct MP4 URL into player markup.
 */
function nasim_normalize_lesson_embed($code) {

    $code = trim((string) $code);

    if ('' === $code) {
        return '';
    }

    if (preg_match('/^https?:\/\//i', $code) && !preg_match('/<[^>]+>/', $code)) {
        $url = esc_url_raw($code);
        if (preg_match('/aparat\.com\/(?:v\/|video\/video\/embed\/videohash\/)([A-Za-z0-9]+)/i', $url, $m)
            || preg_match('/aparat\.com\/v\/([A-Za-z0-9]+)/i', $url, $m)) {
            $code = '<iframe src="https://www.aparat.com/video/video/embed/videohash/' . rawurlencode($m[1]) . '/vt/frame" allowfullscreen="true"></iframe>';
        } elseif (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([A-Za-z0-9_-]+)/i', $url, $m)) {
            $code = '<iframe src="https://www.youtube.com/embed/' . rawurlencode($m[1]) . '" allowfullscreen="true" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"></iframe>';
        } elseif (preg_match('/\.(mp4|webm|ogg)(\?|$)/i', $url)) {
            $type = 'video/mp4';
            if (preg_match('/\.webm(\?|$)/i', $url)) {
                $type = 'video/webm';
            } elseif (preg_match('/\.ogg(\?|$)/i', $url)) {
                $type = 'video/ogg';
            }
            $code = '<video controls playsinline preload="metadata" style="width:100%;max-height:70vh"><source src="' . esc_url($url) . '" type="' . esc_attr($type) . '"></video>';
        } else {
            return '';
        }
    }

    return nasim_sanitize_embed($code);
}


/**
 * Keep the first HTTPS iframe or self-hosted video (drops scripts and wrappers)
 */
function nasim_sanitize_embed($code) {

    $clean = wp_kses((string) $code, nasim_embed_allowed_html());

    if (preg_match('/<iframe\b[^>]*>.*?<\/iframe>/is', $clean, $iframe)
        && preg_match('/\ssrc=["\']https:\/\//i', $iframe[0])) {
        return $iframe[0];
    }

    if (preg_match('/<video\b[^>]*>.*?<\/video>/is', $clean, $video)
        && preg_match('/\ssrc=["\']https:\/\//i', $video[0])) {
        return $video[0];
    }

    return '';
}
