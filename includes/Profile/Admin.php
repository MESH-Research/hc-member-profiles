<?php

namespace MLA\Commons\Profile;

use \MLA\Commons\Profile;

class Admin {

	function add_admin_menu() {
		// Bail if current user cannot moderate community.
		if ( ! bp_current_user_can( 'bp_moderate' ) ) {
			return false;
		}

		add_users_page(
			_x( 'Profile Options', 'Profile admin page title', 'buddypress' ),
			_x( 'Profile Options', 'Admin Users menu', 'buddypress' ),
			'manage_options',
			'profile-options',
			[ $this, 'profile_admin' ]
		);

	}

	function profile_admin( $message = '', $type = 'error' ) {

		// Get all of the profile groups & fields.
		$groups = bp_xprofile_get_groups( [
			'fetch_fields' => true,
		] );

		?>

		<div class="wrap">

			<h1>
				Profile Options
			</h1>

			<form action="" id="profile-field-form" method="post">

				<?php wp_nonce_field( 'profile_create_group', '_wpnonce_profile_create_group' ); ?>
				<?php wp_nonce_field( 'profile_create_fields', '_wpnonce_profile_create_fields' ); ?>

				<?php if ( !empty( $groups ) ) : foreach ( $groups as $group ) : ?>

					<div id="tabs-<?php echo esc_attr( $group->id ); ?>" class="tab-wrapper">

						<?php if ( ! empty( $group->description ) ) : ?>

							<p><?php
							/** This filter is documented in bp-xprofile/bp-xprofile-template.php */
							echo esc_html( apply_filters( 'bp_get_the_profile_group_description', $group->description ) );
							?></p>

						<?php endif; ?>

						<fieldset id="<?php echo esc_attr( $group->id ); ?>" class="connectedSortable field-group" aria-live="polite" aria-atomic="true" aria-relevant="all">
							<legend class="screen-reader-text"><?php
							/** This filter is documented in bp-xprofile/bp-xprofile-template.php */
							/* translators: accessibility text */
							printf( esc_html__( 'Fields for "%s" Group', 'buddypress' ), apply_filters( 'bp_get_the_profile_group_name', $group->name ) );
							?></legend>
						</fieldset>

						<?php if ( empty( $group->can_delete ) ) : ?>

							<p><?php esc_html_e( '* Fields in this group appear on the signup page.', 'buddypress' ); ?></p>

						<?php endif; ?>

					</div>

				<?php endforeach; else : ?>

				<div id="message" class="error"><p><?php _ex( 'You have no groups.', 'You have no profile fields groups.', 'buddypress' ); ?></p></div>
				<p><a href="users.php?page=bp-profile-setup&amp;mode=add_group"><?php _ex( 'Add New Group', 'Add New Profile Fields Group', 'buddypress' ); ?></a></p>

				<?php endif; ?>

		</form>
	</div>
	<?php
	}

}
