<?php

do_action( 'bp_before_member_header' );

$Profile = MLA\Commons\Profile::get_instance();
$follow_counts = $Profile->get_follow_counts();

?>

<div id="item-header-avatar">
	<a href="<?php bp_displayed_user_link(); ?>">
		<?php bp_displayed_user_avatar( 'type=full' ); ?>
	</a>
</div><!-- #item-header-avatar -->

<div id="item-header-content">

	<div id="item-main">
		<h4 class="name">
			<?php bp_member_profile_data( 'field=Name' ) ?>
		</h4>
		<h4 class="title">
			<?php bp_member_profile_data( 'field=Title' ) ?>
		</h4>
		<h4 class="affiliation">
			<?php bp_member_profile_data( 'field=Institutional or Other Affiliation' ) ?>
		</h4>
		<div class="username">
			<?php echo "@" . bp_get_displayed_user_username() ?>
		</div>
		<?php foreach ( array( 'Site', 'Twitter', 'Facebook', 'LinkedIn', 'ORCID' ) as $field ): ?>
			<?php if ( ! empty( bp_get_member_profile_data( "field=$field" ) ) ): ?>
			<div class="field-<?php echo str_replace( ' ', '-', strtolower( strip_tags( $field ) ) ) ?>">
				<?php bp_member_profile_data( "field=$field" ) ?>
			</div>
			<?php endif ?>
		<?php endforeach ?>
	</div><!-- #item-main -->

	<?php do_action( 'bp_before_member_header_meta' ); ?>

	<div id="item-meta">

		<div class="n-items-in-core">
			<?php humcore_deposit_count() ?> items in CORE
		</div>
		<div class="n-groups">
			<?php bp_total_group_count_for_user() ?> groups
		</div>
		<div class="n-sites">
			<?php bp_total_blog_count_for_user() ?> sites
		</div>
		<div class="following-n-members">
			<?php if ( bp_displayed_user_id() === bp_loggedin_user_id() ): ?>
				<a href="<?php echo bp_loggedin_user_domain() . BP_FOLLOWING_SLUG ?>">
			<?php endif ?>
				<?php printf( __( 'Following <span>%d</span> members', 'bp-follow' ), $follow_counts['following'] ) ?>
			<?php if ( bp_displayed_user_id() === bp_loggedin_user_id() ): ?>
				</a>
			<?php endif ?>
		</div>

		<div id="item-buttons">

			<?php echo $Profile->get_header_actions(); ?>

		</div><!-- #item-buttons -->

		<?php do_action( 'bp_profile_header_meta' ); ?>

	</div><!-- #item-meta -->

</div><!-- #item-header-content -->

<?php do_action( 'bp_after_member_header' ); ?>
