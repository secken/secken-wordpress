<?php

/**
 * 洋葱网授权类 v1.0
 * @author me@sanliang.org
 */
class yangcong_admin extends yangcong {

    public static function init() {
        if (is_user_logged_in() && isset($_POST['uuid'])) {
            nocache_headers();
            if (isset($_GET['cancel'])) {
                $info = self::getResult($_POST['uuid']);
                if (!empty($info['userid']) && $info['userid'] === self::getUserMeta(wp_get_current_user()->ID)) {
                    self::delUserMeta(wp_get_current_user()->ID) ? self::success('取消成功', 'profile.php?page=yangcong-profile') : self::failure('取消失败');
                } else {
                    self::failure(self::get_message());
                }
            } else {
                $info = self::getResult($_POST['uuid']);
                if (!empty($info['userid'])) {
                    self::updateUserMeta(wp_get_current_user()->ID, $info['userid']) ? self::success('绑定成功', $_SERVER['REQUEST_URI']) : self::failure('绑定失败');
                } else {
                    self::failure(self::get_message());
                }
            }
        }
        add_action('admin_menu', array('yangcong_admin', 'add_plugin_page'));
        add_action('admin_init', array('yangcong_admin', 'page_init'));
        self::_login();
    }

    public static function _login() {
        if (is_user_logged_in()) {

        }
    }

    public static $options;

    public static function add_plugin_page() {
        add_options_page('洋葱网授权', '洋葱网授权', 'manage_options', 'yangcong_admin', array('yangcong_admin', 'create_admin_page'));


        //add_filter('user_contactmethods', array('yangcong_admin', 'profile'));

        add_submenu_page(
                'profile.php', '我的洋葱网授权', '我的洋葱网授权', 'level_0', 'yangcong-profile', array('yangcong_admin', 'yangcong_page')
        );
    }

    public static function yangcong_page() {


//getUserMeta(wp_get_current_user()->ID)




        wp_register_script('yangcong_login', YANGCONG__PLUGIN_URL . 'js/yangcong_login.js', array('jquery'), YANGCONG_VERSION);
        wp_enqueue_script('yangcong_login');
        if (isset($_GET['cancel'])) {
            $binding = self::verifyOneClick(self::getUserMeta(wp_get_current_user()->ID));
        } else {
            $binding = self::getBindingCode();
            $url = get_permalink();
            $authPage = self::authPage($url);
        }
        ?>
        <div class="wrap">
            <h2>我的洋葱网授权</h2>
            <form method="post">
                <h2>绑定或重新绑定洋葱网授权</h2>
                <?php
                if (!isset($_GET['cancel'])) {
                    print '<p style="text-align: center;"><img width="300px" height="300px" src="' . $binding['qrcode_url'] . '"></p>';
                    print '<p style="text-align: center;" id="code_message">请扫描二维码授权</p>';
                    print '<p style="text-align: center;">手机无法连接网络?请<a href="' . $authPage . '">点击这里</a>或<a href="profile.php?page=yangcong-profile">重试</a></p>';
                    if (self::getUserMeta(wp_get_current_user()->ID)) {
                        print '<a href="profile.php?page=yangcong-profile" class="button button-primary">绑定重试</a> <a href="profile.php?page=yangcong-profile&cancel=true" class="button button-primary">取消绑定</a>';
                    } else {
                        print '<a href="profile.php?page=yangcong-profile" class="button button-primary">绑定重试</a>';
                    }
                } else {
                    ?>
                    <table class="form-table">
                        <tbody>
                            <tr class="user-first-name-wrap">
                                <th><label for="first_name"></label></th>
                                <td><p id="code_message">等待取消</p></td>
                            </tr>

                            <tr class="user-first-name-wrap">
                                <th><label for="first_name"></label></th>
                                <td><a href="profile.php?page=yangcong-profile&cancel=true" class="button button-primary">重试</a></td>
                            </tr>


                        </tbody>
                    </table>
                    <?php
                    //submit_button('提交取消');
                }
                ?>
            </form>
        </div>
        <?php
        print <<<EOF
<script type="text/javascript">
var yangcong_uuid="{$binding['uuid']}",yangcong_login_url=location.href;
</script>
EOF;
    }

    public static function create_admin_page() {

        self::$options = get_option('yangcong');
        ?>
        <div class="wrap">
            <h2>洋葱网授权</h2>
            <form method="post" action="options.php">
                <?php
                settings_fields('yangcong_group');
                do_settings_sections('yangcong_admin');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public static function page_init() {
        register_setting(
                'yangcong_group', // Option group
                'yangcong', // Option name
                array('yangcong_admin', 'sanitize') // Sanitize
        );

        add_settings_section(
                'setting_section_id', // ID
                '洋葱网授权配制', // Title
                array('yangcong_admin', 'print_section_info'), // Callback
                'yangcong_admin' // Page
        );

        add_settings_field(
                'appid', // ID
                '应用id', // Title
                array('yangcong_admin', 'appid_callback'), // Callback
                'yangcong_admin', // Page
                'setting_section_id' // Section
        );

        add_settings_field(
                'appkey', '应用Key', array('yangcong_admin', 'appkey_callback'), 'yangcong_admin', 'setting_section_id'
        );
        add_settings_field(
                'webauthcode', 'web授权code', array('yangcong_admin', 'webauthcode_callback'), 'yangcong_admin', 'setting_section_id'
        );
    }

    public static function sanitize($input) {
        $new_input = array();
        if (isset($input['appid']))
            $new_input['appid'] = sanitize_text_field($input['appid']);

        if (isset($input['appkey']))
            $new_input['appkey'] = sanitize_text_field($input['appkey']);

        if (isset($input['webauthcode']))
            $new_input['webauthcode'] = sanitize_text_field($input['webauthcode']);

        return $new_input;
    }

    public static function print_section_info() {
        print '欢迎洋葱网授权配制';
    }

    public static function appid_callback() {
        printf(
                '<input type="text" id="appid" name="yangcong[appid]" value="%s" />', isset(self::$options['appid']) ? esc_attr(self::$options['appid']) : ''
        );
    }

    public static function appkey_callback() {
        printf(
                '<input type="text" id="appkey" name="yangcong[appkey]" value="%s" />', isset(self::$options['appkey']) ? esc_attr(self::$options['appkey']) : ''
        );
    }

    public static function webauthcode_callback() {
        printf(
                '<input type="text" id="webauthcode" name="yangcong[webauthcode]" value="%s" />', isset(self::$options['webauthcode']) ? esc_attr(self::$options['webauthcode']) : ''
        );
    }

}
