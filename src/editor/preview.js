import { store as blockEditorStore } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import { iconPath, previewSvg, useResource } from './icons';
import { isLink } from './extend-block';

// エディターのDOMを書き換えず、公開wrapperPropsでコアの矢印にプレビューを重ねる。
addFilter(
	'editor.BlockListBlock',
	'od-pagination-icons/preview',
	( BlockListBlock ) =>
		function PaginationPreview( props ) {
			const values = useSelect(
				( select ) => {
					if ( ! isLink( props.name ) ) {
						return null;
					}
					const editor = select( blockEditorStore );
					const parent = editor
						.getBlockParents( props.clientId, true )
						.find(
							( id ) =>
								editor.getBlockName( id ) ===
								'core/query-pagination'
						);
					return parent ? editor.getBlockAttributes( parent ) : null;
				},
				[ props.clientId, props.name ]
			);
			const name = values?.odpiEnabled
				? values[
						props.name === 'core/query-pagination-previous'
							? 'odpiPreviousIcon'
							: 'odpiNextIcon'
					]
				: '';
			const icon = useResource( iconPath( name ) );
			const wrapperProps = { ...props.wrapperProps };
			if ( values?.odpiEnabled && values.showLabel === false ) {
				wrapperProps[ 'aria-label' ] =
					props.attributes.label ||
					( props.name === 'core/query-pagination-previous'
						? __( 'Previous Page' )
						: __( 'Next Page' ) );
			}
			if ( ! icon.data?.content ) {
				return (
					<BlockListBlock
						{ ...props }
						wrapperProps={ wrapperProps }
					/>
				);
			}
			const content = previewSvg( icon.data.content );
			const fixedColor =
				/(?:fill|stroke)=["'](?!none|currentColor)[^"']+["']/i.test(
					content
				);
			const url = `url("data:image/svg+xml,${ encodeURIComponent( content ) }")`;
			return (
				<BlockListBlock
					{ ...props }
					className={ `${ props.className || '' } odpi-preview${ fixedColor ? ' odpi-preview-fixed' : '' }` }
					wrapperProps={ {
						...wrapperProps,
						style: {
							...props.wrapperProps?.style,
							'--odpi-preview': url,
						},
					} }
				/>
			);
		}
);
