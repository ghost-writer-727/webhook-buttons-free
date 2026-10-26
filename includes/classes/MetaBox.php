<?php

namespace WebhookButtons;

defined( 'ABSPATH' ) || exit;

/**
 * One box on the order screen, holding buttons. Built from the stored settings array (see sanitize()).
 * Fields added by the 'webhook_buttons_box_sanitize' filter (Pro) land in $extra.
 */
class MetaBox {
	public $id;
	public $title;
	/** @var Button[] */
	public $buttons = [];
	/** @var array Fields added by filters (Pro). */
	public $extra = [];

	public function __construct( array $args ) {
		$args        = self::sanitize( $args );
		$this->id    = $args['id'];
		$this->title = $args['title'];
		$this->extra = array_diff_key( $args, array_flip( [ 'id', 'title', 'buttons' ] ) );
		foreach ( $args['buttons'] as $button ) {
			$this->buttons[] = new Button( $button );
		}
	}

	/** A filter-added field, or $default when absent. */
	public function get( $key, $default = null ) {
		return $this->extra[ $key ] ?? $default;
	}

	public static function sanitize( array $args ) {
		$buttons = [];
		foreach ( (array) ( $args['buttons'] ?? [] ) as $button ) {
			if ( is_array( $button ) ) {
				$buttons[] = Button::sanitize( $button );
			}
		}
		$clean = [
			'id'      => sanitize_key( (string) ( $args['id'] ?? '' ) ),
			'title'   => sanitize_text_field( (string) ( $args['title'] ?? '' ) ),
			'buttons' => $buttons,
		];
		/** Add typed fields to a box. Return only clean values. */
		return (array) apply_filters( 'webhook_buttons_box_sanitize', $clean, $args );
	}

	/** May this user see the box at all? A module (Pro user access control) can say no. */
	public function allowed( $user_id = null ) {
		return (bool) apply_filters( 'webhook_buttons_can_see', true, $this, $user_id ?? get_current_user_id() );
	}

	/** Buttons the current user may see on the order screen. */
	public function visible_buttons() {
		return array_values( array_filter( $this->buttons, fn( Button $b ) => $b->visible() ) );
	}
}
