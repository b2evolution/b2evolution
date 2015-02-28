/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * Used only to initialize textcomplete jquery plugin for the textareas with class "autocomplete_usernames"
 * Don't load this file directly, It is appended to "/build/textcomplete.bmin.js" by Grunt.
 */
jQuery( document ).ready( function()
{
	var IE_version = false;
	if( typeof( navigator ) !== "undefined" && typeof( navigator.appVersion ) !== "undefined" )
	{
		var app_match = navigator.appVersion.match( /msie (\d+)/i );
		if( app_match )
		{
			IE_version = parseInt( app_match[1] );
		}
	}
	if( IE_version && IE_version <= 9 )
	{ // Dont allow this plugin on IE <= 9
		return;
	}

	var mentions = [];
	jQuery( ".user.login" ).each( function()
	{ // Init array with all usernames from the page
		var login = jQuery( this ).text();
		if( login != "" && mentions.indexOf( login ) == -1 )
		{
			if( login[0] == "@" )
			{
				login = login.substr( 1 );
			}
			mentions.push( login );
		}
	} );

	jQuery( "textarea.autocomplete_usernames" ).textcomplete(
	[ {
		match: /\B@(\w+)$/,
		search: function ( term, callback )
		{
			if( term.length < 4 )
			{ // Search only on the page
				callback( jQuery.map( mentions, function ( mention )
				{ return mention.indexOf(term) === 0 ? mention : null; } ) );
			}
			else
			{ // Also search in DB by AJAX
				jQuery.ajax(
				{
					type: "POST",
					dataType: "JSON",
					url: htsrv_url + "anon_async.php",
					data: "action=autocomplete_usernames&q=" + term,
					success: function( db_mentions )
					{
						db_mentions = db_mentions.concat( mentions );
						callback( jQuery.map( db_mentions, function ( mention )
						{ return mention.indexOf(term) === 0 ? mention : null; } ) );
					}
				} );
			}
		},
		index: 1,
		replace: function ( mention ) { return "@" + mention + " "; },
		cache: true
	} ] );
} );