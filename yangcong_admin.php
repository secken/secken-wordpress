<?php
//后台菜单
add_action('admin_menu', 'yangcong_admin_menu');
function yangcong_admin_menu(){
    $yangcong_name = apply_filters('yangcong_name', __('洋葱授权','yangcong'));
    //添加洋葱授权菜单项
    add_menu_page($yangcong_name, $yangcong_name, 'manage_options', 'yangcong_setting', 'yangcong_app_page', '');

    //添加设置二级菜单
    add_submenu_page('yangcong_setting',__('设置','yangcong').' &lsaquo; '.__('洋葱授权','yangcong'), __('设置','yangcong'), 'manage_options', 'yangcong_setting', 'yangcong_app_page');

    //添加我的洋葱二级菜单
    add_submenu_page('yangcong_setting', __('我的洋葱','yangcong').' &lsaquo; '.__('洋葱授权','yangcong'), __('我的洋葱','yangcong'), 'manage_options', 'yangcong_profile', 'yangcong_profile');
}

add_action( 'admin_init', 'yangcong_admin_init' );
function yangcong_admin_init() {
	yangcong_add_settings(yangcong_get_option_labels());
}

//我的洋葱
function yangcong_profile(){
    global $secken;
    wp_register_script('yangcong_login', YANGCONG__PLUGIN_URL . 'js/yangcong_login.js', array('jquery'), YANGCONG_VERSION);
    wp_enqueue_script('yangcong_login');
    if (isset($_GET['cancel'])) {
        $binding = $secken->realtimeAuth(getUserMeta(wp_get_current_user()->ID));
    } else {
        $binding = $secken->getBinding();
        $callback = get_permalink();
        $authPage = $secken->getAuthPage($callback);
    }
    ?>
    <div class="wrap">
        <h2 class="nav-tab-wrapper">
            <a class="nav-tab nav-tab-active" href='javascript:;' id="tab-title-yangcong-prifile"><?php echo _e('我的洋葱','yangcong');?></a>
        </h2>
        <form method="post">
            <h3><?php echo _e('请使用洋葱APP扫描下方二维码绑定或重新绑定洋葱网授权','yangcong');?></h3>
            <?php
            if (!isset($_GET['cancel'])) {
                print '<p style="text-align: center;"><img width="300px" height="300px" src="' . $binding['qrcode_url'] . '"></p>';
                print '<p style="text-align: center;" id="code_message">'.__('请使用洋葱APP扫描二维码','yangcong').'</p>';
            } else {
                ?>
                <table class="form-table">
                    <tbody>
                        <tr class="user-first-name-wrap">
                            <th><label for="first_name"></label></th>
                            <td><p id="code_message"><?php echo _e('等待取消','yangcong');?></p></td>
                        </tr>
                        <tr class="user-first-name-wrap">
                            <th><label for="first_name"></label></th>
                            <td><a href="profile.php?page=yangcong-profile&cancel=true" class="button button-primary"><?php echo _e('重试','yangcong')?></a></td>
                        </tr>
                    </tbody>
                </table>
                <?php
            }
            ?>
        </form>
    </div>
    <?php
    print <<<EOF
<script type="text/javascript">
var yangcong_uuid="{$binding['event_id']}",yangcong_login_url=location.href;
</script>
EOF;
}

add_action( 'admin_init', 'yangcong_bind' );
function yangcong_bind(){
    global $secken;
    if (is_user_logged_in() && isset($_POST['uuid'])) { //如果用户已经登录而且post uuid
        nocache_headers();
        if (isset($_GET['cancel'])) {
            $info = $secken->getResult($_POST['uuid']);
            if (!empty($info['uid']) && $info['uid'] === getUserMeta(wp_get_current_user()->ID)) {
                delUserMeta(wp_get_current_user()->ID) ? message(__('取消成功','yangcong'), 1, 'profile.php?page=yangcong-profile') : message(__('取消失败','yangcong'));
            } else {
                message($secken->getMessage());
            }
        } else {
            $info = $secken->getResult($_POST['uuid']);
            if (!empty($info['uid'])) {
                updateUserMeta(wp_get_current_user()->ID, $info['uid']) ? message(__('绑定成功','yangcong'), 1, $_SERVER['REQUEST_URI']) : message(__('绑定失败','yangcong'));
            } else {
                message($secken->getMessage());
            }
        }
    }
}

//接口配置页面
function yangcong_app_page(){
    settings_errors();
    $labels = yangcong_get_option_labels();
	yangcong_option_html($labels);

}

// 在选项页面添加设置
function yangcong_add_settings($labels){
	extract($labels);
	register_setting( $option_group, $option_name, $field_validate);

	$field_callback = empty($field_callback)? 'yangcong_option_field_callback' : $field_callback;
	if($sections){
		foreach ($sections as $section_name => $section) {
			add_settings_section( $section_name, $section['title'], $section['callback'], $option_page );

			$fields = isset($section['fields'])?$section['fields']:(isset($section['fields'])?$section['fields']:''); // 尼玛写错英文单词的 fallback

			if($fields){
				foreach ($fields as $field_name=>$field) {
					$field['option']	= $option_name;
					$field['name']		= $field_name;

					$field_title		= $field['title'];

					$field_title = '<label for="'.$field_name.'">'.$field_title.'</label>';

					add_settings_field(
						$field_name,
						$field_title,
						$field_callback,
						$option_page,
						$section_name,
						$field
					);
				}
			}
		}
	}
}

