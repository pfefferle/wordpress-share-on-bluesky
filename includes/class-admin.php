<?php
/**
 * Admin-Klasse für die Einstellungsseite
 *
 * @package Share_On_Bluesky
 */

namespace Share_On_Bluesky;

defined( 'ABSPATH' ) || exit;

/**
 * Admin-Klasse
 */
class Admin {
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
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( dirname( dirname( __FILE__ ) ) . '/share-on-bluesky.php' ), array( $this, 'add_settings_link' ) );
	}

	/**
	 * Fügt die Einstellungsseite zum Admin-Menü hinzu
	 */
	public function add_admin_menu(): void {
		add_options_page(
			esc_html__( 'Bluesky', 'share-on-bluesky' ),
			esc_html__( 'Bluesky', 'share-on-bluesky' ),
			'manage_options',
			'share-on-bluesky',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Registriert die Einstellungen
	 */
	public function register_settings(): void {
		register_setting(
			'share-on-bluesky',
			'bluesky_domain',
			array(
				'type'              => 'string',
				'description'       => __( 'The domain of your Bluesky instance', 'share-on-bluesky' ),
				'default'           => Share_On_Bluesky::DEFAULT_DOMAIN,
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_setting(
			'share-on-bluesky',
			'bluesky_password',
			array(
				'type'              => 'string',
				'description'       => __( 'The password of your Bluesky account (will not be stored permanently)', 'share-on-bluesky' ),
				'sanitize_callback' => 'sanitize_text_field',
			)
		);

		register_setting(
			'share-on-bluesky',
			'bluesky_identifier',
			array(
				'type'              => 'string',
				'description'       => __( 'The identifier of your Bluesky account', 'share-on-bluesky' ),
				'sanitize_callback' => 'sanitize_text_field',
			)
		);
	}

	/**
	 * Fügt einen Link zu den Einstellungen hinzu
	 *
	 * @param array $links Array von Plugin-Action-Links.
	 * @return array
	 */
	public function add_settings_link( array $links ): array {
		$settings_link = sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( admin_url( 'options-general.php?page=share-on-bluesky' ) ),
			esc_html__( 'Settings', 'share-on-bluesky' )
		);
		array_unshift( $links, $settings_link );

		return $links;
	}

	/**
	 * Rendert die Einstellungsseite
	 */
	public function render_settings_page(): void {
		printf(
			'<h2 id="bluesky">%1$s</h2>',
			esc_html__( 'Share on Bluesky', 'share-on-bluesky' )
		);

		if ( get_option( 'bluesky_identifier' ) && get_option( 'bluesky_password' ) ) {
			$this->api->create_session();
		}
		?>
		<div class="share-on-bluesky-settings share-on-bluesky-settings-page hide-if-no-js">
			<form method="post" action="options.php">
				<?php settings_fields( 'share-on-bluesky' ); ?>
				<table class="form-table" role="presentation">
					<tbody>
						<tr class="domain-wrap">
							<th>
								<label for="bluesky-domain"><?php esc_html_e( 'Bluesky Domain', 'share-on-bluesky' ); ?></label>
							</th>
							<td>
								<input type="text" name="bluesky_domain" id="bluesky-domain" value="<?php echo esc_attr( get_option( 'bluesky_domain', Share_On_Bluesky::DEFAULT_DOMAIN ) ); ?>" placeholder="https://bsky.social" />
								<p class="description" id="bluesky-domain-description">
									<?php esc_html_e( 'The domain of your Bluesky instance. (This has to be a valid URL including "http(s)")', 'share-on-bluesky' ); ?>
								</p>
							</td>
						</tr>

						<tr class="user-identifier-wrap">
							<th>
								<label for="bluesky-identifier"><?php esc_html_e( 'Bluesky "Identifier"', 'share-on-bluesky' ); ?></label>
							</th>
							<td>
								<input type="text" name="bluesky_identifier" id="bluesky-identifier" aria-describedby="email-description" value="<?php echo esc_attr( get_option( 'bluesky_identifier' ) ); ?>">
								<p class="description" id="bluesky-identifier-description">
									<?php esc_html_e( 'Your Bluesky identifier.', 'share-on-bluesky' ); ?>
								</p>
							</td>
						</tr>

						<tr class="user-password-wrap">
							<th>
								<label for="bluesky-password"><?php esc_html_e( 'Password', 'share-on-bluesky' ); ?></label>
							</th>
							<td>
								<input type="text" name="bluesky_password" id="bluesky-password" class="regular-text code" value="<?php echo esc_attr( get_option( 'bluesky_password' ) ); ?>">
								<p class="description" id="bluesky-password-description">
									<?php esc_html_e( 'Your Bluesky application password. It is needed to get an Access-Token and will not be stored anywhere.', 'share-on-bluesky' ); ?>
								</p>
							</td>
						</tr>

					</tbody>
				</table>
				<?php do_settings_sections( 'share-on-bluesky' ); ?>

				<?php submit_button(); ?>
			</form>

			<details>
				<summary><?php esc_html_e( 'Debug Informations', 'share-on-bluesky' ); ?></summary>
				<table class="form-table" role="presentation">
					<tbody>
						<tr class="access-token-wrap">
							<th>
								<label for="bluesky-did"><?php esc_html_e( 'DID', 'share-on-bluesky' ); ?></label>
							</th>
							<td>
								<input id="bluesky-did" type="text" class="regular-text code" value="<?php echo esc_attr( get_option( 'bluesky_did' ) ); ?>" readonly>
							</td>
						</tr>
						<tr class="access-token-wrap">
							<th>
								<label for="bluesky-access-jwt"><?php esc_html_e( 'Access Token', 'share-on-bluesky' ); ?></label>
							</th>
							<td>
								<input id="bluesky-access-jwt" type="text" class="regular-text code" value="<?php echo esc_attr( get_option( 'bluesky_access_jwt' ) ); ?>" readonly>
							</td>
						</tr>
						<tr class="access-token-wrap">
							<th>
								<label for="bluesky-refresh-jwt"><?php esc_html_e( 'Refresh Token', 'share-on-bluesky' ); ?></label>
							</th>
							<td>
								<input id="bluesky-refresh-jwt" type="text" class="regular-text code" value="<?php echo esc_attr( get_option( 'bluesky_refresh_jwt' ) ); ?>" readonly>
							</td>
						</tr>
					</tbody>
				</table>
			</details>
		</div>
		<?php
	}
}
