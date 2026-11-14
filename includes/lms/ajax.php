<?php

if(!defined('ABSPATH')) exit;



/**
 * Player actions for logged-in users: complete, bookmark, note
 */
function nasim_ajax_lesson_action() {

    check_ajax_referer('nasim_lesson', 'nonce');

    $course_id = absint($_POST['course_id'] ?? 0);
    $lesson_id = sanitize_key(wp_unslash($_POST['lesson_id'] ?? ''));
    $type      = sanitize_key(wp_unslash($_POST['type'] ?? ''));
    $lesson    = 'nasim_course' === get_post_type($course_id) ? nasim_find_lesson($course_id, $lesson_id) : null;

    if (!$lesson) {
        wp_send_json_error(['message' => 'درس پیدا نشد.'], 404);
    }

    if (!nasim_can_watch_lesson($course_id, $lesson)) {
        wp_send_json_error(['message' => 'به این درس دسترسی نداری.'], 403);
    }

    switch ($type) {

        case 'complete':
            $completed = nasim_toggle_user_lesson($course_id, 'progress', $lesson_id);
            wp_send_json_success(['active' => $completed] + nasim_course_progress($course_id));

        case 'bookmark':
            wp_send_json_success(['active' => nasim_toggle_user_lesson($course_id, 'bookmarks', $lesson_id)]);

        case 'note':
            $note = sanitize_textarea_field(wp_unslash($_POST['note'] ?? ''));
            nasim_save_lesson_note($course_id, $lesson_id, mb_substr($note, 0, 5000));
            wp_send_json_success();
    }

    wp_send_json_error(['message' => 'درخواست نامعتبر است.'], 400);
}
add_action('wp_ajax_nasim_lesson_action', 'nasim_ajax_lesson_action');
