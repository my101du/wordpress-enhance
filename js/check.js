var wait = 60; // 获取验证码短信时间间隔

jQuery(document).ready(function($) {
    function countdown() {
        if (wait > 0) {
            $("#sendSmsBtn").val(wait + "秒后重新获取验证码");
            wait--;
            setTimeout(countdown, 1000);
        } else {
            document.getElementById("captcha_img").src =
                captcha + "?v=" + Math.random();
            $("#CAPTCHA").val("");
            $("#CAPTCHA").focus();
            $("#sendSmsBtn")
                .val("获取短信验证码")
                .attr("disabled", false)
                .fadeTo("slow", 1);
            wait = 60;
        }
    }

    /**
     * Send SMS code
     */
    $("#sendSmsBtn").click(function() {

        // 验证手机号码格式
        var phone = $("input[name=phone]").val();
        if (
            phone == "" ||
            !phone.match(
                /^(((13[0-9]{1})|(15[0-9]{1})|(17[0-9]{1})|(18[0-9]{1}))+\d{8})$/
            )
        ) {
            $("#sendSmsBtnErr")
                .html(
                    '<img src="' +
                    pic_no +
                    '" style="vertical-align:middle;" alt=""/> ' +
                    "手机号不正确"
                )
                .slideDown();
            $("#phone").focus();
            setTimeout(function() {
                $("#sendSmsBtnErr").slideUp();
            }, 3000);
            return;
        }

        // 前端检查图片验证码格式
        var captcha_code = $("input[name=captcha_code]").val();
        var token = $("input[name=token]").val();

        if (captcha_code == "" || captcha_code.length != 5) {
            $("#captchaErr")
                .html(
                    '<img src="' +
                    pic_no +
                    '" style="vertical-align:middle;" alt=""/> ' +
                    "填写错误"
                )
                .slideDown();
            $("#CAPTCHA").focus();
            setTimeout(function() {
                $("#captchaErr").slideUp();
            }, 3000);
            return;
        }

        var admin = 0;
        if ($("#admin_check").length) admin = 1;

        $.ajax({
            type: "post",
            dataType: "json",
            url: ajaxurl,
            data: {
                action: "sendSms",
                phone: phone,
                captcha_code: captcha_code,
                token: token,
                admin: admin
            },
            success: function(response) {
                if (response.result == "fail") {
                    error = response.errors[0];
                    $("#captchaErr").html(
                        '<img src="' +
                        pic_no +
                        '" style="vertical-align:middle;" alt="" /> ' +
                        error.message
                    ).slideDown();
                    $("#CAPTCHA").focus();
                    setTimeout(function() {
                        $("#captchaErr").slideUp();
                    }, 3000);
                } else {
                    $("#sendSmsBtn")
                        .attr("disabled", true)
                        .fadeTo("slow", 0.5);
                    countdown();
                }

            }
        });
    });

    /**
     * Register form ajax submit
     * @param  {[type]} event) {                       } [description]
     * @return {[type]}        [description]
     */
    $("#btn-register-submit").click(function(event) {
        if (event.preventDefault) {
            event.preventDefault();
        } else {
            event.returnValue = false;
        }

        var token = $("input[name=token]").val();
        var nonce = $("input[name=mc_register_nonce]").val();

        // 如果有两个 popup  一个 login  一个 register, 会导致 user_login 重名
        var user_login = $("#register_user_login").val();
        var user_email = $("#register_user_email").val();
        var user_pass = $("#user_pwd1").val();
        var user_pass2 = $("#user_pwd2").val();
        var phone = $("#phone").val();
        var code = $("#code").val();
        var token = $("input[name=token]").val();

        $.ajax({
            type: "post",
            dataType: "json",
            url: ajaxurl,
            data: {
                action: "register_user",
                token: token,
                mc_register_nonce: nonce,
                user_login: user_login,
                user_email: user_email,
                user_pass: user_pass,
                user_pass2: user_pass2,
                phone: phone,
                code: code
            },
            success: function(response) {
                if (response.result === "fail") {
                    $("#registerErr").html(response.errors[0].message);
                } else {
                    //login
                    alert("注册成功, 请登录");
                    PUM.close(182);
                }
            }
        });
    });
});