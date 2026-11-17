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
$odpi_original_post = $GLOBALS['post'] ?? null;
$odpi_term = 0;
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
		$odpi_ids[] = wp_insert_post( array( 'post_title' => 'ODPI検証 ' . $index, 'post_date' => '2026-01-0' . $index . ' 12:00:00', 'post_status' => 'publish', 'post_type' => 'post' ) );
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
	$sized = do_blocks( str_replace( '"odpiEnabled":true', '"odpiIconSize":"32px","odpiEnabled":true', $markup ) );
	odpi_assert( str_contains( $sized, '--odpi-icon-size:32px' ), 'サイズを親からページ送りへ渡す' );
	foreach ( array( '32px', '1.5em', '.75rem' ) as $size ) {
		odpi_assert( str_contains( odpi_get_icon( 'core/plus', $size ), '--odpi-icon-size:' . $size ), '許可した寸法をSVGへ反映する' );
	}
	foreach ( array( '', '0px', '-1em', '24%', '2vw', "24px;display:none", '2rem" onload="alert(1)', array( '24px' ) ) as $size ) {
		odpi_assert( ! str_contains( odpi_get_icon( 'core/plus', $size ), '--odpi-icon-size' ), '無効な寸法では文字サイズ連動へ戻る' );
	}
	$label = '<span class="wp-block-post-navigation-link__arrow-next">→</span>';
	$post_html = '<div class="wp-block-post-navigation-link"><a href="/next" rel="next">' . $label . '</a><span class="wp-block-post-navigation-link__arrow-next" aria-hidden="true">→</span></div>';
	$post_attrs = array( 'attrs' => array( 'odpiEnabled' => true, 'odpiIcon' => 'core/plus' ) );
	odpi_assert( str_contains( odpi_render_post_icon( $post_html, $post_attrs ), $label . '</a><span class="wp-block-post-navigation-link__arrow-next" aria-hidden="true">' . odpi_get_icon( 'core/plus' ) ), 'ラベルの同名クラスを変更せず兄弟の矢印だけを置換する' );
	$outside = '<div class="wp-block-post-navigation-link"></div><span class="wp-block-post-navigation-link__arrow-next">→</span>';
	odpi_assert( $outside === odpi_render_post_icon( $outside, $post_attrs ), '投稿ナビゲーションの外側を変更しない' );
	$post_block = WP_Block_Type_Registry::get_instance()->get_registered( 'core/post-navigation-link' );
	odpi_assert( isset( $post_block->attributes['odpiIcon'], $post_block->attributes['odpiIconSize'] ), '投稿ナビゲーション属性が登録される' );
	$term = wp_insert_term( 'ODPI navigation ' . wp_unique_id(), 'category' );
	$odpi_term = $term['term_id'];
	foreach ( $odpi_ids as $id ) {
		wp_set_post_categories( $id, array( $odpi_term ) );
	}
	$GLOBALS['wp_query'] = new WP_Query( array( 'p' => $odpi_ids[1] ) );
	$GLOBALS['post'] = get_post( $odpi_ids[1] );
	setup_postdata( $GLOBALS['post'] );
	foreach ( array( 'previous' => '←', 'next' => '→' ) as $direction => $arrow ) {
		foreach ( array( array( false, false ), array( true, false ), array( true, true ) ) as $display ) {
			$attrs = array( 'type' => $direction, 'arrow' => 'none', 'label' => '移動 <em>ラベル</em>', 'showTitle' => $display[0], 'linkLabel' => $display[1], 'taxonomy' => 'category', 'odpiEnabled' => true, 'odpiIcon' => 'core/plus', 'odpiIconSize' => '2rem' );
			$render = static function ( $attrs ) {
				return do_blocks( '<!-- wp:post-navigation-link ' . wp_json_encode( $attrs ) . ' /-->' );
			};
			$custom = $render( $attrs );
			$core_attrs = array_merge( $attrs, array( 'odpiEnabled' => false, 'arrow' => 'arrow' ) );
			$core = $render( $core_attrs );
			odpi_assert( str_contains( $custom, 'odpi-icon' ), '前後の投稿でカスタムアイコンを表示する' );
			odpi_assert( str_replace( $arrow, odpi_get_icon( 'core/plus', '2rem' ), $core ) === $custom, 'コアのURL・タイトル・ラベル・分類設定を保持する' );
			odpi_assert( ! str_contains( $render( array_merge( $attrs, array( 'odpiEnabled' => false ) ) ), '__arrow-' ), '無効化すると元の矢印なしへ戻る' );
			odpi_assert( $core === $render( array_merge( $attrs, array( 'odpiIcon' => 'missing/icon' ) ) ), '未登録アイコンは標準矢印へ戻る' );
			odpi_assert( $core === $render( array_merge( $attrs, array( 'odpiIcon' => '' ) ) ), '未選択でも標準矢印へ戻る' );
		}
		$boundary = 'previous' === $direction ? $odpi_ids[0] : $odpi_ids[3];
		$GLOBALS['wp_query'] = new WP_Query( array( 'p' => $boundary ) );
		$GLOBALS['post'] = get_post( $boundary );
		setup_postdata( $GLOBALS['post'] );
		odpi_assert( ! str_contains( $render( $attrs ), 'odpi-icon' ) && ! str_contains( $render( $attrs ), '<a ' ), '隣接投稿がない場合はリンクを作らない' );
		$GLOBALS['wp_query'] = new WP_Query( array( 'p' => $odpi_ids[1] ) );
		$GLOBALS['post'] = get_post( $odpi_ids[1] );
		setup_postdata( $GLOBALS['post'] );
	}
	$GLOBALS['wp_query'] = new WP_Query( array( 'post_type' => 'post' ) );
	odpi_assert( '' === $render( $attrs ), '一覧ではコア同様に投稿ナビゲーションを出力しない' );
	WP_CLI::success( $odpi_checks . ' 件のPHP統合検証に合格しました。' );
} finally {
	$_GET = $odpi_original_get;
	$GLOBALS['wp_query'] = $odpi_original_query;
	$GLOBALS['paged'] = $odpi_original_paged;
	$GLOBALS['post'] = $odpi_original_post;
	if ( $odpi_original_post ) {
		setup_postdata( $odpi_original_post );
	}
	if ( $odpi_term ) {
		wp_delete_term( $odpi_term, 'category' );
	}
	foreach ( $odpi_ids as $id ) {
		wp_delete_post( $id, true );
	}
	if ( WP_Icon_Collections_Registry::get_instance()->is_registered( 'odpi-test' ) ) {
		wp_unregister_icon_collection( 'odpi-test' );
	}
}
