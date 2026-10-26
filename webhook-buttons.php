<?php defined( 'ABSPATH' ) || exit;

/*
Plugin Name: Webhook Buttons for WooCommerce
Plugin URI: https://noblestrategies.dev/webhook-buttons
Description: Adds buttons to the WooCommerce order screen. Each button sends the order data to a webhook URL you choose.
Author: Noble Strategies
Author URI: https://noblestrategies.dev
Version: 1.0.4
Requires at least: 6.5
Requires PHP: 7.4
Requires Plugins: woocommerce
WC requires at least: 10.0
WC tested up to: 11.2
Text Domain: webhook-buttons
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
@fs_premium_only /includes/pro/, /react-settings-ui/src/pro/
*/

// Another copy of this plugin already loaded (Lite from wordpress.org next to the paid build from Freemius).
// Tell the SDK which file is the paid one, so it deactivates the free copy, and stop before defining anything twice.
// The paid build is the one that ships includes/pro/.
if ( function_exists( 'webhook_buttons_fs' ) && webhook_buttons_fs() ) {
    webhook_buttons_fs()->set_basename( is_dir( __DIR__ . '/includes/pro' ), __FILE__ );
    return;
}

define( 'WEBHOOK_BUTTONS_VERSION', '1.0.4' );
define( 'WEBHOOK_BUTTONS_PLUGIN_FILE', __FILE__ );
define( 'WEBHOOK_BUTTONS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WEBHOOK_BUTTONS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WEBHOOK_BUTTONS_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Freemius must load at file scope, before plugins_loaded.
require_once WEBHOOK_BUTTONS_PLUGIN_DIR . 'includes/channel.php';
require_once WEBHOOK_BUTTONS_PLUGIN_DIR . 'includes/freemius.php';

/**
 * Remove all plugin data. Runs once, when the plugin is deleted.
 * Freemius calls it through 'after_uninstall'; without Freemius, WordPress calls it directly.
 * Wrapped so a second copy of the plugin (see the guard above) does not redeclare it at compile time.
 */
if ( ! function_exists( 'webhook_buttons_uninstall' ) ) {
    function webhook_buttons_uninstall() {
        delete_option( 'webhook_buttons_settings' );
        delete_option( 'webhook_buttons_db_version' );
        delete_option( 'webhook_buttons_review' );
    }
}

// With Freemius loaded, its 'after_uninstall' action (see includes/freemius.php) calls webhook_buttons_uninstall().
// A WordPress uninstall hook would block the Freemius uninstall report, so it is used only without Freemius (marketplace build).
register_activation_hook( __FILE__, function () {
    if ( ! function_exists( 'webhook_buttons_fs' ) || ! webhook_buttons_fs() ) {
        register_uninstall_hook( WEBHOOK_BUTTONS_PLUGIN_FILE, 'webhook_buttons_uninstall' );
    }
} );

// HPOS compatibility
add_action( 'before_woocommerce_init', function () {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
} );

// Bootstrap inside plugins_loaded with WooCommerce check
add_action( 'plugins_loaded', function () {

    if ( ! class_exists( 'WooCommerce' ) ) {
        add_action( 'admin_notices', function () {
            $message = __( '<strong>WooCommerce</strong> must be active to use <strong>Webhook Buttons for WooCommerce</strong>.', 'webhook-buttons' );
            printf( '<div class="error"><p>%s</p></div>', wp_kses_post( $message ) );
        } );
        return;
    }

    // One class per file.
    foreach ( [ 'License', 'Main', 'Settings', 'Endpoints', 'Upgrades', 'PostEdit', 'MetaBox', 'Button', 'OrderPayload', 'ReviewNotice' ] as $class ) {
        require_once WEBHOOK_BUTTONS_PLUGIN_DIR . "includes/classes/{$class}.php";
    }
    // Pro modules hook into the core filters. The folder is absent from the Lite (wordpress.org) build.
    if ( WebhookButtons\License::has_pro_files() ) {
        require_once WEBHOOK_BUTTONS_PLUGIN_DIR . 'includes/pro/Pro.php';
        WebhookButtons\Pro\Pro::register();
    }

    // Run DB migration only when version mismatch detected
    $current_db_version = (int) get_option( WebhookButtons\Upgrades::DB_VERSION_OPTION_KEY, 0 );
    if ( $current_db_version < WebhookButtons\Upgrades::DB_VERSION ) {
        WebhookButtons\Upgrades::run();
    }

    // Instantiate plugin classes
    WebhookButtons\Main::get_instance();
    WebhookButtons\Settings::get_instance();
    WebhookButtons\Endpoints::get_instance();
    WebhookButtons\PostEdit::get_instance();
    WebhookButtons\ReviewNotice::get_instance();
} );
