<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} 

class MCPopupLogin
{
    public static function init()
    {
        add_shortcode('renqizx_mobile_register_form', [__CLASS__, 'shortcodeOfRegisterForm']);
        add_shortcode('renqizx_login_logout', [__CLASS__, 'shortcodeOfLogin']);

        add_action('wp_ajax_register_user', [__CLASS__, 'ajaxRegisterUser']);
		add_action('wp_ajax_nopriv_register_user', [__CLASS__, 'ajaxRegisterUser']);
    }

    public function __contructor()
    {

    }

    /**
     * “注册表单”的 shortcode
     *
     * @param array $atts
     * @param [type] $content
     * @return void
     */
    public static function shortcodeOfRegisterForm($atts = [], $content = null)
    {
        ob_start();

        //login
        //'<form name="loginform" id="login_form" class="login_form" action="' . esc_url(wp_login_url()) . '" method="post">
        //register
        //'<form method="post" id="register_form" class="wp-user-form" action="'.site_url('wp-login.php?action=register', 'login_post').'">'
?>
	<form method="post" id="register_form" class="wp-user-form" action="' . site_url('wp-login.php?action=register', 'login_post') . '">
       <label for="">用户名<br/><input type="text" id="register_user_login" name="register_user_login"></label>
       <label for="">Email<br/><input type="text" id="register_user_email" name="register_user_email"></label>

        <?php do_action('register_form'); ?>

		<input id="btn-register-submit" type="submit" name="submit" value="提交"/>
		<span id="registerErr" style="color:#ff5c57;font-size: 12px;"></span>
    </form>
<?php

        return ob_get_clean();
        // return $content;
    }



    /**
     * 页面顶部 登录/退出 显示的shortcode
     *
     * @param array $atts
     * @param [type] $content
     * @return void
     */
    public function shortcodeOfLogin($atts = [], $content = null)
    {
        if (is_user_logged_in()) {
            global $current_user;

            wp_get_current_user();
            $current_user->user_login;

            return $current_user->user_login . " <a href=" . wp_logout_url(get_permalink()) . ">退出</a>";
        } else {
            return "<a href=" . wp_login_url(get_permalink()) . ">登录</a> <a href=" . wp_registration_url(get_permalink()) . ">注册</a>";
        }

        return $content;
    }

    /**
	 * 对应 ajax 提交注册请求
	 *
	 * @return void
	 */
	function ajaxRegisterUser() {
		$errors = new MCErrors();

		if( !isset( $_POST['mc_register_nonce'] ) || !wp_verify_nonce( $_POST['mc_register_nonce'], 'mc_register' ) )
	    	$errors->add('nonce error', '会话已过期，请刷新后重试');

	    $verifyResult = MCMobileAuth::verifyRegisterFields($errors);
	    
	    if(count($verifyResult->get()) > 0) {
	    	echo json_encode(['result'=>'fail', 'errors'=>$verifyResult->get()]);
	    }else{
	    	// save user
	    	$saveUserResult = MCMobileAuth::saveUserData($verifyResult);

	    	if(is_numeric($saveUserResult)) {
	    		echo json_encode(['result'=>'success', 'message'=> 'register success, user id' .  $saveUserResult]);
	    	} else {
	    		echo json_encode(['result'=>'fail', 'errors'=>$saveUserResult->get()]);
	    	}
	    }

	    // 否则会多输出一个 “0” （在后面的代码里生成）
	    die(); //exit();
	}
}
