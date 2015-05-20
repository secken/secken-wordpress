<?php

/**
 * 洋葱网授权类 v2.0
 * @author me@sanliang.org
 */
class yangcong {

	/**
	 * 应用id
	 * @var string
	 */
	public static $APP_ID = 'GgxRmTDu2ntGvEskS2npG4kqMZRpWZNv';

	/**
	 * 应用Key
	 * @var string
	 */
	public static $APP_KEY = 'SEZXRTBVTPcrTzX6HqOY';

	/**
	 * web授权code
	 * @var string
	 */
	public static $WEBAUTHCODE = 'uMGWRULNxggi9XATNk6I';

	/**
	 * 获取绑定二维码
	 * @var string
	 */
	public static $_GetBindingCode = 'https://api.yangcong.com/v2/qrcode_for_binding';

	/**
	 * 获取登录二维码
	 * @var string
	 */
	public static $_GetLoginCode = 'https://api.yangcong.com/v2/qrcode_for_auth';

	/**
	 * 查询UUID事件结果
	 * @var string
	 */
	public static $_GetResult = 'https://api.yangcong.com/v2/event_result';

	/**
	 * 一键认证
	 * @var string
	 */
	public static $_VerifyOneClick = 'https://api.yangcong.com/v2/realtime_authorization';

	/**
	 * 动态码验证
	 * @var string
	 */
	public static $_VerifyOTP = 'https://api.yangcong.com/v2/offline_authorization';

	/**
	 * 洋葱网授权页
	 * @var string
	 */
	public static $_AuthPage = 'https://auth.api.yangcong.com/v2/auth_page';

	/**
	 * 错误码
	 * @var array
	 */
	public static $_error_code = array(
		200 => '请求成功',
		400=>'请求参数格式错误',
		401=>'动态码过期',
		402=>'app_id错误',
		403=>'请求签名错误',
		404=>'请你API不存在',
		405=>'请求方法错误',
		406=>'不在应用白名单里',
		407=>'30s离线验证太多次，请重新打开离线验证页面',
		500=>'洋葱系统服务错误',
		501=>'生成二维码图片失败',
		600=>'动态验证码错误',
		601=>'用户拒绝授权',
		602=>'等待用户响应超时，可重试',
		603=>'等待用户响应超时，不可重试',
		604=>'用户不存在'
	);
	private static $_message;
	public static $options;

	public static function init() {
		self::$options = get_option('yangcong');

		if (isset(self::$options['appid'])) {
			self::$APP_ID = self::$options['appid'];
		}

		if (isset(self::$options['appkey'])) {
			self::$APP_KEY = self::$options['appkey'];
		}

		if (isset(self::$options['webauthcode'])) {
			self::$WEBAUTHCODE = self::$options['webauthcode'];
		}

		self::login();
		add_action('login_form', array('yangcong', 'login_form'));

	}

	public static function login() {
		global $wpdb;
		if (!empty($_GET['redirect_to']) && $_GET['redirect_to'] === 'yangcong_login' && isset($_POST['uuid'])) {
			$info = self::getResult($_POST['uuid']);
			if (!empty($info['userid'])) {
				$author_id = $wpdb->get_var("SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'yangcong_uid' AND meta_value ='{$info['userid']}'");
				if (is_numeric($author_id)) {
					wp_clear_auth_cookie();
					wp_set_auth_cookie($author_id, true, is_ssl());
					wp_set_current_user($author_id);
					self::success('登录成功', (isset($_POST['redirect_to']) ? $_POST['redirect_to'] : null));
				} else {
					self::failure('登录失败');
				}
			} else {
				self::failure(self::get_message());
			}
		}
	}

