<?php

use MLA\Commons\Profile;
use MLA\Commons\Profile\Template;

$template = new Template;

do_action( 'bp_before_profile_edit_content' );

?>

<form action="<?php bp_the_profile_group_edit_form_action(); ?>" method="post" id="profile-edit-form" class="standard-form <?php bp_the_profile_group_slug(); ?>">
	<div class="left">
		<div class="academic-interests editable">
			<h4>Academic Interests</h4>
			<?php echo $template->get_academic_interests_edit(); ?>
		</div>
		<div class="recent-commons-activity">
			<h4>Recent Commons Activity</h4>
			<?php echo $template->get_activity(); ?>
		</div>
		<div class="commons-groups wordblock">
			<h4>Commons Groups</h4>
			<?php echo $template->get_groups(); ?>
		</div>
		<div class="commons-sites wordblock">
			<h4>Commons Sites</h4>
			<?php echo $template->get_sites(); ?>
		</div>
	</div>

	<div class="right">
		<div class="about editable">
			<h4>About</h4>
			<?php $template->get_edit_field( Profile::XPROFILE_FIELD_NAME_ABOUT ) ?>
		</div>
		<div class="education editable">
			<h4>Education</h4>
			<?php $template->get_edit_field( Profile::XPROFILE_FIELD_NAME_EDUCATION ) ?>
		</div>
		<div class="publications editable">
			<h4>Publications</h4>
			<?php $template->get_edit_field( Profile::XPROFILE_FIELD_NAME_PUBLICATIONS ) ?>
		</div>
		<div class="projects editable">
			<h4>Projects</h4>
			<?php $template->get_edit_field( Profile::XPROFILE_FIELD_NAME_PROJECTS ) ?>
		</div>
		<div class="work-shared-in-core">
			<h4>Work Shared in CORE</h4>
			<?php echo $template->get_core_deposits(); ?>
		</div>
		<div class="upcoming-talks-and-conferences editable">
			<h4>Upcoming Talks and Conferences</h4>
			<?php $template->get_edit_field( Profile::XPROFILE_FIELD_NAME_UPCOMING_TALKS_AND_CONFERENCES ) ?>
		</div>
		<div class="memberships editable">
			<h4>Memberships</h4>
			<?php $template->get_edit_field( Profile::XPROFILE_FIELD_NAME_MEMBERSHIPS ) ?>
		</div>
	</div>

	<?php do_action( 'bp_after_profile_field_content' ); ?>

	<?php wp_nonce_field( 'bp_xprofile_edit' ); ?>

	<div class="edit-action-bar">
		<?php do_action( 'template_notices' ); ?>

		<div class="generic-button">
			<input type="submit" value="Back to View Mode" id="cancel">
			<input type="submit" name="profile-group-edit-submit" id="profile-group-edit-submit" value="Save Changes" />
		</div>
	</div>
</form>

<?php do_action( 'bp_after_profile_edit_content' ); ?>
