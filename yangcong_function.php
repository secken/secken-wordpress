<?php

/**
 * ajax返回信息
 */
function message($message, $status=0, $url='', $time = 3) {
	$data['message'] = $message;
	$data['status'] = $status;
	$data['url'] = $url;
	$data['time'] = $time;
	header('Content-Type:application/json;charset=utf-8');
	exit(json_encode($data));
}


/**
 * 获取被绑定用户对应的洋葱ID
 */
function getUserMeta($userId) {
    return function_exists('get_user_meta') ? get_user_meta($userId, 'yangcong_uid', true) : get_usermeta($userId, 'yangcong_uid');
}

/**
 * 删除被绑定用户对应的洋葱ID
 */
function delUserMeta($userId) {
    return function_exists('delete_user_meta') ? delete_user_meta($userId, 'yangcong_uid') : delete_usermeta($userId, 'yangcong_uid');
}

/**
 * 修改被绑定用户对应的洋葱ID
 */
function updateUserMeta($userId, $metaValue) {
    return function_exists('update_user_meta') ? update_user_meta($userId, 'yangcong_uid', $metaValue) : update_usermeta($userId, 'yangcong_uid', $metaValue);
}
