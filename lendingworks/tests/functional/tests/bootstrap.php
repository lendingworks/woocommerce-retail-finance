<?php
/**
 * PHPUnit bootstrap file
 *
 * @package Lendingworks
 */

if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find $_tests_dir/includes/functions.php, have you run bin/install-wp-tests.sh ?" . PHP_EOL; // WPCS: XSS ok.
	exit( 1 );
}

// Give access to tests_add_filter() function.
require_once $_tests_dir . '/includes/functions.php';

// define fake ABSPATH
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', __DIR__ . '/../../../../wordpress/' );
}

require_once __DIR__ . '/../../../../lendingworks/vendor/autoload.php';
require_once __DIR__ . '/../../../../wordpress/wp-load.php';

// Defining WC constants, including WC files and registering wordpress hooks.
require_once dirname( dirname( __FILE__ ) ) . '/../../../wordpress/wp-content/plugins/woocommerce/woocommerce.php';
// Install the WooCommerce plugin in testing environment.
require_once dirname( dirname( __FILE__ ) ) . '/../../../wordpress/wp-content/plugins/woocommerce/includes/class-wc-install.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require dirname( dirname( __FILE__ ) ) . '/../../lendingworks.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

// Start up the WP testing environment.
require $_tests_dir . '/includes/bootstrap.php';
