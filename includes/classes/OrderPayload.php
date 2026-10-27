<?php

namespace WebhookButtons;

defined( 'ABSPATH' ) || exit;

/**
 * Builds the order payload and posts it to a webhook URL.
 *
 * Shape (nested): order, totals, billing, shipping, items[], button, sent_by, sent_at.
 * Modules add keys with 'webhook_buttons_order_data', change the request body with
 * 'webhook_buttons_request_body' (for example a flat form) and add headers with 'webhook_buttons_request_headers'.
 */
class OrderPayload {
	private $order;
	private $data;
	private $url;
	private $log_detail = '';
	private $response;
	private $err = false;
	private $exclude_attributes = [];
	private $use_sample_data;
	private $sample_item_count = 10;

	const SAMPLE_MAX_ITEMS = 100;

	/**
	 * @param bool $use_sample_data  Build fake data instead of reading an order.
	 * @param int  $sample_item_count How many line items the sample has. Zapier can only map fields it has seen
	 *                                in a test, so a sample needs as many items as a real order might have.
	 */
	public function __construct( $use_sample_data = false, $sample_item_count = 10 ) {
		if ( $use_sample_data === true ) $this->use_sample_data = true;
		$this->sample_item_count = max( 1, min( self::SAMPLE_MAX_ITEMS, (int) $sample_item_count ) );
	}

	public function get_data() {
		return $this->data;
	}

	public function get_order() {
		return $this->order;
	}

	public function get_status() {
		return $this->response['status_note'] ?? __( 'Not sent yet.', 'webhook-buttons' );
	}

	public function error() {
		return $this->err ?? false;
	}

	public function get_response() {
		return $this->response ?? __( 'No response from the webhook.', 'webhook-buttons' );
	}

	public function set_url( $url ) {
		$this->url = $url;
	}

	/** Technical detail of the last failure, for error_log only (never shown to staff). */
	public function get_log_detail() {
		return $this->log_detail;
	}

	public function set_excluded_attributes( $atts ) {
		if ( is_array( $atts ) ) {
			$this->exclude_attributes = $atts;
		} elseif ( is_string( $atts ) ) {
			$this->exclude_attributes = explode( ',', $atts );
		}
	}

	/**
	 * Build the payload.
	 *
	 * @param int|false $order_id Order to send, or false with sample data.
	 * @param array     $context  button (Button), request (array posted by the order screen), sent_by_name.
	 */
	public function compile_order( $order_id = false, $context = [] ) {
		$context = wp_parse_args( $context, [
			'button'       => null,
			'request'      => [],
			'sent_by_name' => self::current_user_name(),
		] );
		$context['sample'] = ( $order_id === false && $this->use_sample_data );

		if ( $context['sample'] ) {
			$data = self::sample_data( $this->sample_item_count );
		} elseif ( $order_id === false ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Webhook Buttons: compile_order needs an order ID.' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
			return false;
		} else {
			$this->order = wc_get_order( $order_id );
			if ( ! $this->order ) {
				return false;
			}
			$data = $this->order_data( $this->order );
		}

		$button          = $context['button'];
		$data['button']  = [ 'title' => $button instanceof Button ? $button->title : '' ];
		$data['sent_by'] = [ 'name' => (string) $context['sent_by_name'] ];
		$data['sent_at'] = gmdate( 'c' );

		/**
		 * The nested payload before sending. Add or remove keys here.
		 *
		 * @param array          $data
		 * @param \WC_Order|null $order   Null for a sample.
		 * @param array          $context button, request, sent_by_name, sample.
		 */
		$this->data = apply_filters( 'webhook_buttons_order_data', $data, $this->order, $context );
		return $this->data;
	}

