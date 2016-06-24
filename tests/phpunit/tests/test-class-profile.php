<?php

use MLA\Commons\Profile;

class Test_Profile extends BP_UnitTestCase {

	function test_construct() {
		$this->assertInstanceOf( 'MLA\Commons\Profile', new Profile );
	}

	function test_get_instance() {
		$this->assertInstanceOf( 'MLA\Commons\Profile', Profile::get_instance() );
	}

	/**
	 * @dataProvider filter_load_template_provider
	 */
	function test_filter_load_template( $template_name, $should_replace ) {
		// load_template filter should be added on instantiation
		$instance = Profile::get_instance();

		// TODO something's missing (probably some do_action() initialization somewhere)
		// to make locate_template work the same here as it does when rendering
		//$result = locate_template( [ $template_name ], true );
		$result = $instance->filter_load_template( $template_name );

		if ( $should_replace ) {
			$this->assertContains( Profile::$plugin_templates_dir, $result );
		} else {
			$this->assertThat(
				$result,
				$this->logicalNot(
					$this->stringContains( Profile::$plugin_templates_dir )
				)
			);
		}
	}

	function filter_load_template_provider() {
		return [
			[ 'members/single/member-header.php', true ],
			[ 'members/single/profile/edit.php', true ],
			[ 'members/single/profile/profile-loop.php', true ],
			[ 'members/single/profile.php', true ],
			[ 'groups/groups-loop.php', false ],
			[ 'bogus', false ],
			[ '', false ]
		];
	}

}
