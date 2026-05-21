<?php
/**
 * API-Klasse für Bluesky-Aufrufe
 *
 * @package Share_On_Bluesky
 */

namespace Share_On_Bluesky;

use Share_On_Bluesky\API\Post;
use Share_On_Bluesky\API\Session;

defined( 'ABSPATH' ) || exit;

/**
 * API-Klasse für Bluesky
 */
class API {
	/**
	 * Konstruktor
	 */
	public function __construct() {
		add_action( 'bluesky_refresh_token', array( $this, 'refresh_access_token' ) );
	}

	/**
	 * Erstellt eine neue Session
	 *
	 * @return bool
	 */
	public function create_session(): bool {
		$bluesky_identifier = get_option( 'bluesky_identifier' );
		$bluesky_domain     = get_option( 'bluesky_domain', Share_On_Bluesky::DEFAULT_DOMAIN );
		$bluesky_password   = get_option( 'bluesky_password' );

		if (
			empty( $bluesky_domain )
			|| empty( $bluesky_identifier )
			|| empty( $bluesky_password )
		) {
			return false;
		}

		$bluesky_domain = trailingslashit( $bluesky_domain );
		$session_url    = $bluesky_domain . 'xrpc/com.atproto.server.createSession';
		$wp_version     = get_bloginfo( 'version' );
		$user_agent     = apply_filters( 'http_headers_useragent', 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ) );

		$response = wp_safe_remote_post(
			esc_url_raw( $session_url ),
			array(
				'user-agent' => "$user_agent; Share On Bluesky",
				'headers'    => array(
					'Content-Type' => 'application/json',
				),
				'body'       => wp_json_encode(
					array(
						'identifier' => $bluesky_identifier,
						'password'   => $bluesky_password,
					)
				),
			)
		);

		if (
			is_wp_error( $response )
			|| wp_remote_retrieve_response_code( $response ) >= 300
		) {
			return false;
		}

		try {
			$session = Session::from_json( wp_remote_retrieve_body( $response ) );
			update_option( 'bluesky_access_jwt', $session->get_access_jwt() );
			update_option( 'bluesky_refresh_jwt', $session->get_refresh_jwt() );
			update_option( 'bluesky_did', $session->get_did() );
			update_option( 'bluesky_password', '' );
			return true;
		} catch ( \InvalidArgumentException $e ) {
			return false;
		}
	}

	/**
	 * Aktualisiert den Access Token
	 *
	 * @return bool
	 */
	public function refresh_access_token(): bool {
		$bluesky_domain = get_option( 'bluesky_domain', Share_On_Bluesky::DEFAULT_DOMAIN );
		$bluesky_domain = trailingslashit( $bluesky_domain );
		$session_url    = $bluesky_domain . 'xrpc/com.atproto.server.refreshSession';
		$wp_version     = get_bloginfo( 'version' );
		$user_agent     = apply_filters( 'http_headers_useragent', 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ) );
		$access_token   = get_option( 'bluesky_refresh_jwt' );

		$response = wp_safe_remote_post(
			esc_url_raw( $session_url ),
			array(
				'user-agent' => "$user_agent; Share On Bluesky",
				'headers'    => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $access_token,
				),
			)
		);

		if (
			is_wp_error( $response )
			|| wp_remote_retrieve_response_code( $response ) >= 300
		) {
			return false;
		}

		try {
			$session = Session::from_json( wp_remote_retrieve_body( $response ) );
			update_option( 'bluesky_access_jwt', $session->get_access_jwt() );
			update_option( 'bluesky_refresh_jwt', $session->get_refresh_jwt() );
			return true;
		} catch ( \InvalidArgumentException $e ) {
			return false;
		}
	}

	/**
	 * Sendet einen Beitrag an Bluesky
	 *
	 * @param int $post_id Post-ID.
	 * @return \WP_Error|array
	 */
	public function send_post( int $post_id ) {
		$post = get_post( $post_id );

		$this->refresh_access_token();

		$access_token   = get_option( 'bluesky_access_jwt' );
		$did            = get_option( 'bluesky_did' );
		$bluesky_domain = get_option( 'bluesky_domain', Share_On_Bluesky::DEFAULT_DOMAIN );
		$bluesky_domain = trailingslashit( $bluesky_domain );

		if ( ! $access_token || ! $did || ! $bluesky_domain ) {
			return new \WP_Error( 'missing_credentials', __( 'Missing Bluesky credentials', 'share-on-bluesky' ) );
		}

		$wp_version = get_bloginfo( 'version' );
		$user_agent = apply_filters( 'http_headers_useragent', 'WordPress/' . $wp_version . '; ' . get_bloginfo( 'url' ) );

		$shortlink = wp_get_shortlink( $post->ID );
		$excerpt   = $this->get_excerpt( $post );
		$post_obj  = new Post(
			$excerpt,
			gmdate( 'c', strtotime( $post->post_date_gmt ) )
		);
		$post_obj->add_link_facet(
			$shortlink,
			strlen( $excerpt ) - strlen( $shortlink ),
			strlen( $excerpt )
		);

		return wp_safe_remote_post(
			$bluesky_domain . 'xrpc/com.atproto.repo.createRecord',
			array(
				'user-agent' => "$user_agent; Share on Bluesky",
				'headers'    => array(
					'Content-Type'  => 'application/json',
					'Authorization' => 'Bearer ' . $access_token,
				),
				'body'       => wp_json_encode(
					array(
						'collection' => 'app.bsky.feed.post',
						'did'        => esc_html( $did ),
						'repo'       => esc_html( $did ),
						'record'     => $post_obj->to_array(),
					)
				),
			)
		);
	}

	/**
	 * Gibt einen Auszug zurück
	 *
	 * @param \WP_Post $post Post-Objekt.
	 * @param int      $length Maximale Länge.
	 * @return string
	 */
	private function get_excerpt( \WP_Post $post, int $length = 300 ): string {
		$string = get_post_field( 'post_content', $post );
		$string = html_entity_decode( $string );
		$string = wp_strip_all_tags( $string, true );

		$shortlink    = wp_get_shortlink( $post->ID );
		$excerpt_more = apply_filters( 'excerpt_more', '...' );
		$length       = $length - strlen( $shortlink );
		$length       = $length - strlen( $excerpt_more );
		$length       = $length - 3; // just to be sure

		if ( strlen( $string ) > $length ) {
			$string = wordwrap( $string, $length, '</bluesky-summary>' );
			$string = explode( '</bluesky-summary>', $string, 2 );
			$string = $string[0];
		}

		return $string . $excerpt_more . ' ' . $shortlink;
	}
}
