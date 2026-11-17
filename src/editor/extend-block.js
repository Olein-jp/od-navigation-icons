import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { addFilter } from '@wordpress/hooks';
import { __ } from '@wordpress/i18n';
import IconPicker from './IconPicker';
import IconSizeControl from './IconSizeControl';

const attributes = {
	odpiEnabled: { type: 'boolean', default: false },
	odpiIconSize: { type: 'string', default: '' },
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
	if ( name === 'core/post-navigation-link' ) {
		return {
			...settings,
			attributes: {
				...settings.attributes,
				odpiEnabled: attributes.odpiEnabled,
				odpiIconSize: attributes.odpiIconSize,
				odpiIcon: { type: 'string', default: '' },
			},
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
			const isPost = props.name === 'core/post-navigation-link';
			if ( props.name !== 'core/query-pagination' && ! isPost ) {
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
					<BlockEdit
						{ ...props }
						attributes={
							isPost && values.odpiEnabled
								? { ...values, arrow: 'arrow' }
								: values
						}
					/>
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
									{ ! isPost &&
										values.paginationArrow === 'none' && (
											<p>
												{ __(
													'For icons without text, first choose Arrow or Chevron in the standard arrow setting, then turn off Show label text.',
													'od-pagination-icons'
												) }
											</p>
										) }
									{ isPost ? (
										<IconPicker
											label={ __(
												'Post link icon',
												'od-pagination-icons'
											) }
											value={ values.odpiIcon }
											onChange={ ( value ) =>
												setAttributes( {
													odpiIcon: value,
												} )
											}
										/>
									) : (
										<>
											<IconPicker
												label={ __(
													'Previous page icon',
													'od-pagination-icons'
												) }
												value={
													values.odpiPreviousIcon
												}
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
									<IconSizeControl
										value={ values.odpiIconSize }
										onChange={ ( value ) =>
											setAttributes( {
												odpiIconSize: value,
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