// 选项的每个字段回调函数，显示具体 HTML 结构
function yangcong_option_field_callback($field) {

	$field_name		= $field['name'];
	$field['key']	= $field_name;
	$field['name']	= $field['option'].'['.$field_name.']';

	$yangcong_option	= yangcong_get_option( $field['option'] );
	$field['value'] = (isset($yangcong_option[$field_name]))?$yangcong_option[$field_name]:'';

	echo yangcong_admin_get_field_html($field);
}

// 获取选项
function yangcong_get_option($option_name){
	$option = get_option( $option_name );
	if($option && !is_admin()){
		return $option;
	}else{
		$defaults = apply_filters($option_name.'_defaults', array());
		return wp_parse_args($option, $defaults);
	}
}

//获取对应的field html
function yangcong_admin_get_field_html($field){

	$key		= $field['key'];
	$name		= $field['name'];
	$type		= $field['type'];
	$value		= $field['value'];

	$class		= isset($field['class'])?$field['class']:'regular-text';
	$description= (!empty($field['description']))?( ($type == 'checkbox')? ' <label for="'.$key.'">'.$field['description'].'</label>':'<p>'.$field['description'].'</p>'):'';

	$title 	= isset($field['title'])?$field['title']:$field['name'];
	$label 	= '<label for="'.$key.'">'.$title.'</label>';

	switch ($type) {
		case 'text':
			$field_html = '<input name="'.$name.'" id="'. $key.'" type="'.$type.'"  value="'.esc_attr($value).'" class="'.$class.'" />';
			break;
		default:
			$field_html = '<input name="'.$name.'" id="'. $key.'" type="text"  value="'.esc_attr($value).'" class="'.$class.'" />';
			break;
	}

	return $field_html.$description;
}

function yangcong_option_html($labels, $title='', $type='default'){
	extract($labels);
	?>
	<div class="wrap">
		<h2 class="nav-tab-wrapper">
	        <?php foreach ( $sections as $section_name => $section) { ?>
	            <a class="nav-tab nav-tab-active" href='javascript:;' id="tab-title-<?php echo $section_name; ?>"><?php echo $section['title']; ?></a>
	        <?php } ?>
	    </h2>
		<form action="options.php" method="POST">
			<?php settings_fields( $option_group ); ?>
			<?php foreach ( $sections as $section_name => $section ) { ?>
	            <div id="tab-<?php echo $section_name; ?>" class="div-tab">
	                <?php yangcong_option_do_settings_section($option_page, $section_name); ?>
	            </div>
	        <?php } ?>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}

// 拷贝自 do_settings_sections 函数，用于 tab 显示选项。
function yangcong_option_do_settings_section($option_page, $section_name){
	global $wp_settings_sections, $wp_settings_fields;

	if ( ! isset( $wp_settings_sections[$option_page] ) )
		return;

	$section = $wp_settings_sections[$option_page][$section_name];

	if ( $section['title'] )
		echo "<h3>{$section['title']}</h3>\n";

	if ( $section['callback'] )
		call_user_func( $section['callback'], $section );

	if ( isset( $wp_settings_fields ) && isset( $wp_settings_fields[$option_page] ) && !empty($wp_settings_fields[$option_page][$section['id']] ) ){
		echo '<table class="form-table">';
		do_settings_fields( $option_page, $section['id'] );
		echo '</table>';
	}
}

/* 基本设置的字段 */
function yangcong_get_option_labels(){
	global $plugin_page;

	$sections	   =	array();
	$option_group  =   'yangcong_app_setting';
    $option_name = $option_page =   'yangcong_basic';
    $field_validate				=	'yangcong_basic_validate';

    if($plugin_page == 'yangcong_setting'){

	    $app_section_fields = array(
			'app_id'		 => array('title'=>__('洋葱AppID','yangcong'),	'type'=>'text',	'description'=>__('设置洋葱授权所需的 AppID，如果没申请，可不填！','yangcong')),
			'app_key'		 => array('title'=>__('洋葱AppKey','yangcong'),	'type'=>'text', 'description'=>__('设置洋葱授权所需的 AppKey，如果没申请，可不填！','yangcong')),
			'web_auth_code'  => array('title'=>__('洋葱Web授权码','yangcong'),'type'=>'text', 'description'=>__('设置洋葱Web授权码，如果没申请，可不填！','yangcong')),
		);

	    $sections = array(
		    'app' => array('title'=>__('接口设置','yangcong'), 'fields'=>$app_section_fields, 'callback'=>''),
		);

		$sections = apply_filters('yangcong_setting',$sections);

    }

	return compact('option_group','option_name','option_page','sections','field_validate');
}

function yangcong_basic_validate($yangcong_basic) {
	global $plugin_page;

	$current = get_option( 'yangcong_basic' );

	if($plugin_page == 'yangcong_setting'){
		if(empty($yangcong_basic['app_id'])){
			$yangcong_basic['app_id'] = $current['app_id'];
			add_settings_error('yangcong_basic', 'invalid-empty', __('洋葱AppId信息不能为空！','yangcong'));
		}

		if(empty($yangcong_basic['app_key'])){
			$yangcong_basic['app_key'] = $current['app_key'];
			add_settings_error('yangcong_basic', 'invalid-empty', __('洋葱AppKey信息不能为空!','yangcong') );
		}

        if(empty($yangcong_basicp['web_auth_code'])){
            $yangcong_basic['web_auth_code'] = $current['web_auth_code'];
            add_settings_error('yangcong_basic', 'invalid-empty', __('洋葱Web授权码不能为空','yangcong'));
        }
	}

	return wp_parse_args($yangcong_basic,$current);
}
