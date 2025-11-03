<?php require ABSPATH . 'wp-admin/options-head.php'; // not a general options page, so it must be included here ?>
<?php
$sanitized_tab = '';
if ( isset( $_GET['tab'] ) ) {
	$maybe_tab = sanitize_key( wp_unslash( $_GET['tab'] ) );
	if ( isset( SC_Settings_API::get_option_tabs()[ $maybe_tab ] ) ) {
		$sanitized_tab = $maybe_tab;
	}
}
$current_page = ( '' === $sanitized_tab ) ? $page : self::TEXT_DOMAIN . '/' . $sanitized_tab;
?>
<div id="<?php echo esc_attr( $current_page ); ?>" class="wrap">

	<h2 class="nav-tab-wrapper">
		<?php do_action( 'sprout_settings_tabs' ); ?>
	</h2>
	<div class="clearfix">
		<?php do_action( 'sprout_settings_page_sub_heading_'.$_GET['page'] ); ?>
	</div>

	<span id="ajax_saving" style="display:none" data-message="<?php _e( 'Saving...', 'sprout-invoices' ) ?>"></span>
	<form method="post" enctype="multipart/form-data" action="<?php echo admin_url( 'options.php' ); ?>" class="sprout_settings_form <?php echo esc_attr( $current_page ); if ( $ajax ) { echo ' ajax_save'; } if ( $ajax_full_page ) { echo ' full_page_ajax'; } ?>">
		<?php settings_fields( $current_page ); ?>
		<table class="form-table">
			<?php do_settings_fields( $current_page, 'default' ); ?>
		</table>
		<?php do_settings_sections( $current_page ); ?>
		<?php submit_button(); ?>
		<?php if ( $reset ) : ?>
			<?php submit_button( sc__( 'Reset Defaults' ), 'secondary', $current_page . '-reset', false ); ?>
		<?php endif ?>
	</form>

	<?php do_action( 'sprout_settings_page', $current_page ) ?>
	<?php do_action( 'sprout_settings_page_'.$current_page, $current_page ) ?>
</div>
