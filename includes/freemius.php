<?php defined( 'ABSPATH' ) || exit;

/*
 * Freemius placeholders. Replace both with the values from the Freemius dashboard
 * (Product > Settings > Keys). Public key only. Never put the secret key in this plugin.
 * While these are placeholders, Freemius stays off and the store counts as unlicensed.
 */
define( 'WEBHOOK_BUTTONS_FS_PRODUCT_ID', '42421' );
define( 'WEBHOOK_BUTTONS_FS_PUBLIC_KEY', 'pk_863850cdc4f14d194ea59f5829c24' );

if ( WEBHOOK_BUTTONS_CHANNEL !== 'marketplace' && ! function_exists( 'webhook_buttons_fs' ) ) {
    /**
     * The Freemius instance, or null while the product ID is still a placeholder.
     * The SDK needs a numeric ID; a placeholder ID would fatal or call the API for a product that does not exist.
     *
     * @return Freemius|null
     */
    function webhook_buttons_fs() {
        global $webhook_buttons_fs;

        if ( ! is_numeric( WEBHOOK_BUTTONS_FS_PRODUCT_ID ) || 0 !== strpos( WEBHOOK_BUTTONS_FS_PUBLIC_KEY, 'pk_' ) ) {
            return null;
        }

        if ( ! isset( $webhook_buttons_fs ) ) {
            require_once WEBHOOK_BUTTONS_PLUGIN_DIR . 'vendor/freemius/start.php';

            $webhook_buttons_fs = fs_dynamic_init( [
                'id'                  => WEBHOOK_BUTTONS_FS_PRODUCT_ID,
                'slug'                => 'webhook-buttons',
                'type'                => 'plugin',
                'public_key'          => WEBHOOK_BUTTONS_FS_PUBLIC_KEY,
                // The freemius zip is the paid build (all code); the lite zip is the free wordpress.org build.
                'is_premium'          => WEBHOOK_BUTTONS_CHANNEL === 'freemius',
                'premium_suffix'      => 'Pro',
                'has_premium_version' => true,
                'has_addons'          => false,
                'has_paid_plans'      => true,
                'is_org_compliant'    => true,
                'menu'                => [
                    'slug' => 'webhook-buttons',
                ],
            ] );
        }

        return $webhook_buttons_fs;
    }

    if ( webhook_buttons_fs() ) {
        webhook_buttons_fs()->add_action( 'after_uninstall', 'webhook_buttons_uninstall' );
        // In-dashboard pricing page: show "$49 / year", not "$4.08 / mo", and trim the Freemius boilerplate blocks.
        webhook_buttons_fs()->add_filter( 'pricing/show_annual_in_monthly', '__return_false' );
        // One Pro card with a site-count picker, instead of one card per site count (same text three times).
        webhook_buttons_fs()->add_filter( 'pricing/disable_single_package', '__return_true' );
        webhook_buttons_fs()->add_filter( 'pricing/css_path', function () {
            return WEBHOOK_BUTTONS_PLUGIN_DIR . 'assets/pricing.css';
        } );
        do_action( 'webhook_buttons_fs_loaded' );
    }
}
