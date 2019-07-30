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