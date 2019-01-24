<?php

class MCCore
{
    public static function init()
    {
    	// do not use '$this'
    	// register_activation_hook(MC_PLUGIN_FILE, array($this, 'activePlugin'));
        
        // Hooks during active & deactive this plugin
        register_activation_hook(MC_PLUGIN_FILE, array(__CLASS__, 'activePlugin'));
        register_deactivation_hook(MC_PLUGIN_FILE, [__CLASS__, 'deactivePlugin']);

        // shortcode:   __CLASS__.'::shortcodeOfLogin' or ['MCCore', 'shortcodeOfLogin']
        // add_shortcode('renqizx_login_logout', __CLASS__.'::shortcodeOfLogin');
        // add_shortcode('renqizx_login_logout', [__CLASS__, 'shortcodeOfLogin']);
        add_shortcode('renqizx_login_logout', ['MCCore', 'shortcodeOfLogin']);

        // don't know the useage
		add_filter('gettext', array('MCCore', 'changeTranslatedText'), 20, 3);
		add_action('admin_init', array('MCCore', 'removeDefaultPasswordNag'));
    }

    public function __construct() {
    	// register_activation_hook(MC_PLUGIN_FILE, array(__CLASS__, 'activePlugin'));
    }

    /**
	 * 插件激活
	 *
	 * @return void
	 */
	public function activePlugin()
	{
		add_option('mc_actived', 'true');
	}

	/**
	 * 插件禁用
	 *
	 * @return void
	 */
	public function deactivePlugin()
	{
		// global $wpdb, $table_name;

		// $sql = "DROP TABLE IF EXISTS $table_name";
		// $wpdb->query($sql);
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
}
