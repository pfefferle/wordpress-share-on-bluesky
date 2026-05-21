<?php
/**
 * Post-Handler-Klasse für die Verarbeitung von Beiträgen
 *
 * @package Share_On_Bluesky
 */

namespace Share_On_Bluesky;

defined( 'ABSPATH' ) || exit;

/**
 * Post-Handler-Klasse
 */
class Post_Handler {
	/**
	 * API-Instanz
	 *
	 * @var API
	 */
	private $api;

	/**
	 * Konstruktor
	 */
	public function __construct() {
		$this->api = new API();
		$this->init_hooks();
	}

	/**
	 * Initialisiert die Hooks
	 */
	private function init_hooks(): void {
		add_action( 'publish_post', array( $this, 'schedule_post' ), 10, 2 );
		add_action( 'bluesky_send_post', array( $this, 'send_post' ) );
	}

	/**
	 * Plant das Senden eines Beitrags
	 *
	 * @param int      $post_id Post-ID.
	 * @param \WP_Post $post Post-Objekt.
	 */
	public function schedule_post( int $post_id, \WP_Post $post ): void {
		if ( get_option( 'bluesky_access_jwt' ) ) {
			wp_schedule_single_event( time(), 'bluesky_send_post', array( $post_id ) );
		}
	}

	/**
	 * Sendet einen Beitrag an Bluesky
	 *
	 * @param int $post_id Post-ID.
	 * @return void
	 */
	public function send_post( int $post_id ): void {
		$response = $this->api->send_post( $post_id );

		if ( is_wp_error( $response ) ) {
			error_log( sprintf( 'Bluesky Error: %s', $response->get_error_message() ) );
		}

		if ( wp_remote_retrieve_response_code( $response ) >= 300 ) {
			$body = wp_remote_retrieve_body( $response );
			$body = json_decode( $body, true );
			error_log( sprintf( 'Bluesky Error: %s', $body['message'] ) );
		}
	}

	/**
	 * CLI-Befehl zum Senden eines Beitrags
	 *
	 * @param array $args       Die Liste der Positionsargumente.
	 * @param array $assoc_args Die Liste der assoziativen Argumente.
	 * @return void
	 */
	public function cli_send( array $args, array $assoc_args ): void {
		if ( ! isset( $assoc_args['post_id'] ) ) {
			\WP_CLI::error( 'Please provide a post_id' );
		}

		$post_id  = (int) $assoc_args['post_id'];
		$response = $this->api->send_post( $post_id );

		if ( is_wp_error( $response ) ) {
			\WP_CLI::error( $response->get_error_message() );
		}

		if ( wp_remote_retrieve_response_code( $response ) >= 300 ) {
			$body = wp_remote_retrieve_body( $response );
			$body = json_decode( $body, true );
			\WP_CLI::error( $body['message'] );
		}

		\WP_CLI::success( 'Post sent to Bluesky' );
	}
}
