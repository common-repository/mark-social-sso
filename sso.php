<?php 

/**
 * Plugin Name: Mark Social SSO
 * Description: 适用于微信登陆，QQ登陆，微博登陆，抖音登陆。退休程序猿马克为中国主流媒体开放平台开发的SSO单点登陆插件
 * Plugin URI: http://markchen.me/works
 * Author: Mark
 * Author URI: http://markchen.me
 * Version: 1.0.0
 * License: GPL2
 * Text Domain: mark
 * Domain Path: lang
 */

if ( ! defined( 'ABSPATH' ) )  die( 'Invalid request.' );

define('MARK_SOCIAL_SSO_VERION', "1.0.0");
define('MARK_SOCIAL_SSO_PLUGIN_URL', plugins_url('', __FILE__));
define('MARK_SOCIAL_SSO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MARK_SOCIAL_SSO_PLUGIN_FILE',  __FILE__);

include MARK_SOCIAL_SSO_PLUGIN_DIR . "admin/settings.php";
include MARK_SOCIAL_SSO_PLUGIN_DIR . "includes/class-basic.php";
include MARK_SOCIAL_SSO_PLUGIN_DIR . "includes/class-dy.php";
include MARK_SOCIAL_SSO_PLUGIN_DIR . "includes/class-wx.php";
include MARK_SOCIAL_SSO_PLUGIN_DIR . "includes/class-qq.php";
include MARK_SOCIAL_SSO_PLUGIN_DIR . "includes/class-wb.php";


add_action( 'login_enqueue_scripts', 'mksso_login_enqueue_scripts' );
function mksso_login_enqueue_scripts() {
	wp_enqueue_style( 'mksso-css', MARK_SOCIAL_SSO_PLUGIN_URL . '/mksso.css', ['login'] );
}



add_action("parse_request", function(){
	$mksso = isset($_GET['mksso']) ? sanitize_text_field($_GET['mksso']) : false;
	if(in_array( $mksso, ['wx', 'qq', 'wb', 'dy'] )){
		$code = isset($_GET['code']) ? sanitize_text_field($_GET['code']) : false;
		$state = isset($_GET['state']) ? sanitize_text_field($_GET['state']) : false;	
		if($code){
			if( $mksso == 'wb'){
				$check = true;

			}else if( $state ){
				$check = wp_verify_nonce($state,  $mksso . "nonce" );
			}else{
				wp_die("access Denied", "error");
			}
			
			if($check){
				$model = "OAuth_".strtoupper( $mksso );
				$app   = get_option('mksso_'.$mksso);
				$model = new $model( $app['appid'], $app['secret'] );
				$res   = $model->code_to_openid($code);
				if(is_wp_error($res)) wp_die('登陆失败，请重试');
				if($res && isset($res['openid']) ){
					$user_id = $model->get_user_id( $mksso, $res['openid'] );

					if(is_numeric($user_id) && $user_id){
						$user = get_user_by('ID', $user_id );
						wp_set_current_user( $user_id , $user->user_login);
					    wp_set_auth_cookie( $user_id );
					    do_action( 'wp_login', $user->user_login );
						wp_safe_redirect(  admin_url() );
						exit;
					}else{
						wp_die('登陆失败了', '错误提示');	
					}
					
				}else{
					wp_die('登陆失败', '错误提示');
				}
			}
			exit;	
		}
	}
});


function mark_social_sso_request($url, $args = []){
	
	if( isset($args['body']) && !empty($args['body']) ){
		$res = wp_remote_post($url, $args);
	}else{
		$res = wp_remote_request($url, $args);
	}
	
	if(is_wp_error( $res )) return $res;

	$body = wp_remote_retrieve_body($res);
	
	$data = json_decode($body, true);

	return $data;	
}



add_action('login_form', function( ){

	$cb_url   = urlencode(admin_url());
	$app_dy = get_option('mksso_dy');
	$dy_state = wp_create_nonce('dynonce');
	$dy_url = 'https://open.douyin.com/platform/oauth/connect?client_key='.$app_dy['appid'].'&response_type=code&scope=user_info&state='.$dy_state.'&redirect_uri='. $cb_url;

	$app_qq = get_option('mksso_qq');
	$qq_state = wp_create_nonce('qqnonce');
	$qq_url = 'https://graph.qq.com/oauth2.0/authorize?response_type=code&client_id='.$app_qq['appid'].'&redirect_uri='.$cb_url.'&state='.$qq_state.'&scope=get_user_info';

	$app_wx = get_option('mksso_wx');
	$wx_state = wp_create_nonce('wxnonce');
	$wx_url = 'https://open.weixin.qq.com/connect/qrconnect?appid='.$app_wx['appid'].'&redirect_uri='.$cb_url.'&response_type=code&scope=SCOPE&state='.$wx_state.'#wechat_redirect';

	$app_wb = get_option('mksso_wb');
	$wb_state = wp_create_nonce('wbnonce');
	$cb_url = home_url().'/?mksso=wb';
	$wb_url = 'https://api.weibo.com/oauth2/authorize?client_id='.$app_wb['appid'].'&response_type=code&redirect_uri='.$cb_url;

	$str = '<div class="sso-items">';
	
	if(isset($app_wx['status']) && $app_wx['status'] )
		$str .= '<div class="sso-item sso-weixin"><a href="'.$wx_url.'">微信登陆</a></div>';
	
	if(isset($app_qq['status']) && $app_qq['status'] )
		$str .= '<div class="sso-item sso-qq"><a href="'.$qq_url.'">QQ登陆</a></div>';
	
	if(isset($app_wb['status']) && $app_wb['status'] )
		$str .= '<div class="sso-item sso-weibo"><a href="'.$wb_url.'">微博登陆</a></div>';
	
	if(isset($app_dy['status']) && $app_dy['status'] )
		$str .= '<div class="sso-item sso-douyin"><a href="'.$dy_url.'">抖音登陆</a></div>';
	
	$str .= '</div>';

	echo $str;
});