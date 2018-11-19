/* 
 * This file contains functions to highlight during editing
 */


/**
 * Fades the relevant object to provide feedback, in case of success.
 *
 * Used on BACK-office in the following files:
 *  - _misc_js.funcs.php
 *  - blog_widgets.js
 *  - src/evo_links.js
 * Used on FRONT-office in the following files:
 *  - _item_fields_compare.widget.php
 *
 * @param jQuery selector
 */
function evoFadeSuccess( selector )
{
	evoFadeBg( selector, [ '#ddff00', '#bbff00' ] );
}


/**
 * Fades the relevant object to provide feedback, in case of failure.
 *
 * Used on BACK-office in the following files:
 *  - _misc_js.funcs.php
 *  - src/evo_links.js
 * Used on FRONT-office in the following files:
 *  - _item_fields_compare.widget.php
 *
 * @param jQuery selector
 */
function evoFadeFailure( selector )
{
	evoFadeBg( selector, [ '#9300ff', '#ff000a', '#ff0000' ] );
}


/**
 * Fades the relevant object to provide feedback, in case of highlighting
 * e.g. for items the file manager get called for ("#fm_highlighted").
 *
 * Used on BACK-office in the following file:
 *  - _file_list.inc.php
 * Used on FRONT-office in the following files:
 *  - _item_fields_compare.widget.php
 *
 * @param jQuery selector
 */
function evoFadeHighlight( selector )
{
	evoFadeBg( selector, [ '#ffbf00', '#ffe79f' ] );
}


/**
 * Fade jQuery selector via backgrounds colors (bgs), back to original background
 * color and then remove any styles (from animations and others)
 *
 * Used on BACK-office in the following files:
 *  - _misc_js.funcs.php
 *  - blog_widgets.js
 *  - src/evo_links.js
 *  - _file_list.inc.php
 * Used on FRONT-office in the following files:
 *  - _item_fields_compare.widget.php
 *
 * @param string|jQuery
 * @param Array
 * @param object Options ("speed")
 */
function evoFadeBg( selector, bgs, options )
{
	var conf = jQuery.extend( {
		speed: '"slow"',
		finish_orig_bg: true,
	}, options );

	var orig_bg = jQuery( selector ).data( 'orig-fade-bg' );
	if( typeof( orig_bg ) == 'undefined' )
	{	// Set original bg color on first calling:
		orig_bg = jQuery( selector ).css( 'backgroundColor' );
		jQuery( selector ).data( 'orig-fade-bg', orig_bg );
	}

	var animation_code = 'jQuery(selector)';
	if( typeof( bgs ) != 'undefined' )
	{
		for( e in bgs )
		{
			if( typeof( bgs[e] ) != 'string' )
			{	// Skip wrong color value:
				continue;
			}
			animation_code += '.animate({ backgroundColor: "'+bgs[e]+'"'+'}, '+conf.speed+' )';
		}
	}
	if( conf.finish_orig_bg )
	{	// Finish animation with original background color:
		animation_code += '.animate({ backgroundColor: orig_bg }, '+conf.speed+', "", function(){jQuery( this ).css( "backgroundColor", "" )})';
	}

	// Run animation:
	eval( animation_code );
}