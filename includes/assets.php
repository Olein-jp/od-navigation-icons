<?php
/**
 * エディター専用JSと共通CSS。
 *
 * @package OD_Pagination_Icons
 */

/**
 * コアの登録スクリプトより先にフィルタを読み込む。
 *
 * @return void
 */
function odpi_editor_assets() {
	$asset_file = __DIR__ . '/../build/index.asset.php';
	if ( ! file_exists( $asset_file ) ) {
		return;
	}
	$asset = require $asset_file;
	wp_enqueue_script( 'odpi-editor', plugins_url( 'build/index.js', ODPI_FILE ), $asset['dependencies'], $asset['version'], true );
	wp_set_script_translations( 'odpi-editor', 'od-pagination-icons', dirname( ODPI_FILE ) . '/languages' );
	wp_enqueue_style( 'odpi-editor', plugins_url( 'build/index.css', ODPI_FILE ), array(), ODPI_VERSION );
}
add_action( 'enqueue_block_editor_assets', 'odpi_editor_assets' );

/**
 * Iframe内とフロントエンドに共通スタイルを読み込む。
 *
 * @return void
 */
function odpi_block_assets() {
	wp_enqueue_style( 'odpi-style', plugins_url( 'build/style-index.css', ODPI_FILE ), array(), ODPI_VERSION );
}
add_action( 'enqueue_block_assets', 'odpi_block_assets' );
