<?php defined( 'ABSPATH' ) || exit;

/*
 * Build channel. scripts/build.sh rewrites this one line per zip:
 *   freemius    direct sales + the Pro upgrade for the wordpress.org version
 *   marketplace WooCommerce.com / CodeCanyon (store handles licensing; no upsell links)
 *   lite        free wordpress.org build
 * See includes/classes/License.php.
 */
define( 'WEBHOOK_BUTTONS_CHANNEL', 'lite' );
