<?php
/** 一時データだけを削除する。 */
$fixture = get_option( 'odpi_e2e_fixture', array() );
foreach ( $fixture['posts'] ?? array() as $id ) {
	wp_delete_post( $id, true );
}
if ( ! empty( $fixture['page'] ) ) {
	wp_delete_post( $fixture['page'], true );
}
if ( ! empty( $fixture['template'] ) ) {
	wp_delete_post( $fixture['template'], true );
}
delete_option( 'odpi_e2e_fixture' );
delete_option( 'odpi_e2e_icons_disabled' );
if ( file_exists( WPMU_PLUGIN_DIR . '/odpi-e2e-icons.php' ) ) {
	unlink( WPMU_PLUGIN_DIR . '/odpi-e2e-icons.php' );
}
if ( file_exists( __DIR__ . '/fixture.json' ) ) {
	unlink( __DIR__ . '/fixture.json' );
}
WP_CLI::success( '一時データを削除しました。' );
