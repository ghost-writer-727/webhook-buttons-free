<?php namespace WebhookButtons;
defined( 'ABSPATH' ) || exit;

class Upgrades {
    const DB_VERSION = 3;
    const DB_VERSION_OPTION_KEY = 'webhook_buttons_db_version';

    // Keys used before the rename to Webhook Buttons. Never deleted, so the old plugin keeps its data.
    const LEGACY_SETTINGS_KEY   = 'custom_zapier_buttons_settings';
    const LEGACY_DB_VERSION_KEY = 'custom_zapier_buttons_db_version';

    /**
     * Run all pending migrations up to DB_VERSION.
     * Called from main plugin file only when a version mismatch is detected.
     */
    public static function run() {
        $current = (int) get_option( self::DB_VERSION_OPTION_KEY, 0 );
        if ( $current === 0 ) {
            $current = (int) get_option( self::LEGACY_DB_VERSION_KEY, 0 );
        }

        for ( $v = $current + 1; $v <= self::DB_VERSION; $v++ ) {
            $method = "to_v{$v}";
            if ( method_exists( self::class, $method ) ) {
                self::$method();
            }
        }

        update_option( self::DB_VERSION_OPTION_KEY, self::DB_VERSION );
    }

    /**
     * Check whether the given data is already in v1 format.
     * v1 data has a top-level 'metaBoxes' array key.
     */
    private static function is_v1_format( $data ) {
        return is_array( $data ) && array_key_exists( 'metaBoxes', $data );
    }

    /**
     * Migrate v0 flat settings to v1 structured format, in the legacy option.
     * If data is already v1 or empty, do nothing.
     */
    private static function to_v1() {
        $settings_key = self::LEGACY_SETTINGS_KEY;
        $old_settings = get_option( $settings_key, [] );

        // Already migrated or fresh install
        if ( empty( $old_settings ) || self::is_v1_format( $old_settings ) ) {
            return;
        }

        $general = [
            'fallbackToUserName' => $old_settings['default_to_user_full_name'] ?? '',
            'excludeMeta'        => $old_settings['excluded_meta'] ?? [],
        ];

        $wcAdminCustomOrderFields = [];
        $zaps = [];

        foreach ( $old_settings as $old_setting_key => $old_setting_value ) {
            if ( strpos( $old_setting_key, '_wc_acof_' ) === 0 ) {
                $wcAdminCustomOrderFields[ $old_setting_key ] = $old_setting_value;
            } elseif ( strpos( $old_setting_key, 'zap_button_' ) === 0 ) {
                $zap_sub_key = substr( $old_setting_key, strlen( 'zap_button_' ) );
                $key_parts   = explode( '_', $zap_sub_key );
                $field_type  = $key_parts[0];
                $number      = intval( $key_parts[1] );
                $index       = $number - 1;

                if ( ! isset( $zaps[ $index ] ) ) {
                    $zaps[ $index ] = [
                        'id'             => 'zap-fromV0toV1-' . str_pad( $number, 6, '0', STR_PAD_LEFT ),
                        'enabled'        => false,
                        'title'          => '',
                        'catchHook'      => '',
                        'message'        => '',
                        'selectOption'   => 'Just Send',
                        'allowUserNotes' => false,
                    ];
                }

                if ( is_string( $old_setting_value ) ) {
                    $old_setting_value = trim( $old_setting_value );
                }

                switch ( $field_type ) {
                    case 'name':
                        $zaps[ $index ]['title'] = $old_setting_value;
                        break;
                    case 'url':
                        $zaps[ $index ]['catchHook'] = $old_setting_value;
                        break;
                    case 'description':
                        $zaps[ $index ]['message'] = $old_setting_value;
                        if ( ! empty( $old_setting_value ) ) {
                            $zaps[ $index ]['selectOption'] = 'Confirmation';
                        }
                        break;
                    case 'notes':
                        $zaps[ $index ]['allowUserNotes'] = $old_setting_value === 'on';
                        break;
                    case 'active':
                        $zaps[ $index ]['enabled'] = $old_setting_value === 'on';
                        break;
                }
            }
        }

        $new_settings = [
            'advanced'  => [
                'general'                  => $general,
                'wcAdminCustomOrderFields' => $wcAdminCustomOrderFields,
            ],
            'metaBoxes' => [
                0 => [
                    'id'             => 'metabox-' . time() . wp_rand( 1, 1000 ),
                    'title'          => 'Webhook Buttons',
                    'zaps'           => $zaps,
                    'visibleToUsers' => [],
                ],
            ],
        ];

        update_option( $settings_key, $new_settings );
    }

    /**
     * Copy settings from the legacy option key to the new one.
     * Stored data shape (metaBoxes, zaps, catchHook, ...) is unchanged.
     */
    private static function to_v2() {
        $legacy = get_option( self::LEGACY_SETTINGS_KEY, [] );
        if ( empty( $legacy ) || false !== get_option( Settings::OPTION_KEY ) ) {
            return;
        }
        update_option( Settings::OPTION_KEY, $legacy );
    }

    /**
     * v3: generic names. zaps -> buttons, catchHook -> url. Also drops settings no longer used
     * (fallbackToUserName, wcAdminCustomOrderFields): custom fields are sent automatically now.
     */
    private static function to_v3() {
        $settings = get_option( Settings::OPTION_KEY );
        if ( ! is_array( $settings ) ) {
            return;
        }
        $boxes = is_array( $settings['metaBoxes'] ?? null ) ? $settings['metaBoxes'] : [];
        foreach ( $boxes as $i => $box ) {
            if ( ! is_array( $box ) ) {
                continue;
            }
            $buttons = $box['buttons'] ?? $box['zaps'] ?? [];
            unset( $box['zaps'] );
            foreach ( (array) $buttons as $j => $button ) {
                if ( ! is_array( $button ) ) {
                    continue;
                }
                if ( ! isset( $button['url'] ) && isset( $button['catchHook'] ) ) {
                    $button['url'] = $button['catchHook'];
                }
                unset( $button['catchHook'] );
                $buttons[ $j ] = $button;
            }
            $box['buttons'] = array_values( (array) $buttons );
            $boxes[ $i ]    = $box;
        }
        $settings['metaBoxes'] = array_values( $boxes );
        unset( $settings['advanced']['general']['fallbackToUserName'], $settings['advanced']['wcAdminCustomOrderFields'] );
        update_option( Settings::OPTION_KEY, Settings::sanitize( $settings ) );
    }
}
