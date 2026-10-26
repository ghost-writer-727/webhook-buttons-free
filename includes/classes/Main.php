<?php namespace WebhookButtons;
defined( 'ABSPATH' ) || exit;

use Exception;

class Main {
    private $slug = 'webhook-buttons';
    private $name = 'Webhook Buttons';
    private $plugin_dir;
    private $plugin_url;
    private static $instance = null;

    public static function get_instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->plugin_dir = WEBHOOK_BUTTONS_PLUGIN_DIR;
        $this->plugin_url = WEBHOOK_BUTTONS_PLUGIN_URL;

        add_filter( 'plugin_action_links_' . WEBHOOK_BUTTONS_PLUGIN_BASENAME, [ $this, 'action_links' ] );
    }

    public function __get( $name ) {
        if ( property_exists( $this, $name ) ) {
            return $this->$name;
        }
        throw new Exception( esc_html( "Undefined property: $name" ) );
    }

    public function action_links( $links ) {
        $settings_link = '<a href="' . esc_url( admin_url( 'admin.php?page=' . $this->slug ) ) . '">' . esc_html__( 'Settings', 'webhook-buttons' ) . '</a>';
        array_unshift( $links, $settings_link );
        return $links;
    }
}
