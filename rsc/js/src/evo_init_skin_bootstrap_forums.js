/**
 * This file initialize the JS for Bootstrap Forums skin
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois PLANQUE - {@link http://fplanque.com/}
 * 
 * Depends on jQuery
 */
jQuery( document ).ready( function()
{
	if( typeof( evo_skin_bootstrap_forums__post_list_header ) != 'undefined' )
	{
		jQuery( "#evo_workflow_status_filter" ).change( function()
			{
				var url = location.href.replace( /([\?&])((status|redir)=[^&]*(&|$))+/, "$1" );
				var status_ID = jQuery( this ).val();
				if( status_ID !== "" )
				{
					url += ( url.indexOf( "?" ) == -1 ? "?" : "&" ) + "status=" + status_ID + "&redir=no";
				}
				location.href = url.replace( "?&", "?" ).replace( /\?$/, "" );
			} );
	}
} );