	public static function login_form() {

		//if (!empty($_GET['redirect_to']) && $_GET['redirect_to'] === 'yangcong_login') {
		wp_register_script('yangcong_login', YANGCONG__PLUGIN_URL . 'js/yangcong_login.js', array('jquery'), YANGCONG_VERSION);
		wp_enqueue_script('yangcong_login');
		$loginCode = self::getLoginCode();
		$login_url = wp_login_url('yangcong_login');
		$authPage = self::authPage($login_url);
		if (is_array($loginCode)) {
			//echo 11;exit;
			print '<div id="yangcong_login" style="display:none">';
			print '<p style="text-align: center;"><img width="100%" src="' . $loginCode['qrcode_url'] . '"></p>';
			print '<p style="text-align: center;" id="code_message">请扫描二维码授权</p>';
			print '<p style="text-align: center;">手机无法连接网络?请<a href="' . $authPage . '">点击这里</a>或<a href="javascript:;" onclick="jQuery(this).parent().parent().hide();jQuery(\'#yangcong_login_bt\').fadeToggle();jQuery(\'#loginform p\').show();">账号登录</a></p>';
			print <<<EOF
<script type="text/javascript">
var yangcong_uuid="{$loginCode['uuid']}",yangcong_login_url="{$login_url}";
</script>
EOF;
			print '<br/></div>';

			// } else {

			print '<p id="yangcong_login_bt"><a href="javascript:;" onclick="jQuery(this).parent().hide();jQuery(\'#loginform p\').hide();jQuery(\'#yangcong_login\').fadeToggle();"><img src="./wp-content/plugins/yangcong/image/login.jpg"  alt="洋葱扫一扫登录" /></a></p>';
		}else{
			echo 22;
		}
	}

	/**
	 * 获取绑定二维码
	 * @return array
	 * code      成功、错误码
	 * message   错误信息
	 * url       二维码地址
	 * uuid      事件id
	 */
	public static function getBindingCode() {
		// $result = self::_post(self::$_GetBindingCode, array('app_id' => self::$APP_ID, 'signature' => md5('app_id=' . self::$APP_ID . self::$APP_KEY)));
		$arr=array('app_id' => self::$APP_ID, 'signature' => md5('app_id=' . self::$APP_ID . self::$APP_KEY));
		$url=self::$_GetBindingCode."?app_id=".$arr['app_id']."&signature=".$arr['signature'];
	 	$result=self::_get($url);
		return $result;
	}

	/**
	 * 获取登录二维码
	 * @return array
	 * code      成功、错误码
	 * message   错误信息
	 * url       二维码地址
	 * uuid      事件id
	 */
	public static function getLoginCode() {
		// $result = self::_post(self::$_GetLoginCode, array('app_id' => self::$APP_ID, 'signature' => md5('app_id=' . self::$APP_ID . self::$APP_KEY)));
		$arr=array('app_id' => self::$APP_ID, 'signature' => md5('app_id=' . self::$APP_ID . self::$APP_KEY));
		$url=self::$_GetLoginCode."?app_id=".$arr['app_id']."&signature=".$arr['signature'];
	 	$result=self::_get($url);
		return $result;
	}

	/**
	 * 查询UUID事件结果
	 * @param string $uuid 事件id
	 * @return array
	 * code    成功、错误码
	 * message 错误信息
	 * userid  用户ID
	 */
	public static function getResult($uuid) {
		// $result = self::_post(self::$_GetResult, array('app_id' => self::$APP_ID, 'event_id' => $uuid, 'signature' => md5('app_id=' . self::$APP_ID . 'event_id=' . $uuid . self::$APP_KEY)));
		$arr=array('app_id' => self::$APP_ID, 'event_id' => $uuid, 'signature' => md5('app_id=' . self::$APP_ID . 'event_id=' . $uuid . self::$APP_KEY));
		$url=self::$_GetResult."?app_id=".$arr['app_id']."&signature=".$arr['signature']."&event_id=".$arr['event_id'];
		return $result;
	}

	/**
	 * 一键认证
	 * @param string $userid 用户ID
	 * @return array
	 * code    成功、错误码
	 * message 错误信息
	 * uuid    事件id
	 */
	public static function verifyOneClick($userid,$action=1) {
		$result = self::_post(self::$_VerifyOneClick, array('app_id' => self::$APP_ID, 'uid' => $userid, 'action_type' => $action, 'signature' => md5('action_type=' . $action . 'app_id=' . self::$APP_ID . 'uid=' . $userid . self::$APP_KEY)));
		return $result;
	}

	/**
	 * 动态码验证
	 * @param string $userid 用户ID
	 * @param string $dnum 6位数字
	 * @return array
	 * code    成功、错误码
	 * message 错误信息
	 */
	public static function verifyOTP($userid, $dnum) {
		$result = self::_post(self::$_VerifyOTP, array('app_id' => self::$APP_ID, 'uid' => $userid, 'dynamic_code' => $dnum, 'signature' => md5('app_id=' . self::$APP_ID . 'dynamic_code=' . $dnum . 'uid=' . $userid . self::$APP_KEY)));
		return $result;
	}

