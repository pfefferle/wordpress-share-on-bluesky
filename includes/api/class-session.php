<?php
/**
 * Session object for Bluesky API
 *
 * @package Share_On_Bluesky
 */

namespace Share_On_Bluesky\API;

defined( 'ABSPATH' ) || exit;

/**
 * Session object
 */
class Session extends API_Object {
	/**
	 * Access JWT token
	 *
	 * @var string
	 */
	private string $access_jwt;

	/**
	 * Refresh JWT token
	 *
	 * @var string
	 */
	private string $refresh_jwt;

	/**
	 * DID (Decentralized Identifier)
	 *
	 * @var string
	 */
	private string $did;

	/**
	 * Constructor
	 *
	 * @param string $access_jwt Access JWT token.
	 * @param string $refresh_jwt Refresh JWT token.
	 * @param string $did DID.
	 */
	public function __construct( string $access_jwt, string $refresh_jwt, string $did ) {
		$this->access_jwt  = $access_jwt;
		$this->refresh_jwt = $refresh_jwt;
		$this->did        = $did;
	}

	/**
	 * Get access JWT token
	 *
	 * @return string
	 */
	public function get_access_jwt(): string {
		return $this->access_jwt;
	}

	/**
	 * Get refresh JWT token
	 *
	 * @return string
	 */
	public function get_refresh_jwt(): string {
		return $this->refresh_jwt;
	}

	/**
	 * Get DID
	 *
	 * @return string
	 */
	public function get_did(): string {
		return $this->did;
	}

	/**
	 * Convert to array
	 *
	 * @return array
	 */
	public function to_array(): array {
		return array(
			'accessJwt'  => $this->access_jwt,
			'refreshJwt' => $this->refresh_jwt,
			'did'        => $this->did,
		);
	}

	/**
	 * Create from array
	 *
	 * @param array $data Array data.
	 * @return static
	 */
	public static function from_array( array $data ): self {
		if ( empty( $data['accessJwt'] ) || empty( $data['refreshJwt'] ) || empty( $data['did'] ) ) {
			throw new \InvalidArgumentException( 'Missing required session data' );
		}

		return new static(
			$data['accessJwt'],
			$data['refreshJwt'],
			$data['did']
		);
	}
}