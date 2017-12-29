<?php do_action( 'bp_before_profile_content' ); ?>

<div id="profile-main" role="main">

	<?php
		// Profile Edit
		if ( bp_is_current_action( 'edit' ) ) {
			bp_locate_template( array( 'members/single/profile/edit.php' ), true );
		}

		// Display XProfile
		elseif ( bp_is_active( 'xprofile' ) ) {
			bp_locate_template( array( 'members/single/profile/profile-loop.php' ), true );
		}
	?>

</div><!-- .profile -->

<?php do_action( 'bp_after_profile_content' ); ?>
