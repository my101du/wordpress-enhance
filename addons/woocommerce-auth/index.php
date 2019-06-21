<?php
if (!defined('ABSPATH')) {
    exit();
}

// echo $wc_auth instanceof WC_Auth;

if (class_exists('WC_Auth')) {
    class MCWoocommerceAuthExt extends WC_Auth
    {
        public function __construct()
        {
            parent::__construct();

            // Register the REST API endpoint  /wp-json/wc-rest-ext/v1/login
            add_action('rest_api_init', function () {
                register_rest_route('wc-rest-ext/v1', '/auto_login', array(
                    'methods' => 'GET',
                    'callback' => array($this, 'autoLogin'),
                    // 'permissions_callback' => 'is_user_logged_in',
                    'args' => array(
                        'openid' => array(
                            'required' => true,
                            'type' => 'string',
                            'description' => 'The client\'s openid',
                        ),
                    ),
                ));
            });


            add_action('rest_api_init', function () {
                register_rest_route('wc-rest-ext/v1', '/sms_variables', array(
                    'methods'  => 'GET',
                    'callback' => array($this, 'getSMSVariables'),
                ));
            });

            // WooCommerce 里
            add_action('wp_ajax_sendWCSms', array($this, 'sendWCSms'));
            add_action('wp_ajax_nopriv_sendWCSms', array($this, 'sendWCSms'));

        }

        public function getSMSVariables()
        {
            if (!isset($_SESSION)) {
                session_start();
                session_regenerate_id(true);
            }

            //client js use
            $response = [
                'ajaxurl' => admin_url('admin-ajax.php'),
                'pic_no'  => constant("WC_REST_API_FOR_APP_PLUGIN_URL") . 'img/no.png',
                'captcha' => constant("WC_REST_API_FOR_APP_PLUGIN_URL") . 'captcha/captcha.php',
                'token'   => wp_create_nonce(plugin_basename(__FILE__)), // use the parent file?
            ];

            echo json_encode($response);
            exit();
        }

        // handler the REST API of "login"
        public function autoLogin($data)
        {
            $openid = $data['openid'];

            global $wpdb;

            $sql = "SELECT * FROM " . $wpdb->users . " WHERE user_login='" . $openid . "'";
            $users = $wpdb->get_results($sql);
            foreach ($users as $user) {
                $user_id = (int) $user->ID;
            }

            // reset password (in the wp-rest-api plugin, use NULL, and will not be login successfully)
            $password = 'newpwd';
            wp_set_password($password, $user_id);

            // auto login
            $creds = array();
            $creds['user_login'] = $openid;
            $creds['user_password'] = $password; //NULL? or ''
            $creds['remember'] = true;

            $user = wp_signon($creds, false);

            if (is_wp_error($user)) :
                echo $user->get_error_message();
            endif;

            // must use this function to replace current user
            wp_set_current_user($user->ID);

            // create customer key
            $data = array(
                'app_name' => wc_clean('WooCommerce App'),
                'user_id' => wc_clean($user_id),
                'return_url' => $this->get_formatted_url('https://new.renqizx.com/test-rest.php'),
                'callback_url' => $this->get_formatted_url('https://new.renqizx.com/test-rest.php?action=callback'),
                'scope' => wc_clean('read_write'),
            );

            $consumer_data = $this->create_keys($data['app_name'], $data['user_id'], $data['scope']);
            // $response      = $this->post_consumer_data( $consumer_data, $this->get_formatted_url( $data['callback_url'] ) );

            // if ( $response ) {
            //     wp_redirect( esc_url_raw( add_query_arg( array( 'success' => 1, 'user_id' => wc_clean( $data['user_id'] ) ), $this->get_formatted_url( $data['return_url'] ) ) ) );
            //     exit;
            // }

            if ($consumer_data) {
                $consumer_data['wc_basic_auth'] = base64_encode($consumer_data['consumer_key'] . ':' . $consumer_data['consumer_secret']);
                echo json_encode($consumer_data);
                exit();
            }
        }
    }
}

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    require_once dirname(MC_PLUGIN_PATH) . '/woocommerce/woocommerce.php';

    new MCWoocommerceAuthExt();
}