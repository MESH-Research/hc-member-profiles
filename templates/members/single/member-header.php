<?php

use MLA\Commons\Profile;
use MLA\Commons\Profile\Template;

do_action( 'bp_before_member_header' );

$template = new Template;
$follow_counts = $template->get_follow_counts();
$affiliation_data = $template->get_xprofile_field_data( Profile::XPROFILE_FIELD_NAME_INSTITUTIONAL_OR_OTHER_AFFILIATION );
$affiliation_search_url = add_query_arg(
	[ 's' => urlencode( $affiliation_data ) ],
	bp_get_members_directory_permalink()
);
$twitter_link = $template->get_twitter_link();
$orcid_link = $template->get_orcid_link();

?>

<div id="item-header-avatar">
	<a href="<?php bp_displayed_user_link(); ?>">
		<?php bp_displayed_user_avatar( 'type=full' ); ?>
	</a>
</div><!-- #item-header-avatar -->

<div id="item-header-content">

	<div id="item-main">
		<h4 class="name">
			<?php echo $template->get_xprofile_field_data( Profile::XPROFILE_FIELD_NAME_NAME ) ?>
		</h4>
		<h4 class="title">
			<?php echo $template->get_xprofile_field_data( Profile::XPROFILE_FIELD_NAME_TITLE ) ?>
		</h4>
		<h4 class="affiliation">
			<a href="<?php echo esc_url( $affiliation_search_url ) ?>" rel="nofollow"><?php echo $affiliation_data ?></a>
		</h4>
		<div class="username">
			<em>Commons</em> username: <?php echo $template->get_username_link() ?>
		</div>
		<?php if ( $twitter_link ) : ?>
			<div class="twitter">
				<?php echo Profile::XPROFILE_FIELD_NAME_TWITTER_USER_NAME ?>: <?php echo $twitter_link ?>
			</div>
		<?php endif ?>
		<?php if ( $orcid_link ) : ?>
			<div class="orcid">
				<?php echo Profile::XPROFILE_FIELD_NAME_ORCID ?>: <?php echo $orcid_link ?>
			</div>
		<?php endif ?>
		<div class="site">
			<?php echo $template->get_site_link() ?>
		</div>
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