	private function order_data( \WC_Order $order ) {
		$created = $order->get_date_created();

		$items = [];
		foreach ( $order->get_items() as $item ) {
			$product = is_callable( [ $item, 'get_product' ] ) ? $item->get_product() : false;
			$items[] = [
				'name'         => $item->get_name(),
				'sku'          => $product ? (string) $product->get_sku() : '',
				'product_id'   => (int) $item->get_product_id(),
				'variation_id' => (int) $item->get_variation_id(),
				'quantity'     => (int) $item->get_quantity(),
				'total'        => wc_format_decimal( $item->get_total(), wc_get_price_decimals() ),
				'attributes'   => $this->item_attributes( $item, $product ),
			];
		}

		return [
			'order'    => [
				'id'            => $order->get_id(),
				'number'        => (string) $order->get_order_number(),
				'status'        => $order->get_status(),
				'created_at'    => $created ? $created->date( 'c' ) : '',
				'currency'      => $order->get_currency(),
				'customer_note' => $order->get_customer_note(),
				'edit_url'      => $order->get_edit_order_url(),
			],
			'totals'   => [
				'total'    => wc_format_decimal( $order->get_total(), wc_get_price_decimals() ),
				'subtotal' => wc_format_decimal( $order->get_subtotal(), wc_get_price_decimals() ),
				'tax'      => wc_format_decimal( $order->get_total_tax(), wc_get_price_decimals() ),
				'shipping' => wc_format_decimal( $order->get_shipping_total(), wc_get_price_decimals() ),
				'discount' => wc_format_decimal( $order->get_discount_total(), wc_get_price_decimals() ),
			],
			'billing'  => self::address( $order, 'billing' ),
			'shipping' => self::address( $order, 'shipping' ),
			'items'    => $items,
		];
	}

	private static function address( \WC_Order $order, $type ) {
		$address = [];
		foreach ( [ 'first_name', 'last_name', 'company', 'email', 'phone', 'address_1', 'address_2', 'city', 'state', 'postcode', 'country' ] as $field ) {
			if ( 'shipping' === $type && 'email' === $field ) continue;
			$getter            = "get_{$type}_{$field}";
			$address[ $field ] = is_callable( [ $order, $getter ] ) ? (string) $order->$getter() : '';
		}
		return $address;
	}

	/**
	 * Visible line item meta as label => value (variation attributes, add-ons). Keys starting with "_" are private and skipped.
	 */
	private function item_attributes( $item, $product ) {
		$attributes = [];
		foreach ( $item->get_meta_data() as $meta ) {
			if ( empty( $meta->id ) || '' === $meta->value || ! is_scalar( $meta->value ) || strpos( (string) $meta->key, '_' ) === 0 ) {
				continue;
			}
			$key           = rawurldecode( (string) $meta->key );
			$attribute_key = str_replace( 'attribute_', '', $key );
			if ( in_array( $attribute_key, $this->exclude_attributes, true ) ) continue;

			$value = rawurldecode( (string) $meta->value );
			if ( taxonomy_exists( $attribute_key ) ) {
				$term = get_term_by( 'slug', $value, $attribute_key );
				if ( $term && ! is_wp_error( $term ) ) $value = $term->name;
			}
			$attributes[ wc_attribute_label( $attribute_key, $product ) ] = wp_strip_all_tags( $value );
		}
		// Empty must encode as {} not [], so the key type never changes between orders.
		return $attributes ?: new \stdClass();
	}

	private static function current_user_name() {
		$user = wp_get_current_user();
		if ( ! $user->exists() ) return '';
		$name = trim( $user->first_name . ' ' . $user->last_name );
		return $name !== '' ? $name : $user->display_name;
	}

	private static function sample_data( $item_count ) {
		$address = [
			'first_name' => 'Sam', 'last_name' => 'Sample', 'company' => 'Sample Co', 'email' => 'sam@example.com', 'phone' => '555-0100',
			'address_1' => '1 Main St', 'address_2' => '', 'city' => 'Springfield', 'state' => 'IL', 'postcode' => '62701', 'country' => 'US',
		];
		$shipping = $address;
		unset( $shipping['email'] );
		return [
			'order'    => [
				'id' => 1234, 'number' => '1234', 'status' => 'processing', 'created_at' => gmdate( 'c' ), 'currency' => get_woocommerce_currency(),
				'customer_note' => 'Please ring the bell.', 'edit_url' => admin_url( 'admin.php?page=wc-orders&action=edit&id=1234' ),
			],
			'totals'   => [ 'total' => '45.00', 'subtotal' => '40.00', 'tax' => '0.00', 'shipping' => '5.00', 'discount' => '0.00' ],
			'billing'  => $address,
			'shipping' => $shipping,
			'items'    => self::sample_items( $item_count ),
		];
	}

