<?php

use MLA\Commons\Profile;

class Test_Profile extends BP_UnitTestCase {
	function setup() {
	}

	function tearDown() {
	}

	function test_construct() {
		$this->assertInstanceOf( 'MLA\Commons\Profile', new Profile );
	}

	function test_get_instance() {
		$this->assertInstanceOf( 'MLA\Commons\Profile', Profile::get_instance() );
	}

	/**
	 * @uses locate_template()
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
			$this->assertContains( $instance->plugin_templates_dir, $result );
		} else {
			$this->assertThat(
				$result,
				$this->logicalNot(
					$this->stringContains( $instance->plugin_templates_dir )
				)
			);
		}
	}

	function filter_load_template_provider() {
		return [
			[ 'members/single/profile.php', true ],
			[ 'bogus', false ]
		];
	}
}
