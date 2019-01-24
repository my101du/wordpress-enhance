<?php

class MCCompanyCheck {
	public function __construct() {
		add_shortcode('mc_verify_tool', [$this, 'shortcodeOfCompanyCheck']);
		
	}

	// 检查提交表单数据
    public function checkFields()
    {
        $errors = new MCErrors();

        global $wpdb, $table_name;

        if (empty($_POST['token']) || !wp_verify_nonce($_POST['token'], plugin_basename(__FILE__))) {
            wp_die('非法请求！');
        }

        // if (strlen($_POST['user_pass']) < 6) {
        //     $errors->add('password_length', "<strong>错误</strong>：密码长度至少6位");
        // } elseif ($_POST['user_pass'] != $_POST['user_pass2']) {
        //     $errors->add('password_error', "<strong>错误</strong>：两次输入的密码必须一致");
        // }

        // $_SESSION['ludou_lcr_secretword'] to $_POST['captcha_session']
        if (empty($_POST['captcha_code']) || empty($_POST['captcha_session'])) {
            $errors->add('captcha_spam', "<strong>错误</strong>：图片验证码填写错误");
        } else {
            $secretword = explode("-", $_POST['captcha_session']);

            if (time() - $secretword[1] > 120) {
                $errors->add('captcha_spam', "<strong>错误</strong>：图片验证码已过期，请刷新页面后重新输入");
            } else if (trim(strtolower($_POST['captcha_code'])) != $secretword[0]) {
                $errors->add('captcha_spam', "<strong>错误</strong>：图片验证码填写错误");
            }
        }

        unset($_SESSION['ludou_lcr_secretword']);

        $phone = trim($_POST['phone']);
        if (!$this->isPhone($phone)) {
            $errors->add('phone_error', "<strong>错误</strong>：手机号不正确");
            $_POST['phone'] = '';
            $_POST['code']  = '';
        } else {
            // $phone_exist = $wpdb->get_var($wpdb->prepare("SELECT `user_id` FROM `" . $wpdb->prefix . "usermeta` WHERE `meta_key` = 'phone' AND `meta_value` = %s;", $phone));

            // if (!empty($phone_exist)) {
            //     $errors->add('phone_error', "<strong>错误</strong>：" . $_POST['phone'] . " 该手机号已在本站注册过");
            //     $_POST['phone'] = '';
            //     $_POST['code']  = '';
            // }
            // else
            // {

            // }

            if (empty($_POST['code'])) {
                $errors->add('code_error1', "<strong>错误</strong>：请填写短信验证码");
            } else {
                $code = $wpdb->get_var($wpdb->prepare("SELECT `code` FROM `$table_name` WHERE `phone` = %s;", $phone));
                if (empty($code)) {
                    $errors->add('code_error2', "<strong>错误</strong>：请先获取短信验证码");
                } else if ($code != $_POST['code']) {
                    $errors->add('code_error3', "<strong>错误</strong>：短信验证码不正确");
                }
            }
        }
        $result['errors'] = $errors->getErrors();
        $result           = json_encode($result);
        echo $result;

        exit();
    }

