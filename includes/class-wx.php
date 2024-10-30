<?php 
if ( ! defined( 'ABSPATH' ) )  die( 'Invalid request.' );
/**
 * Oauth
 */
class OAuth_WX
{
	private $appid;
	private $secret;
	
	const ACCESS_TOKEN = 'https://api.weixin.qq.com/sns/oauth2/access_token';
	const USER_INFO = 'https://api.weixin.qq.com/sns/userinfo';

	function __construct($appid, $secret)
	{
		$this->appid = $appid;	
		$this->secret = $secret;
	}

	function code_to_openid($code){
		$args = [
			'appid'    => $this->appid,
			'secret' => $this->secret,
			'code'          => $code,
			'grant_type'    => 'authorization_code'
		];
		$url = add_query_arg($args, self::ACCESS_TOKEN);

		$data = mark_social_sso_request($url);
		
		if(is_wp_error( $data )) return $data;

		if( isset( $data['access_token'], $data['openid'] ) ){
			
			wp_cache_set( $data['access_token'] , $data['openid'], 'wx_access_token_list', $data['expires_in'] );
			wp_cache_set( $data['access_token'] , $data['refresh_token'], 'wx_access_token_list', $data['expires_in'] );
			wp_cache_set( $data['openid'] , $data['access_token'], 'wx_access_token_list', $data['expires_in'] );
			wp_cache_set( $data['openid'] , $data['refresh_token'], 'wx_access_token_list', $data['expires_in'] );

			return [
				'expires_in'	=> $data['expires_in'],
				'access_token'	=> $data['access_token'],
				'openid'		=> $data['openid'],
				'refresh_token'	=> $data['refresh_token'],
			];

		}else{
			return new WP_Error( $data['data']['error_code'], $data['data']['description'] );
		}
	}


	static function get_user_info($openid){

		$access_token = wp_cache_get( $openid , "wx_access_token_list" );
		
		if($access_token == false) return false;
		
		$args  = [ 'access_token' => $access_token, 'openid' => $openid ];	
		$url   = add_query_arg( $args  , self::USER_INFO) ;
		$user  = mark_social_sso_request($url);
		
		if( is_array($user) && isset($user['openid'], $user['nickname']) ){
			return [
				'openid'   => $openid,
				'nickname' => $user['nickname'],
				'avatar'   => $user['headimgurl']
			];
		}else{
			return false;
		}

	}


}