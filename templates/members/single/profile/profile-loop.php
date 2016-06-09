<?php do_action( 'bp_before_profile_loop_content' ); ?>

<?php $Profile = MLA\Commons\Profile::get_instance(); ?>

<form> <?php // only here for styling consistency between edit & view modes ?>
	<div class="left">
		<div class="academic-interests wordblock">
			<h4>Academic Interests</h4>
			<?php echo $Profile->get_academic_interests(); ?>
		</div>
		<div class="recent-commons-activity">
			<h4>Recent Commons Activity</h4>
			<?php echo $Profile->get_activity(); ?>
		</div>
		<div class="commons-groups wordblock">
			<h4>Commons Groups</h4>
			<?php echo $Profile->get_groups(); ?>
		</div>
		<div class="commons-sites wordblock">
			<h4>Commons Sites</h4>
			<?php echo $Profile->get_sites(); ?>
		</div>
	</div>

	<div class="right">
		<div class="about">
			<h4>About</h4>
			<?php bp_member_profile_data( 'field=About' ) ?>
		</div>
		<div class="education">
			<h4>Education</h4>
			<?php bp_member_profile_data( 'field=Education' ) ?>
		</div>
		<div class="publications">
			<h4>Publications</h4>
			<?php bp_member_profile_data( 'field=Publications' ) ?>
		</div>
		<div class="projects">
			<h4>Projects</h4>
			<?php bp_member_profile_data( 'field=Projects' ) ?>
		</div>
		<div class="work-shared-in-core">
			<h4>Work Shared in CORE</h4>
			<?php echo $Profile->get_core_deposits(); ?>
		</div>
		<div class="upcoming-talks-and-conferences">
			<h4>Upcoming Talks and Conferences</h4>
			<?php bp_member_profile_data( 'field=Upcoming Talks and Conferences' ) ?>
		</div>
		<div class="memberships">
			<h4>Memberships</h4>
			<?php bp_member_profile_data( 'field=Memberships' ) ?>
		</div>
	</div>
</form>

<?php do_action( 'bp_after_profile_loop_content' ); ?>
