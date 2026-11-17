import apiFetch from '@wordpress/api-fetch';
import { useEffect, useState } from '@wordpress/element';

// 成功したRESTレスポンスと進行中リクエストだけを共有する。
const requests = new Map();
export function useResource( path, retry = 0 ) {
	const [ state, setState ] = useState( { path, loading: !! path } );
	useEffect( () => {
		let active = true;
		if ( ! path ) {
			setState( { path, loading: false } );
			return;
		}
		setState( { path, loading: true } );
		if ( ! requests.has( path ) ) {
			requests.set(
				path,
				apiFetch( { path } ).catch( ( error ) => {
					requests.delete( path );
					throw error;
				} )
			);
		}
		requests.get( path ).then(
			( data ) => active && setState( { path, data, loading: false } ),
			( error ) => active && setState( { path, error, loading: false } )
		);
		return () => {
			active = false;
		};
	}, [ path, retry ] );
	return state.path === path ? state : { loading: !! path };
}

export function iconPath( name ) {
	return /^[a-z0-9](?:[a-z0-9_-]*[a-z0-9])?\/[a-z0-9](?:[a-z0-9_-]*[a-z0-9])?$/.test(
		name
	)
		? `/wp/v2/icons/${ name }`
		: null;
}

export function filterIcons( icons, search ) {
	const needle = search.trim().toLocaleLowerCase();
	return icons.filter( ( icon ) =>
		`${ icon.name } ${ icon.label }`.toLocaleLowerCase().includes( needle )
	);
}

// HTML内のSVGと同じ属性名補正を行い、CSS画像としてもviewBoxを有効にする。
export function previewSvg( content ) {
	const svg = new window.DOMParser()
		.parseFromString( content, 'text/html' )
		.querySelector( 'svg' );
	if ( ! svg ) {
		return '';
	}
	svg.setAttribute( 'width', '24' );
	svg.setAttribute( 'height', '24' );
	return new window.XMLSerializer().serializeToString( svg );
}
