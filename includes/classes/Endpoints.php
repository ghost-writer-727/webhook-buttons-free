<?php

namespace WebhookButtons;

defined( 'ABSPATH' ) || exit;

/**
 * REST routes for the settings page. Every route needs manage_options.
 */
class Endpoints {
	const API_NAMESPACE   = 'webhook-buttons/v1';
	const FETCH           = '/fetch-settings';
	const UPDATE          = '/update-settings';

	private static $instance;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_endpoints' ] );
	}

	public function register_endpoints() {
		$admin_only = fn() => current_user_can( 'manage_options' );

		register_rest_route( self::API_NAMESPACE, self::FETCH, [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_settings' ],
			'permission_callback' => $admin_only,
		] );
		register_rest_route( self::API_NAMESPACE, self::UPDATE, [
			'methods'             => 'POST',
			'callback'            => [ $this, 'update_settings' ],
			'permission_callback' => $admin_only,
			'args'                => [
				'metaBoxes' => [ 'type' => 'array' ],
				'advanced'  => [ 'type' => 'object' ],
			],
		] );
	}

	public function get_settings() {
		return rest_ensure_response( [ 'success' => true, 'settings' => Settings::get_instance()->get() ] );
	}

	public function update_settings( \WP_REST_Request $request ) {
		$settings = Settings::get_instance();
		$updated  = $settings->update( (array) $request->get_json_params() );
		return rest_ensure_response( [
			'success'  => $updated,
			'message'  => $updated ? __( 'Settings saved.', 'webhook-buttons' ) : __( 'Settings could not be saved.', 'webhook-buttons' ),
			'settings' => $settings->get( true ),
		] );
	}
}
