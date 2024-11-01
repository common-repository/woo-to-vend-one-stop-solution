<?php
/**
 * Class SampleTest
 *
 * @package Vendoo
 */

/**
 * Sample test case.
 */
class WVOS_Vend_Functions_Test extends WP_UnitTestCase {

	/**
	 * A single example test.
	 */
	public function name_test() {
		// Replace this with some actual testing code.
		$x = new WVOS_Vend_Functions('asd','asd');
		$result = $x->callback_wpp_debug();
		$this->assertEquals( 'What are you looking for?' ,$result);
	}
}
