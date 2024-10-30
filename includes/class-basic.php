<?php 

if ( ! defined( 'ABSPATH' ) )  die( 'Invalid request.' );

/**
 * OAuth Basic
 */

class OAuth_Basic
{
	
	function get_user_id( $type, $openid ){
	    $args = [
	        'meta_query' => [
	            ['key' => $type . "_openid", 'value' => $openid ]
	        ]
	    ];
	    $user_query = new WP_User_Query( $args );
	    $users = $user_query->get_results();
	    if(empty($users)){
	        $user_info = static::get_user_info($openid);
	        if($user_info){
	        	$userdata = [
					'user_login'    => $openid,
					'user_email'    => $openid."@".$type.".com",
					'user_nicename' => $user_info['nickname'],
					'display_name'  => $user_info['nickname'],
					'nickname'      => $user_info['nickname']
		        ];
		        $user_id = wp_insert_user($userdata);
		        if(is_numeric($user_id) && $user_id ) {
		        	add_user_meta($user_id, $type."_avatar", $user_info['avatar']);
		        	add_user_meta($user_id, $type."_openid", $openid);
		        	return $user_id;
		        }else{
		        	return false;
		        }
	        }else{
	        	return false;
	        }
	    }else{
	    	$user = $users[0];
	        return $user->ID;
	    }
	}

}