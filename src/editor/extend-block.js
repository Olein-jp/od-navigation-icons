import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import IconPicker from './IconPicker';

const attributes = {
	odpiEnabled: { type: 'boolean', default: false },
	odpiPreviousIcon: { type: 'string', default: '' },
	odpiNextIcon: { type: 'string', default: '' },
};
export const isLink = ( name ) =>
	[ 'core/query-pagination-previous', 'core/query-pagination-next' ].includes(
		name
	);

export function extendSettings( settings, name ) {
	const context = Object.fromEntries(
		Object.keys( attributes ).map( ( key ) => [
			`od-pagination-icons/${ key }`,
			key,
		] )
	);
	if ( name === 'core/query-pagination' ) {
		return {
			...settings,
			attributes: { ...settings.attributes, ...attributes },
			providesContext: { ...settings.providesContext, ...context },
		};
	}
	return isLink( name )
		? {
				...settings,
				usesContext: [
					...new Set( [
						...( settings.usesContext || [] ),
						...Object.keys( context ),
					] ),
				],
			}
		: settings;
}
addFilter(
	'blocks.registerBlockType',
	'od-pagination-icons/attributes',
	extendSettings
);

addFilter(
	'editor.BlockEdit',
	'od-pagination-icons/inspector',
	( BlockEdit ) =>
		function PaginationEdit( props ) {
			if ( props.name !== 'core/query-pagination' ) {
				const context =
					isLink( props.name ) &&
					props.context?.[ 'od-pagination-icons/odpiEnabled' ]
						? { ...props.context, paginationArrow: 'arrow' }
						: props.context;
				return <BlockEdit { ...props } context={ context } />;
			}
			const { attributes: values, setAttributes } = props;
			return (
				<>
					<BlockEdit { ...props } />
					<InspectorControls>
						<PanelBody
							title={ __(
								'Custom icons',
								'od-pagination-icons'
							) }
						>
							<ToggleControl
								label={ __(
									'Use custom icons',
									'od-pagination-icons'
								) }
								checked={ !! values.odpiEnabled }
								onChange={ ( enabled ) =>
									setAttributes( { odpiEnabled: enabled } )
								}
							/>
							{ values.odpiEnabled && (
								<>
									<p>
										{ __(
											'Custom mode overrides the standard arrow display. Disabling it restores your standard arrow setting. An empty selection uses the standard arrow.',
											'od-pagination-icons'
										) }
									</p>
									{ values.paginationArrow === 'none' && (
										<p>
											{ __(
												'For icons without text, first choose Arrow or Chevron in the standard arrow setting, then turn off Show label text.',
												'od-pagination-icons'
											) }
										</p>
									) }
									<IconPicker
										label={ __(
											'Previous page icon',
											'od-pagination-icons'
										) }
										value={ values.odpiPreviousIcon }
										onChange={ ( value ) =>
											setAttributes( {
												odpiPreviousIcon: value,
											} )
										}
									/>
									<IconPicker
										label={ __(
											'Next page icon',
											'od-pagination-icons'
										) }
										value={ values.odpiNextIcon }
										onChange={ ( value ) =>
											setAttributes( {
												odpiNextIcon: value,
											} )
										}
									/>
								</>
							) }
						</PanelBody>
					</InspectorControls>
				</>
			);
		}
);
