<?php
/**
 * コアブロックの保存属性とコンテキストを拡張する。
 *
 * @package OD_Navigation_Icons
 */

/**
 * コア登録前にサーバー側の定義を追加する。
 *
 * @param array  $args 登録設定.
 * @param string $name ブロック名.
 * @return array
 */
function odpi_register_attributes( $args, $name ) {
	$attributes = array(
		'odpiEnabled'      => array(
			'type'    => 'boolean',
			'default' => false,
		),
		'odpiPreviousIcon' => array(
			'type'    => 'string',
			'default' => '',
		),
		'odpiIconSize'     => array(
			'type'    => 'string',
			'default' => '',
		),
		'odpiNextIcon'     => array(
			'type'    => 'string',
			'default' => '',
		),
	);
	$context    = array();
	foreach ( $attributes as $key => $schema ) {
		$context[ 'od-navigation-icons/' . $key ] = $key;
	}
	if ( 'core/query-pagination' === $name ) {
		$args['attributes']       = array_merge( $args['attributes'] ?? array(), $attributes );
		$args['provides_context'] = array_merge( $args['provides_context'] ?? array(), $context );
	} elseif ( 'core/post-navigation-link' === $name ) {
		$args['attributes'] = array_merge(
			$args['attributes'] ?? array(),
			array(
				'odpiEnabled'  => $attributes['odpiEnabled'],
				'odpiIconOnly' => array(
					'type'    => 'boolean',
					'default' => false,
				),
				'odpiIconSize' => $attributes['odpiIconSize'],
				'odpiIcon'     => array(
					'type'    => 'string',
					'default' => '',
				),
			)
		);
	} elseif ( in_array( $name, array( 'core/query-pagination-previous', 'core/query-pagination-next' ), true ) ) {
		$args['uses_context'] = array_values( array_unique( array_merge( $args['uses_context'] ?? array(), array_keys( $context ) ) ) );
	}
	return $args;
}
add_filter( 'register_block_type_args', 'odpi_register_attributes', 10, 2 );

/**
 * 保存済みの標準設定を変更せず、描画時だけ標準矢印を生成する。
 *
 * @param array $context コンテキスト.
 * @param array $parsed 解析済みブロック.
 * @return array
 */
function odpi_render_context( $context, $parsed ) {
	if ( ! empty( $context['od-navigation-icons/odpiEnabled'] ) && in_array( $parsed['blockName'], array( 'core/query-pagination-previous', 'core/query-pagination-next' ), true ) ) {
		$context['paginationArrow'] = 'arrow';
	}
	return $context;
}
add_filter( 'render_block_context', 'odpi_render_context', 10, 2 );

/**
 * 投稿ナビゲーションの保存値を保ち、描画時だけ矢印を用意する。
 *
 * @param array $parsed 解析済みブロック.
 * @return array
 */
function odpi_post_navigation_data( $parsed ) {
	if ( 'core/post-navigation-link' === $parsed['blockName'] && ! empty( $parsed['attrs']['odpiEnabled'] ) ) {
		$parsed['attrs']['arrow'] = 'arrow';
	}
	return $parsed;
}
add_filter( 'render_block_data', 'odpi_post_navigation_data' );
