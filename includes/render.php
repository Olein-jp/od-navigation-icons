<?php
/**
 * コアのリンクを保持したアイコン描画。
 *
 * @package OD_Pagination_Icons
 */

/**
 * 識別子と公開コレクションを検証して登録済みSVGを取得する。
 *
 * @param string $name アイコン識別子.
 * @param string $size アイコンの寸法.
 * @return string
 */
function odpi_get_icon( $name, $size = '' ) {
	if ( ! is_string( $name ) || ! preg_match( '~^[a-z0-9](?:[a-z0-9_-]*[a-z0-9])?/[a-z0-9](?:[a-z0-9_-]*[a-z0-9])?$~', $name ) || ! function_exists( 'wp_get_icon' ) ) {
		return '';
	}
	$collection = WP_Icon_Collections_Registry::get_instance()->get_registered( explode( '/', $name )[0] );
	if ( ! $collection || ( isset( $collection['public'] ) && ! $collection['public'] ) ) {
		return '';
	}
	$svg  = wp_get_icon(
		$name,
		array(
			'size'  => 24,
			'class' => 'odpi-icon',
		)
	);
	$size = odpi_icon_size( $size );
	if ( '' !== $size && '' !== $svg ) {
		$p = new WP_HTML_Tag_Processor( $svg );
		if ( $p->next_tag( 'SVG' ) ) {
			$p->set_attribute( 'style', '--odpi-icon-size:' . $size );
			$svg = $p->get_updated_html();
		}
	}
	return $svg;
}

/**
 * 正の数値と許可した単位だけを寸法として受け付ける。
 *
 * @param mixed $size 保存された寸法.
 * @return string
 */
function odpi_icon_size( $size ) {
	return is_string( $size ) && preg_match( '/^(?:[0-9]+(?:\.[0-9]+)?|\.[0-9]+)(?:px|em|rem)$/D', $size ) && (float) $size > 0 ? $size : '';
}

/**
 * HTML APIで確認した標準矢印のテキストトークンだけを置換する。
 *
 * @param string $html コアの出力.
 * @param string $svg 検証済みSVG.
 * @param string $direction previous または next.
 * @param bool   $icon_only ラベルなしの場合の余白を除く.
 * @return string
 */
function odpi_replace_arrow( $html, $svg, $direction, $icon_only = false ) {
	if ( '' === $html || ( '' === $svg && ! $icon_only ) ) {
		return $html;
	}
	$p = new WP_HTML_Tag_Processor( $html );
	if ( ! $p->next_tag(
		array(
			'tag_name'   => 'A',
			'class_name' => 'wp-block-query-pagination-' . $direction,
		)
	) || ! $p->next_tag( array( 'tag_closers' => 'visit' ) ) || $p->is_tag_closer() || 'SPAN' !== $p->get_tag() || ! $p->has_class( 'wp-block-query-pagination-' . $direction . '-arrow' ) ) {
		return $html;
	}
	if ( $icon_only ) {
		$p->add_class( 'odpi-no-label' );
	}
	if ( ! $p->next_token() || '#text' !== $p->get_token_type() || ! in_array( trim( $p->get_modifiable_text() ), array( '←', '→', '«', '»' ), true ) ) {
		return $html;
	}
	// 内部HTMLの挿入APIはないため、一意なテキストを経由して境界を保持する.
	$marker = 'ODPI_ICON_' . wp_unique_id();
	while ( str_contains( $html, $marker ) ) {
		$marker = 'ODPI_ICON_' . wp_unique_id();
	}
	if ( '' !== $svg ) {
		$p->set_modifiable_text( $marker );
	}
	if ( ! $p->next_token() || 'SPAN' !== $p->get_tag() || ! $p->is_tag_closer() ) {
		return $html;
	}
	return '' === $svg ? $p->get_updated_html() : str_replace( $marker, $svg, $p->get_updated_html() );
}

/**
 * 子ブロックの標準矢印を差し替える。
 *
 * @param string   $html コア出力.
 * @param array    $parsed 解析済みブロック.
 * @param WP_Block $block ブロックインスタンス.
 * @return string
 */
function odpi_render_icon( $html, $parsed, $block ) {
	if ( empty( $block->context['od-pagination-icons/odpiEnabled'] ) ) {
		return $html;
	}
	$previous  = 'core/query-pagination-previous' === $parsed['blockName'];
	$key       = $previous ? 'odpiPreviousIcon' : 'odpiNextIcon';
	$svg       = odpi_get_icon( $block->context[ 'od-pagination-icons/' . $key ] ?? '', $block->context['od-pagination-icons/odpiIconSize'] ?? '' );
	$icon_only = isset( $block->context['showLabel'] ) && ! $block->context['showLabel'];
	return odpi_replace_arrow( $html, $svg, $previous ? 'previous' : 'next', $icon_only );
}
add_filter( 'render_block_core/query-pagination-previous', 'odpi_render_icon', 10, 3 );
add_filter( 'render_block_core/query-pagination-next', 'odpi_render_icon', 10, 3 );

/**
 * 投稿ナビゲーションの標準矢印だけを差し替える。
 *
 * @param string $html コア出力.
 * @param array  $parsed 解析済みブロック.
 * @return string
 */
function odpi_render_post_icon( $html, $parsed ) {
	$attributes = $parsed['attrs'];
	if ( empty( $attributes['odpiEnabled'] ) ) {
		return $html;
	}
	$svg = odpi_get_icon( $attributes['odpiIcon'] ?? '', $attributes['odpiIconSize'] ?? '' );
	if ( '' === $svg ) {
		return $html;
	}
	$direction = 'previous' === ( $attributes['type'] ?? 'next' ) ? 'previous' : 'next';
	$p         = new WP_HTML_Tag_Processor( $html );
	if ( ! $p->next_tag(
		array(
			'tag_name'   => 'DIV',
			'class_name' => 'wp-block-post-navigation-link',
		)
	) ) {
		return $html;
	}
	// コアの矢印はリンクの兄弟要素。ラベル内の同名クラスには触れない.
	if ( 'next' === $direction ) {
		if ( ! $p->next_tag( array( 'tag_closers' => 'visit' ) ) || $p->is_tag_closer() || 'A' !== $p->get_tag() ||
			! $p->next_tag(
				array(
					'tag_name'    => 'A',
					'tag_closers' => 'visit',
				)
			) || ! $p->is_tag_closer() ) {
			return $html;
		}
	}
	if ( ! $p->next_tag( array( 'tag_closers' => 'visit' ) ) || $p->is_tag_closer() || 'SPAN' !== $p->get_tag() ||
		! $p->has_class( 'wp-block-post-navigation-link__arrow-' . $direction ) ||
		! $p->next_token() || '#text' !== $p->get_token_type() ||
		! in_array( trim( $p->get_modifiable_text() ), array( '←', '→', '«', '»' ), true ) ) {
		return $html;
	}
	$marker = 'ODPI_ICON_' . wp_unique_id();
	while ( str_contains( $html, $marker ) ) {
		$marker = 'ODPI_ICON_' . wp_unique_id();
	}
	$p->set_modifiable_text( $marker );
	if ( ! $p->next_token() || 'SPAN' !== $p->get_tag() || ! $p->is_tag_closer() ) {
		return $html;
	}
	return str_replace( $marker, $svg, $p->get_updated_html() );
}
add_filter( 'render_block_core/post-navigation-link', 'odpi_render_post_icon', 10, 2 );
