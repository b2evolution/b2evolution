/**
 * This file initialize the Widget "Poll".
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
	if( ( typeof( evo_widget_poll_initialize ) == 'boolean' ) && evo_widget_poll_initialize )
	{
		jQuery( '.evo_poll__selector input[type="checkbox"]' ).on( 'click', function()
			{	// Check max possible answers per user for multiple poll:
				var poll_table = jQuery( this ).closest( '.evo_poll__table' );
				var is_disabled = ( jQuery( '.evo_poll__selector input:checked', poll_table ).length >= poll_table.data( 'max-answers' ) );
				jQuery( '.evo_poll__selector input[type=checkbox]:not(:checked)', poll_table ).prop( 'disabled', is_disabled );
			} );

		jQuery( '.evo_poll__table' ).each( function()
			{	// Fix answer long text width because of labels uses css "white-space:nowrap" by default:
				var table = jQuery( this );
				if( table.width() > table.parent().width() )
				{	// If table width more than parent:
					jQuery( '.evo_poll__title', table ).css( 'white-space', 'normal' );
					jQuery( '.evo_poll__title label', table ).css( {
						'width': Math.floor( table.parent().width() / 2 ) + 'px', // Use 50% of table width for long answers
						'word-wrap': 'break-word' // Wrap long words
					} );
				}
			} );
	}
} );