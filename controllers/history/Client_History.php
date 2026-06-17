<?php

/**
 * History Controller
 *
 *
 * @package SC_Client_History
 * @subpackage Clients
 */
class SC_Client_History extends SC_Controller {
	private static $current_page = 1;
	private static $total_pages = 1;

	public static function init() {

		add_action( 'admin_init', array( __CLASS__, 'register_meta_boxes' ), 5 );

		add_action( 'wp_ajax_sa_create_client_private_note',  array( __CLASS__, 'maybe_create_private_note' ), 10, 0 );
		add_action( 'wp_ajax_nopriv_sa_create_client_private_note',  array( __CLASS__, 'maybe_create_private_note' ), 10, 0 );

		add_filter( 'client_history', array( __CLASS__, 'add_records_to_client_history' ), 10, 3 );

		add_filter( 'client_history', array( __CLASS__, 'filter_client_history' ), PHP_INT_MAX, 2 );
	}

	/////////////////
	// Meta boxes //
	/////////////////

	/**
	 * Regsiter meta boxes for estimate editing.
	 *
	 * @return
	 */
	public static function register_meta_boxes() {
		// estimate specific
		$args = array(
			'si_client_history' => array(
				'title' => sc__( 'History' ),
				'show_callback' => array( __CLASS__, 'show_client_history_view' ),
				'save_callback' => array( __CLASS__, '_save_null' ),
				'context' => 'normal',
				'priority' => 'low',
				'weight' => 50,
			),
		);
		do_action( 'sprout_meta_box', $args, Sprout_Client::POST_TYPE );
	}


	/**
	 * Show the history
	 *
	 * @param WP_Post $post
	 * @param array   $metabox
	 * @return
	 */
	public static function show_client_history_view( $post, $metabox ) {
		if ( 'auto-draft' === $post->post_status ) {
			printf( '<p>%s</p>', sc__( 'No history available.' ) );
			return;
		}
		$client = Sprout_Client::get_instance( $post->ID );
		$type = ( isset( $_REQUEST['history_type'] ) ) ? $_REQUEST['history_type'] : '' ;
		self::load_view( 'admin/meta-boxes/clients/history', array(
				'id' => $post->ID,
				'post' => $post,
				'client' => $client,
				'history' => $client->get_history( $type ),
				'pagination' => self::get_pagination(),
		), false );
	}

	public static function maybe_create_private_note() {

		if ( ! isset( $_REQUEST['security'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Forget something?' , 'sprout-invoices' ) ) );
		}

		$nonce = $_REQUEST['security'];
		if ( ! wp_verify_nonce( $nonce, self::NONCE ) ) {
			wp_send_json_error( array( 'message' => __( 'Not going to fall for it!' , 'sprout-invoices' ) ) );
		}

		if ( ! current_user_can( 'edit_sprout_clients' ) ) {
			return;
		}

		$record_id = (int) SC_Internal_Records::new_record( $_REQUEST['notes'], SC_Controller::PRIVATE_NOTES_TYPE, $_REQUEST['associated_id'], sprintf( __( 'Note from %s' , 'sprout-invoices' ), sc_get_users_full_name( get_current_user_id() ) ), 0, false );
		$error = ( $record_id ) ? '' : sc__( 'Private note failed to save, try again.' );
		$data = array(
			'id' => $record_id,
			'content' => esc_html( $_REQUEST['notes'] ),
			'type' => sc__( 'Private Note' ),
			'post_date' => sc__( 'Just now' ),
			'error' => $error,
		);

		if ( self::DEBUG ) { header( 'Access-Control-Allow-Origin: *' ); }
		wp_send_json_success( $data );

	}

	public static function add_records_to_client_history( $history, Sprout_Client $client, $type = '' ) {
		$history = $history ?? array();
		$records = SC_Record::get_records_by_association( $client->get_id() );
		if ( ! empty( $records ) ) {

			foreach ( $records as $record_id ) {
				$record = SC_Record::get_instance( $record_id );
				if ( '' !== $type && $type != $record->get_type() ) {
					continue;
				}
				// Take care of the standard records only
				if ( $record->get_type() !== SC_Controller::PRIVATE_NOTES_TYPE ) {
					continue;
				}
				$r_post = $record->get_post();
				$time = strtotime( $r_post->post_date );
				$history[ $time ] = array(
					'id' => $record_id,
					'record_id' => $record_id,
					'title' => esc_html( $r_post->post_title ),
					'content' => wpautop( $r_post->post_content ),
					'date' => wpautop( $r_post->post_date ),
					'type' => sc__( 'Private Note' ),
					'type_slug' => SC_Controller::PRIVATE_NOTES_TYPE,
					'edit' => true,
					);
			}
		}
		return $history;
	}

	/**
	 * Filter and sort the client history.
	 * @param  array  $history
	 * @return array
	 */
	public static function filter_client_history( $history = array() ) {
		$show_per_page = apply_filters( 'sc_client_history_records', 5 );
		self::$total_pages = ceil( count( $history ) / $show_per_page );
		self::$current_page = ( isset( $_REQUEST['history_page'] ) ) ? (int) $_REQUEST['history_page'] : 1;
		$start = ( self::$current_page > 1 ) ? ( self::$current_page - 1 ) * $show_per_page : 0 ;
		$history = array_slice( $history, $start, $show_per_page, true );
		krsort( $history );
		return $history;
	}

	public static function get_pagination() {
		return paginate_links( array(
			'base' => add_query_arg( 'history_page', '%#%' ),
			'format' => '',
			'prev_text' => __( '&laquo;' , 'sprout-invoices' ),
			'next_text' => __( '&raquo;' , 'sprout-invoices' ),
			'total' => self::$total_pages,
			'current' => self::$current_page,
		) );
		return  '<div class="sa_tablenav">' . $page_links . '</div>';
	}
}
