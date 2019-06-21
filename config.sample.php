<?php

// captcha session name
define('MC_CAPTCHA_SESSION', 'mc_captcha_session');
$captcha_session_name =  'mc_form_secretword';

// sms code table
define('MC_SMS_CODE_TABLE', 'mc_sms_code');

// 阿里云 Access Key Id
$AccessKeyId = 'ACCESS_KEY_ID';
   
// 阿里云 Access Key Secret
$AccessKeySecret = 'access_key_secret';
   
// 短信签名
$sign = '阿里云短信测试专用';

// 短信模板CODE，如 SMS_123456789  用户注册验证码
$template = 'SMS_123456789';

// addons(enable/disable)
$addons = [
	'mobile-auth'=>'MCMobileAuth',
	'popup-login'=>'MCPopupLogin',
	'woocommerce-auth'=>'MCWoocommerceAuthExt'
];

// customers
$customers = [
	'apple'=>'MCCustomerApple'
];