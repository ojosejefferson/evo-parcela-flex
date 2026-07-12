<?php
namespace EvoParcelaFlex\Model;

/**
 * Handle Plugin Logging
 */
class Logger {

    private static $logger = null;
    private static $context = [ 'source' => 'evo-parcela-flex' ];

    /**
     * Log a message if debug mode is enabled
     */
    public static function log( $message, $level = 'info' ) {
        $settings = get_option( 'evo_flex_settings', [] );
        if ( empty( $settings['debug_mode'] ) ) {
            return;
        }

        if ( is_null( self::$logger ) && function_exists( 'wc_get_logger' ) ) {
            self::$logger = wc_get_logger();
        }

        if ( self::$logger ) {
            if ( ! is_string( $message ) ) {
                $message = print_r( $message, true );
            }
            
            switch ( $level ) {
                case 'error':
                    self::$logger->error( $message, self::$context );
                    break;
                case 'warning':
                    self::$logger->warning( $message, self::$context );
                    break;
                case 'debug':
                    self::$logger->debug( $message, self::$context );
                    break;
                default:
                    self::$logger->info( $message, self::$context );
                    break;
            }
        }
    }

    public static function error( $message ) {
        self::log( $message, 'error' );
    }

    public static function warning( $message ) {
        self::log( $message, 'warning' );
    }
}
