const wordPressVersion = require( 'siteorigin-tests-common/playground/wordPressVersion' );

/**
 * The block themes the specs preview through core's `?wp_theme_preview=`.
 * Each ships with WordPress, so the Playground has it. `font` is a font file
 * the theme serves, and `fontDescriptors` are the @font-face descriptors that
 * make text set in it take more room than the browser's fallback font.
 */
const TWENTY_TWENTY_FIVE = {
	name: 'Twenty Twenty-Five',
	preview: 'twentytwentyfive',
	font: '/wp-content/themes/twentytwentyfive/assets/fonts/fira-code/FiraCode-VariableFont_wght.woff2',
	// Fira Code is monospaced, so it is wider than the fallback as it is.
	fontDescriptors: '',
};

const TWENTY_TWENTY_FOUR = {
	name: 'Twenty Twenty-Four',
	preview: 'twentytwentyfour',
	font: '/wp-content/themes/twentytwentyfour/assets/fonts/inter/Inter-VariableFont_slnt,wght.woff2',
	// Inter is about as wide as the fallback, so it is drawn larger.
	fontDescriptors: 'size-adjust: 170%;',
};

/**
 * Chooses the block theme to preview on a WordPress version. Twenty
 * Twenty-Five first shipped with WordPress 6.7, so an older version previews
 * Twenty Twenty-Four.
 *
 * @param {string} [version] A WordPress version such as 6.6 or 6.6.2, or empty
 *                           for the latest. Defaults to `SO_TESTS_WP_VERSION`.
 *
 * @return {Object} `{ name, preview, font, fontDescriptors }`.
 */
const previewTheme = ( version = wordPressVersion() ) => {
	if ( version === '' ) {
		return TWENTY_TWENTY_FIVE;
	}

	const [ major, minor ] = version.split( '.' ).map( Number );

	return major > 6 || ( major === 6 && minor >= 7 ) ? TWENTY_TWENTY_FIVE : TWENTY_TWENTY_FOUR;
};

module.exports = previewTheme;
