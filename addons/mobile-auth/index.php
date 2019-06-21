<?php
if (!defined('ABSPATH')) {
    exit;
}

/**
 * 使用手机号码注册 & 登录
 */
class MCMobileAuth
{
    public static function init()
    {
        add_action('admin_init', [__CLASS__, 'createSmsVerifyTable']);

        add_action('register_form', [__CLASS__, 'addMobileRegisterFields']);
        add_action('register_post', [__CLASS__, 'verifyRegisterPost'], 10, 3);
        add_action('user_register', [__CLASS__, 'updateUserMobilePhone']);

        add_action('wp_ajax_sendSms', [__CLASS__, 'ajaxSendSmsCode']);
        add_action('wp_ajax_nopriv_sendSms', [__CLASS__, 'ajaxSendSmsCode']);

        //暂未明白用途
        add_filter('send_password_change_email', '__return_false');
        add_filter('user_contactmethods', [__CLASS__, 'mcAddContactFields']);

        // don't know the useage
        add_filter('gettext', array('MCCore', 'changeTranslatedText'), 20, 3);
        add_action('admin_init', array('MCCore', 'removeDefaultPasswordNag'));
    }

    /**
     * create the sms code table when plugin install
     * @return [type] [description]
     */
    public function createSmsVerifyTable()
    {
        if (is_admin() && get_option('mc_actived') == 'true') {

            delete_option('mc_actived');

            global $wpdb;
            $table_name = MC_SMS_CODE_TABLE;

            $charset_collate = $wpdb->get_charset_collate();

            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                   `phone` varchar(14) NOT NULL COMMENT '手机号',
                   `code` char(4) NOT NULL COMMENT '验证码',
                   `time` bigint(20) unsigned NOT NULL COMMENT '时间戳'
                   ) $charset_collate;

                   ALTER TABLE $table_name
                      ADD PRIMARY KEY (`phone`),
                      ADD UNIQUE KEY `phone` (`phone`,`code`);";

            require_once ABSPATH . 'wp-admin/includes/upgrade.php';

            dbDelta($sql);
        }
    }

    /**
     * 向注册表单中添加手机号码验证字段
     */
    public function addMobileRegisterFields()
    {
        // do not use "require" to load a template file
        include MC_PLUGIN_PATH . 'templates/mobile_register.php';
    }

    /**
     * 提交注册表单时，对数据进行验证(from web)
     *
     * @param [type] $login
     * @param [type] $email
     * @param [type] $errors
     * @return void
     */
    public function verifyRegisterPost($login, $email, $errors)
    {
        if (empty($_POST['token']) || !wp_verify_nonce($_POST['token'], plugin_basename(MC_PLUGIN_FILE))) {
            wp_die('非法请求！');
        }

        $this->verifyRegisterFields($errors);
    }

    /**
     * 通用注册表单字段验证，（在 ajax 注册时也被调用）
     * @param  [type] $errors [description]
     * @return [type]         [description]
     */
    public static function verifyRegisterFields($errors)
    {
        // 不是 $self....
        $errors = self::verifyBasicFields($errors);
        $errors = self::verifyCaptcha($errors);
        $errors = self::verifyMobilePhone($errors);
        $errors = self::verifySmsCode($errors);

        return $errors;
    }

    /**
     * 验证基本字段（用户名，邮箱，密码）
     *
     * @param [type] $errors
     * @param string $mode
     * @return void
     */
    public static function verifyBasicFields($errors)
    {
        if (empty($_POST['user_login'])) {
            $errors->add('require_username', "<strong>错误</strong>：请输入用户名", true);
        } elseif (empty($_POST['user_email'])) {
            $errors->add('require_email', "<strong>错误</strong>：请输入邮箱", true);
        } elseif (strlen($_POST['user_pass']) < 6) {
            $errors->add('password_length', "<strong>错误</strong>：密码长度至少6位", true);
        } elseif ($_POST['user_pass'] != $_POST['user_pass2']) {
            $errors->add('password_error', "<strong>错误</strong>：两次输入的密码必须一致", true);
        }

        return $errors;
    }

    /**
     * 验证手机号码
     * 1. 格式是否正确
     * 2. 是否已经注册过
     * @return [type] [description]
     */
    public static function verifyMobilePhone($errors)
    {
        global $wpdb;

        $phone = trim($_POST['phone']);

        if (!isPhone($phone)) {
            $errors->add('phone_error', "<strong>错误</strong>：手机号不正确");
            $_POST['phone'] = '';
            $_POST['code']  = '';

        } else {
            $user_id = $wpdb->get_var($wpdb->prepare("SELECT `user_id` FROM `" . $wpdb->prefix . "usermeta` WHERE `meta_key` = 'phone' AND `meta_value` = %s;", $phone));

            if(TEST_MODE) {
                $user_id = null;
            }

            if ($_POST['admin'] == 1) {
                // modify mobile in the admin panel
                global $current_user;
                if ($current_user->ID == $user_id) {
                    $errors->add('phone_same', "<strong>错误</strong>：" . $_POST['phone'] . " 手机号没有修改");
                }
            } elseif (!empty($user_id)) {
                // register new user in the frontend
                $errors->add('phone_error', "<strong>错误</strong>：" . $_POST['phone'] . " 该手机号已在本站注册过");
                $_POST['phone'] = '';
                $_POST['code']  = '';
            }
        }

        return $errors;
    }

    /**
     * After verify user data, store it.
     * @return [type] [description]
     */
    public static function saveUserData($errors)
    {
        $userdata = array(
            'user_login' => $_POST['user_login'],
            'user_pass'  => $_POST['user_pass'],
            'user_email' => $_POST['user_email'],
        );

        $user_register = wp_insert_user($userdata);

        if (!is_wp_error($user_register)) {

            // save the mobile phone
            $this->updateUserMobilePhone($user_register);

            // auto login
            // $this->autoLogin($_POST['user_login'], $_POST['user_pass']);

            return $user_register;

        } else {
            $error = $user_register->get_error_codes();
            // __($user_register->get_error_message('This username is already registered.'));

            if (in_array('empty_user_login', $error)) {
                $errors->add($error, '用户名为空');
            } elseif (in_array('existing_user_login', $error)) {
                $errors->add($error, '用户名已注册');
            } elseif (in_array('existing_user_email', $error)) {
                $errors->add($error, '邮箱已注册');
            }

        }

        return $errors;
    }

    /**
     * 自动登录（注册成功后）
     * @param  [type] $user_login [description]
     * @param  [type] $password   [description]
     * @return [type]             [description]
     */
    public function autoLogin($user_login, $password)
    {
        $info                  = array();
        $info['user_login']    = $user_login;
        $info['user_password'] = $password;
        $info['remember']      = true;

        $user_signon = wp_signon($info, ''); // From false to '' since v4.9

        if (is_wp_error($user_signon)) {
            // echo json_encode(array('loggedin'=>false, 'message'=>__('Wrong username or password.')));
        } else {
            wp_set_current_user($user_signon->ID);
            // echo json_encode(array('loggedin'=>true, 'message'=>__($login.' successful, redirecting...')));
        }
    }

    /**
     * 验证图片验证码 （下发验证短信代码之前，要提交图片验证码）
     * @return [type] [description]
     */
    public static function verifyCaptcha($errors)
    {
        global $wpdb;
        $table_name = MC_SMS_CODE_TABLE;

        if (empty($_POST['captcha_code']) || empty($_SESSION[MC_CAPTCHA_SESSION])) {
            $errors->add('captcha_empty', "<strong>错误</strong>：图片验证码可能为空, 或服务器错误", true);
        } else {
            // m2dbsa-1548225501 验证码+时间戳，2分钟后图片验证码就过期了
            $secretword = explode("-", $_SESSION[MC_CAPTCHA_SESSION]);

            if (time() - $secretword[1] > 120) {
                $errors->add('captcha_expired', "<strong>错误</strong>：图片验证码已过期，请刷新页面后重新输入", true);
            } else if (trim(strtolower($_POST['captcha_code'])) != $secretword[0]) {
                $errors->add('captcha_error', "<strong>错误</strong>：图片验证码填写错误", true);
            }
        }

        // 如果图片验证码成功，清空session
        unset($_SESSION[MC_CAPTCHA_SESSION]);

        return $errors;
    }

    /**
     * 处理 ajax 发送短信验证码请求
     * @return [type] [description]
     */
    public function ajaxSendSmsCode()
    {
        $errors = new MCErrors();

        if ('POST' != $_SERVER['REQUEST_METHOD']) {
            header('Allow: POST');
            header('HTTP/1.1 405 Method Not Allowed');
            header('Content-Type: text/plain');
            $errors->add('unauthorized_access', '非法访问1', true);
        }

        if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
            $errors->add('unauthorized_access', '非法访问2', true);
        }

        // plugin_basename(MC_PLUGIN_FILE)
        if (!check_ajax_referer(plugin_basename(MC_PLUGIN_FILE), 'token', false)) {
            $errors->add('unauthorized_access', '非法访问3 表单过期 请刷新 ' . $_POST['token'], true);
        }

        // if( !isset( $_POST['mc_register_nonce'] ) || !wp_verify_nonce( $_POST['mc_register_nonce'], 'mc_register' ) )
	    // 	$errors->add('nonce error', '会话已过期，请刷新后重试');

        // 图形验证码、手机号码验证完后，才正式发送短信到手机上
        $errors = self::verifyCaptcha($errors);
        $errors = self::verifyMobilePhone($errors);

        // 调用短信发送
        if (!count($errors->get())) {

            // 用 $this->sendSms() 不运行 ??
            // 是因为 add_action('wp_ajax_sendSms', [__CLASS__, 'ajaxSendSmsCode']); 产生了一个静态方法
            // 而静态方法不能调用非静态方法  导致吗？
            self::sendSms();
        } else {
            return $errors;
        }
    }

    /**
     * 发送短信，保存记录
     * @param  [type] $errors [description]
     * @return [type]         [description]
     */
    public static function sendSms()
    {
        $errors = new MCErrors();

        global $wpdb;
        
        $table_name = MC_SMS_CODE_TABLE;
        $phone      = trim($_POST['phone']);

        // 查询本手机号码上次请求发送短信的时间，防止短信攻击
        $time = $wpdb->get_var($wpdb->prepare("SELECT `time` FROM `$table_name` WHERE `phone` = %s;", $phone));

        if (!empty($time) && (time() - $time) < 61) {
            $errors->add('frequent_request', '获取验证码太频繁', true);
        }

        $code = generateCode();

        if (empty($time)) {
            $db = $wpdb->insert($table_name, array('phone' => $phone, 'code' => $code, 'time' => time()), array('%s', '%s', '%d'));
        } else {
            $db = $wpdb->update($table_name, array('code' => $code, 'time' => time()), array('phone' => $phone), array('%s', '%d'), array('%s'));
        }

        if ($db) {
            // TODO(): change to aliSMS
            $send_status = aliSMS($phone, $code);

            if($send_status == 1) {
                echo json_encode( ['result'=>'success'] );
                exit();
            } else {
                $errors->add('sms_error', '验证码短信发送失败 ' . $send_status, true);
            }
        } else {
            $errors->add('sms_error', '短信验证码数据表错误！', true);
        }

        return $errors;
    }

    /**
     * 验证短信验证码是否正确（和手机匹配）
     * @return [type] [description]
     */
    public static function verifySmsCode($errors)
    {
        global $wpdb;
        $table_name = MC_SMS_CODE_TABLE;

        $phone = trim($_POST['phone']);

        // 短信验证码过期时间

        if (empty($_POST['code'])) {
            $errors->add('code_error1', "<strong>错误</strong>：请填写短信验证码");
        } else {
            $code = $wpdb->get_var($wpdb->prepare("SELECT `code` FROM `$table_name` WHERE `phone` = %s;", $phone));
            
            if (empty($code)) {
                $errors->add('code_error2', "<strong>错误</strong>：请先获取短信验证码", true);
            } else if ($code != $_POST['code']) {
                $errors->add('code_error3', "<strong>错误</strong>：短信验证码不正确", true);
            }
        }

        return $errors;
    }

    /**
     * 设置/更新注册用户的手机号码
     *
     * @param [type] $user_id
     * @return void
     */
    public function updateUserMobilePhone($user_id)
    {
        global $wpdb;

        $table_name = MC_SMS_CODE_TABLE;

        update_user_meta($user_id, 'phone', $_POST['phone']);

        // 删除过期的短信验证码下发记录（10分钟后，要重新发送短信验证码）
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM `$table_name`
            WHERE `time` < %s
        ",
                (time() - 600)
            )
        );

        // $userdata              = array();
        // $userdata['ID']        = $user_id;
        // $userdata['user_pass'] = $_POST['user_pass'];

        // wp_update_user($userdata);
    }

    public function mcAddContactFields($contactmethods)
    {
        global $current_user;

        if ($current_user->roles[0] == 'administrator') {
            $contactmethods['phone'] = '手机号';
        }

        return $contactmethods;
    }

    public function overwriteFunctions()
    {
        /**
         * 后台注册模块，添加注册表单,修改新用户通知。
         */
        if (!function_exists('wp_new_user_notification')):

            /**
             * Notify the blog admin of a new user, normally via email.
             *
             * @since 2.0
             *
             * @param int $user_id User ID
             * @param string $plaintext_pass Optional. The user's plaintext password
             */
            function wpNewUserNotification($user_id, $deprecated = null, $notify = '')
        {
                if ($deprecated !== null) {
                    _deprecated_argument(__FUNCTION__, '4.3.1');
                }

                global $wpdb, $wp_hasher;
                $user = get_userdata($user_id);

                // The blogname option is escaped with esc_html on the way into the database in sanitize_option
                // we want to reverse this for the plain text arena of emails.
                $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);

                if ('user' !== $notify) {
                    $switched_locale = switch_to_locale(get_locale());
                    $message         = sprintf(__('New user registration on your site %s:'), $blogname) . "\r\n\r\n";
                    $message .= sprintf(__('Username: %s'), $user->user_login) . "\r\n\r\n";
                    $message .= sprintf(__('Email: %s'), $user->user_email) . "\r\n";

                    @wp_mail(get_option('admin_email'), sprintf(__('[%s] New User Registration'), $blogname), $message);

                    if ($switched_locale) {
                        restore_previous_locale();
                    }
                }
            }

        endif;
    }

    public function removeDefaultPasswordNag()
    {
        global $user_ID;
        delete_user_setting('default_password_nag', $user_ID);
        update_user_option($user_ID, 'default_password_nag', false, true);
    }

    public static function changeTranslatedText($translated_text, $untranslated_text, $domain)
    {
        if ($untranslated_text === 'A password will be e-mailed to you.' || $untranslated_text === 'Registration confirmation will be emailed to you.')
            return '';
        else if ($untranslated_text === 'Registration complete. Please check your e-mail.' || $untranslated_text === 'Registration complete. Please check your email.')
            return '注册成功！';
        else
            return $translated_text;
    }

}
