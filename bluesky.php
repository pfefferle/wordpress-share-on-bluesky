<?php
/**
 * Plugin Name: Bluesky
 * Plugin URI: https://github.com/pfefferle/wordpress-bluesky
 * Description: A simple Crossposter for Bluesky (AT Protocol)
 * Author: Matthias Pfefferle
 * Author URI: https://notiz.blog/
 * Version: 1.0.0
 * License: GPL-2.0
 * License URI: https://opensource.org/license/gpl-2-0/
 * Text Domain: bluesky
 * Domain Path: /languages
 */

namespace Bluesky;

/**
 * Register Settings
 *
 * @return void
 */
function admin_init() {
	register_setting(
		'bluesky',
		'bluesky_domain',
		array(
			'type'         => 'string',
			'description'  => esc_html__( 'The domain of the instance you are using', 'bluesky' ),
			'show_in_rest' => true,
			'default'      => 'https://bsky.social',
		)
	);
	register_setting(
		'bluesky',
		'bluesky_identifier',
		array(
			'type'         => 'string',
			'description'  => esc_html__( 'Your identifier', 'bluesky' ),
			'show_in_rest' => true,
			'default'      => '',
		)
	);
	register_setting(
		'bluesky',
		'bluesky_password',
		array(
			'type'         => 'string',
			'description'  => esc_html__( 'Your password (will not be stored)', 'bluesky' ),
			'show_in_rest' => true,
			'default'      => '',
		)
	);
}
add_action( 'admin_init', __NAMESPACE__ . '\admin_init' );

/**
 * On plugin activation, redirect to the profile page so folks can connect to their Bluesky profile.
 *
 * @param string $plugin        Path to the plugin file relative to the plugins directory.
 * @param bool   $network_wide  Whether to enable the plugin for all sites in the network.
 */
function redirect_to_settings( $plugin, $network_wide ) {
	// Bail if the plugin is not Bluesky Verify.
	if ( plugin_basename( __FILE__ ) !== $plugin ) {
		return;
	}

	// Bail if we're on a multisite and the plugin is network activated.
	if ( $network_wide ) {
		return;
	}

	wp_safe_redirect( get_edit_profile_url() . '#bluesky' );
}
add_action( 'activated_plugin', __NAMESPACE__ . '\redirect_to_settings', 10, 2 );

/**
 * Add a link to the plugin's settings page
 * in the plugin's description in the admin.
 *
 * @param array $links Array of plugin action links.
 * @return array
 */
function add_settings_link( $links ) {
	$settings_link = sprintf(
		'<a href="%1$s#bluesky">%2$s</a>',
		esc_url( get_edit_profile_url() ),
		esc_html__( 'Settings', 'bluesky' )
	);
	array_unshift( $links, $settings_link );

	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), __NAMESPACE__ . '\add_settings_link' );

/**
 * Add a section to user's profile to add their Bluesky name and public key.
 *
 * @param WP_User $user User instance to output for.
 *
 * @return void
 */
function add_profile_section( $user ) {
	wp_nonce_field( 'bluesky_user_profile_update', 'bluesky_nonce' );

	printf(
		'<h2 id="bluesky">%1$s</h2>',
		esc_html__( 'Bluesky', 'bluesky' )
	);

	if ( get_the_author_meta( 'bluesky_access_jwt', $user->ID ) ) {
		_e( 'Connected!', 'bluesky' );
	}

	?>
	<table class="form-table" role="presentation">
		<tbody>
			<tr class="domain-wrap">
				<th>
					<label for="bluesky-domain"><?php esc_html_e( 'Bluesky Domain', 'bluesky' ); ?></label>
				</th>
				<td>
					<input type="text" name="bluesky-domain" id="bluesky-domain" value="<?php echo esc_attr( get_the_author_meta( 'bluesky_domain', $user->ID ) ); ?>" placeholder="https://bsky.social" />
					<p class="description" id="bluesky-domain-description">
						<?php _e( 'The Domain of your Bluesky-Instance.', 'bluesky' ); ?>
					</p>
				</td>
			</tr>

			<tr class="user-identifier-wrap">
				<th>
					<label for="bluesky-identifier"><?php esc_html_e( 'Bluesky "Identifier"', 'bluesky' ); ?></label>
				</th>
				<td>
					<input type="text" name="bluesky-identifier" id="bluesky-identifier" aria-describedby="email-description" value="<?php echo esc_attr( get_the_author_meta( 'bluesky_identifier', $user->ID ) ); ?>">
					<p class="description" id="bluesky-identifier-description">
						<?php _e( 'Your Bluesky-identifier.', 'bluesky' ); ?>
					</p>
				</td>
			</tr>

			<tr class="user-password-wrap">
				<th>
					<label for="bluesky-password"><?php esc_html_e( 'Password', 'bluesky' ); ?></label>
				</th>
				<td>
					<input type="text" name="bluesky-password" id="bluesky-password" class="regular-text code">
					<p class="description" id="bluesky-password-description">
						<?php _e( 'Your Bluesky-Password. It is needed to get an Access-Token and will not be stored anywhere.', 'bluesky' ); ?>
					</p>
				</td>
			</tr>
		</tbody>
	</table>
	<?php
}
add_action( 'show_user_profile', __NAMESPACE__ . '\add_profile_section' );
add_action( 'edit_user_profile', __NAMESPACE__ . '\add_profile_section' );

