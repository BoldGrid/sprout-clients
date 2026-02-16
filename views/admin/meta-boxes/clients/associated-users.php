<?php if ( $associated_users ) : ?>
	<div id="client_users" class="clearfix">
		<ul>
			<?php foreach ( $associated_users as $user_id ) :

				$a_user = get_userdata( $user_id );
				if ( ! is_a( $a_user, 'WP_User' ) ) {
					continue;
				} ?>
				<li class="associated_user clearfix">

					<?php do_action( 'sc_client_pre_user_listed', $user_id, $id ) ?>

					<button data-id="<?php echo (int) $user_id ?>" data-client-id="<?php echo (int) $id ?>" class="unassociate_user button button-small casper"><?php _e( 'Remove' , 'sprout-invoices' ) ?></button>

					<?php
						$full_name = ( '' === $a_user->first_name && '' === $a_user->last_name ) ? __( 'Mystery Man' , 'sprout-invoices' ) : $a_user->first_name . '&nbsp;' . $a_user->last_name ;
							?>
					<span class="users_first_last_name"><?php echo esc_attr( $full_name ) ?>&nbsp;<?php printf( '<a href="%s"><span class="dashicons dashicons-edit"></span></a>', esc_url( admin_url( 'user-edit.php?user_id=' . $user_id ) ) ) ?></span>

					<div class="gravatar_wrap">
						<span class="client_user_gravatar clearfix"><?php echo get_avatar( $user_id, 120 ) ?></span>

						<div class="client_users_social_icons clearfix">
							<?php
								$twitter_handle = ltrim( trim( sc_get_users_twitter( $user_id ) ), '@' );
								$linkedin_url = sc_get_users_linkedin( $user_id );
							?>
							<?php if ( ! empty( $twitter_handle ) && 'N/A' !== $twitter_handle ) : ?>
								<span class="user_meta users_twitter"><?php printf( '&nbsp;<a href="%s" title="%s" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-twitter"></span></a>', esc_url( 'https://twitter.com/' . $twitter_handle ), esc_attr( sc__( 'Twitter Profile' ) ) ); ?></span>
							<?php endif; ?>

							<?php if ( ! empty( $linkedin_url ) && 'N/A' !== $linkedin_url ) : ?>
								<span class="user_meta users_linkedin"><?php printf( '&nbsp;<a href="%s" title="%s" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-external"></span></a>', esc_url( $linkedin_url ), esc_attr( sc__( 'Linkedin Profile' ) ) ); ?></span>
							<?php endif; ?>
						</div>
					</div>

					<div class="sc_person_info clearfix">
						<span class="user_meta users_name"><span class="dashicons dashicons-admin-users"></span>&nbsp;<?php echo esc_attr( $a_user->display_name ) ?></span>

						<span class="user_meta users_email"><span class="dashicons dashicons-email-alt"></span>&nbsp;<a href="mailto:<?php echo esc_attr( $a_user->user_email ) ?>"><?php echo esc_attr( $a_user->user_email ) ?></a></span>

						<?php if ( '' !== sc_get_users_phone( $user_id ) ) : ?>
							<span class="user_meta users_dob"><span class="dashicons dashicons-phone"></span> <?php echo esc_attr( sc_get_users_phone( $user_id ) ) ?></span>
						<?php endif ?>

						<?php if ( '' !== $a_user->user_url ) : ?>
							<span class="user_meta users_website"><span class="dashicons dashicons-admin-site"></span>&nbsp;<a href="<?php echo esc_url( $a_user->user_url ) ?>" title="<?php echo esc_attr( sc__( "User's website" ) ) ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_url( $a_user->user_url ) ?></a></span>
						<?php endif ?>

						<?php if ( '' !== sc_get_users_dob( $user_id ) ) : ?>
							<span class="user_meta users_dob"><span class="dashicons dashicons-calendar"></span> <?php echo esc_attr( sc_get_users_dob( $user_id ) ) ?></span>
						<?php endif ?>

						<?php do_action( 'sc_client_user_listed', $user_id, $id ) ?>

						<textarea id="user_note_field_<?php echo esc_attr( $user_id ) ?>" class="si_redactorize sc_user_note" data-user-id="<?php echo (int) $user_id ?>" placeholder="<?php _e( 'Save a note about this person.' , 'sprout-invoices' ) ?>"><?php echo esc_textarea( sc_get_users_note( $user_id ) ) ?></textarea>
						
						<button id="submit_user_note_<?php echo esc_attr( $user_id ) ?>" class="submit_user_note button button-mute" data-user-id="<?php echo esc_attr( $user_id ) ?>"><?php echo _e( 'Save Note' , 'sprout-invoices' ) ?></button>
					</div>
				</li>
			<?php endforeach ?>
		</ul>
	</div><!-- #client_users -->

<?php endif ?>

<div id="associated_users_mngt">
	<select id="associated_users" class="sa_select2" data-nonce="<?php echo wp_create_nonce( SC_Controller::NONCE ) ?>">
		<option></option>
		<?php foreach ( $users as $user ) : ?>
			<?php if ( ! in_array( $user->ID, $associated_users ) ) : ?>
				<option value="<?php echo (int) $user->ID ?>" <?php selected( in_array( $user->ID, $associated_users ), true ) ?> data-client-id="<?php echo (int) $id; ?>"><?php echo esc_html( $user->display_name ) ?></option>
			<?php endif ?>
		<?php endforeach ?>
	</select>&nbsp;<span class="helptip associate_user_select_help" data-sa-dropdown="#associate_user_select_help"></span>
		<div id="associate_user_select_help" class="sa-dropdown sa-dropdown-tip sa-dropdown-relative">
			<div class="sa-dropdown-panel">
				<?php _e( 'Contacts can have multiple people associated; consider a company/client that may have multiple contacts. Each person may receive notifications depending on what is sent.' , 'sprout-invoices' ) ?>
			</div>
		</div>
	
	<a href="#TB_inline?width=300&height=250&inlineId=user_creation_modal" id="user_creation_modal_tb_link" class="thickbox button" title="<?php echo esc_attr( sc__( 'Create new user for this lead' ) ) ?>"><?php sc_e( 'New Person' ) ?></a>&nbsp;<span class="helptip add_user_select_help" data-sa-dropdown="#add_user_select_help"></span>
		<div id="add_user_select_help" class="sa-dropdown sa-dropdown-tip sa-dropdown-relative">
			<div class="sa-dropdown-panel">
				<?php _e( '"People" are WordPress users set with the role of client, they have no permissions by default but providing your contacts a user (which they won\'t know about by default) allows for a whole slew of features and flexibility for customizations.', 'sprout-invoices' ) ?>
			</div>
		</div>
</div>
