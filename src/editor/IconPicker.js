import {
	Button,
	Modal,
	Notice,
	SearchControl,
	SelectControl,
	Spinner,
} from '@wordpress/components';
import { RawHTML, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { filterIcons, iconPath, useResource } from './icons';

const PAGE_SIZE = 48;

function IconLibrary( { value, onChange, onClose } ) {
	const [ retry, setRetry ] = useState( 0 );
	const collections = useResource( '/wp/v2/icon-collections', retry );
	const [ selectedCollection, setCollection ] = useState(
		value.split( '/' )[ 0 ]
	);
	const [ search, setSearch ] = useState( '' );
	const [ page, setPage ] = useState( 0 );
	const available = ( collections.data || [] ).filter(
		( item ) => item.public !== false
	);
	const collection = available.some(
		( item ) => item.slug === selectedCollection
	)
		? selectedCollection
		: available[ 0 ]?.slug;
	const icons = useResource(
		collection
			? `/wp/v2/icons?collection=${ encodeURIComponent( collection ) }`
			: null,
		retry
	);
	const filtered = filterIcons( icons.data || [], search );
	const pages = Math.max( 1, Math.ceil( filtered.length / PAGE_SIZE ) );
	const currentPage = Math.min( page, pages - 1 );
	const error = collections.error || icons.error;
	return (
		<Modal
			title={ __( 'Icon library', 'od-pagination-icons' ) }
			onRequestClose={ onClose }
			className="odpi-library"
		>
			{ error && (
				<Notice status="error" isDismissible={ false }>
					{ __(
						'Could not load icons. Check your connection and permissions.',
						'od-pagination-icons'
					) }
					<Button
						variant="secondary"
						onClick={ () => setRetry( retry + 1 ) }
					>
						{ __( 'Retry', 'od-pagination-icons' ) }
					</Button>
				</Notice>
			) }
			<SelectControl
				label={ __( 'Collection', 'od-pagination-icons' ) }
				value={ collection || '' }
				options={ available.map( ( item ) => ( {
					label: item.label,
					value: item.slug,
				} ) ) }
				onChange={ ( slug ) => {
					setCollection( slug );
					setPage( 0 );
				} }
			/>
			<SearchControl
				label={ __( 'Search icons', 'od-pagination-icons' ) }
				value={ search }
				onChange={ ( text ) => {
					setSearch( text );
					setPage( 0 );
				} }
			/>
			<div aria-live="polite" role="status">
				{ ( collections.loading || icons.loading ) && <Spinner /> }
				{ ! error &&
					! collections.loading &&
					! icons.loading &&
					sprintf(
						/* translators: %d: number of matching icons. */
						__( '%d icons found', 'od-pagination-icons' ),
						filtered.length
					) }
			</div>
			<div className="odpi-icon-grid">
				{ filtered
					.slice(
						currentPage * PAGE_SIZE,
						( currentPage + 1 ) * PAGE_SIZE
					)
					.map( ( icon ) => (
						<Button
							key={ icon.name }
							label={ icon.name }
							aria-pressed={ value === icon.name }
							variant={
								value === icon.name ? 'primary' : 'secondary'
							}
							onClick={ () => {
								onChange( icon.name );
								onClose();
							} }
						>
							<span
								className="odpi-picker-icon"
								aria-hidden="true"
							>
								<RawHTML>{ icon.content }</RawHTML>
							</span>
							<span>{ icon.label || icon.name }</span>
						</Button>
					) ) }
			</div>
			<div className="odpi-pages">
				<Button
					variant="secondary"
					disabled={ currentPage === 0 }
					onClick={ () => setPage( currentPage - 1 ) }
				>
					{ __( 'Previous results', 'od-pagination-icons' ) }
				</Button>
				<span>
					{ sprintf(
						/* translators: 1: current page, 2: total pages. */
						__( '%1$d / %2$d', 'od-pagination-icons' ),
						currentPage + 1,
						pages
					) }
				</span>
				<Button
					variant="secondary"
					disabled={ currentPage + 1 >= pages }
					onClick={ () => setPage( currentPage + 1 ) }
				>
					{ __( 'Next results', 'od-pagination-icons' ) }
				</Button>
			</div>
		</Modal>
	);
}

export default function IconPicker( { label, value, onChange } ) {
	const [ open, setOpen ] = useState( false );
	const icon = useResource( iconPath( value ) );
	return (
		<div className="odpi-picker">
			<p>{ label }</p>
			{ icon.data && (
				<span className="odpi-picker-icon" aria-hidden="true">
					<RawHTML>{ icon.data.content }</RawHTML>
				</span>
			) }
			{ value && <p className="odpi-icon-name">{ value }</p> }
			{ value && ! icon.loading && ! icon.data && (
				<Notice status="warning" isDismissible={ false }>
					{ __(
						'This icon is unavailable. The standard arrow will be used; the saved selection is retained.',
						'od-pagination-icons'
					) }
				</Notice>
			) }
			<Button variant="secondary" onClick={ () => setOpen( true ) }>
				{ __( 'Choose icon', 'od-pagination-icons' ) }
			</Button>
			{ value && (
				<Button variant="tertiary" onClick={ () => onChange( '' ) }>
					{ __( 'Clear selection', 'od-pagination-icons' ) }
				</Button>
			) }
			{ open && (
				<IconLibrary
					value={ value }
					onChange={ onChange }
					onClose={ () => setOpen( false ) }
				/>
			) }
		</div>
	);
}
