<?php

namespace WebhookButtons;

defined( 'ABSPATH' ) || exit;

/**
 * The one stored option: boxes (with buttons) and advanced settings. Reads, cleans, writes, and serves
 * the settings page. Modules (Pro) add their own keys through the filters named below.
 */
class Settings {
	const OPTION_KEY = 'webhook_buttons_settings';

	private static $instance;
	private $settings_cache = [];
	/** @var MetaBox[]|null */
	private $boxes;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_settings_page_scripts' ] );
		// Default priority: the menu must exist before Freemius adds its submenus at priority 999999999
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
	}

	/** Default shape of the option. Modules add keys with 'webhook_buttons_settings_defaults'. */
	public static function defaults() {
		$defaults = [
			'metaBoxes' => [],
			'advanced'  => [
				'general' => [ 'excludeMeta' => [] ],
			],
		];
		return (array) apply_filters( 'webhook_buttons_settings_defaults', $defaults );
	}

	// ---- read ----

	public function get( $reset_cache = false ) {
		if ( empty( $this->settings_cache ) || $reset_cache ) {
			$stored               = get_option( self::OPTION_KEY, [] );
			$this->settings_cache = self::sanitize( array_replace_recursive( self::defaults(), is_array( $stored ) ? $stored : [] ) );
			$this->boxes          = null;
		}
		return $this->settings_cache;
	}

	public function __get( $name ) {
		return $this->get()[ $name ] ?? null;
	}

	/** @return MetaBox[] keyed by box id */
	public function boxes() {
		if ( $this->boxes === null ) {
			$this->boxes = [];
			foreach ( $this->get()['metaBoxes'] as $box ) {
				$object                     = new MetaBox( $box );
				$this->boxes[ $object->id ] = $object;
			}
		}
		return $this->boxes;
	}

	public function box( $id ) {
		return $this->boxes()[ $id ] ?? null;
	}

	/** The button with this id and the box it sits in, or null. */
	public function find_button( $button_id ) {
		foreach ( $this->boxes() as $box ) {
			foreach ( $box->buttons as $button ) {
				if ( $button->id === $button_id ) {
					return [ $box, $button ];
				}
			}
		}
		return null;
	}

	// ---- write ----

	/**
	 * Replace the stored settings with a cleaned copy of $data. Only known top-level keys are kept.
	 */
	public function update( array $data ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		$clean   = self::sanitize( array_replace_recursive( $this->get(), array_intersect_key( $data, self::defaults() ) ) );
		$current = $this->get( true );
		if ( $clean === $current ) {
			return true;
		}
		$result               = update_option( self::OPTION_KEY, $clean );
		$this->settings_cache = $clean;
		$this->boxes          = null;
		return $result;
	}

	/**
	 * Typed, cleaned copy of a full settings array. Boxes and buttons clean themselves.
	 * Modules clean their own keys with 'webhook_buttons_settings_sanitize'.
	 */
	public static function sanitize( array $data ) {
		$boxes = [];
		foreach ( (array) ( $data['metaBoxes'] ?? [] ) as $box ) {
			if ( is_array( $box ) ) {
				$boxes[] = MetaBox::sanitize( $box );
			}
		}
		$advanced = (array) ( $data['advanced'] ?? [] );
		$clean    = [
			'metaBoxes' => $boxes,
			'advanced'  => [
				'general' => [
					'excludeMeta' => array_values( array_map( 'sanitize_text_field', array_filter( (array) ( $advanced['general']['excludeMeta'] ?? [] ), 'is_string' ) ) ),
				],
			],
		];
		return (array) apply_filters( 'webhook_buttons_settings_sanitize', $clean, $data );
	}

	// ---- admin page ----

	public function admin_menu() {
		$main = Main::get_instance();
		add_menu_page( $main->name, $main->name, 'manage_options', $main->slug, [ $this, 'settings_page' ] );
	}

	public function settings_page() {
		echo '<div id="webhook-buttons-settings-root"><!-- Load React App --></div>';
	}

	public function enqueue_settings_page_scripts() {
		$main = Main::get_instance();
		// Read-only: which admin page is open. No form is processed here.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! current_user_can( 'manage_options' ) || ! is_admin() || $page !== $main->slug ) {
			return;
		}

		wp_add_inline_style( 'admin-bar', '
			body.webhook-buttons-fullscreen-ui #wpcontent { padding-left: 0; }
			body.webhook-buttons-fullscreen-ui .wrap { margin: 0; }
		' );

		// The settings UI ships as one IIFE bundle; its CSS is inside main.js.
		wp_enqueue_script(
			'webhook-buttons-settings-ui',
			$main->plugin_url . 'react-settings-ui/dist/assets/main.js',
			[],
			WEBHOOK_BUTTONS_VERSION,
			true
		);

		$config = [
			'wp_rest_nonce' => wp_create_nonce( 'wp_rest' ),
			'api_url'       => rest_url(),
			'api_namespace' => Endpoints::API_NAMESPACE,
			'meta_fields'   => $this->attribute_taxonomies(),
			'license'       => License::for_js(),
		];
		/** Add values the settings page needs. Pro modules hook here. */
		wp_localize_script( 'webhook-buttons-settings-ui', 'webhookButtonsSettings', (array) apply_filters( 'webhook_buttons_settings_js', $config ) );

		wp_add_inline_script( 'webhook-buttons-settings-ui', 'jQuery(function($){ $("#webhook-buttons-settings-root").show(); });', 'after' );
	}

	private function attribute_taxonomies() {
		$options = [];
		foreach ( wc_get_attribute_taxonomies() as $taxonomy ) {
			$options[ $taxonomy->attribute_name ] = $taxonomy->attribute_label;
		}
		return $options;
	}
}
