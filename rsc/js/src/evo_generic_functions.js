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
 * Render comment ratings to star buttons
 */
function evo_render_star_rating()
{
	jQuery( '#comment_rating' ).each( function( index ) {
		var raty_params = jQuery( 'span.raty_params', this );
		if( raty_params )
		{
			jQuery( this ).html( '' ).raty( raty_params );
		}
	} );
}
