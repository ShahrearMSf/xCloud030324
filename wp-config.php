<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the web site, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * Localized language
 * * ABSPATH
 *
 * @link https://wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'local' );

/** Database username */
define( 'DB_USER', 'root' );

/** Database password */
define( 'DB_PASSWORD', 'root' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',          '/In(R8uWL_=BQ?Ab3[dW7?c%N|?)5W1*6wT>T*#r#|-x$f]m!n4jt0r*6 ~*q?=;' );
define( 'SECURE_AUTH_KEY',   'O51<y$22eX4S&;X)S.<e`aj.T.NEN0=cowrh[w)_gx3[gobKk+L9e[+2DF@Dga}O' );
define( 'LOGGED_IN_KEY',     'ZpB2]xF#un0$@2fpgKn^#N43$3T(p,/PsKD|;Y/{p,V!pees=:&T4?sZ>e6TT.//' );
define( 'NONCE_KEY',         '7fzk%:N5`p--w,lDp-M)Gd-w%-)Vx6=?A8]2Ra#Ot:*iYg9]<fQd6s7&n%WTYjxK' );
define( 'AUTH_SALT',         'mo1M/_:/x}ifUe8Rib>9TP`dbciQH`|t_^u6B-OXpDCiC#-|MeT:Q{d:#DXH(G0q' );
define( 'SECURE_AUTH_SALT',  '],sP0?s_*.:/*RqSZu=9;0Z])evfPm`38_e0OvYeDnf.nz#$07/HLNO=npeh1y~u' );
define( 'LOGGED_IN_SALT',    'VD<f<WyP!0lF[_mDKKO6tNjK-H~r[V&P{MG@+vb}Cl>B0ezeH*(X{Z8wj]DdY=$N' );
define( 'NONCE_SALT',        '*8v|AC`5Ze:bv8Z8VYo>=:4/Qdp<aZ0^&!:H5~$.#0f`AnC6Kefn?yc<%~v7N#+x' );
define( 'WP_CACHE_KEY_SALT', '1Igx;So9mLK/`!+T;Bz<Go=,C1o+42;LM&-E.EHZ` L<jun5/8{}&A4<PCh+xQ}]' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_';


/* Add any custom values between this line and the "stop editing" line. */



/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'WP_DEBUG_LOG', true );

define( 'WP_ENVIRONMENT_TYPE', 'local' );
/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
