<?php

class Test_BP_XProfile_Field_Type_Activity extends BP_UnitTestCase {

	//public static function setUpBeforeClass() {
	//}

	/**
	 * TODO
	 */
	public function test_display_filter() {
		$u = $this->factory->user->create();
		$g = $this->factory->xprofile_group->create();
		$f = $this->factory->xprofile_field->create( [
			'field_group_id' => $g,
			'type' => 'bp_activity',
		] );

		$this->factory->activity->create( array(
			'component' => buddypress()->profile->id,
			'type' => 'updated_profile',
			'user_id' => $u,
		) );

		$this->factory->activity->create( [
			'component' => buddypress()->profile->id,
			'type' => 'new_avatar',
			'user_id' => $u,
		] );

		$value = BP_XProfile_Field_Type_Activity::display_filter( null, $f );
		var_dump( $value );
		die;
		$this->assertNotEmpty( $data );

		var_dump( $output );
		die;

		$this->assertTrue( $field->is_valid( 'a string' ) );
		$this->assertFalse( $field->set_whitelist_values( 'pizza' )->is_valid( 'pasta' ) );
		$this->assertTrue( $field->is_valid( 'pizza' ) );
	}

	protected function setup_updated_profile_data() {
		$this->updated_profile_data['u'] = $this->factory->user->create();
		$this->updated_profile_data['g'] = $this->factory->xprofile_group->create();
		$this->updated_profile_data['f'] = $this->factory->xprofile_field->create( array(
			'field_group_id' => $this->updated_profile_data['g'],
		) );

	}

}
