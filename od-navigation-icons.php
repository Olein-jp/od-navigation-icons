<?php
/**
 * Plugin Name: OD Navigation Icons
 * Plugin URI: https://github.com/Olein-jp/od-navigation-icons
 * Description: Choose registered WordPress icons and sizes for Query Pagination and Post Navigation links.
 * Version: 0.2.1
 * Requires at least: 7.1
 * Requires PHP: 8.1
 * Author: Koji Kuno
 * Author URI: https://olein-design.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: od-navigation-icons
 * Domain Path: /languages
 * Update URI: https://github.com/Olein-jp/od-navigation-icons
 *
 * @package OD_Navigation_Icons
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ODPI_VERSION', '0.2.1' );
define( 'ODPI_FILE', __FILE__ );

require_once __DIR__ . '/includes/block-registration.php';
require_once __DIR__ . '/includes/render.php';
require_once __DIR__ . '/includes/assets.php';

/**
 * 翻訳を登録する。
 *
 * @return void
 */
function od_navigation_icons_load_textdomain() {
	load_plugin_textdomain( 'od-navigation-icons', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'od_navigation_icons_load_textdomain' );

/**
 * GitHub Releases による更新を登録する。
 *
 * @return void
 */
function od_navigation_icons_initialize_updater() {
	$autoload = __DIR__ . '/vendor/autoload.php';

	if ( ! file_exists( $autoload ) ) {
		add_action( 'admin_notices', 'od_navigation_icons_missing_dependencies_notice' );
		return;
	}

	require_once $autoload;

	new Inc2734\WP_GitHub_Plugin_Updater\Bootstrap(
		plugin_basename( __FILE__ ),
		'Olein-jp',
		'od-navigation-icons'
	);
}
add_action( 'init', 'od_navigation_icons_initialize_updater' );

/**
 * 開発用チェックアウトで依存関係がない場合に案内する。
 *
 * @return void
 */
function od_navigation_icons_missing_dependencies_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}
	?>
	<div class="notice notice-error"><p>
		<?php esc_html_e( 'OD Navigation Icons: Dependencies are missing. Run composer install in the plugin directory or install the ZIP from GitHub Releases.', 'od-navigation-icons' ); ?>
	</p></div>
	<?php
}
