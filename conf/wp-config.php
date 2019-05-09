<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the
 * installation. You don't have to use the web site, you can
 * copy this file to "wp-config.php" and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * MySQL settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://codex.wordpress.org/Editing_wp-config.php
 *
 * @package WordPress
 */

// ** MySQL settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'rf-woocommerce' );

/** MySQL database username */
define( 'DB_USER', 'live_usom' );

/** MySQL database password */
define( 'DB_PASSWORD', 'live_usom' );

/** MySQL hostname */
define( 'DB_HOST', 'db:3360' );

/** Database Charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The Database Collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication Unique Keys and Salts.
 *
 * Change these to different unique phrases!
 * You can generate these using the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}
 * You can change these at any point in time to invalidate all existing cookies. This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define('AUTH_KEY',         'Qv,r~hZ?}8-+JRg%}voP1g5^O^%w,k-TWVZqS1mg1gF+|9SI#-_=@%>Hrnv[M%5%');
define('SECURE_AUTH_KEY',  'w[l-[(-Ev` u@4|X@%4xjczT+^(I)~T[OaWXm^|WM~uLVX|6)<MW7l{?+|{L:pU<');
define('LOGGED_IN_KEY',    '<BX?Pi$%L2gosS`2$)D]X]-aGvv2VV@+;D*c|k;?Mn+KdHz|DkCBY1V*Yt.@/%e(');
define('NONCE_KEY',        '^-dSvNC%@|Mp4iat9rK,6//eVDU~/kYt:&}>7-VD0Gkp#!n7/IAV.`<0#vE]EXuJ');
define('AUTH_SALT',        'v|@E6+<{H4*%i&dTtlTF|QfL&A q0P3EN8~RYnkKF|3zY;$<e{r`TXJ]Ta6j1qdu');
define('SECURE_AUTH_SALT', 'i!k`6CTVF[XE.},+j5L|65~I6o%1W5R}<+@mur!L_+Udug#|?p%->?F8KiqAi7XV');
define('LOGGED_IN_SALT',   'afmgF}r]SwR%|CSmjcG~TJo0(reDR>HApBdmfEMCelD0@Rd+w?>]c0-d/dt*X^=f');
define('NONCE_SALT',       'lh/sJD{|[nB_<<k6.!K5(jzsDj+]8YEaU|wGJG>d0J_62i-gBHG^976j[-g#Bx2b');

/**#@-*/

/**
 * WordPress Database Table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the Codex.
 *
 * @link https://codex.wordpress.org/Debugging_in_WordPress
 */
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', '/dev/stdout' );
define( 'WP_DEBUG_DISPLAY', true );

define( 'WP_HOME', 'http://woocommerce.docker:8080/' );
define( 'WP_SITEURL', 'http://woocommerce.docker:8080/' );

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', dirname( __FILE__ ) . '/' );
}

/** Sets up WordPress vars and included files. */
require_once( ABSPATH . 'wp-settings.php' );
