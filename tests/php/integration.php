<?php
/**
 * wp eval-file で実行する実コア統合テスト。作成した投稿は必ず削除する。
 *
 * @package OD_Pagination_Icons
 */

global $odpi_checks;
$odpi_checks = 0;
function odpi_assert( $condition, $message ) {
	global $odpi_checks;
	++$odpi_checks;
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
}

$odpi_ids = array();
$odpi_original_get = $_GET;
$odpi_original_query = $GLOBALS['wp_query'];
$odpi_original_paged = $GLOBALS['paged'] ?? null;
try {
	odpi_assert( function_exists( 'wp_get_icon' ), 'Icons APIが存在する' );
	$parent = WP_Block_Type_Registry::get_instance()->get_registered( 'core/query-pagination' );
	odpi_assert( isset( $parent->attributes['odpiEnabled'] ), '親属性が登録される' );
	odpi_assert( 'odpiNextIcon' === $parent->provides_context['od-pagination-icons/odpiNextIcon'], 'コンテキストが登録される' );
	wp_register_icon_collection( 'odpi-test', array( 'label' => 'ODPI Test' ) );
	wp_register_icon( 'odpi-test/star', array( 'label' => 'Test star', 'content' => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path fill="currentColor" d="M2 2h20v20H2z"/></svg>' ) );
	odpi_assert( str_contains( odpi_get_icon( 'odpi-test/star' ), 'odpi-icon' ), '外部コレクションを取得できる' );
	odpi_assert( '' === odpi_get_icon( 'odpi-test/missing' ), '削除済みアイコンは空を返す' );
	odpi_assert( '' === odpi_get_icon( '<script>alert(1)</script>' ), '不正な識別子を拒否する' );
	$html = '<a href="/?query-1-page=2" class="wp-block-query-pagination-next" rel="next" aria-label="Next" data-wp-key="next" data-wp-on--click="core/query::actions.navigate"><span class="wp-block-query-pagination-next-arrow" aria-hidden="true">→</span></a>';
	$svg = odpi_get_icon( 'odpi-test/star' );
	$replaced = odpi_replace_arrow( $html, $svg, 'next' );
	odpi_assert( str_replace( '→', $svg, $html ) === $replaced, 'アンカーの全バイトを保持して矢印だけを置換する' );
	odpi_assert( $html === odpi_replace_arrow( $html, '', 'next' ), '取得失敗は元HTMLを返す' );
	$unexpected = str_replace( '→', '<b>→</b>', $html );
	odpi_assert( $unexpected === odpi_replace_arrow( $unexpected, $svg, 'next' ), '想定外のHTMLを変更しない' );
	$outside = '<a class="wp-block-query-pagination-next"></a><span class="wp-block-query-pagination-next-arrow">→</span>';
	odpi_assert( $outside === odpi_replace_arrow( $outside, $svg, 'next' ), '別要素の矢印を誤って置換しない' );
	foreach ( range( 1, 4 ) as $index ) {
		$odpi_ids[] = wp_insert_post( array( 'post_title' => 'ODPI検証 ' . $index, 'post_status' => 'publish', 'post_type' => 'post' ) );
	}
	$_GET['query-9182-page'] = 2;
	$markup = '<!-- wp:query {"queryId":9182,"query":{"perPage":1,"postType":"post","order":"desc","orderBy":"date","inherit":false}} --><div class="wp-block-query"><!-- wp:query-pagination {"paginationArrow":"none","showLabel":false,"odpiEnabled":true,"odpiPreviousIcon":"core/plus","odpiNextIcon":"odpi-test/star"} --><!-- wp:query-pagination-previous /--><!-- wp:query-pagination-next /--><!-- /wp:query-pagination --></div><!-- /wp:query -->';
	$output = do_blocks( $markup );
	odpi_assert( 2 === substr_count( $output, 'odpi-icon' ), '親子コンテキストから前後別々のSVGを描画する' );
	odpi_assert( 2 === substr_count( $output, 'aria-label=' ) - 1, 'ラベル非表示でも前後にアクセシブルネームがある' );
	odpi_assert( str_contains( $output, 'query-9182-page=1' ) && str_contains( $output, 'query-9182-page=3' ), '前後URLはコアが計算する' );
	odpi_assert( str_contains( $output, 'aria-hidden="true"' ) && str_contains( $output, 'focusable="false"' ), 'SVGは装飾として描画する' );
	wp_unregister_icon_collection( 'odpi-test' );
	$fallback = do_blocks( $markup );
	odpi_assert( 1 === substr_count( $fallback, 'odpi-icon' ) && str_contains( $fallback, '→' ), '外部登録解除で標準矢印へ戻る' );
	odpi_assert( str_contains( $markup, 'odpi-test/star' ), '識別子は保持する' );
	$disabled = do_blocks( str_replace( '"odpiEnabled":true', '"odpiEnabled":false', $markup ) );
	odpi_assert( ! str_contains( $disabled, 'odpi-icon' ) && ! str_contains( $disabled, '-arrow' ), '無効化すると元のnone設定に戻る' );
	$enhanced = do_blocks( str_replace( '"queryId":9182', '"queryId":9182,"enhancedPagination":true', $markup ) );
	odpi_assert( str_contains( $enhanced, 'data-wp-on--click="core/query::actions.navigate"' ), 'Enhanced Pagination属性を保持する' );
	$multiple = do_blocks( $markup . str_replace( array( '9182', '"odpiEnabled":true' ), array( '9183', '"odpiEnabled":false' ), $markup ) );
	odpi_assert( 1 === substr_count( $multiple, 'odpi-icon' ), '複数クエリー間で設定が混在しない' );
	$GLOBALS['wp_query'] = new WP_Query( array( 'post_type' => 'post', 'posts_per_page' => 1, 'paged' => 2 ) );
	$GLOBALS['paged'] = 2;
	$inherited = do_blocks( str_replace( '"inherit":false', '"inherit":true', $markup ) );
	odpi_assert( str_contains( $inherited, 'odpi-icon' ), '親クエリーを継承する場合もアイコンを描画する' );
	WP_CLI::success( $odpi_checks . ' 件のPHP統合検証に合格しました。' );
} finally {
	$_GET = $odpi_original_get;
	$GLOBALS['wp_query'] = $odpi_original_query;
	$GLOBALS['paged'] = $odpi_original_paged;
	foreach ( $odpi_ids as $id ) {
		wp_delete_post( $id, true );
	}
	if ( WP_Icon_Collections_Registry::get_instance()->is_registered( 'odpi-test' ) ) {
		wp_unregister_icon_collection( 'odpi-test' );
	}
}
