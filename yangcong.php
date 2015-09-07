<?php

/*
  Plugin Name: 洋葱授权
  Plugin URI: http://www.yangcong.com
  Description: 原DNSPod创始人吴洪声，第二次创业起航，倾情打造划时代产品，带领豪华技术团队，为用户提供移动互联网时代的全新账号安全体系
  Version: 2.0
  Author: support@secken.com
  Author URI: http://www.yangcong.com
  License: A "Slug" license name e.g. GPL2
 */
/*  Copyright 2015  洋葱授权  (email:support@secken.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
if (!function_exists('add_action')) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}
define('YANGCONG_VERSION', '2.0.0');
define('YANGCONG__PLUGIN_URL', plugin_dir_url(__FILE__));
define('YANGCONG__PLUGIN_DIR', plugin_dir_path(__FILE__));

//加载语言包
load_plugin_textdomain( 'yangcong', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

require_once( YANGCONG__PLUGIN_DIR . 'yangcong_function.php');       //放置共有方法
require_once( YANGCONG__PLUGIN_DIR . 'secken.class.php' );           //洋葱授权sdk

//实例化洋葱授权类
$options = get_option('yangcong_basic');
$app_id  = isset($options['app_id']) ? $options['app_id'] : '';
$app_key = isset($options['app_key']) ? $options['app_key'] : '';
$auth_id = isset($options['web_auth_code']) ? $options['web_auth_code'] : '';

$secken = new secken($app_id, $app_key, $auth_id);


require_once( YANGCONG__PLUGIN_DIR . 'yangcong_login.php');         //洋葱授权登录渲染

if(is_admin()){
    require_once(YANGCONG__PLUGIN_DIR . 'yangcong_admin.php');     //后台菜单选项
}
