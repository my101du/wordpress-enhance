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

            // define constants
            define('MC_PLUGIN_PATH', plugin_dir_path(__FILE__));
            define('MC_PLUGIN_URL', plugin_dir_url(__FILE__));
            define('MC_PLUGIN_FILE', __FILE__);

            register_activation_hook(MC_PLUGIN_FILE, array(__CLASS__, 'activePlugin'));
            register_deactivation_hook(MC_PLUGIN_FILE, [__CLASS__, 'deactivePlugin']);

            // add_action('init', [$this, 'registerFiles']);
            // add_action('wp_enqueue_scripts', [$this, 'enqueueFiles'], 50);

            // load necessary classes
            require_once 'includes/MCErrors.class.php';

            // load addons
            foreach ($addons as $name => $class) {
                require_once 'addons/' . $name . '/index.php';
                add_action('init', array($class, 'init'), 11);
            }

            // load customer functinons
            foreach ($customers as $name => $class) {
                require_once 'customers/' . $name . '/index.php';
                add_action('init', array($class, 'init'), 11);
            }
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
    }
}

MCEnhance::instance();
