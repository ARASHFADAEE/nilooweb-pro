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
 * Full course access: active subscription, a manual grant for this course, or course editor
 */
function nasim_user_has_course_access($course_id, $user_id = null) {

    $user_id = $user_id ?? get_current_user_id();

    if (!$user_id) {
        return false;
    }

    return user_can($user_id, 'edit_post', $course_id)
        || nasim_has_active_subscription($user_id)
        || in_array((int) $course_id, nasim_get_course_grants($user_id), true);
}


function nasim_can_watch_lesson($course_id, $lesson, $user_id = null) {

    return !empty($lesson['is_free']) || nasim_user_has_course_access($course_id, $user_id);
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
 * Allowed markup for lesson video embeds: a single iframe
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
    ];
}


/**
 * Keep only the first HTTPS iframe of an embed code (drops scripts, wrappers and inline styles)
 */
function nasim_sanitize_embed($code) {

    $clean = wp_kses((string) $code, nasim_embed_allowed_html());

    if (!preg_match('/<iframe\b[^>]*>.*?<\/iframe>/is', $clean, $iframe)) {
        return '';
    }

    if (!preg_match('/\ssrc=["\']https:\/\//i', $iframe[0])) {
        return '';
    }

    return $iframe[0];
}
