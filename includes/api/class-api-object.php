<?php
/**
 * Base class for API objects
 *
 * @package Share_On_Bluesky
 */

namespace Share_On_Bluesky\API;

defined( 'ABSPATH' ) || exit;

/**
 * Base class for API objects
 */
abstract class API_Object {
	/**
	 * Convert object to array
	 *
	 * @return array
	 */
	abstract public function to_array(): array;

	/**
	 * Create object from array
	 *
	 * @param array $data Array data.
	 * @return static
	 */
	abstract public static function from_array( array $data ): self;

	/**
	 * Get JSON representation
	 *
	 * @return string
	 */
	public function to_json(): string {
		return wp_json_encode( $this->to_array() );
	}

	/**
	 * Create object from JSON
	 *
	 * @param string $json JSON string.
	 * @return static
	 */
	public static function from_json( string $json ): self {
		$data = json_decode( $json, true );
		if ( ! is_array( $data ) ) {
			throw new \InvalidArgumentException( 'Invalid JSON data' );
		}
		return static::from_array( $data );
	}
}