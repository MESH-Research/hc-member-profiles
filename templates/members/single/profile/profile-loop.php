<?php

use MLA\Commons\Profile;
use MLA\Commons\Profile\Template;

$template = new Template;

do_action( 'bp_before_profile_loop_content' );

?>

<form> <?php // <form> is only here for styling consistency between edit & view modes ?>

	<div class="left">
		<div class="academic-interests wordblock">
			<h4>Academic Interests</h4>
			<?php echo $template->get_academic_interests(); ?>
		</div>
		<div class="recent-commons-activity">
			<h4>Recent Commons Activity</h4>
			<?php echo $template->get_activity(); ?>
		</div>
		<div class="commons-groups wordblock js-dynamic-height" data-maxheight="250">
			<h4>Commons Groups</h4>
			<div class="dynamic-height-wrap">
				<?php echo $template->get_groups(); ?>
			</div>
			<button class="js-dynamic-show-hide button" title="Show more" data-replace-text="Show less">Show more</button>
		</div>
		<div class="commons-sites wordblock js-dynamic-height" data-maxheight="250">
			<h4>Commons Sites</h4>
			<div class="dynamic-height-wrap">
				<?php echo $template->get_sites(); ?>
			</div>
			<button class="js-dynamic-show-hide button" title="Show more" data-replace-text="Show less">Show more</button>
		</div>
	</div>

	<div class="right">
		<?php echo $template->get_field( Profile::XPROFILE_FIELD_NAME_ABOUT ) ?>
		<?php echo $template->get_field( Profile::XPROFILE_FIELD_NAME_EDUCATION ) ?>
		<?php echo $template->get_field( Profile::XPROFILE_FIELD_NAME_PUBLICATIONS ) ?>
		<?php echo $template->get_field( Profile::XPROFILE_FIELD_NAME_PROJECTS ) ?>
		<div class="work-shared-in-core js-dynamic-height" data-maxheight="250">
			<h4>Work Shared in CORE</h4>
			<div class="dynamic-height-wrap">
				<?php echo $template->get_core_deposits(); ?>
			</div>
			<button class="js-dynamic-show-hide button" title="Show more" data-replace-text="Show less">Show more</button>
		</div>
		<?php echo $template->get_field( Profile::XPROFILE_FIELD_NAME_UPCOMING_TALKS_AND_CONFERENCES ) ?>
		<?php echo $template->get_field( Profile::XPROFILE_FIELD_NAME_MEMBERSHIPS ) ?>
	</div>

</form>

<?php do_action( 'bp_after_profile_loop_content' ); ?>
