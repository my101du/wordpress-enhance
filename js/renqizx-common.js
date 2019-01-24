jQuery(document).ready(function($) {
	// alert(jQuery('.masthead').html());


	// // hide the top area when open in wechat (webview)
	var ua = navigator.userAgent.toLowerCase();
	if(ua.match(/MicroMessenger/i)=="micromessenger") {
		// return "weixin";
		jQuery(".masthead").hide();
		jQuery(".mobile-header-space").css({'height': 0});
	} else if (ua.match(/QQ/i) == "qq") {
			return "QQ";
	}
	console.log(window.__wxjs_environment);
	if(window.__wxjs_environment === 'miniprogram') {
		jQuery(".masthead").hide();
		jQuery(".mobile-header-space").css({'height': 0});
	}
	// return false;
});

