<?php

namespace WebhookButtons;

defined( 'ABSPATH' ) || exit;

/**
 * The one place that answers "is this store Pro?".
 *
 * The build channel (includes/channel.php) picks the answer:
 *   freemius    — sold direct or as the Pro upgrade of the wordpress.org version; the Freemius SDK decides.
 *   marketplace — WooCommerce.com or CodeCanyon build: the store handles licensing, so always Pro, no upsell links.
 *   lite        — the free wordpress.org build: never Pro.
 *
 * Everything in the Lite zip works without limits. Pro features are extra code in includes/pro/ that is not
 * in the Lite zip at all (wordpress.org rule: no gated code). In the paid build those modules check is_pro()
 * before acting, so a lapsed license turns them off while the saved settings stay.
 */
class License {
	const FREEMIUS    = 'freemius';
	const MARKETPLACE = 'marketplace';
	const LITE        = 'lite';

	public static function channel() {
		$channel = defined( 'WEBHOOK_BUTTONS_CHANNEL' ) ? WEBHOOK_BUTTONS_CHANNEL : self::FREEMIUS;
		return in_array( $channel, [ self::FREEMIUS, self::MARKETPLACE, self::LITE ], true ) ? $channel : self::FREEMIUS;
	}

	public static function is_pro() {
		switch ( self::channel() ) {
			case self::MARKETPLACE:
				return true;
			case self::LITE:
				return false;
			default:
				$fs = function_exists( 'webhook_buttons_fs' ) ? webhook_buttons_fs() : null;
				return $fs ? (bool) $fs->can_use_premium_code() : false;
		}
	}

	/** True when the Pro module files are present (paid build), whatever the license state. */
	public static function has_pro_files() {
		return file_exists( WEBHOOK_BUTTONS_PLUGIN_DIR . 'includes/pro/Pro.php' );
	}

	/** Where "Upgrade" points, or null when this build must not show upsell links (marketplace rule). */
	public static function upgrade_url() {
		if ( self::channel() === self::MARKETPLACE ) {
			return null;
		}
		$fs = function_exists( 'webhook_buttons_fs' ) ? webhook_buttons_fs() : null;
		return $fs ? $fs->get_upgrade_url() : 'https://noblestrategies.dev/webhook-buttons';
	}

	/** What the settings page needs to know. */
	public static function for_js() {
		return [
			'channel'     => self::channel(),
			'pro'         => self::is_pro(),
			'has_pro'     => self::has_pro_files(),
			'upgrade_url' => self::upgrade_url(),
		];
	}
}
