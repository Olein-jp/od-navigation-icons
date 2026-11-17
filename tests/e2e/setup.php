<?php
/** wp-env専用の一時データ。test:e2e:cleanupで削除する。 */
if ( get_option( 'odpi_e2e_fixture' ) ) {
	WP_CLI::error( '既存のテストデータをtest:e2e:cleanupで削除してください。' );
}
wp_mkdir_p( WPMU_PLUGIN_DIR );
copy( __DIR__ . '/test-icons.php', WPMU_PLUGIN_DIR . '/odpi-e2e-icons.php' );
$ids = array();
for ( $i = 1; $i <= 4; ++$i ) {
	$ids[] = wp_insert_post( array( 'post_title' => 'ODPI E2E ' . $i, 'post_status' => 'publish' ) );
}
$content = '<!-- wp:query {"queryId":9821,"enhancedPagination":true,"query":{"perPage":1,"postType":"post","order":"desc","orderBy":"date","inherit":false}} --><div class="wp-block-query"><!-- wp:post-template --><!-- wp:post-title /--><!-- /wp:post-template --><!-- wp:query-pagination {"paginationArrow":"chevron","odpiEnabled":true,"odpiPreviousIcon":"core/plus","odpiNextIcon":"odpi-e2e/test-0"} --><!-- wp:query-pagination-previous /--><!-- wp:query-pagination-next /--><!-- /wp:query-pagination --></div><!-- /wp:query -->';
$page = wp_insert_post( array( 'post_type' => 'page', 'post_title' => 'ODPI E2E検証', 'post_status' => 'publish', 'post_content' => $content ) );
$template = wp_insert_post( array( 'post_type' => 'wp_template', 'post_name' => 'odpi-e2e', 'post_title' => 'ODPI E2E Template', 'post_status' => 'publish', 'post_content' => $content ) );
wp_set_object_terms( $template, get_stylesheet(), 'wp_theme' );
$fixture = array( 'posts' => $ids, 'page' => $page, 'url' => get_permalink( $page ), 'template' => $template, 'templateId' => get_stylesheet() . '//odpi-e2e' );
update_option( 'odpi_e2e_fixture', $fixture );
file_put_contents( __DIR__ . '/fixture.json', wp_json_encode( $fixture ) );
WP_CLI::success( '一時データを作成しました。' );
