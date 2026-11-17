import { iconSize } from '../../src/editor/size';

describe( 'アイコンサイズの保存値', () => {
	it.each( [ '24px', '1.5em', '.75rem' ] )( '%s を受け付ける', ( size ) => {
		expect( iconSize( size ) ).toBe( size );
	} );
	it.each( [
		'',
		undefined,
		24,
		'0px',
		'-1em',
		'2vw',
		'24%',
		'24px;display:none',
		'2rem\n',
	] )( '無効な値 %s は文字サイズ連動へ戻す', ( size ) => {
		expect( iconSize( size ) ).toBe( '' );
	} );
} );
