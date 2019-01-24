<?php
/**
 * Class SampleTest
 *
 * @package Renqizx_Enhance
 */

require(dirname(__FILE__) . '/../vendor/autoload.php');

/**
 * Sample test case.
 */
class TestMobileAuth extends WP_UnitTestCase {

	protected $client;

	public function setUp(){
		parent::setUp();

		$this->client = new GuzzleHttp\Client([
			'base_uri' => 'https://www.renqizx.com'
		]);
	}

	// public function test_register() {
		// $inputErrors = new MCErrors();

		// $errors = $this->class_instance::verifyBasicFields($inputErrors);
		// $expected = json_encode(['result'=>'fail', 'errors'=>[]]);
		// $this->assertEquals($expected, $errors);
	// }

	public function test_sms() {
		if (!isset($_SESSION)) {
			session_start();
			session_regenerate_id(TRUE);
		}

		$plugin_dir = '/Users/ericzhang/zzy_work/env/wwwroot/customer/renqizx/wwwroot/wp-content/plugins/renqizx-enhance/';

		require_once($plugin_dir . 'includes/captcha/config.php');
		require_once($plugin_dir . 'includes/captcha/captcha.class.php');

		global $captcha_session_name;

		$captcha = new SimpleCaptcha();
		$captcha->resourcesPath = $plugin_dir . "includes/captcha/resources";
		$captcha->session_var = $captcha_session_name;

		// in unit test, do not write image(send headers)
		$captcha->unit_test = true;

		$captcha->CreateImage();

		$captchaText = explode('-', $_SESSION[$captcha_session_name]);

		// Cannot modify header information - headers already sent by (output started at /private/var/folders/7g/hk8r20fd003ftkl_q69mkds40000gn/T/wordpress-tests-lib/includes/bootstrap.php:72)
		// error_reporting(0);

		// $this->assertContains(
		// '"Content-type: image/jpeg"', xdebug_get_headers()
		// );

		$data = [
			'action'=>'sendSms',
			'phone'=>'18565720073',
			'captcha_code'=>$captchaText[0],
			'token'=>wp_create_nonce('renqizx-enhance/renqizx-enhance.php'),
			'admin'=>0
		];
		
		echo 'request:' . json_encode( $data );

		$response = $this->client->post('/wp-admin/admin-ajax.php', [
			'headers' => [
				'X-Requested-With'=> 'XMLHttpRequest'
			],
			'form_params' => $data
			// 'body'=>json_encode( $data )
			// 'json'=>$data
		]);
		
		echo 'response:' . $response->getBody();

		$this->assertEquals('1234-aa', $response);
	}
}
