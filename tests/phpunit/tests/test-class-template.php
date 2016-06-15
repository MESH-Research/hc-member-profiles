<?php

use MLA\Commons\Profile;
use MLA\Commons\Profile\Migration;
use MLA\Commons\Profile\Template;

class Test_Template extends BP_UnitTestCase {

	public static function setUpBeforeClass() {
		$profile = Profile::get_instance();

		// since tests use an empty db, group & fields need to be created
		$migration = new Migration;
		$migration->create_xprofile_group();
		$migration->create_xprofile_fields();
	}

	/**
	 * @dataProvider get_twitter_link_provider
	 */
	function test_get_twitter_link( $value ) {
		// TODO need to populate a mock user with values in relevant fields for this type of thing
		$this->markTestSkipped();

		$template = new Template;
		$result = $template->get_twitter_link();

		$this->assertTrue( preg_match( '/^<a href="https:\/\/twitter.com\/[a-z0-9_]+">@[a-z0-9_]+<\/a>$/', $result ) );
	}

	function get_twitter_link_provider() {
		return [
			[ 'example' ],
			[ '@example' ],
			[ '@@example' ],
		];
	}

}
