const path = require( 'path' );
const { expect, test } = require( '@playwright/test' );

const root = path.resolve( __dirname, '../../..' );

// These small DOM fixtures exercise the field event ordering relied on by the
// Customizer block form without requiring a particular widget configuration.
test.beforeEach( async ( { page } ) => {
	await page.goto( '/' );
	await page.setContent( `
		<div class="customize-control-sidebar_block_editor">
			<form>
				<div class="siteorigin-widget-field-type-multi-measurement">
					<div class="sow-multi-measurement-input-container">
						<input class="sow-multi-measurement-input">
						<select class="sow-multi-measurement-select-unit"><option>px</option><option>em</option></select>
					</div>
					<input type="hidden" class="sow-multi-measurement-input-values" data-separator=" " data-autofill="false">
				</div>
				<div class="siteorigin-widget-field-type-code"><textarea class="siteorigin-widget-code-input"></textarea></div>
			</form>
		</div>
	` );
	await page.addScriptTag( { url: '/wp-includes/js/jquery/jquery.js' } );
	await page.addScriptTag( { content: 'var pagenow = "customize";' } );
	await page.addScriptTag( { path: path.join( root, 'base/inc/fields/js/multi-measurement-field.js' ) } );
	await page.addScriptTag( { path: path.join( root, 'base/inc/fields/js/code-field.js' ) } );
	await page.evaluate( () => {
		// Behave's existing cursor helper expects a trigger method on the DOM
		// textarea. Supply it here so these assertions cover event timing.
		document.querySelector( 'textarea' ).trigger = () => {};
		window.jQuery( '.siteorigin-widget-field-type-multi-measurement, .siteorigin-widget-field-type-code' )
			.trigger( 'sowsetupformfield' );
	} );
} );

test( 'measurement serialization is current when input reaches the form', async ( { page } ) => {
	const observed = await page.evaluate( () => {
		const values = [];
		const form = document.querySelector( 'form' );
		const hidden = form.querySelector( '.sow-multi-measurement-input-values' );
		const input = form.querySelector( '.sow-multi-measurement-input' );
		form.addEventListener( 'input', () => values.push( hidden.value ) );
		input.value = '12.5';
		input.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		input.value = '';
		input.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		return values;
	} );
	expect( observed ).toEqual( [ '12.5px', '' ] );
} );

test( 'Behave programmatic edits emit input with the updated code', async ( { page } ) => {
	const observed = await page.evaluate( () => {
		const values = [];
		const form = document.querySelector( 'form' );
		const textarea = form.querySelector( 'textarea' );
		form.addEventListener( 'input', () => values.push( textarea.value ) );
		textarea.dispatchEvent( new KeyboardEvent( 'keypress', { which: 40, keyCode: 40, bubbles: true, cancelable: true } ) );
		textarea.dispatchEvent( new KeyboardEvent( 'keydown', { keyCode: 8, bubbles: true, cancelable: true } ) );
		textarea.dispatchEvent( new KeyboardEvent( 'keydown', { keyCode: 9, bubbles: true, cancelable: true } ) );
		return values;
	} );
	expect( observed ).toEqual( [ '()', '', '    ' ] );
} );
