import { SelectControl, TextControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { iconSize } from './size';

export default function IconSizeControl( { value, onChange } ) {
	const [ emptyUnit, setEmptyUnit ] = useState( 'px' );
	const size = iconSize( value );
	const unit = size ? size.match( /(?:px|em|rem)$/ )[ 0 ] : emptyUnit;
	return (
		<>
			<TextControl
				label={ __( 'Icon size', 'od-navigation-icons' ) }
				help={ __(
					'Leave empty to match the text size.',
					'od-navigation-icons'
				) }
				type="number"
				min="0.1"
				step="any"
				value={ size ? parseFloat( size ) : '' }
				onChange={ ( number ) => {
					setEmptyUnit( unit );
					onChange( iconSize( `${ number }${ unit }` ) );
				} }
				__next40pxDefaultSize
				__nextHasNoMarginBottom
			/>
			<SelectControl
				label={ __( 'Size unit', 'od-navigation-icons' ) }
				value={ unit }
				options={ [ 'px', 'em', 'rem' ].map( ( item ) => ( {
					label: item,
					value: item,
				} ) ) }
				onChange={ ( nextUnit ) => {
					setEmptyUnit( nextUnit );
					if ( size ) {
						onChange( `${ parseFloat( size ) }${ nextUnit }` );
					}
				} }
				__next40pxDefaultSize
				__nextHasNoMarginBottom
			/>
		</>
	);
}
