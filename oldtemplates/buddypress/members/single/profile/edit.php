<?php

use MLA\Commons\Profile;
use MLA\Commons\Profile\Template;

$template = new Template;

do_action( 'bp_before_profile_edit_content' );

?>

<form action="<?php bp_the_profile_group_edit_form_action(); ?>" method="post" id="profile-edit-form" class="standard-form <?php bp_the_profile_group_slug(); ?>">

	<div class="left">
		<div class="synced">
			<?php echo $template->get_edit_field( Profile::XPROFILE_FIELD_NAME_NAME ) ?>
			<?php echo $template->get_edit_field( Profile::XPROFILE_FIELD_NAME_TITLE ) ?>
			<?php echo $template->get_edit_field( Profile::XPROFILE_FIELD_NAME_INSTITUTIONAL_OR_OTHER_AFFILIATION ) ?>
		</div>
		<div class="social">
			<?php echo $template->get_edit_field( Profile::XPROFILE_FIELD_NAME_TWITTER_USER_NAME ) ?>
			<?php echo $template->get_edit_field( Profile::XPROFILE_FIELD_NAME_ORCID ) ?>
			<?php echo $template->get_edit_field( Profile::XPROFILE_FIELD_NAME_FACEBOOK ) ?>
			<?php echo $template->get_edit_field( Profile::XPROFILE_FIELD_NAME_LINKEDIN ) ?>
			<?php echo $template->get_edit_field( Profile::XPROFILE_FIELD_NAME_SITE ) ?>
		</div>
		<div class="academic-interests editable">
			<h4><?php echo $template->get_academic_interests_field_display_name(); ?></h4>
			<?php echo $template->get_academic_interests_edit(); ?>
		</div>
		<div class="commons-groups wordblock">
			<h4>Commons Groups</h4>
			<?php echo $template->get_groups(); ?>
		</div>
		<div class="recent-commons-activity">
			<h4>Recent Commons Activity</h4>
			<?php echo $template->get_activity(); ?>
		</div>
		<div class="commons-sites wordblock">
			<h4>Commons Sites</h4>
			<?php echo $template->get_sites(); ?>
		</div>
	</div>

	<div class="right">
		<div class="about editable hideable">
			<h4><?php echo Profile::$display_names[ Profile::XPROFILE_FIELD_NAME_ABOUT ] ?></h4>
			<?php echo $template->get_edit_field( Profile::XPROFILE_FIELD_NAME_ABOUT ) ?>
		</div>
		<div class="education editable hideable">
			<h4><?php echo Profile::$display_names[ Profile::XPROFILE_FIELD_NAME_EDUCATION ] ?></h4>
			<?php echo $template->get_edit_field( Profile::XPROFILE_FIELD_NAME_EDUCATION ) ?>
		</div>
		<div class="cv editable hideable">
			<h4><?php echo Profile::$display_names[ Profile::XPROFILE_FIELD_NAME_CV ] ?></h4>
			<?php echo $template->get_edit_field( Profile::XPROFILE_FIELD_NAME_CV ) ?>
		</div>
		<div class="work-shared-in-core editable">
			<h4><?php echo Profile::$display_names[ Profile::XPROFILE_FIELD_NAME_CORE_DEPOSITS ] ?></h4>
			<?php echo $template->get_edit_field( Profile::XPROFILE_FIELD_NAME_CORE_DEPOSITS ) ?>
		</div>
		<div class="publications editable hideable">
			<h4><?php echo Profile::$display_names[ Profile::XPROFILE_FIELD_NAME_PUBLICATIONS ] ?></h4>
			<?php echo $template->get_edit_field( Profile::XPROFILE_FIELD_NAME_PUBLICATIONS ) ?>
		</div>
		<div class="projects editable hideable">
			<h4><?php echo Profile::$display_names[ Profile::XPROFILE_FIELD_NAME_PROJECTS ] ?></h4>
			<?php echo $template->get_edit_field( Profile::XPROFILE_FIELD_NAME_PROJECTS ) ?>
		</div>
		<div class="upcoming-talks-and-conferences editable hideable">
			<h4><?php echo Profile::$display_names[ Profile::XPROFILE_FIELD_NAME_UPCOMING_TALKS_AND_CONFERENCES ] ?></h4>
			<?php echo $template->get_edit_field( Profile::XPROFILE_FIELD_NAME_UPCOMING_TALKS_AND_CONFERENCES ) ?>
		</div>
		<div class="memberships editable hideable">
			<h4><?php echo Profile::$display_names[ Profile::XPROFILE_FIELD_NAME_MEMBERSHIPS ] ?></h4>
			<?php echo $template->get_edit_field( Profile::XPROFILE_FIELD_NAME_MEMBERSHIPS ) ?>
		</div>
	</div>

	<div class="edit-action-bar">
		<?php do_action( 'template_notices' ); ?>

		<div class="generic-button">
			<input type="submit" value="Back to View Mode" id="cancel">
			<input type="submit" name="profile-group-edit-submit" id="profile-group-edit-submit" value="Save Changes" />
		</div>
	</div>

	<input type="hidden" name="field_ids" id="field_ids" value="<?php bp_the_profile_field_ids(); ?>" />

	<?php wp_nonce_field( 'bp_xprofile_edit' ); ?>

</form>

<?php do_action( 'bp_after_profile_edit_content' ); ?>