/**
 * Save the Nostr name and public key when the user profile is updated.
 *
 * @param int $user_id User ID.
 * @return void
 */
function save_bluesky_profile_info( $user_id ) {
	if ( ! check_admin_referer( 'bluesky_user_profile_update', 'bluesky_nonce' ) ) {
		return;
	}

	$bluesky_identifier = ! empty( $_POST['bluesky-identifier'] ) ? sanitize_text_field( wp_unslash( $_POST['bluesky-identifier'] ) ) : '';
	$bluesky_domain     = ! empty( $_POST['bluesky-domain'] ) ? sanitize_text_field( wp_unslash( $_POST['bluesky-domain'] ) ) : 'https://bsky.social';
	$bluesky_password   = ! empty( $_POST['bluesky-password'] ) ? sanitize_text_field( wp_unslash( $_POST['bluesky-password'] ) ) : '';

	update_user_meta( $user_id, 'bluesky_identifier', $bluesky_identifier );
	update_user_meta( $user_id, 'bluesky_domain', $bluesky_domain );

	$wp_version = \get_bloginfo( 'version' );
	$user_agent = \apply_filters( 'http_headers_useragent', 'WordPress/' . $wp_version . '; ' . \get_bloginfo( 'url' ) );

	$bluesky_domain = trailingslashit( $bluesky_domain );

	$response = wp_safe_remote_post(
		$bluesky_domain . 'xrpc/com.atproto.server.createSession',
		array(
			'user-agent' => "$user_agent; ActivityPub",
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

	if ( is_wp_error( $response ) ) {
		// show error
	}

	$data = json_decode( wp_remote_retrieve_body( $response ), true );

	if ( ! empty( $data['accessJwt'] ) && ! empty( $data['refreshJwt'] ) ) {
		update_user_meta( $user_id, 'bluesky_access_jwt', $data['accessJwt'] );
		update_user_meta( $user_id, 'bluesky_refresh_jwt', $data['refreshJwt'] );
		update_user_meta( $user_id, 'bluesky_did', $data['did'] );
	}
}
add_action( 'personal_options_update', __NAMESPACE__ . '\save_bluesky_profile_info' );
add_action( 'edit_user_profile_update', __NAMESPACE__ . '\save_bluesky_profile_info' );

/**
 * Schedule Cross-Posting-Event to not slow down publishing
 *
 * @param int     $post_id
 * @param WP_Post $post
 * @return void
 */
function publish_post( $post_id, $post ) {
	wp_schedule_single_event( time(), 'bluesky_send_post', array( $post_id ) );
}
add_action( 'publish_post', __NAMESPACE__ . '\publish_post', 10, 2 );

/**
 * Undocumented function
 *
 * @param int $post_id
 * @return void
 */
function send_post( $post_id ) {
	$post = get_post( $post_id );

	$access_token   = get_user_meta( $post->post_author, 'bluesky_access_jwt', true );
	$did            = get_user_meta( $post->post_author, 'bluesky_did', true );
	$bluesky_domain = get_user_meta( $post->post_author, 'bluesky_domain', true );
	$bluesky_domain = trailingslashit( $bluesky_domain );

	$excerpt_length = apply_filters( 'excerpt_length', 55 );
	$excerpt_more   = apply_filters( 'excerpt_more', ' [...]' );

	$text = wp_trim_words( get_the_excerpt( $post ), $excerpt_length, $excerpt_more );

	$wp_version = \get_bloginfo( 'version' );
	$user_agent = \apply_filters( 'http_headers_useragent', 'WordPress/' . $wp_version . '; ' . \get_bloginfo( 'url' ) );

	$response = wp_safe_remote_post(
		$bluesky_domain . 'xrpc/com.atproto.repo.createRecord',
		array(
			'user-agent' => "$user_agent; ActivityPub",
			'headers'    => array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $access_token,
			),
			'body'       => wp_json_encode(
				array(
					'collection' => 'app.bsky.feed.post',
					'did'        => $did,
					'repo'       => $did,
					'record'     => array(
						'$type'     => 'app.bsky.feed.post',
						'text'      => $text,
						'createdAt' => gmdate( 'c', strtotime( $post->post_date_gmt ) ),
						'embed'     => array(
							'$type'    => 'app.bsky.embed.external',
							'external' => array(
								'uri'         => wp_get_shortlink( $post->ID ),
								'title'       => $post->post_title,
								'description' => $text,
							),
						),
					),
				)
			),
		)
	);

	if ( is_wp_error( $response ) ) {
		// show error
	}
}
add_action( 'bluesky_send_post', __NAMESPACE__ . '\send_post' );
