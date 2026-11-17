import { filterIcons, iconPath, previewSvg } from '../../src/editor/icons';

describe( '公開アイコンの検索と識別子', () => {
	it( 'コアの小文字viewboxをCSS画像用のSVG属性へ正規化する', () => {
		const svg = previewSvg(
			'<svg viewbox="0 0 48 48"><path d="M1 1h46v46z" /></svg>'
		);
		expect( svg ).toContain( 'viewBox="0 0 48 48"' );
		expect( svg ).toContain( 'width="24"' );
		expect( previewSvg( '' ) ).toBe( '' );
	} );
	it( 'コレクション/アイコン形式だけをRESTへ渡す', () => {
		expect( iconPath( 'my-theme/arrow_left' ) ).toBe(
			'/wp/v2/icons/my-theme/arrow_left'
		);
		for ( const invalid of [
			'',
			'../core/plus',
			'core/plus?x=1',
			'core/<svg>',
			'core/UPPER',
		] ) {
			expect( iconPath( invalid ) ).toBeNull();
		}
	} );
	it( '大量のアイコンを名前とラベルで検索する', () => {
		const icons = Array.from( { length: 5000 }, ( _, i ) => ( {
			name: `test/icon-${ i }`,
			label: `ラベル ${ i }`,
		} ) );
		expect( filterIcons( icons, ' ICON-4999 ' ) ).toEqual( [
			icons[ 4999 ],
		] );
		expect( filterIcons( icons, 'ラベル 4999' ) ).toEqual( [
			icons[ 4999 ],
		] );
		expect( filterIcons( icons, '' ) ).toHaveLength( 5000 );
	} );
} );
