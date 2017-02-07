<?php

use MLA\Commons\Profile;

class Test_Profile extends BP_UnitTestCase {

	function test_construct() {
		$this->assertInstanceOf( 'MLA\Commons\Profile', new Profile );
	}

	function test_get_instance() {
		$this->assertInstanceOf( 'MLA\Commons\Profile', Profile::get_instance() );
	}

}
