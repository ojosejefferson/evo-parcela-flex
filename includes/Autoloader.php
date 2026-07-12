<?php
namespace EvoParcelaFlex;

/**
 * PSR-4 Autoloader
 */
class Autoloader {

    /**
     * Register the autoloader
     */
    public static function register() {
        spl_autoload_register( [ __CLASS__, 'autoload' ] );
    }

    /**
     * Autoload classes
     *
     * @param string $class
     */
    public static function autoload( $class ) {
        if ( strpos( $class, 'EvoParcelaFlex\\' ) !== 0 ) {
            return;
        }

        $relative_class = substr( $class, 15 );
        $file = EVO_PARCELA_FLEX_PATH . 'includes/' . str_replace( '\\', '/', $relative_class ) . '.php';

        if ( file_exists( $file ) ) {
            require $file;
        }
    }
}