	public static function authPage($callback) {
		$time = time();
		$d['signature'] = md5('auth_id=' . self::$WEBAUTHCODE . 'timestamp=' . $time . 'callback=' . $callback . self::$APP_KEY);
		$d['auth_id'] = self::$WEBAUTHCODE;
		$d['timestamp'] = $time;
		$d['callback'] = $callback;

		return self::$_AuthPage . '?' . http_build_query($d);
	}

	/**
	 * 返回消息
	 * @return string
	 */
	public static function get_message() {
		return self::$_message;
	}

	public static function check_error($result) {
		self::$_message = (isset(self::$_error_code[$result['status']]) ? self::$_error_code[$result['status']] : NULL);
		return $result['status'] === 200 ? TRUE : FALSE;
	}

	public static function _post($url, $post = array()) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		//curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, TRUE);
		curl_setopt($curl, CURLOPT_USERAGENT, !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : FALSE);
		curl_setopt($curl, CURLOPT_HEADER, FALSE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_POST, TRUE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($curl);
		if (curl_errno($curl)) {
			//exit('Errno' . curl_error($curl));
			return NULL;
		}
		curl_close($curl);
		$result = (array) json_decode($result);
		return self::check_error($result) === TRUE ? $result : NULL;
	}
	public static function _get($url) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
		//curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, TRUE);
		curl_setopt($curl, CURLOPT_USERAGENT, !empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : FALSE);
		curl_setopt($curl, CURLOPT_HEADER, FALSE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_TIMEOUT, 30);
		curl_setopt($curl, CURLOPT_HEADER, 0);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		$result = curl_exec($curl);
		if (curl_errno($curl)) {
			//exit('Errno' . curl_error($curl));
			return NULL;
		}
		curl_close($curl);
		$result = (array) json_decode($result);
		return self::check_error($result) === TRUE ? $result : NULL;
	}

	/**
	 * 输入消息
	 * @param string $message 消息
	 * @param int $status 状态
	 * @param string $url URL
	 * @param int $time 时间
	 */
	public static function message($message, $status, $url, $time = 3) {
		$data['message'] = $message;
		$data['status'] = $status;
		$data['url'] = $url;
		$data['time'] = $time;
		header('Content-Type:application/json;charset=utf-8');
		exit(json_encode($data));
	}

	/**
	 * 返回成功
	 * @param string $message 消息
	 * @param string $url URL
	 */
	public static function success($message, $url = '', $time = 3) {
		self::message($message, 1, $url, $time);
	}

	/**
	 * 返回失败
	 * @param string $message 消息
	 * @param string $url URL
	 */
	public static function failure($message, $url = '') {
		self::message($message, 0, $url);
	}

	public static function updateUserMeta($userId, $metaValue) {
		return function_exists('update_user_meta') ? update_user_meta($userId, 'yangcong_uid', $metaValue) : update_usermeta($userId, 'yangcong_uid', $metaValue);
	}

	public static function getUserMeta($userId) {
		return function_exists('get_user_meta') ? get_user_meta($userId, 'yangcong_uid', true) : get_usermeta($userId, 'yangcong_uid');
	}

	public static function delUserMeta($userId) {
		return function_exists('delete_user_meta') ? delete_user_meta($userId, 'yangcong_uid') : delete_usermeta($userId, 'yangcong_uid');
	}

	public function userLogin($id, $uuid = NULL) {
		nocache_headers();
		if (!empty($uuid)) {
			$user = get_user_by('id', $id);

			self::updateUserMeta($user->ID, $uuid);

			wp_clear_auth_cookie();
			wp_set_auth_cookie($user->ID, true, is_ssl());
			wp_set_current_user($user->ID);

			if (isset($_GET['redirect_to'])) {
				// wordpress 采用的是redirect_to字段
				wp_redirect($_GET['redirect_to']);
				exit;
			}
		} else {
			if (isset($_GET['redirect_to']) && $_GET['redirect_to'] !== admin_url()) {
//	如果是在内容页登录，无论如何都把用户带回内容页
				wp_redirect($_GET['redirect_to']);
				exit;
			} else {

			}
		}
	}

}
