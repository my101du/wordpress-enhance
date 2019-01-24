<?php
/*
Plugin Name: Macode365 Wordpress Enhance
Plugin URI:
Description: Macode365
Version: 1.0
Author: my101du@gmail.com
Author URI: https://www.4guangnian.com
 */

if (!defined('ABSPATH')) {
    exit();
}

if (!isset($_SESSION)) {
    @session_start();
    @session_regenerate_id(true);
}

if (!class_exists('MCEnhance')) {
    final class MCEnhance
    {
        //Singleton mode
        private static $_instance = null;

        public static function instance()
        {
            if (is_null(self::$_instance)) {
                self::$_instance = new self();
            }

            return self::$_instance;
        }

        private function __construct()
        {
            $this->init();

        }

        private function init()
        {
            require_once 'includes/helper_functions.php';
            require_once 'config.php';

            // captcha session name
            require_once 'includes/captcha/config.php';

            // define constants
            define('MC_PLUGIN_PATH', plugin_dir_path(__FILE__));
            define('MC_PLUGIN_URL', plugin_dir_url(__FILE__));
            define('MC_PLUGIN_FILE', __FILE__);

            //[TODO] 插件列表开关
            //
            add_action('init', [$this, 'registerFiles']);
            // add_action('wp_enqueue_scripts', [$this, 'enqueueFiles'], 50);

            // load includes
            require_once 'includes/MCCore.class.php';
            require_once 'includes/MCErrors.class.php';
            require_once 'includes/MCMobileAuth.class.php';
            require_once 'includes/MCPopupLogin.class.php';

            // call the init() of classes
            add_action('init', array('MCCore', 'init'), 10);
            add_action('init', array('MCMobileAuth', 'init'), 11);
            add_action('init', array('MCPopupLogin', 'init'), 12);

            // when woocommerce installed
            if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
                require_once dirname(MC_PLUGIN_PATH) . '/woocommerce/woocommerce.php';

                require_once 'includes/MCWoocommerceAuthExt.class.php';
                new MCWoocommerceAuthExt();
            }
        }

        public function registerFiles()
        {
            wp_register_script('mc_js', plugins_url('/js/renqizx-common.js', __FILE__));
            wp_register_style('mc_style', plugins_url('/css/renqizx-common.css', __FILE__), false, time(), 'all');

            wp_enqueue_script('mc_js');
			wp_enqueue_style('mc_style');
        }
    }
}

MCEnhance::instance();
