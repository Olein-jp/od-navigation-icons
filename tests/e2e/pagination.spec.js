const { test, expect } = require( '@playwright/test' );
const { execFileSync } = require( 'node:child_process' );
const fixture = require( './fixture.json' );

function cli( ...args ) {
	return execFileSync( 'node_modules/.bin/wp-env', [ 'run', 'cli', 'wp', ...args ], { encoding: 'utf8' } );
}

async function login( page ) {
	await page.goto( '/wp-login.php' );
	await page.locator( '#user_login' ).fill( 'admin' );
	await page.locator( '#user_pass' ).fill( 'password' );
	await page.locator( '#wp-submit' ).click();
	await page.waitForURL( /wp-admin/ );
}

async function selectPagination( page ) {
	await page.evaluate( () => {
		const editor = wp.data.select( 'core/block-editor' );
		const find = ( blocks ) => blocks.flatMap( ( block ) => [ block, ...find( block.innerBlocks || [] ) ] );
		const parent = find( editor.getBlocks() ).find( ( block ) => block.name === 'core/query-pagination' );
		wp.data.dispatch( 'core/block-editor' ).selectBlock( parent.clientId );
		wp.data.dispatch( 'core/interface' ).enableComplementaryArea( 'core', 'edit-post/block' );
	} );
}

test( 'アイコンの選択・キーボード操作・保存とエディタープレビュー', async ( { page } ) => {
	const errors = [];
	page.on( 'pageerror', ( error ) => errors.push( error.message ) );
	await login( page );
	await page.goto( `/wp-admin/post.php?post=${ fixture.page }&action=edit` );
	await expect.poll( () => page.evaluate( () => !! window.wp?.data?.select( 'core/block-editor' )?.getBlocks()?.length ) ).toBe( true );
	await page.evaluate( () => wp.data.dispatch( 'core/preferences' ).set( 'core/edit-post', 'welcomeGuide', false ) );
	await selectPagination( page );
	const toggle = page.getByRole( 'checkbox', { name: /Use custom icons|カスタムアイコンを使用/ } );
	await expect( toggle ).toBeChecked();
	await page.getByRole( 'button', { name: /Choose icon|アイコンを選択/ } ).last().click();
	const modal = page.getByRole( 'dialog' );
	await expect( modal ).toBeVisible();
	await modal.getByRole( 'tab', { name: 'ODPI E2E Collection', exact: true } ).click();
	await expect( modal.locator( '.odpi-icon-grid button' ) ).toHaveCount( 48 );
	const search = modal.getByRole( 'searchbox' );
	await search.fill( 'test-1199' );
	await expect( modal.locator( '.odpi-icon-grid button' ) ).toHaveCount( 1 );
	const chosen = modal.getByRole( 'button', { name: 'odpi-e2e/test-1199', exact: true } );
	await chosen.focus();
	await page.keyboard.press( 'Enter' );
	await expect( modal ).not.toBeVisible();
	await expect.poll( () => page.evaluate( () => {
		const frame = document.querySelector( 'iframe[name="editor-canvas"]' );
		const doc = frame?.contentDocument || document;
		return doc.querySelector( '.odpi-preview.wp-block-query-pagination-next' )?.style.getPropertyValue( '--odpi-preview' ) || '';
	} ) ).toContain( 'data:image/svg+xml' );
	await page.evaluate( async () => { await wp.data.dispatch( 'core/editor' ).savePost(); } );
	await page.reload();
	await expect.poll( () => page.evaluate( () => {
		const find = ( blocks ) => blocks.flatMap( ( block ) => [ block, ...find( block.innerBlocks || [] ) ] );
		return find( wp.data.select( 'core/block-editor' ).getBlocks() ).find( ( block ) => block.name === 'core/query-pagination' )?.attributes.odpiNextIcon;
	} ) ).toBe( 'odpi-e2e/test-1199' );
	await selectPagination( page );
	await toggle.uncheck();
	await expect( page.getByRole( 'button', { name: /Choose icon|アイコンを選択/ } ) ).toHaveCount( 0 );
	await expect.poll( () => page.evaluate( () => {
		const find = ( blocks ) => blocks.flatMap( ( block ) => [ block, ...find( block.innerBlocks || [] ) ] );
		return find( wp.data.select( 'core/block-editor' ).getBlocks() ).find( ( block ) => block.name === 'core/query-pagination' )?.attributes.paginationArrow;
	} ) ).toBe( 'chevron' );
	await toggle.check();
	await page.evaluate( async () => { await wp.data.dispatch( 'core/editor' ).savePost(); } );
	expect( errors ).toEqual( [] );
} );

test( 'フロントのSVGとEnhanced Paginationの遷移', async ( { page } ) => {
	await page.goto( `/?page_id=${ fixture.page }&query-9821-page=2` );
	const previous = page.locator( 'a.wp-block-query-pagination-previous' );
	const next = page.locator( 'a.wp-block-query-pagination-next' );
	await expect( previous.locator( 'svg.odpi-icon' ) ).toHaveCount( 1 );
	await expect( next.locator( 'svg.odpi-icon' ) ).toHaveCount( 1 );
	await expect( next ).toHaveAttribute( 'data-wp-on--click', 'core/query::actions.navigate' );
	await next.click();
	await expect( page ).toHaveURL( /query-9821-page=3/ );
	await expect( previous ).toHaveAttribute( 'href', /query-9821-page=2/ );
} );

