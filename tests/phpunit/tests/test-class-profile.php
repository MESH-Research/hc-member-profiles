<?php

use MLA\Commons\Profile;
use MLA\Commons\Migration;

class Test_Profile extends BP_UnitTestCase {
	static $profile;

	static function setUpBeforeClass() {
		self::$profile = Profile::get_instance();
		$migration = new Migration;
		$migration->create_xprofile_group();
		$migration->create_xprofile_fields();
	}

	function test_construct() {
		$this->assertInstanceOf( 'MLA\Commons\Profile', new Profile );
	}

	function test_get_instance() {
		$this->assertInstanceOf( 'MLA\Commons\Profile', Profile::get_instance() );
	}

	function test_init() {
		self::$profile->init();
		$this->assertTrue( is_object( self::$profile->xprofile_group ) );
	}

	function test_filter_xprofile_get_field_types() {
		$filtered_field_type_keys = array_keys( bp_xprofile_get_field_types() );

		$this->assertContains( 'activity', $filtered_field_type_keys );
		$this->assertContains( 'groups', $filtered_field_type_keys );
		$this->assertContains( 'blogs', $filtered_field_type_keys );
		$this->assertNotContains( 'core_deposits', $filtered_field_type_keys );
		$this->assertNotContains( 'academic_interests', $filtered_field_type_keys );
	}
}
