<?php 
if ( ! defined( 'ABSPATH' ) )  die( 'Invalid request.' );


add_action( "admin_menu", function(){
	add_submenu_page("options-general.php", "新媒体登陆设置", "新媒体登陆设置", "manage_options", "mark-social-sso", "mark_social_sso_page");
});


function mark_social_sso_page(){
?>
<div class="wrap mark-social-sso-page">
	<h1 class="wp-heading-inline">社交媒体登陆设置</h1>
	<hr class="wp-header-end">
  	<form method='post' action='options.php'>
      	<?php 
           settings_fields( 'mark_social_setting_group' );
           do_settings_sections( 'mksso' );
      	?>
       	<p class='submit'>
            <input name='submit' type='submit' id='submit' class='button-primary' value='<?php _e("Save Changes") ?>' />
       	</p>
  	</form>
</div>
<?php 
}


function section_callback($args){
    $allowed_tags = [
            'a' => array( 'href' => array(),'title' => array(), 'target' => array() ) 
        ];
    $cb = home_url('/?mksso=');
    switch ($args['id']) {
        case 'section_id_qq':
            $url = $cb . str_replace( "section_id_", "", $args['id']);
            echo wp_kses( '设置教程<a target="_blank" href="https://connect.qq.com/">点击这里</a>',$allowed_tags );    
            echo wp_kses( ', 回调链接为： <span><pre>'.$url.'</pre></span>',$allowed_tags );    
            break;
        case 'section_id_wx':
            $url = $cb . str_replace( "section_id_", "", $args['id']);
            echo wp_kses( '设置教程<a target="_blank" href="https://open.weixin.qq.com/">点击这里</a>', $allowed_tags );    
            echo wp_kses( ', 回调链接为： <span><pre>'.$url.'</pre></span>',$allowed_tags );    
            break;
        case 'section_id_dy':
            $url = $cb . str_replace( "section_id_", "", $args['id']);
            echo wp_kses( '设置教程<a target="_blank" href="https://open.douyin.com/platform/doc/m-2-1-1">点击这里</a>', $allowed_tags );    
            echo wp_kses( ', 回调链接为： <span><pre>'.$url.'</pre></span>',$allowed_tags );    
            break;
        case 'section_id_wb':
            $url = $cb . str_replace( "section_id_", "", $args['id']);
            echo wp_kses( '设置教程<a target="_blank" href="https://open.weibo.com/authentication">点击这里</a>', $allowed_tags );     
            echo wp_kses( ', 回调链接为： <span><pre>'.$url.'</pre></span>',$allowed_tags );    
            break;
        default:
            break;
    }
  
}

function plugin_admin_init() {
     $list = [
        'wx' => '微信',
        'qq' => 'QQ',
        'wb' => '微博',
        'dy' => '抖音'
     ];
    foreach ($list as $app_key => $app_val) {
        $status = 'mksso_' .$app_key .'[status]';
        $appid = 'mksso_' .$app_key .'[appid]';
        $secret = 'mksso_' .$app_key .'[secret]';
        $section_id = 'section_id_'.$app_key;

        register_setting('mark_social_setting_group', 'mksso_'. $app_key);
        add_settings_section($section_id , $app_val.'登陆', 'section_callback', 'mksso');
        add_settings_field($status, '是否启用', 'mark_sso_field_cb', 'mksso', $section_id , array( 'id' => 'status', 'type' => 'checkbox',  'key' => $app_key));
        add_settings_field($appid, $app_val.'应用ID (APP ID)', 'mark_sso_field_cb', 'mksso', $section_id , array( 'id' => 'appid', 'type' => 'text',  'key' => $app_key));
        add_settings_field($secret, $app_val.'密匙App Secret', 'mark_sso_field_cb', 'mksso', $section_id , array( 'id' => 'secret', 'type' => 'password', 'key' => $app_key)); 
    }
}
add_action( 'admin_init', 'plugin_admin_init' );

function mark_sso_field_cb($args){
  $val = get_option( 'mksso_' . $args['key'] );
  $name = 'mksso_'. $args['key'] .'['.$args["id"].']';
  $attr = '';
  if($args['type'] == 'checkbox'){
      $attr = checked($val[$args['id']] , 1, false);
      echo '<input type="checkbox" '.$attr.' value="1" name="'.$name.'"></label>';
  }else{
    $val = $val[ $args['id'] ];
    echo '<input type="'.$args['type'].'" class="regular-text" name="'.$name.'" value="'.$val.'"></label>';  
  }
}


