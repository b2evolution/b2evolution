/**
 * This file initialize the Widget "Item List Sort order".
 * 
 * This will restrict the number of max answers per user
 * and fix wrapping for answers that have are too long.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois PLANQUE - {@link http://fplanque.com/}
 * 
 * 
 * Depends on: jQuery
 */
jQuery( document ).ready( function()
{
	jQuery( 'select[data-item-list-sort-order-widget]' ).change( function()
	{
		var selected_option = jQuery( this ).find( 'option:selected' );
		location.href = jQuery( this ).data( 'url' )
			.replace( '$orderby$', selected_option.data( 'order' ) )
			.replace( '$orderdir$', selected_option.data( 'order-dir' ) );
	} );
} );
