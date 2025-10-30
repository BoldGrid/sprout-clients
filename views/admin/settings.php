<?php require ABSPATH . 'wp-admin/options-head.php'; // not a general options page, so it must be included here ?>
<?php
$base_page = isset( $page ) && '' !== $page ? $page : self::TEXT_DOMAIN;
$current_tab = isset( $current_tab ) ? $current_tab : '';
$settings_page = ( '' !== $current_tab ) ? $base_page . '/' . $current_tab : $base_page;
$wrap_id = $settings_page;
$form_classes = array( 'sprout_settings_form', $wrap_id );

if ( ! empty( $ajax ) ) {
	$form_classes[] = 'ajax_save';
}

if ( ! empty( $ajax_full_page ) ) {
	$form_classes[] = 'full_page_ajax';
}

$form_class_attribute = \implode( ' ', \array_filter( $form_classes ) );
?>
<div id="<?php echo esc_attr( $wrap_id ); ?>" class="wrap">

	<h2 class="nav-tab-wrapper">
		<?php do_action( 'sprout_settings_tabs' ); ?>
	</h2>
	<div class="clearfix">
		<?php do_action( 'sprout_settings_page_sub_heading_' . $base_page ); ?>
	</div>

	<span id="ajax_saving" style="display:none" data-message="<?php _e( 'Saving...', 'sprout-invoices' ) ?>"></span>
	<form method="post" enctype="multipart/form-data" action="<?php echo admin_url( 'options.php' ); ?>" class="<?php echo esc_attr( $form_class_attribute ); ?>">
		<?php settings_fields( $settings_page ); ?>
		<table class="form-table">
			<?php do_settings_fields( $settings_page, 'default' ); ?>
		</table>
		<?php do_settings_sections( $settings_page ); ?>
		<?php submit_button(); ?>
		<?php if ( $reset ) : ?>
			<?php submit_button( sc__( 'Reset Defaults' ), 'secondary', $settings_page . '-reset', false ); ?>
		<?php endif ?>
	</form>

	<?php do_action( 'sprout_settings_page', $settings_page ) ?>
	<?php do_action( 'sprout_settings_page_' . $settings_page, $settings_page ) ?>
</div>
