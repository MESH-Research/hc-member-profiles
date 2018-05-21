<?php
/**
 * Buddypress template override.
 *
 * @package Hc_Member_Profiles
 */

do_action( 'bp_before_profile_loop_content' );

?>

<form> <?php // <form> is only here for styling consistency between edit & view modes ?>

	<div class="left">
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_ACADEMIC_INTERESTS ); ?>
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_GROUPS ); ?>
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_ACTIVITY ); ?>
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_BLOGS ); ?>
	</div>

	<div class="right">
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_ABOUT ); ?>
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_EDUCATION ); ?>
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_CV ); ?>
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_CORE_DEPOSITS ); ?>
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_PUBLICATIONS ); ?>
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_PROJECTS ); ?>
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_UPCOMING_TALKS_AND_CONFERENCES ); ?>
		<?php echo hcmp_get_field( HC_Member_Profiles_Component::XPROFILE_FIELD_NAME_MEMBERSHIPS ); ?>
	</div>

</form>

<?php do_action( 'bp_after_profile_loop_content' ); ?>
