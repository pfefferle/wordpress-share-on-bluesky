<?php
/**
 * Plugin Name: Share On Bluesky
 * Plugin URI: https://github.com/pfefferle/wordpress-bluesky
 * Description: A simple Crossposter for Bluesky (AT Protocol)
 * Author: Matthias Pfefferle
 * Author URI: https://notiz.blog/
 * Version: 2.1.0
 * License: GPL-2.0
 * License URI: https://opensource.org/license/gpl-2-0/
 * Text Domain: share-on-bluesky
 * Domain Path: /languages
 */

namespace Share_On_Bluesky;

defined( 'ABSPATH' ) || exit;
define( 'SHARE_ON_BLUESKY_DEFAULT_DOMAIN', 'https://bsky.social' );

/**
 * Add a settings page to the admin menu.
 *
 * @return void
 */
function admin_menu() {
	\add_options_page(
		\esc_html__( 'Bluesky', 'share-on-bluesky' ),
		\esc_html__( 'Bluesky', 'share-on-bluesky' ),
		'manage_options',
		'share-on-bluesky',
		__NAMESPACE__ . '\settings_page'
	);
}
\add_action( 'admin_menu', __NAMESPACE__ . '\admin_menu' );

/**
 * Add a link to the plugin's settings page
 * in the plugin's description in the admin.
 *
 * @param array $links Array of plugin action links.
 * @return array
 */
function add_settings_link( $links ) {
	$settings_link = \sprintf(
		'<a href="%1$s">%2$s</a>',
		\esc_url( \admin_url( 'options-general.php?page=share-on-bluesky' ) ),
		\esc_html__( 'Settings', 'share-on-bluesky' )
	);
	array_unshift( $links, $settings_link );

	return $links;
}
\add_filter( 'plugin_action_links_' . \plugin_basename( __FILE__ ), __NAMESPACE__ . '\add_settings_link' );

/**
 * Register ActivityPub settings
 */
