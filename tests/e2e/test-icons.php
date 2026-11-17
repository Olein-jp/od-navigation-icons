<?php
/** テスト専用。配布ZIPには含めない。 */
add_action( 'init', static function () {
	if ( get_option( 'odpi_e2e_icons_disabled' ) ) {
		return;
	}
	wp_register_icon_collection( 'odpi-e2e', array( 'label' => 'ODPI E2E Collection' ) );
	for ( $i = 0; $i < 1200; ++$i ) {
		wp_register_icon( 'odpi-e2e/test-' . $i, array( 'label' => 'Test icon ' . $i, 'content' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M2 2h20v20H2z"/></svg>' ) );
	}
} );
