<?php
/**
 *  洋葱授权插件嵌入
 */

add_action('init', 'login');
function login(){
	global $secken, $wpdb;
	if (!empty($_GET['redirect_to']) && $_GET['redirect_to'] === 'yangcong_login' && isset($_POST['uuid'])) {
		$info = $secken->getResult($_POST['uuid']);
		if (!empty($info['uid'])) {
			$author_id = $wpdb->get_var("SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'yangcong_uid' AND meta_value ='{$info['uid']}'");
			if (is_numeric($author_id)) {
				wp_clear_auth_cookie();
				wp_set_auth_cookie($author_id, true, is_ssl());
				wp_set_current_user($author_id);
				message(__('登录成功','yangcong'), 1, (isset($_POST['redirect_to']) ? $_POST['redirect_to'] : null));
			} else {
				message(__('登录失败','yangcong'));
			}
		} else {
			message($secken->getMessage());
		}
	}
}

//添加登录插件页面
add_action('login_form', 'login_form');
function login_form() {
	global $secken;
	wp_register_script('yangcong_login', YANGCONG__PLUGIN_URL . 'js/yangcong_login.js', array('jquery'), YANGCONG_VERSION);//注册js,异步发送
	wp_enqueue_script('yangcong_login');
	$loginCode = $secken->getAuth();
	$login_url = wp_login_url('yangcong_login');
	$authPage = $secken->getAuthPage($login_url);
	if (is_array($loginCode)) {
		print '<div id="yangcong_login" style="display:none">';
		print '<p style="text-align: center;"><img width="100%" src="' . $loginCode['qrcode_url'] . '"></p>';
		print '<p style="text-align: center;" id="code_message">'.__('请使用洋葱APP扫描二维码','yangcong').'</p>';
		print '<p style="text-align: center;">'.__('切换登录方式', 'yangcong').'，<a href="javascript:;" onclick="jQuery(this).parent().parent().hide();jQuery(\'#yangcong_login_bt\').fadeToggle();jQuery(\'#loginform > p\').show();">'.__('账号登录','yangcong').'</a></p>';
		print <<<EOF
<script type="text/javascript">
var yangcong_uuid="{$loginCode['event_id']}",yangcong_login_url="{$login_url}";
</script>
EOF;
		print '<br/></div>';
		print '<p id="yangcong_login_bt"><a href="javascript:;" onclick="jQuery(this).parent().hide();jQuery(\'#loginform > p\').hide();jQuery(\'#yangcong_login\').fadeToggle();"><img src="'.plugins_url('image/login.png',__FILE__).'"  alt="'.__('请使用洋葱APP扫描二维码', 'yangcong').'" /></a></p>';
	}
}
