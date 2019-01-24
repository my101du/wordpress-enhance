<?php
/**
 * Class SampleTest
 *
 * @package Renqizx_Enhance
 */

/**
 * Sample test case.
 */
class TestErrors extends WP_UnitTestCase {

	public function setUp(){
		parent::setUp();

        $this->class_instance = new MCErrors();
	}

	// public function test_add_error() {
	// 	$this->class_instance->add('test_error_code', 'this is test error message');
		
	// 	$errors = $this->class_instance->get();
		
	// 	$expected = [['code'=>'test_error_code', 'message'=>'this is test error message']];
		
	// 	$this->assertEquals($expected, $errors);
	// }

	// public function test_sms_verify() {

	// }

	/**
	 * A single example test.
	 */
	public function test_sample() {
		// Replace this with some actual testing code.
		$this->assertTrue( true );
	}
}
