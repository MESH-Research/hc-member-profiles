<?php

use MLA\Commons\Profile;
use MLA\Commons\Profile\Template;

$template = new Template;

do_action( 'bp_before_profile_loop_content' );

?>

<form> <?php // <form> is only here for styling consistency between edit & view modes ?>

	<div class="left">
		<?php echo $template->get_field( Profile::XPROFILE_FIELD_NAME_ACADEMIC_INTERESTS ) ?>
		<?php echo $template->get_field( Profile::XPROFILE_FIELD_NAME_GROUPS ) ?>
		<?php echo $template->get_field( Profile::XPROFILE_FIELD_NAME_ACTIVITY ) ?>
		<?php echo $template->get_field( Profile::XPROFILE_FIELD_NAME_BLOGS ) ?>
	</div>

	<div class="right">
		<?php echo $template->get_field( Profile::XPROFILE_FIELD_NAME_ABOUT ) ?>
		<?php echo $template->get_field( Profile::XPROFILE_FIELD_NAME_EDUCATION ) ?>
		<?php echo $template->get_field( Profile::XPROFILE_FIELD_NAME_CORE_DEPOSITS ) ?>
		<?php echo $template->get_field( Profile::XPROFILE_FIELD_NAME_PUBLICATIONS ) ?>
		<?php echo $template->get_field( Profile::XPROFILE_FIELD_NAME_PROJECTS ) ?>
		<?php echo $template->get_field( Profile::XPROFILE_FIELD_NAME_UPCOMING_TALKS_AND_CONFERENCES ) ?>
		<?php echo $template->get_field( Profile::XPROFILE_FIELD_NAME_MEMBERSHIPS ) ?>
	</div>

</form>

<?php do_action( 'bp_after_profile_loop_content' ); ?>
