<?php 
if ( ! defined( 'ABSPATH' ) )  die( 'Invalid request.' );
/**
 * Oauth
 */
class OAuth_DY
{
	private $appid;
	private $secret;
	
	const ACCESS_TOKEN = 'https://open.douyin.com/oauth/access_token/';
	const USER_INFO = 'https://open.douyin.com/oauth/userinfo/';

	function __construct($appid, $secret)
	{
		$this->appid = $appid;	
		$this->secret = $secret;
	}


	function code_to_openid($code){
		$args = [
			'client_key'    => $this->appid,
			'client_secret' => $this->secret,
			'code'          => $code,
			'grant_type'    => 'authorization_code'
		];
		$url = add_query_arg($args, self::ACCESS_TOKEN);

		$data = mark_social_sso_request($url);
		
		if(is_wp_error( $data )) return $data;

		if( isset( $data['message'] ) && $data['message'] == 'success' ){
			$data = $data['data'];

			wp_cache_set( $data['access_token'] , $data['open_id'], 'dy_access_token_list', $data['expires_in'] );
			wp_cache_set( $data['access_token'] , $data['refresh_token'], 'dy_access_token_list', $data['expires_in'] );
			wp_cache_set( $data['open_id'] , $data['access_token'], 'dy_access_token_list', $data['expires_in'] );
			wp_cache_set( $data['open_id'] , $data['refresh_token'], 'dy_access_token_list', $data['expires_in'] );

			return [
				'expires_in'	=> $data['expires_in'],
				'access_token'	=> $data['access_token'],
				'openid'		=> $data['open_id'],
				'refresh_token'	=> $data['refresh_token'],
			];

		}else{
			return new WP_Error( $data['data']['error_code'], $data['data']['description'] );
		}

	}


	static function get_user_info($openid){

		$access_token = wp_cache_get( $openid , "dy_access_token_list" );
		
		if($access_token == false) return false;

		$args = [ 'access_token' => $access_token, 'open_id' => $openid ];	
		$url  = add_query_arg( $args  , self::USER_INFO) ;
		$user = mark_social_sso_request($url);
		
		if( is_array($user) && isset($user['data']) && isset( $user['data']['open_id']) ){
			return [
				'openid'   => $user['data']['open_id'],
				'nickname' => $user['data']['nickname'],
				'avatar'   => $user['data']['avatar']
			];
		}else{
			return false;
		}

	}


}