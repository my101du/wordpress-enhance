<?php
/*
 Template Name: Social - Login
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

?>

<script>
  var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";
  var pic_no = "<?php echo constant("MC_PLUGIN_URL"); ?>img/no.png";
  var captcha = "<?php echo constant("MC_PLUGIN_URL"); ?>includes/captcha/captcha.php";
</script>
<script src="<?php echo constant("MC_PLUGIN_URL"); ?>/js/check.js"></script>
<p>
  <label for="user_pwd1">密码(至少6位)<br/>
     <input id="user_pwd1" class="input" type="password" size="25" value="" name="user_pass" />
  </label>
</p>
<p>
  <label for="user_pwd2">重复密码<br/>
     <input id="user_pwd2" class="input" type="password" size="25" value="" name="user_pass2" />
  </label>
</p>
<p>
  <label for="CAPTCHA">图片验证码 &nbsp;<span id="captchaErr" style="color:#ff5c57;font-size: 12px;"></span> <br/>
     <input id="CAPTCHA" style="width:180px;float:left;" class="input" type="text" size="10" value="" name="captcha_code" autocomplete="off" />
     <img id="captcha_img" src="<?php echo constant("MC_PLUGIN_URL"); ?>includes/captcha/captcha.php" title="看不清?点击更换" alt="看不清?点击更换" onclick="document.getElementById('captcha_img').src = '<?php echo constant("MC_PLUGIN_URL"); ?>includes/captcha/captcha.php?' + Math.random();document.getElementById('CAPTCHA').focus();return false;" />
  </label>
</p>
<p>
  <label for="phone">手机号 &nbsp;<span id="sendSmsBtnErr" style="color:#ff5c57;font-size: 12px;"></span> <br/>
     <input id="phone" class="input" style="width:180px;float:left;" type="text" size="15" value="<?php echo empty($_POST['phone']) ? '' : $_POST['phone']; ?>" name="phone" autocomplete="off" />
     <input id="sendSmsBtn" type="button" style="float:left;margin-top:5px;" value="获取验证码" class="button button-secondary" />
  </label>
</p>
<p>
  <label for="code">短信验证码 &nbsp;
     <input id="code" class="input" style="width:180px;float:left;" type="text" size="4" value="<?php echo empty($_POST['code']) ? '' : $_POST['code']; ?>" name="code" />
  </label>
</p>
<input type="hidden" name="token" value="<?php echo wp_create_nonce(plugin_basename(MC_PLUGIN_FILE)); ?>">

<?php
// used for ajax form
wp_nonce_field('mc_register', 'mc_register_nonce', true, true);

