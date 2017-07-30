<?php

use MLA\Commons\Profile;
use MLA\Commons\Template;

do_action( 'bp_before_profile_loop_content' );

bp_has_profile( 'profile_group_id=' . Profile::get_instance()->xprofile_group->id );
bp_the_profile_group();

?>

<form> <?php // <form> is only here for styling consistency between edit & view modes ?>

	<div class="left">
		<?php echo Template::get_field( Profile::XPROFILE_FIELD_NAME_ACADEMIC_INTERESTS ) ?>
		<?php echo Template::get_field( Profile::XPROFILE_FIELD_NAME_GROUPS ) ?>
		<?php echo Template::get_field( Profile::XPROFILE_FIELD_NAME_ACTIVITY ) ?>
		<?php echo Template::get_field( Profile::XPROFILE_FIELD_NAME_BLOGS ) ?>
	</div>

	<div class="right">
		<?php echo Template::get_field( Profile::XPROFILE_FIELD_NAME_ABOUT ) ?>
		<?php echo Template::get_field( Profile::XPROFILE_FIELD_NAME_EDUCATION ) ?>
		<?php echo Template::get_field( Profile::XPROFILE_FIELD_NAME_CORE_DEPOSITS ) ?>
		<?php echo Template::get_field( Profile::XPROFILE_FIELD_NAME_PUBLICATIONS ) ?>
		<?php echo Template::get_field( Profile::XPROFILE_FIELD_NAME_PROJECTS ) ?>
		<?php echo Template::get_field( Profile::XPROFILE_FIELD_NAME_UPCOMING_TALKS_AND_CONFERENCES ) ?>
		<?php echo Template::get_field( Profile::XPROFILE_FIELD_NAME_MEMBERSHIPS ) ?>
	</div>

</form>

<?php do_action( 'bp_after_profile_loop_content' ); ?>
