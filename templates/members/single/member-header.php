<?php

do_action( 'bp_before_member_header' );

$template = new MLA\Commons\Profile\Template;
$follow_counts = $template->get_follow_counts();
$affiliation_data = bp_get_member_profile_data( 'field=Institutional or Other Affiliation' );
$affiliation_search_url = add_query_arg(
	[ 's' => urlencode( $affiliation_data ) ],
	bp_get_members_directory_permalink()
);

?>

<div id="item-header-avatar">
	<a href="<?php bp_displayed_user_link(); ?>">
		<?php bp_displayed_user_avatar( 'type=full' ); ?>
	</a>
</div><!-- #item-header-avatar -->

<div id="item-header-content">

	<div id="item-main">
		<h4 class="name">
			<?php echo $template->get_xprofile_field_data( 'Name' ) ?>
		</h4>
		<h4 class="title">
			<?php echo $template->get_xprofile_field_data( 'Title' ) ?>
		</h4>
		<h4 class="affiliation">
			<a href="<?php echo esc_url( $affiliation_search_url ) ?>" rel="nofollow"><?php echo $affiliation_data ?></a>
		</h4>
		<div class="username">
			<?php echo "@" . bp_get_displayed_user_username() ?>
		</div>
		<?php foreach ( array( 'Site', 'Twitter', 'Facebook', 'LinkedIn', 'ORCID' ) as $field ): ?>
			<?php if ( ! empty( bp_get_member_profile_data( "field=$field" ) ) ): ?>
			<div class="field-<?php echo str_replace( ' ', '-', strtolower( strip_tags( $field ) ) ) ?>">
				<a href="<?php bp_member_profile_data( "field=$field" ) ?>"><?php bp_member_profile_data( "field=$field" ) ?></a>
			</div>
			<?php endif ?>
		<?php endforeach ?>
	</div><!-- #item-main -->

	<?php do_action( 'bp_before_member_header_meta' ); ?>

	<div id="item-meta">
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
			<?php echo $template->get_header_actions(); ?>
		</div><!-- #item-buttons -->

		<?php do_action( 'bp_profile_header_meta' ); ?>
	</div><!-- #item-meta -->
</div><!-- #item-header-content -->

<?php do_action( 'bp_after_member_header' ); ?>
