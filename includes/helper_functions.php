<?php

// 生成4位随机数字
function generateCode()
{
    return mt_rand(0, 9) . mt_rand(100, 999);
}

// 验证是否为手机号
function isPhone($phone)
{
    if ((empty($phone) || !preg_match("/^(((13[0-9]{1})|(15[0-9]{1})|(17[0-9]{1})|(18[0-9]{1}))+\d{8})$/", $phone))) {
        return 0;
    } else {
        return 1;
    }
}


// 阿里短信
function aliSMS($phone, $code) {
    // in test mode, always return 1, to save the quote
    if(TEST_MODE) {
        return 1;
    }

    require_once 'sms/alisms.php';
    require_once 'sms/config.php';

    $alisms = new SmsDemo($AccessKeyId, $AccessKeySecret);

    $res = $alisms->sendSms(
        $sign,     // 短信签名
        $template, // 短信模板编号
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

    return $send_status;
}