function register_settings() {
	\register_setting(
		'share-on-bluesky',
		'bluesky_domain',
		array(
			'type'              => 'string',
			'description'       => \__( 'The domain of your Bluesky instance', 'share-on-bluesky' ),
			'default'           => SHARE_ON_BLUESKY_DEFAULT_DOMAIN,
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	\register_setting(
		'share-on-bluesky',
		'bluesky_password',
		array(
			'type'              => 'string',
			'description'       => \__( 'The password of your Bluesky account (will not be stored permanently)', 'share-on-bluesky' ),
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	\register_setting(
		'share-on-bluesky',
		'bluesky_identifier',
		array(
			'type'              => 'string',
			'description'       => \__( 'The identifier of your Bluesky account', 'share-on-bluesky' ),
			'sanitize_callback' => 'sanitize_text_field',
		)
	);
}
\add_action( 'admin_init', __NAMESPACE__ . '\register_settings' );

/**
 * Add a section to user's profile to add their Bluesky name and public key.
 *
 * @return void
 */
function settings_page() {
	printf(
		'<h2 id="bluesky">%1$s</h2>',
		\esc_html__( 'Share on Bluesky', 'share-on-bluesky' )
	);

	if ( \get_option( 'bluesky_identifier' ) && \get_option( 'bluesky_password' ) ) {
		get_access_token();
	}
	?>
	<div class="activitypub-settings activitypub-settings-page hide-if-no-js">
		<form method="post" action="options.php">
			<?php \settings_fields( 'share-on-bluesky' ); ?>
			<table class="form-table" role="presentation">
				<tbody>
					<tr class="domain-wrap">
						<th>
							<label for="bluesky-domain"><?php \esc_html_e( 'Bluesky Domain', 'share-on-bluesky' ); ?></label>
						</th>
						<td>
							<input type="text" name="bluesky_domain" id="bluesky-domain" value="<?php echo \esc_attr( \get_option( 'bluesky_domain', SHARE_ON_BLUESKY_DEFAULT_DOMAIN ) ); ?>" placeholder="https://bsky.social" />
							<p class="description" id="bluesky-domain-description">
								<?php \esc_html_e( 'The domain of your Bluesky instance. (This has to be a valid URL including "http(s)")', 'share-on-bluesky' ); ?>
							</p>
						</td>
					</tr>

					<tr class="user-identifier-wrap">
						<th>
							<label for="bluesky-identifier"><?php \esc_html_e( 'Bluesky "Identifier"', 'share-on-bluesky' ); ?></label>
						</th>
						<td>
							<input type="text" name="bluesky_identifier" id="bluesky-identifier" aria-describedby="email-description" value="<?php echo \esc_attr( \get_option( 'bluesky_identifier' ) ); ?>">
							<p class="description" id="bluesky-identifier-description">
								<?php \esc_html_e( 'Your Bluesky identifier.', 'share-on-bluesky' ); ?>
							</p>
						</td>
					</tr>

					<tr class="user-password-wrap">
						<th>
							<label for="bluesky-password"><?php \esc_html_e( 'Password', 'share-on-bluesky' ); ?></label>
						</th>
						<td>
							<input type="text" name="bluesky_password" id="bluesky-password" class="regular-text code" value="<?php echo \esc_attr( \get_option( 'bluesky_password' ) ); ?>">
							<p class="description" id="bluesky-password-description">
								<?php \esc_html_e( 'Your Bluesky application password. It is needed to get an Access-Token and will not be stored anywhere.', 'share-on-bluesky' ); ?>
							</p>
						</td>
					</tr>

				</tbody>
			</table>
			<?php \do_settings_sections( 'share-on-bluesky' ); ?>

			<?php \submit_button(); ?>
		</form>

		<details>
			<summary><?php _e( 'Debug Informations', 'share-on-bluesky' ); ?></summary>
			<table class="form-table" role="presentation">
				<tbody>
					<tr class="access-token-wrap">
						<th>
							<label for="bluesky-did"><?php \esc_html_e( 'DID', 'share-on-bluesky' ); ?></label>
						</th>
						<td>
							<input id="bluesky-did" type="text" class="regular-text code" value="<?php echo \esc_attr( \get_option( 'bluesky_did' ) ); ?>" readonly>
						</td>
					</tr>
					<tr class="access-token-wrap">
						<th>
							<label for="bluesky-access-jwt"><?php \esc_html_e( 'Access Token', 'share-on-bluesky' ); ?></label>
						</th>
						<td>
							<input id="bluesky-access-jwt" type="text" class="regular-text code" value="<?php echo \esc_attr( \get_option( 'bluesky_access_jwt' ) ); ?>" readonly>
						</td>
					</tr>
					<tr class="access-token-wrap">
						<th>
							<label for="bluesky-refresh-jwt"><?php \esc_html_e( 'Refresh Token', 'share-on-bluesky' ); ?></label>
						</th>
						<td>
							<input id="bluesky-refresh-jwt" type="text" class="regular-text code" value="<?php echo \esc_attr( \get_option( 'bluesky_refresh_jwt' ) ); ?>" readonly>
						</td>
					</tr>
				</tbody>
			</table>
		</details>
	</div>
	<?php
}

/**
 * Save Bluesky data when the user profile is updated.
 *
 * @param int $user_id User ID.
 * @return void
 */
function get_access_token() {
	$bluesky_identifier = \get_option( 'bluesky_identifier' );
	$bluesky_domain     = \get_option( 'bluesky_domain', SHARE_ON_BLUESKY_DEFAULT_DOMAIN );
	$bluesky_password   = \get_option( 'bluesky_password' );

	if (
		! empty( $bluesky_domain )
		&& ! empty( $bluesky_identifier )
		&& ! empty( $bluesky_password )
	) {
		$bluesky_domain = \trailingslashit( $bluesky_domain );
		$session_url    = $bluesky_domain . 'xrpc/com.atproto.server.createSession';
		$wp_version     = \get_bloginfo( 'version' );
		$user_agent     = \apply_filters( 'http_headers_useragent', 'WordPress/' . $wp_version . '; ' . \get_bloginfo( 'url' ) );

		$response = \wp_safe_remote_post(
			\esc_url_raw( $session_url ),
			array(
				'user-agent' => "$user_agent; ActivityPub",
				'headers'    => array(
					'Content-Type' => 'application/json',
				),
				'body'       => \wp_json_encode(
					array(
						'identifier' => $bluesky_identifier,
						'password'   => $bluesky_password,
					)
				),
			)
		);

		if (
			\is_wp_error( $response ) ||
			\wp_remote_retrieve_response_code( $response ) >= 300
		) {
			// save error
			return;
		}

		$data = json_decode( \wp_remote_retrieve_body( $response ), true );

		if (
			! empty( $data['accessJwt'] )
			&& ! empty( $data['refreshJwt'] )
			&& ! empty( $data['did'] )
		) {
			\update_option( 'bluesky_access_jwt', sanitize_text_field( $data['accessJwt'] ) );
			\update_option( 'bluesky_refresh_jwt', sanitize_text_field( $data['refreshJwt'] ) );
			\update_option( 'bluesky_did', sanitize_text_field( $data['did'] ) );
			\update_option( 'bluesky_password', '' );
		} else {
			// save error
		}
	}
}

/**
 * Refresh the access token
 *
 * @return void
 */
function refresh_access_token() {
	$bluesky_domain = \get_option( 'bluesky_domain', SHARE_ON_BLUESKY_DEFAULT_DOMAIN );
	$bluesky_domain = \trailingslashit( $bluesky_domain );
	$session_url    = $bluesky_domain . 'xrpc/com.atproto.server.refreshSession';
	$wp_version     = \get_bloginfo( 'version' );
	$user_agent     = \apply_filters( 'http_headers_useragent', 'WordPress/' . $wp_version . '; ' . \get_bloginfo( 'url' ) );
	$access_token   = \get_option( 'bluesky_refresh_jwt' );

	$response = \wp_safe_remote_post(
		\esc_url_raw( $session_url ),
		array(
			'user-agent' => "$user_agent; ActivityPub",
			'headers'    => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $access_token,
			),
		)
	);

	if (
		\is_wp_error( $response ) ||
		\wp_remote_retrieve_response_code( $response ) >= 300
	) {
		// save error
		return;
	}

	$data = \json_decode( \wp_remote_retrieve_body( $response ), true );

	if (
		! empty( $data['accessJwt'] )
		&& ! empty( $data['refreshJwt'] )
	) {
		\update_option( 'bluesky_access_jwt', sanitize_text_field( $data['accessJwt'] ) );
		\update_option( 'bluesky_refresh_jwt', sanitize_text_field( $data['refreshJwt'] ) );
	} else {
		// save error
	}
}

/**
 * Schedule Cross-Posting-Event to not slow down publishing
 *
 * @param int     $post_id
 * @param WP_Post $post
 * @return void
 */
function publish_post( $post_id, $post ) {
	if ( \get_option( 'bluesky_access_jwt' ) ) {
		\wp_schedule_single_event( time(), 'bluesky_send_post', array( $post_id ) );
	}
}
\add_action( 'publish_post', __NAMESPACE__ . '\publish_post', 10, 2 );

/**
 * Undocumented function
 *
 * @param int $post_id
 * @return void
 */
function send_post( $post_id ) {
	$post = \get_post( $post_id );

	refresh_access_token();

	$access_token   = \get_option( 'bluesky_access_jwt' );
	$did            = \get_option( 'bluesky_did' );
	$bluesky_domain = \get_option( 'bluesky_domain', SHARE_ON_BLUESKY_DEFAULT_DOMAIN );
	$bluesky_domain = \trailingslashit( $bluesky_domain );

	if ( ! $access_token || ! $did || ! $bluesky_domain ) {
		return;
	}

	$wp_version = \get_bloginfo( 'version' );
	$user_agent = \apply_filters( 'http_headers_useragent', 'WordPress/' . $wp_version . '; ' . \get_bloginfo( 'url' ) );

	$response = \wp_safe_remote_post(
		$bluesky_domain . 'xrpc/com.atproto.repo.createRecord',
		array(
			'user-agent' => "$user_agent; Share on Bluesky",
			'headers'    => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $access_token,
			),
			'body'       => \wp_json_encode(
				array(
					'collection' => 'app.bsky.feed.post',
					'did'        => \esc_html( $did ),
					'repo'       => \esc_html( $did ),
					'record'     => array(
						'$type'     => 'app.bsky.feed.post',
						'text'      => get_excerpt( $post ),
						'createdAt' => \gmdate( 'c', \strtotime( $post->post_date_gmt ) ),
						'facets'    => array(
							array(
								'features' => array(
									array(
										'uri'   => \wp_get_shortlink( $post->ID ),
										'$type' => 'app.bsky.richtext.facet#link',
									),
								),
								'index'    => array(
									'byteStart' => strlen( get_excerpt( $post ) ) - strlen( \wp_get_shortlink( $post->ID ) ),
									'byteEnd'   => strlen( get_excerpt( $post ) ),
								),
							),
						),
					),
				)
			),
		)
	);

	return $response;
}
\add_action( 'bluesky_send_post', __NAMESPACE__ . '\send_post' );

/**
 * Add a weekly event to refresh the access token.
 *
 * @return void
 */
function add_scheduler() {
	if ( ! \wp_next_scheduled( 'bluesky_refresh_token' ) ) {
		\wp_schedule_event( time(), 'weekly', 'bluesky_refresh_token' );
	}
}
\register_activation_hook( __FILE__, __NAMESPACE__ . '\add_scheduler' );

/**
 * Remove the weekly event to refresh the access token.
 *
 * @return void
 */
function remove_scheduler() {
	\wp_clear_scheduled_hook( 'bluesky_refresh_token' );
}
\register_deactivation_hook( __FILE__, __NAMESPACE__ . '\remove_scheduler' );

/**
 * Returns an excerpt
 *
 * @param WP_Post $post
 * @param int     $length
 * @return void
 */
function get_excerpt( $post, $length = 300 ) {
	$string = \get_post_field( 'post_content', $post );
	$string = \html_entity_decode( $string );
	$string = \wp_strip_all_tags( $string, true );

	$shortlink    = \wp_get_shortlink( $post->ID );
	$excerpt_more = \apply_filters( 'excerpt_more', '...' );
	$length       = $length - strlen( $shortlink );
	$length       = $length - strlen( $excerpt_more );
	$length       = $length - 3; // just to be sure

	if ( \strlen( $string ) > $length ) {
		$string = \wordwrap( $string, $length, '</bluesky-summary>' );
		$string = \explode( '</bluesky-summary>', $string, 2 );
		$string = $string[0];
	}

	return $string . $excerpt_more . ' ' . $shortlink;
}

/**
 * CLI command to send a post to Bluesky
 *
 * ## OPTIONS
 *
 * [--post_id=<post_id>]
 * The Post ID to send to Bluesky
 *
 * @param array $args       The list of positional arguments.
 * @param array $assoc_args The list of associative arguments.
 * @return void
 */
function cli_send( $args, $assoc_args ) {
	if ( ! isset( $assoc_args['post_id'] ) ) {
		\WP_CLI::error( 'Please provide a post_id' );
	}

	$post_id  = (int) $assoc_args['post_id'];
	$response = send_post( $post_id );

	if ( \is_wp_error( $response ) ) {
		\WP_CLI::error( $response->get_error_message() );
	}

	if ( \wp_remote_retrieve_response_code( $response ) >= 300 ) {
		$body = \wp_remote_retrieve_body( $response );
		$body = \json_decode( $body, true );
		\WP_CLI::error( $body['message'] );
	}

	\WP_CLI::success( 'Post sent to Bluesky' );
}

/**
 * Check for CLI env, to add the "Bluesky" CLI commands
 *
 * @return void
 */
function cli_command() {
	\WP_CLI::add_command( 'bluesky send', __NAMESPACE__ . '\cli_send' );
}
\add_action( 'cli_init', __NAMESPACE__ . '\cli_command' );
