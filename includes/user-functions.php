<?php
function aiohm_get_user_arm_id($user_id = null) {
    if (!$user_id) $user_id = get_current_user_id();
    return get_user_meta($user_id, '_aiohm_app_arm_user_id', true);
}

function aiohm_store_user_arm_id($user_id, $arm_id) {
    update_user_meta($user_id, '_aiohm_app_arm_user_id', $arm_id);
}

function aiohm_set_access_level($user_id, $level = 'basic') {
    update_user_meta($user_id, 'aiohm_knowledge_profile', [
        'access_level' => $level,
        'source' => 'aiohm_app'
    ]);
}