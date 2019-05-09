<?php

// define fake ABSPATH
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/../../wordpress/' );
}
// define fake PLUGIN_ABSPATH
if ( ! defined( 'PLUGIN_ABSPATH' ) ) {
    define( 'PLUGIN_ABSPATH', ABSPATH . '/wp-content/plugins/lendingworks/' );
}

// define fake PLUGIN_ABSPATH
if ( ! defined( 'WC_ABSPATH' ) ) {
    define( 'WC_ABSPATH', ABSPATH . '/wp-content/plugins/woocommerce/' );
}

define( __NAMESPACE__, 'WC_Lendingworks\\' );
define( WC_Lending_Works . '\PLUGIN_NAME', 'wc-lending-works' );
define( WC_Lending_Works . '\PLUGIN_CLASS', 'WC_Lending_Works' );
define( WC_Lending_Works . '\PLUGIN_VERSION', '0.1.0' );
define( WC_Lending_Works . '\PLUGIN_DIR', PLUGIN_ABSPATH );

require_once __DIR__ . '/../../lendingworks/vendor/autoload.php';
require_once __DIR__ . '/../../wordpress/wp-load.php';
