<?php

namespace WebhookButtons;

defined( 'ABSPATH' ) || exit;

/**
 * The order screen: draws the boxes and buttons, and handles the click (AJAX send).
 */
class PostEdit {
	private static $instance = null;

	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
		add_action( 'wp_ajax_webhook_buttons_send', [ $this, 'ajax_send' ] );
	}

	private function is_order_screen() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return false;
		}
		return $screen->post_type === 'shop_order' || $screen->id === 'woocommerce_page_wc-orders';
	}

	public function enqueue_scripts() {
		if ( ! $this->is_order_screen() ) {
			return;
		}

		// Our own dialog and toast: no third-party JS on the order screen.
		wp_enqueue_script( 'webhook-buttons-post-edit', WEBHOOK_BUTTONS_PLUGIN_URL . 'assets/post-edit.js', [ 'jquery' ], WEBHOOK_BUTTONS_VERSION, true );
		wp_enqueue_style( 'webhook-buttons-post-edit', WEBHOOK_BUTTONS_PLUGIN_URL . 'assets/post-edit.css', [], WEBHOOK_BUTTONS_VERSION );

		$config = [
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'webhook_buttons_send' ),
			// Read-only: which order screen we are on. No form is processed here.
			'post_id' => get_the_ID() ?: absint( $_GET['id'] ?? 0 ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'i18n'    => [
				'send'     => __( 'Send', 'webhook-buttons' ),
				'cancel'   => __( 'Cancel', 'webhook-buttons' ),
				'ok'       => __( 'OK', 'webhook-buttons' ),
				'confirm'  => __( 'Send this order now?', 'webhook-buttons' ),
				/* translators: %s: button title */
				'sent'     => __( '%s sent', 'webhook-buttons' ),
				/* translators: %s: button title */
				'not_sent' => __( '%s: not sent', 'webhook-buttons' ),
				'generic'  => __( 'Something went wrong. Please try again.', 'webhook-buttons' ),
			],
		];
		/** Add values the order-screen script needs. Pro modules hook here. */
		wp_localize_script( 'webhook-buttons-post-edit', 'webhookButtonsPostEdit', (array) apply_filters( 'webhook_buttons_post_edit_config', $config ) );

		/** Enqueue more order-screen scripts after the core one (handle 'webhook-buttons-post-edit'). */
		do_action( 'webhook_buttons_post_edit_enqueue' );
	}

	public function add_meta_boxes() {
		$screen_ids = [ 'shop_order' ];
		if ( function_exists( 'wc_get_page_screen_id' ) ) {
			$screen_ids[] = wc_get_page_screen_id( 'shop-order' ); // HPOS
		}

		foreach ( Settings::get_instance()->boxes() as $box ) {
			if ( ! $box->allowed() || ! $box->visible_buttons() ) {
				continue;
			}
			foreach ( $screen_ids as $screen_id ) {
				add_meta_box( 'webhook-buttons-' . $box->id, esc_html( $box->title ), [ $this, 'print_meta_box' ], $screen_id, 'side', 'high', [ 'box_id' => $box->id ] );
			}
		}
	}

	public function print_meta_box( $post, $args ) {
		$box = Settings::get_instance()->box( $args['args']['box_id'] ?? '' );
		if ( ! $box ) {
			return;
		}
		echo '<div class="webhook-buttons-widget">';
		foreach ( $box->visible_buttons() as $button ) {
			$attributes = [
				'data-button-id' => $button->id,
				'data-message'   => $button->message,
				'data-confirm'   => $button->selectOption === Button::CONFIRM ? '1' : '0',
			];
			/**
			 * Data attributes on the order-screen button. The script sends 'data-dialog-input' text as the
			 * dialog's input placeholder; modules use it to ask staff for extra text.
			 */
			$attributes = (array) apply_filters( 'webhook_buttons_button_attributes', $attributes, $button );
			$html       = '';
			foreach ( $attributes as $name => $value ) {
				$html .= sprintf( ' %s="%s"', esc_attr( $name ), esc_attr( (string) $value ) );
			}
			printf(
				'<div class="webhook-buttons-button-card"><button type="button" class="button webhook-button"%s>%s</button></div>',
				$html, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- each name and value escaped above
				esc_html( $button->title )
			);
		}
		echo '</div>';
	}

	public function ajax_send() {
		check_ajax_referer( 'webhook_buttons_send', 'nonce' );

		// Nonce alone is not enough: the user must be able to edit orders...
		$capability = apply_filters( 'webhook_buttons_send_capability', 'edit_shop_orders' );
		if ( ! current_user_can( $capability ) ) {
			wp_send_json_error( [ 'message' => __( 'You are not allowed to send order data.', 'webhook-buttons' ) ], 403 );
		}

		$button_id = sanitize_key( (string) ( $_POST['button_id'] ?? '' ) );
		$order_id  = absint( $_POST['post_id'] ?? 0 );
		if ( $button_id === '' || ! $order_id ) {
			wp_send_json_error( [ 'message' => __( 'Missing button or order id.', 'webhook-buttons' ) ] );
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			wp_send_json_error( [ 'message' => __( 'Invalid order id.', 'webhook-buttons' ) ] );
		}

		// ...and must be allowed to see this button and the box it sits in (same checks as the order screen).
		$found = Settings::get_instance()->find_button( $button_id );
		if ( ! $found || ! $found[0]->allowed() || ! $found[1]->visible() ) {
			wp_send_json_error( [ 'message' => __( 'You are not allowed to use this button.', 'webhook-buttons' ) ], 403 );
		}
		[ , $button ] = $found;

		// Extra values from the order screen (dialog text, custom fields). Unslashed here; each module sanitizes what it reads.
		$request = isset( $_POST['extra'] ) && is_array( $_POST['extra'] ) ? (array) wp_unslash( $_POST['extra'] ) : []; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		[ $success, $result ] = $button->send( $order, $request );
		$success ? wp_send_json_success( $result ) : wp_send_json_error( $result );
	}
}