    // 处理客户端发送短信验证码的 ajax 请求
    // 如果是来自 app/小程序，需要对 请求协议、字段等特殊处理（见上面WC...）
    public function sendWCSms()
    {
        $error = '';
        // if ('POST' != $_SERVER['REQUEST_METHOD']) {
        //     header('Allow: POST');
        //     header('HTTP/1.1 405 Method Not Allowed');
        //     header('Content-Type: text/plain');
        //     $error = '非法访问';
        // }

        // if (empty($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
        //     $error = '非法访问 HTTP_X_REQUESTED_WITH' . $_SERVER['HTTP_X_REQUESTED_WITH'];
        // }

        // if (!check_ajax_referer(plugin_basename(__FILE__), 'token', false)) {
        //     $error = '非法访问 check_ajax_referer ' . plugin_basename(__FILE__);
        // }

        global $wpdb, $table_name;

        // change from $_SESSION['ludou_lcr_secretword']
        $code = $wpdb->get_var($wpdb->prepare("SELECT `code` FROM `wc_captcha_session` WHERE `auth` = %s;", $_POST['auth']));

        if (empty($_POST['captcha_code']) || empty($code)) {
            $error = '图片验证码为空,或 session 为空';
        } else {
            $secretword = explode("-", $code);

            if (time() - $secretword[1] > 120) {
                //120
                $error = '验证码已过期，请重新输入';
            } else if (trim(strtolower($_POST['captcha_code'])) != $secretword[0]) {
                $error = '图片验证码错误，正确的是' . $secretword[0];
            }
        }
        //删除过期图形验证码 session?

        // 删除过期验证码
        $wpdb->query(
            $wpdb->prepare("DELETE FROM `$table_name` WHERE `time` < %s ", (time() - 600))
        );

        $phone = trim($_POST['phone']);
        if (!$this->isPhone($phone)) {
            $error = '手机号不正确';
        }
        // else
        // {
        //     $user_id = $wpdb->get_var($wpdb->prepare("SELECT `user_id` FROM `" . $wpdb->prefix . "usermeta` WHERE `meta_key` = 'phone' AND `meta_value` = %s;", $phone));

        //     if ($_POST['admin'] == 1) {
        //         global $current_user;
        //         if ($current_user->ID == $user_id) {
        //             $error = '手机号没有修改';
        //         }

        //     } elseif (!empty($user_id)) {
        //         $error = '该手机号已在本站注册过';
        //     }
        // }

        // 上次发送短信的时间，防止短信攻击
        $time = $wpdb->get_var($wpdb->prepare("SELECT `time` FROM `$table_name` WHERE `phone` = %s;", $phone));

        if (!empty($time) && (time() - $time) < 5) {
            //61
            $error = '获取验证码太频繁';
        }

        if (empty($error)) {
            $code = $this->generateCode();

            if (empty($time)) {
                $db = $wpdb->insert($table_name, array('phone' => $phone, 'code' => $code, 'time' => time()), array('%s', '%s', '%d'));
            } else {
                $db = $wpdb->update($table_name, array('code' => $code, 'time' => time()), array('phone' => $phone), array('%s', '%d'), array('%s'));
            }

            if ($db) {
                
                // ali sms library and config
                require_once plugin_dir_path(MC_PLUGIN_FILE) . 'includes/sms/alisms.php';
                require_once plugin_dir_path(MC_PLUGIN_FILE) . 'includes/sms/config.php';

                $alisms = new SmsDemo($AccessKeyId, $AccessKeySecret);

                $res = $alisms->sendSms(
                    $sign,     // 短信签名
                    $template_company_verify, // 短信模板编号(企业核名)
                    $phone,    // 短信接收者
                    array(     // 短信模板中字段的值
                        "code" => $code,
                    )
                );

                if ($res->Code == 'OK') {
                    $send_status = 1;
                } else {
                    $send_status = $res->Code;
                }

                $result['vHTML'] = ($send_status == 1) ? '' : '验证码发送失败: ' . $send_status;
            } else {
                $result['vHTML'] = '数据库错误！';
            }
        } else {
            $result['vHTML'] = $error;
        }

        $result['type'] = "success";
        $result         = json_encode($result);
        echo $result;

        exit();
    }

	/**
	 * Add verify tool
	 *
	 * @param array $atts
	 * @param [type] $content
	 * @return void
	 */
	function shortcodeOfCompanyCheck($atts = [], $content = null)
	{
		switch ($atts['type']) {
			case 'company-name':

				$content = '
					<form id="company-name-verify-form" class="verify-form" action="" method="post" accept-charset="utf-8">
						<input type="hidden" name="data-type" value="company-name" />
						<label>
							<input type="text" class="more-height" name="keyword" value="" size="80" placeholder="公司名关键词">
							<input type="submit" class="more-height" style="padding: 0px 25px; background-color:#104B74;" name="submit" value="查询" />
						</label>
						
					</form>

					<!-- company list -->
					<div id="result-list" class="element-list"></div>
					';

				break;

			case 'brand':
				$content = '
					<form id="brand-verify-form"  class="verify-form"  action="" method="post" accept-charset="utf-8">
						<input type="hidden" name="data-type" value="brand" />
						<label>
							<input type="text" class="more-height" name="keyword" value="" size="80" placeholder="商标关键词">
							<input type="submit" class="more-height" style="padding: 0px 25px; background-color:#104B74;" name="submit" value="查询" />
						</label>
						
					</form>

					<!-- brand list -->
					<div id="result-list" class="element-list"></div>
					';
				break;

			default:
					# code...
				break;
		}

		return $content;
	}
}