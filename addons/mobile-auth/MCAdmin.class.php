<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} 
class MCAdmin {
	
	public static function init() {
		add_action('admin_menu', [__CLASS__, 'addMenu']);
	}

	/**
     * Add admin menus
     */
    public function addMenu()
    {
        global $current_user;

        if ($current_user->roles[0] == get_option('default_role')) {
            add_submenu_page('users.php', '修改手机号', '修改手机号', 'read', 'mc-mobile-auth-menu', [__CLASS__, 'addMenuPage']);
        }
    }

    public function addMenuPage()
    {
        global $current_user, $wpdb;
        $table_name = MC_SMS_CODE_TABLE;;

        $old_phone = get_user_meta($current_user->ID, 'phone', true);

        if (!empty($_POST['check'])) {
            if (empty($_POST['token']) || !wp_verify_nonce($_POST['token'], plugin_basename(__FILE__))) {
                wp_die('非法请求！');
            }

            $errors = '';

            if (empty($_POST['captcha_code']) || empty($_SESSION[MC_CAPTCHA_SESSION])) {
                $errors .= '图片验证码填写错误';
            } else {
                $secretword = explode("-", $_SESSION[MC_CAPTCHA_SESSION]);

                if (time() - $secretword[1] > 120) {
                    $errors .= '图片验证码已过期，请刷新页面后重新输入';
                } else if (trim(strtolower($_POST['captcha_code'])) != $secretword[0]) {
                    $errors .= '图片验证码填写错误';
                }

            }

            unset($_SESSION[MC_CAPTCHA_SESSION]);

            $phone = trim($_POST['phone']);
            if (!isPhone($phone)) {
                $errors .= "<strong>错误</strong>：手机号不正确<br />";
                $_POST['phone'] = '';
                $_POST['code']  = '';
            } else {
                $phone_exist = $wpdb->get_var($wpdb->prepare("SELECT `user_id` FROM `" . $wpdb->prefix . "usermeta` WHERE `meta_key` = 'phone' AND `meta_value` = %s AND `user_id` != %d;", $phone, $current_user->ID));

                if ($phone == $old_phone) {
                    $errors .= "<strong>错误</strong>：" . $phone . " 手机号没有修改<br />";
                    $_POST['code'] = '';
                } elseif (!empty($phone_exist)) {
                    $errors .= "<strong>错误</strong>：" . $phone . " 该手机号已被他人注册过<br />";
                    $_POST['phone'] = '';
                    $_POST['code']  = '';
                } else if (empty($_POST['code'])) {
                    $errors .= "<strong>错误</strong>：请填写短信验证码<br />";
                } else {
                    $code = $wpdb->get_var($wpdb->prepare("SELECT `code` FROM `$table_name` WHERE `phone` = %s;", $phone));
                    if (empty($code)) {
                        $errors .= "<strong>错误</strong>：请先获取短信验证码<br />";
                        $_POST['code'] = '';
                    } else if ($code != $_POST['code']) {
                        $errors .= "<strong>错误</strong>：短信验证码不正确<br />";
                        $_POST['code'] = '';
                    }
                }
            }

            if (empty($errors)) {
                $ok = update_user_meta($current_user->ID, 'phone', $phone);

                // 删除过期验证码
                $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM `$table_name` WHERE `phone` = %s;",
                        $phone
                    )
                );
            }
        }
        ?>
   <script>
      var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>", pic_no = "<?php echo constant("MC_PLUGIN_URL"); ?>img/no.png", captcha = "<?php echo constant("LCR_PLUGIN_URL"); ?>includes/captcha/captcha.php";
   </script>
   <script src="<?php echo constant("MC_PLUGIN_URL"); ?>/js/check.js"></script>

   <div class="wrap" id="profile-page">
      <h1>修改手机号</h1>
      <?php
if (!empty($errors)) {
            echo '<div class="error notice is-dismissible" id="message"><p>' . $errors . '</p>
	<button type="button" class="notice-dismiss"><span class="screen-reader-text">忽略此通知。</span></button></div>';
        } else if ($ok) {
            echo '<div id="message" class="updated notice is-dismissible">
<p><strong>手机号已修改为 ' . $phone . '</strong></p>
<button type="button" class="notice-dismiss"><span class="screen-reader-text">忽略此通知。</span></button></div>';
        }
        ?>
<form id="your-profile" action="#" method="post">
	<p>
	<label for="phone">手机号 &nbsp;<span id="sendSmsBtnErr" style="color:#ff5c57;font-size: 12px;"></span> <br/>
		<input id="phone" class="regular-text ltr" type="text" size="15" value="<?php echo empty($_POST['phone']) ? $old_phone : $_POST['phone']; ?>" name="phone" autocomplete="off" />
	</label>
	</p>
	<p>
	<label for="CAPTCHA">图片验证码 &nbsp;<span id="captchaErr" style="color:#ff5c57;font-size: 12px;"></span> <br/>
		<input id="CAPTCHA" class="regular-text ltr" type="text" size="10" value="" name="captcha_code" autocomplete="off" />
	</label>
	</p>
	<p>
	<label>
		<img id="captcha_img" src="<?php echo constant("LCR_PLUGIN_URL"); ?>includes/captcha/captcha.php" title="看不清?点击更换" alt="看不清?点击更换" onclick="document.getElementById('captcha_img').src = '<?php echo constant("LCR_PLUGIN_URL"); ?>includes/captcha/captcha.php?' + Math.random();document.getElementById('CAPTCHA').focus();return false;" />
		看不清？<a href="javascript:void(0)" onclick="document.getElementById('captcha_img').src = '<?php echo constant("LCR_PLUGIN_URL"); ?>includes/captcha/captcha.php?' + Math.random();document.getElementById('CAPTCHA').focus();return false;">点击更换</a>
	</label>
	</p>
	<p>
	<label for="code">短信验证码 <br/>
		<input id="code" class="regular-text ltr" type="text" size="4" value="<?php echo empty($_POST['code']) ? '' : $_POST['code']; ?>" name="code" />
		&nbsp;<input id="sendSmsBtn" type="button" value="获取短信验证码" class="button button-secondary" />
	</label>
	</p>
	<input type="hidden" name="check" id="admin_check" value="1" />
	<input type="hidden" name="token" value="<?php echo wp_create_nonce(plugin_basename(__FILE__)); ?>">
	<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="更新手机号"></p>
</form>
</div>
<?php

    }
}