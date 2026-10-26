<?php namespace WebhookButtons;
defined( 'ABSPATH' ) || exit;

/**
 * One polite review request, after the store has really used the plugin.
 *
 * Shows once per store, after REVIEW_AFTER successful sends, only on our own settings page and the
 * WooCommerce order screens, only to users who can manage WooCommerce. Dismissible for good, or
 * "later" (snoozed SNOOZE_DAYS). wordpress.org guideline 11: dismissible, not site-wide, no nagging.
 */
class ReviewNotice {
	const OPTION       = 'webhook_buttons_review';
	const REVIEW_AFTER = 5;
	const SNOOZE_DAYS  = 30;
	const REVIEW_URL   = 'https://wordpress.org/support/plugin/webhook-buttons/reviews/#new-post';

	private static $instance = null;

	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		add_action( 'webhook_buttons_sent', [ $this, 'count_send' ] );
		add_action( 'admin_notices', [ $this, 'maybe_show' ] );
		add_action( 'wp_ajax_webhook_buttons_review_dismiss', [ $this, 'ajax_dismiss' ] );
	}

	/** @return array{sends:int, dismissed:bool, snoozed_until:int} */
	private function state() {
		$state = get_option( self::OPTION, [] );
		return [
			'sends'         => (int) ( $state['sends'] ?? 0 ),
			'dismissed'     => ! empty( $state['dismissed'] ),
			'snoozed_until' => (int) ( $state['snoozed_until'] ?? 0 ),
		];
	}

	private function save( array $state ) {
		update_option( self::OPTION, $state, false );
	}

	/** Fired by Button::send on every successful send. Stops counting once the notice is retired. */
	public function count_send() {
		$state = $this->state();
		if ( $state['dismissed'] || $state['sends'] > self::REVIEW_AFTER ) {
			return;
		}
		$state['sends']++;
		$this->save( $state );
	}

	private function on_our_screen() {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return false;
		}
		return $screen->id === 'toplevel_page_webhook-buttons'
			|| $screen->id === 'woocommerce_page_wc-orders'
			|| $screen->post_type === 'shop_order';
	}

	public function maybe_show() {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! $this->on_our_screen() ) {
			return;
		}
		$state = $this->state();
		if ( $state['dismissed'] || $state['sends'] < self::REVIEW_AFTER || $state['snoozed_until'] > time() ) {
			return;
		}
		$nonce = wp_create_nonce( 'webhook_buttons_review_dismiss' );
		wp_enqueue_script( 'webhook-buttons-review-notice', WEBHOOK_BUTTONS_PLUGIN_URL . 'assets/review-notice.js', [], WEBHOOK_BUTTONS_VERSION, true );
		?>
		<div class="notice notice-info is-dismissible webhook-buttons-review" data-nonce="<?php echo esc_attr( $nonce ); ?>">
			<p>
				<strong><?php esc_html_e( 'Is Webhook Buttons working for you?', 'webhook-buttons' ); ?></strong>
				<?php esc_html_e( 'Your store has sent a few orders with it. A short review on WordPress.org helps other shop owners find it.', 'webhook-buttons' ); ?>
			</p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( self::REVIEW_URL ); ?>" target="_blank" rel="noopener" data-review="done"><?php esc_html_e( 'Leave a review', 'webhook-buttons' ); ?></a>
				<a class="button" href="#" data-review="later"><?php esc_html_e( 'Maybe later', 'webhook-buttons' ); ?></a>
				<a class="button-link" href="#" data-review="done" style="margin-left:8px"><?php esc_html_e( 'Do not ask again', 'webhook-buttons' ); ?></a>
			</p>
		</div>
		<?php
	}

	public function ajax_dismiss() {
		check_ajax_referer( 'webhook_buttons_review_dismiss', 'nonce' );
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( null, 403 );
		}
		$what  = isset( $_POST['what'] ) ? sanitize_key( wp_unslash( $_POST['what'] ) ) : 'later';
		$state = $this->state();
		if ( $what === 'done' ) {
			$state['dismissed'] = true;
		} else {
			$state['snoozed_until'] = time() + self::SNOOZE_DAYS * DAY_IN_SECONDS;
		}
		$this->save( $state );
		wp_send_json_success();
	}
}
