<?php
if ( ! defined( 'ABSPATH' ) )  die( 'Invalid request.' ); 
/**
 * Oauth
 */

class OAuth_WB extends OAuth_Basic
{
	private $appid;
	private $secret;

	const ACCESS_TOKEN = 'https://api.weibo.com/oauth2/access_token';
	const USER_INFO = 'https://api.weibo.com/2/users/show.json';


	function __construct($appid, $secret)
	{
		$this->appid = $appid;	
		$this->secret = $secret;
	}


	
	function code_to_openid($code){
		$args = [
			'client_id'     => $this->appid,
			'client_secret' => $this->secret,
			'code'          => $code,
			'redirect_uri'  => home_url('/?mksso=wb'), 
			'grant_type'    => 'authorization_code'
		];

		$data = mark_social_sso_request( self::ACCESS_TOKEN, ['body' => $args ] );

		if(is_wp_error( $data )) return $data;

		if( isset( $data['access_token'], $data['uid'] ) ){
			
			wp_cache_set( $data['access_token'] , $data['uid'], 'wb_access_token_list', $data['expires_in'] );
			wp_cache_set( $data['uid'] , $data['access_token'], 'wb_access_token_list', $data['expires_in'] );
			
			return [
				'expires_in'	=> $data['expires_in'],
				'access_token'	=> $data['access_token'],
				'openid'		=> $data['uid']
			];

		}else{
			return new WP_Error( 'get_openid_fail', '无法正常获取openid' );
		}
	}



	static function get_user_info($openid){

		$access_token = wp_cache_get( $openid , "wb_access_token_list" );
		
		if($access_token == false) return false;

		$args = [ 'access_token' => $access_token, 'uid' => $openid ];	
		$url  = add_query_arg( $args  , self::USER_INFO) ;
		$user = mark_social_sso_request($url);
		
		if(is_array($user)){
			return [
				'openid'   => $user['idstr'],
				'nickname' => $user['name'],
				'avatar'   => $user['avatar_large']
			];
		}else{
			return false;
		}

	}

}