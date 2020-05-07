/**
 * This file has generic functions
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois PLANQUE - {@link http://fplanque.com/}
 */


 /**
 * Fade jQuery selector via backgrounds colors (bgs), back to original background
 * color and then remove any styles (from animations and others)
 *
 * Already declared in src/backoffice.js
 *
 * @param string|jQuery
 * @param Array
 * @param object Options ("speed")
 */
if( typeof evoFadeBg === 'undefined' )
{
	evoFadeBg = function evoFadeBg( selector, bgs, options )
		{
			var origBg = jQuery(selector).css("backgroundColor");
			var speed = options && options.speed || '"slow"';

			var toEval = 'jQuery(selector).animate({ backgroundColor: ';
			for( e in bgs )
			{
				if( typeof( bgs[e] ) != 'string' )
				{ // Skip wrong color value
					continue;
				}
				toEval += '"'+bgs[e]+'"'+'}, '+speed+' ).animate({ backgroundColor: ';
			}
			toEval += 'origBg }, '+speed+', "", function(){jQuery( this ).css( "backgroundColor", "" );});';

			eval(toEval);
		};
}


 /**
 * Fades the relevant object to provide feedback, in case of success.
 *
 * Already declared in src/backoffice.js
 *
 * @param jQuery selector
 */
if( typeof evoFadeSuccess === 'undefined' )
{
	evoFadeSuccess = function evoFadeSuccess( selector )
		{
			evoFadeBg(selector, new Array("#ddff00", "#bbff00"));
		};
}


/**
 * Prevent submit a form by Enter Key, e.g. when user is editing the owner fields
 *
 * @param string jQuery selector
 */
function evo_prevent_key_enter( selector )
{
	jQuery( selector ).keypress( function( e )
	{
		if( e.keyCode == 13 )
		{
			return false;
		}
	} );
}


/**
 * Open link attachment modal window
 * @param string link_owner_type 
 * @param integer link_owner_ID 
 * @param string root 
 * @param string path 
 * @param string fm_highlight 
 * @param string prefix 
 */
function link_attachment_window( link_owner_type, link_owner_ID, root, path, fm_highlight, prefix )
{
	openModalWindow( '<span class="loader_img loader_user_report absolute_center" title="' + evo_link_attachment_window_config.loader_title + '"></span>',
		'90%', '80%', true, evo_link_attachment_window_config.window_title, '', true );

	var data = {
			'action': 'link_attachment',
			'link_owner_type': link_owner_type,
			'link_owner_ID': link_owner_ID,
			'crumb_link': evo_link_attachment_window_config.crumb_link,
			'root': typeof( root ) == 'undefined' ? '' : root,
			'path': typeof( path ) == 'undefined' ? '' : path,
			'fm_highlight': typeof( fm_highlight ) == 'undefined' ? '' : fm_highlight,
			'prefix': typeof( prefix ) == 'undefined' ? '' : prefix,
		};

	jQuery.ajax(
		{
			type: 'POST',
			url: htsrv_url + 'async.php',
			data: data,
			success: function(result)
			{
				openModalWindow( result, '90%', '80%', true, evo_link_attachment_window_config.window_title, '' );
			}
		} );
	return false;
};
