/**
 * This file initialize Widget "Search Form"
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois PLANQUE - {@link http://fplanque.com/}
 */

jQuery( document ).ready( function()
{
	jQuery( evo_widget_coll_search_form.selector ).tokenInput( evo_widget_coll_search_form.url, evo_widget_coll_search_form.config );

	if( typeof( evo_widget_coll_search_form.placeholder ) != 'undefined' )
	{
		jQuery( '#token-input-search_author' ).attr( 'placeholder', evo_widget_coll_search_form.placeholder ).css( 'width', '100%' );
	}
} );