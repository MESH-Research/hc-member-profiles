<?php do_action( 'bp_before_profile_edit_content' ); ?>

<?php $Profile = MLA\Commons\Profile::get_instance(); ?>

<?php if ( bp_has_profile( 'profile_group_id=' . bp_get_current_profile_group_id() ) ) : ?>
	<?php while ( bp_profile_groups() ) : bp_the_profile_group(); ?>

		<form action="<?php bp_the_profile_group_edit_form_action(); ?>" method="post" id="profile-edit-form" class="standard-form <?php bp_the_profile_group_slug(); ?>">
			<div class="left">
				<div class="academic-interests editable">
					<h4>Academic Interests</h4>
					<?php echo $Profile->get_academic_interests(); ?>
				</div>
				<div class="recent-commons-activity">
					<h4>Recent Commons Activity</h4>
					<?php echo $Profile->get_activity(); ?>
				</div>
				<div class="commons-groups">
					<h4>Commons Groups</h4>
					<?php echo $Profile->get_groups(); ?>
				</div>
				<div class="commons-sites">
					<h4>Commons Sites</h4>
					<?php echo $Profile->get_sites(); ?>
				</div>
			</div>

			<div class="right">
				<div class="about editable">
					<h4>About</h4>
					<?php $Profile->get_edit_field( 'About' ) ?>
				</div>
				<div class="education editable">
					<h4>Education</h4>
					<?php $Profile->get_edit_field( 'Education' ) ?>
				</div>
				<div class="publications editable">
					<h4>Publications</h4>
					<?php $Profile->get_edit_field( 'Publications' ) ?>
				</div>
				<div class="projects editable">
					<h4>Projects</h4>
					<?php $Profile->get_edit_field( 'Projects' ) ?>
				</div>
				<div class="work-shared-in-core">
					<h4>Work Shared in CORE</h4>
					<?php echo $Profile->get_core_deposits(); ?>
				</div>
				<div class="upcoming-talks-and-conferences editable">
					<h4>Upcoming Talks and Conferences</h4>
					<?php $Profile->get_edit_field( 'Upcoming Talks and Conferences' ) ?>
				</div>
				<div class="memberships editable">
					<h4>Memberships</h4>
					<?php $Profile->get_edit_field( 'Memberships' ) ?>
				</div>
			</div>

			<?php do_action( 'bp_after_profile_field_content' ); ?>

			<?php // TODO this is inaccurate, is that a problem? ?>
			<input type="hidden" name="field_ids" id="field_ids" value="<?php bp_the_profile_field_ids(); ?>" />

			<?php wp_nonce_field( 'bp_xprofile_edit' ); ?>

			<div class="edit-action-bar">
				<?php // TODO errors when saving should show here, need to ensure no other notices get inserted when editing ?>
				<?php do_action( 'template_notices' ); ?>

				<div class="generic-button">
					<input type="submit" value="Back to View Mode" id="cancel">
					<input type="submit" name="profile-group-edit-submit" id="profile-group-edit-submit" value="Save Changes" />
				</div>
			</div>
		</form>

	<?php endwhile; ?>
<?php endif; ?>

<?php do_action( 'bp_after_profile_edit_content' ); ?>
