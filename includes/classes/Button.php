<?php

namespace WebhookButtons;

defined( 'ABSPATH' ) || exit;

/**
 * One button on the order screen. Built from the stored settings array (see sanitize()).
 *
 * Core fields are typed properties. Anything a Pro module adds through the 'webhook_buttons_button_sanitize'
 * filter lands in the $extra bag and is read with get().
 */
class Button {
	const CONFIRM   = 'Confirmation';
	const JUST_SEND = 'Just Send';

	public $id;
	public $enabled;
	public $title;
	public $url;
	public $message;
	public $selectOption;
	/** @var array Fields added by filters (Pro). */
	public $extra = [];

	public function __construct( array $args ) {
		$args               = self::sanitize( $args );
		$this->id           = $args['id'];
		$this->enabled      = $args['enabled'];
		$this->title        = $args['title'];
		$this->url          = $args['url'];
		$this->message      = $args['message'];
		$this->selectOption = $args['selectOption'];
		$this->extra        = array_diff_key( $args, array_flip( [ 'id', 'enabled', 'title', 'url', 'message', 'selectOption' ] ) );
	}

	/** A filter-added field, or $default when absent. */
	public function get( $key, $default = null ) {
		return $this->extra[ $key ] ?? $default;
	}

	/**
	 * The one place a button's fields are typed and cleaned. Used for REST input and for stored data.
	 * Modules add their own typed fields with the filter; unknown input keys are dropped.
	 */
	public static function sanitize( array $args ) {
		$select = (string) ( $args['selectOption'] ?? self::CONFIRM );
		$clean  = [
			'id'           => sanitize_key( (string) ( $args['id'] ?? '' ) ),
			'enabled'      => ! empty( $args['enabled'] ),
			'title'        => sanitize_text_field( (string) ( $args['title'] ?? '' ) ),
			'url'          => esc_url_raw( (string) ( $args['url'] ?? '' ), [ 'http', 'https' ] ),
			'message'      => sanitize_textarea_field( (string) ( $args['message'] ?? '' ) ),
			'selectOption' => $select === self::JUST_SEND ? self::JUST_SEND : self::CONFIRM,
		];
		/**
		 * Add typed fields to a button. Return only clean values.
		 *
		 * @param array $clean Cleaned core fields.
		 * @param array $args  Raw input.
		 */
		return (array) apply_filters( 'webhook_buttons_button_sanitize', $clean, $args );
	}

	/** Shown on the order screen only when on, pointing somewhere, and not hidden by a module for this user. */
	public function visible( $user_id = null ) {
		if ( ! $this->enabled || $this->url === '' ) {
			return false;
		}
		/** Hide a box or button from a user. Pro user access control hooks here. */
		return (bool) apply_filters( 'webhook_buttons_can_see', true, $this, $user_id ?? get_current_user_id() );
	}

	/**
	 * Send one order to this button's webhook.
	 *
	 * @param \WC_Order $order
	 * @param array     $request Extra values the order screen posted (unslashed, not yet sanitized). Modules read what they know.
	 * @return array [bool success, array response]
	 */
	public function send( \WC_Order $order, array $request = [] ) {
		if ( ! $this->enabled ) return [ false, [ 'message' => __( 'This button is turned off.', 'webhook-buttons' ) ] ];
		if ( ! $this->url )     return [ false, [ 'message' => __( 'This button has no webhook URL.', 'webhook-buttons' ) ] ];

		$context = [ 'button' => $this, 'request' => $request ];
		$payload = new OrderPayload();
		$data    = $payload->compile_order( $order->get_id(), $context );
		$payload->set_url( $this->url );
		$payload->submit( $this );

		$success = ! $payload->error();
		$status  = $payload->get_status();
		$sent_by = $data['sent_by']['name'] !== '' ? $data['sent_by']['name'] : __( 'Unknown user', 'webhook-buttons' );

		$note  = sprintf( 'Webhook button <em>%s</em> used.', esc_html( $this->title ) );
		$note .= $status ? '<hr><strong>Response:</strong> ' . esc_html( $status ) : '';
		$note .= sprintf( '<hr>Submitted by <strong>%s</strong>', esc_html( $sent_by ) );
		/** Add to the order note (escaped HTML). Receives the compiled payload and the send context. */
		$order->add_order_note( (string) apply_filters( 'webhook_buttons_order_note', $note, $data, $context ) );

		if ( $success ) {
			/** A send succeeded (HTTP 2xx). Receives the button, the order and the compiled payload. */
			do_action( 'webhook_buttons_sent', $this, $order, $data );
		}
		if ( ! $success && defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'Webhook Buttons: ' . $payload->get_log_detail() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
		return [ $success, [ 'message' => $status ] ];
	}
}
