/**
 * This file implements forms specific Javascript functions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2019 by Francois PLANQUE - {@link http://fplanque.com/}
 *
 * @package admin
 */


jQuery( document ).on( 'keydown', 'textarea, input', function ( e )
{
	if( ( e.metaKey || e.ctrlKey ) && ( e.keyCode == 13 || e.keyCode == 10 ) )
	{	// Submit form on press Command+Enter or Ctrl+Enter inside <textarea> or <input>:
		jQuery( this ).closest( "form" ).submit();
	}
} );

// Check/Uncheck/Reverse all checkboxes by input name:
jQuery( document ).on( 'click', 'button[data-checkbox-control]', function()
{
	var checked_state = true;
	switch( jQuery( this ).data( 'checkbox-control-type' ) )
	{
		case 'uncheck':
			checked_state = false;
			break;
		case 'reverse':
			checked_state = function( i, val ) { return ! val };
	}
	jQuery( 'input[name="' + jQuery( this ).data( 'checkbox-control' ) + '"]' ).prop( 'checked', checked_state );
} );