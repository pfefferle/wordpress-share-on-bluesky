<?php
/**
 * Post object for Bluesky API
 *
 * @package Share_On_Bluesky
 */

namespace Share_On_Bluesky\API;

defined( 'ABSPATH' ) || exit;

/**
 * Post object
 */
class Post extends API_Object {
	/**
	 * Post text
	 *
	 * @var string
	 */
	private string $text;

	/**
	 * Creation date
	 *
	 * @var string
	 */
	private string $created_at;

	/**
	 * Link facets
	 *
	 * @var array
	 */
	private array $facets;

	/**
	 * Constructor
	 *
	 * @param string $text Post text.
	 * @param string $created_at Creation date.
	 * @param array  $facets Link facets.
	 */
	public function __construct( string $text, string $created_at, array $facets = array() ) {
		$this->text       = $text;
		$this->created_at = $created_at;
		$this->facets     = $facets;
	}

	/**
	 * Get post text
	 *
	 * @return string
	 */
	public function get_text(): string {
		return $this->text;
	}

	/**
	 * Get creation date
	 *
	 * @return string
	 */
	public function get_created_at(): string {
		return $this->created_at;
	}

	/**
	 * Get facets
	 *
	 * @return array
	 */
	public function get_facets(): array {
		return $this->facets;
	}

	/**
	 * Add a link facet
	 *
	 * @param string $uri Link URI.
	 * @param int    $start Start position.
	 * @param int    $end End position.
	 * @return void
	 */
	public function add_link_facet( string $uri, int $start, int $end ): void {
		$this->facets[] = array(
			'features' => array(
				array(
					'uri'   => $uri,
					'$type' => 'app.bsky.richtext.facet#link',
				),
			),
			'index'    => array(
				'byteStart' => $start,
				'byteEnd'   => $end,
			),
		);
	}

	/**
	 * Convert to array
	 *
	 * @return array
	 */
	public function to_array(): array {
		return array(
			'$type'     => 'app.bsky.feed.post',
			'text'      => $this->text,
			'createdAt' => $this->created_at,
			'facets'    => $this->facets,
		);
	}

	/**
	 * Create from array
	 *
	 * @param array $data Array data.
	 * @return static
	 */
	public static function from_array( array $data ): self {
		if ( empty( $data['text'] ) || empty( $data['createdAt'] ) ) {
			throw new \InvalidArgumentException( 'Missing required post data' );
		}

		return new static(
			$data['text'],
			$data['createdAt'],
			$data['facets'] ?? array()
		);
	}
}