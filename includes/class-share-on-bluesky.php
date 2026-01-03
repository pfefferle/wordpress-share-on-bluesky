<?php
/**
 * Hauptklasse des Share on Bluesky Plugins
 *
 * @package Share_On_Bluesky
 */

namespace Share_On_Bluesky;

defined( 'ABSPATH' ) || exit;

/**
 * Hauptklasse des Plugins
 */
class Share_On_Bluesky {
	/**
	 * Plugin-Version
	 *
	 * @var string
	 */
	const VERSION = '2.1.0';

	/**
	 * Plugin-Domain
	 *
	 * @var string
	 */
	const DEFAULT_DOMAIN = 'https://bsky.social';

	/**
	 * Plugin-Instanz
	 *
	 * @var Share_On_Bluesky
	 */
	private static $instance = null;

	/**
	 * API-Instanz
	 *
	 * @var API
	 */
	private $api;

	/**
	 * Admin-Instanz
	 *
	 * @var Admin
	 */
	private $admin;

	/**
	 * Post-Handler-Instanz
	 *
	 * @var Post_Handler
	 */
	private $post_handler;

	/**
	 * Konstruktor
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Singleton-Instanz
	 *
	 * @return Share_On_Bluesky
	 */
	public static function get_instance(): Share_On_Bluesky {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialisiert die Hooks
	 */
	private function init_hooks(): void {
		// Lade Abhängigkeiten
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-api.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-admin.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-post-handler.php';

		// Initialisiere Klassen
		$this->api = new API();
		$this->admin = new Admin();
		$this->post_handler = new Post_Handler();

		// Registriere Aktivierung/Deaktivierung
		register_activation_hook( dirname( dirname( __FILE__ ) ) . '/share-on-bluesky.php', array( $this, 'activate' ) );
		register_deactivation_hook( dirname( dirname( __FILE__ ) ) . '/share-on-bluesky.php', array( $this, 'deactivate' ) );

		// Registriere CLI-Befehle
		add_action( 'cli_init', array( $this, 'register_cli_commands' ) );
	}

	/**
	 * Aktivierungs-Hook
	 */
	public function activate(): void {
		if ( ! wp_next_scheduled( 'bluesky_refresh_token' ) ) {
			wp_schedule_event( time(), 'weekly', 'bluesky_refresh_token' );
		}
	}

	/**
	 * Deaktivierungs-Hook
	 */
	public function deactivate(): void {
		wp_clear_scheduled_hook( 'bluesky_refresh_token' );
	}

	/**
	 * Registriert CLI-Befehle
	 */
	public function register_cli_commands(): void {
		if ( class_exists( 'WP_CLI' ) ) {
			\WP_CLI::add_command( 'bluesky send', array( $this->post_handler, 'cli_send' ) );
		}
	}
}