	private static function sample_items( $count ) {
		$sizes = [ 'S', 'M', 'L', 'XL' ];
		$items = [];
		for ( $i = 1; $i <= $count; $i++ ) {
			$items[] = [
				'name'         => "Sample product {$i}",
				'sku'          => sprintf( 'SKU-%03d', $i ),
				'product_id'   => 1000 + $i,
				'variation_id' => 2000 + $i,
				'quantity'     => 1 + ( $i % 3 ),
				'total'        => number_format( 10 * ( 1 + ( $i % 3 ) ), 2, '.', '' ),
				'attributes'   => [ 'Size' => $sizes[ $i % 4 ], 'Color' => 'Blue' ],
			];
		}
		return $items;
	}

	/** The body that would go on the wire for this button (modules may reshape it). */
	public function body_for( ?Button $button ) {
		/** Change the request body, for example flatten it. Receives the nested data. */
		return apply_filters( 'webhook_buttons_request_body', $this->data, $this->data, $button );
	}

	/** @param Button|null $button The button being sent, so modules can read its options. */
	public function submit( ?Button $button = null ) {
		if ( ! $this->url ) {
			$this->err                     = true;
			$this->response['status_note'] = __( 'Not sent. The webhook URL is missing.', 'webhook-buttons' );
			return false;
		}
		if ( ! filter_var( $this->url, FILTER_VALIDATE_URL ) ) {
			$this->err                     = true;
			$this->response['status_note'] = __( 'Not sent. The webhook URL is not valid.', 'webhook-buttons' );
			return false;
		}
		if ( ! $this->data ) {
			$this->err                     = true;
			$this->response['status_note'] = __( 'Not sent. No order data was found.', 'webhook-buttons' );
			return false;
		}

		$headers = [
			'Accept'       => 'application/json',
			'Content-Type' => 'application/json',
		];
		/** Add request headers, for example an auth token. Header names and values must already be clean. */
		$headers = (array) apply_filters( 'webhook_buttons_request_headers', $headers, $button );

		// wp_safe_remote_post refuses private/internal hosts and checks every redirect hop (SSRF guard).
		// A self-hosted tool on localhost needs the 'http_request_host_is_external' filter; see readme.
		$response = wp_safe_remote_post( $this->url, [
			'timeout' => 10,
			'headers' => $headers,
			'body'    => wp_json_encode( $this->body_for( $button ) ),
		] );

		if ( is_wp_error( $response ) ) {
			$this->err                     = true;
			$this->log_detail              = $response->get_error_message();
			$this->response['status_note'] = __( 'Not sent. The webhook could not be reached.', 'webhook-buttons' );
			return false;
		}

		// Success = the webhook answered with HTTP 2xx. The body is not checked: each service replies in its own format.
		$code           = (int) wp_remote_retrieve_response_code( $response );
		$decoded        = json_decode( wp_remote_retrieve_body( $response ), true );
		$this->response = is_array( $decoded ) ? $decoded : [];

		if ( $code < 200 || $code > 299 ) {
			$this->err                     = true;
			$this->log_detail              = 'HTTP ' . $code . ' ' . substr( wp_remote_retrieve_body( $response ), 0, 500 );
			$this->response['status_note'] = sprintf( /* translators: %d: HTTP status code */ __( 'Not sent. The webhook answered with HTTP %d.', 'webhook-buttons' ), $code );
		} elseif ( empty( $this->data['items'] ) ) {
			$this->response['status_note'] = __( 'Sent. Note: the order has no products.', 'webhook-buttons' );
		} else {
			$this->response['status_note'] = '';
		}
		return $this->response;
	}
}