test( '外部コレクションの停止と再登録、プラグイン停止時のリンク', async ( { page } ) => {
	try {
		cli( 'option', 'update', 'odpi_e2e_icons_disabled', '1' );
		await page.goto( `/?page_id=${ fixture.page }&query-9821-page=2` );
		await expect( page.locator( '.wp-block-query-pagination-next svg' ) ).toHaveCount( 0 );
		await expect( page.locator( '.wp-block-query-pagination-next-arrow' ) ).toHaveText( '→' );
		cli( 'option', 'delete', 'odpi_e2e_icons_disabled' );
		await page.reload();
		await expect( page.locator( '.wp-block-query-pagination-next svg' ) ).toHaveCount( 1 );
		cli( 'plugin', 'deactivate', 'od-pagination-icons' );
		await page.reload();
		await expect( page.locator( '.wp-block-query-pagination-next svg' ) ).toHaveCount( 0 );
		await page.locator( 'a.wp-block-query-pagination-next' ).click();
		await expect( page ).toHaveURL( /query-9821-page=3/ );
	} finally {
		cli( 'option', 'delete', 'odpi_e2e_icons_disabled' );
		cli( 'plugin', 'activate', 'od-pagination-icons' );
	}
} );

test( 'サイトエディターでテンプレートを保存・再編集できる', async ( { page } ) => {
	await login( page );
	await page.goto( `/wp-admin/site-editor.php?postId=${ encodeURIComponent( fixture.templateId ) }&postType=wp_template&canvas=edit` );
	await expect.poll( () => page.evaluate( () => !! window.wp?.data?.select( 'core/block-editor' )?.getBlocks()?.length ), { timeout: 15000 } ).toBe( true );
	await page.evaluate( () => wp.data.dispatch( 'core/preferences' ).set( 'core/edit-site', 'welcomeGuide', false ) );
	await selectPagination( page );
	const toggle = page.getByRole( 'checkbox', { name: /Use custom icons|カスタムアイコンを使用/ } );
	await expect( toggle ).toBeChecked();
	await page.evaluate( () => {
		const editor = wp.data.select( 'core/block-editor' );
		wp.data.dispatch( 'core/block-editor' ).updateBlockAttributes( editor.getSelectedBlockClientId(), { odpiNextIcon: 'core/plus', showLabel: false } );
	} );
	await expect.poll( () => page.evaluate( () => {
		const frame = document.querySelector( 'iframe[name="editor-canvas"]' );
		return ( frame?.contentDocument || document ).querySelectorAll( '.odpi-preview' ).length;
	} ) ).toBe( 2 );
	await page.evaluate( async () => { await wp.data.dispatch( 'core/editor' ).savePost(); } );
	await page.reload();
	await expect.poll( () => page.evaluate( () => {
		const find = ( blocks ) => blocks.flatMap( ( block ) => [ block, ...find( block.innerBlocks || [] ) ] );
		return find( wp.data.select( 'core/block-editor' ).getBlocks() ).find( ( block ) => block.name === 'core/query-pagination' )?.attributes.odpiNextIcon;
	} ), { timeout: 15000 } ).toBe( 'core/plus' );
	await page.screenshot( { path: 'test-results/site-editor.png', fullPage: true } );
} );

test( 'アイコンのみのリンクのアクセシブルネームと余白', async ( { page } ) => {
	// 投稿本文はCLI出力から復元せず、PHP内で一時変更・復元する。
	cli( 'eval', `$p = get_post(${ fixture.page }); update_option('odpi_e2e_original_content', $p->post_content); wp_update_post(array('ID' => $p->ID, 'post_content' => str_replace('"odpiEnabled":true', '"showLabel":false,"odpiEnabled":true', $p->post_content)));` );
	try {
		await page.goto( `/?page_id=${ fixture.page }&query-9821-page=2` );
		const next = page.locator( 'a.wp-block-query-pagination-next' );
		await expect( next ).toHaveAttribute( 'aria-label', /.+/ );
		await expect( next.locator( '.odpi-icon' ) ).toHaveAttribute( 'aria-hidden', 'true' );
		await expect( next ).toHaveText( '' );
		expect( await next.locator( 'span' ).evaluate( ( node ) => getComputedStyle( node ).marginInlineStart ) ).toBe( '0px' );
	} finally {
		cli( 'eval', `wp_update_post(array('ID' => ${ fixture.page }, 'post_content' => get_option('odpi_e2e_original_content'))); delete_option('odpi_e2e_original_content');` );
	}
} );

test( '日本語と英語のUIを表示できる', async ( { page } ) => {
	cli( 'eval', "update_option('odpi_e2e_original_locale', get_user_meta(1, 'locale', true));" );
	try {
		await login( page );
		for ( const [ locale, label ] of [ [ 'ja', 'カスタムアイコンを使用' ], [ 'en_US', 'Use custom icons' ] ] ) {
			cli( 'user', 'meta', 'update', '1', 'locale', locale );
			await page.goto( `/wp-admin/post.php?post=${ fixture.page }&action=edit` );
			await expect.poll( () => page.evaluate( () => !! window.wp?.data?.select( 'core/block-editor' )?.getBlocks()?.length ) ).toBe( true );
			await page.evaluate( () => wp.data.dispatch( 'core/preferences' ).set( 'core/edit-post', 'welcomeGuide', false ) );
			await selectPagination( page );
			await expect( page.getByRole( 'checkbox', { name: label, exact: true } ) ).toBeChecked();
			await page.screenshot( { path: `test-results/editor-${ locale }.png`, fullPage: true } );
		}
	} finally {
		cli( 'eval', "update_user_meta(1, 'locale', get_option('odpi_e2e_original_locale', '')); delete_option('odpi_e2e_original_locale');" );
	}
} );
