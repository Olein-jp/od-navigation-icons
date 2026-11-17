// PHPと同じ単位・正の数値だけをプレビューへ渡す。
export const iconSize = ( value ) =>
	typeof value === 'string' &&
	/^(?:[0-9]+(?:\.[0-9]+)?|\.[0-9]+)(?:px|em|rem)(?![\s\S])/.test( value ) &&
	parseFloat( value ) > 0
		? value
		: '';
