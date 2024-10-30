<?php 
if ( ! defined( 'ABSPATH' ) )  die( 'Invalid request.' );
/**
 * Oauth
 */
class OAuth_QQ
{
	private $appid;
	private $secret;
	
	const ACCESS_TOKEN = 'https://graph.qq.com/oauth2.0/token';
	const USER_OPENID = 'https://graph.qq.com/oauth2.0/me';
	const USER_INFO = 'https://graph.qq.com/user/get_user_info';


	function __construct($appid, $secret)
	{
		$this->appid = $appid;	
		$this->secret = $secret;
	}


	function code_to_openid($code){
		$args = [
			'client_id'    => $this->appid,
			'client_secret' => $this->secret,
			'code'          => $code,
			'grant_type'    => 'authorization_code'
		];

		$url = add_query_arg($args, self::ACCESS_TOKEN);

		$data = mark_social_sso_request($url);
		
		if(is_wp_error( $data )) return $data;

		if( isset( $data['access_token'], $data['expires_in'] ) ){
			$user = mark_social_sso_request( add_query_arg(['access_token' => $data['access_token']] , self::USER_OPENID) );
			if(is_wp_error($user)) return $user;
			if( !isset($user['openid']) || empty($user['openid']) )
				return new WP_Error('get_openid_fail', '无法返回用户信息');
			
			wp_cache_set( $data['access_token'] , $user['openid'], 'qq_access_token_list', $data['expires_in'] );
			wp_cache_set( $data['access_token'] , $data['refresh_token'], 'qq_access_token_list', $data['expires_in'] );
			wp_cache_set( $user['openid'] , $data['access_token'], 'qq_access_token_list', $data['expires_in'] );
			wp_cache_set( $user['openid'] , $data['refresh_token'], 'qq_access_token_list', $data['expires_in'] );

			return [
				'expires_in'	=> $data['expires_in'],
				'access_token'	=> $data['access_token'],
				'openid'		=> $user['openid'],
				'refresh_token'	=> $data['refresh_token'],
			];

		}else{
			return new WP_Error( $data['code'], $data['msg'] );
		}

	}


	static function get_user_info($openid){

		$access_token = wp_cache_get( $openid , "qq_access_token_list" );
		
		if($access_token == false) return false;
		$app   = get_option( 'mksso_qq');
		$appid = $app['appid'];
		$args  = [ 'access_token' => $access_token, 'oauth_consumer_key' => $appid, 'openid' => $openid ];	
		$url   = add_query_arg( $args  , self::USER_INFO) ;
		$user  = mark_social_sso_request($url);
		
		if( is_array($user) && isset($user['ret']) &&  $user['ret'] == 0  ){
			return [
				'openid'   => $openid,
				'nickname' => $user['nickname'],
				'avatar'   => $user['figureurl_qq_1']
			];
		}else{
			return false;
		}

	}


}