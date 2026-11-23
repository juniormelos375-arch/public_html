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
define( 'DB_NAME', 'wp_66915809352' );

/** Database username */
define( 'DB_USER', 'wp_66915809352' );

/** Database password */
define( 'DB_PASSWORD', 'zyA6W@X' );

/** Database hostname */
define( 'DB_HOST', 'wp_66915809352.mysql.dbaas.com.br' );

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
define( 'AUTH_KEY',          '/*F?p`*b}.%090ND=5)1zx45bQ>;g`8hvd?| ET=ds&{Y1|G>P.%uPtim2>!mi&F' );
define( 'SECURE_AUTH_KEY',   '4xwPI*23)[Pw]RCx(Y2gf0jU!5R!]zl6}a0RCE#[E0j|M)UoMot&>qS8JFd,14sr' );
define( 'LOGGED_IN_KEY',     'bt`~oGf9[<6Or{mQ(3_xXy^@mvV!BG)pM3?6h|`PY$iZB9Vc$WBXU;]?YwK,Hr5~' );
define( 'NONCE_KEY',         '8^<%@XK)/G&HUjimlcc}/Y;]<Go}g;@v60lw`gE/j@dlMFtQJO9+ga+[x[?&mS7#' );
define( 'AUTH_SALT',         'cqQ5a^%mq4f<@+,H55CM,DRapXy?ep<HxQ{8L=7 QV+zHStZHj >oFBix^[u)j&*' );
define( 'SECURE_AUTH_SALT',  'Jw6ATa`W@GbRzzO0C}+M_jW$<lwcy1%,s.O<,`x3-%ay?50IcYi(|}WsNf ,-Q{a' );
define( 'LOGGED_IN_SALT',    ':9XQ+z9r>T?}CqPl?G0!I;j&4N;BkW9i2@kShs!G}rlt,GVPmkFTIQ4C>md ^a4|' );
define( 'NONCE_SALT',        'j?lvk6Q:>doER1P{Alo28fZ;({FxCr#NZoO~7xDT3RH,4-fo~zt}i|@lL7#v&Hcu' );
define( 'WP_CACHE_KEY_SALT', 'Y!M9`%Eh@amW.4`tX.j!31=Vm5([jl2nHDU|#P*1rJ^tT{vE)y3&1lU<d:/T092 ' );


/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 */
$table_prefix = 'wp_66915809352';


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
if ( ! defined( 'WP_DEBUG' ) ) {
	define( 'WP_DEBUG', false );
}

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
