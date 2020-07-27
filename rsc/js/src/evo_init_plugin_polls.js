/**
 * This file initialize plugin "Polls"
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
	window.evo_init_polls_toolbar = function( config )
		{
			window.polls_toolbar = function polls_toolbar( title, prefix )
				{
					var r = config['toolbar_title_before'] + title + config['toolbar_title_after']
							+ config['toolbar_group_before']
							+ '<input type="button" title="' + config['button_title'] + '"'
							+ ' class="' + config['button_class'] + '"'
							+ ' data-func="polls_load_window|' + prefix + '" value="' + config['button_value'] + '" />'
							+ config['toolbar_group_after'];

						jQuery( '.' + prefix + config['plugin_code'] + '_toolbar' ).html( r );
				};

			window.polls_load_window = function polls_load_window( prefix )
				{
					openModalWindow( '<div id="poll_wrapper"></div>', 'auto', '', true,
							config['modal_window_title'],
							[ 'Insert Poll' ],
							true );

					// Load available polls
					polls_load_polls( prefix );

					// To prevent link default event
					return false;
				};

			window.polls_api_request = function polls_api_request( api_path, obj_selector, func )
				{
					jQuery.ajax( {
							url: restapi_url + api_path
						} )
						.then( func, function( jqXHR )
						{
							polls_api_print_error( obj_selector, jqXHR );
						} );
				};

			window.polls_api_print_error = function polls_api_print_error( obj_selector, error )
				{
					if( typeof( error ) != 'string' && typeof( error.code ) == 'undefined' )
					{
						error = typeof( error.responseJSON ) == 'undefined' ? error.statusText : error.responseJSON;
					}

					if( typeof( error.code ) == 'undefined' )
					{	// Unknown non-JSON response
						var error_text = '<h4 class="text-danger">Unknown error: ' + error + '</h4>';
					}
					else
					{
						var error_text = '<h4 class="text-danger">' + error.message + '</h4>';
						if( config['debug'] )
						{
						
							error_text += '<div><b>Code:</b> '	+ error.code + '</div>' + '<div><b>Status:</b> ' + error.data.status + '</div>';
						}
					}

					jQuery( obj_selector ).html( error_text );
				};

			window.polls_load_polls = function polls_load_polls( prefix )
				{
					prefix = ( prefix ? prefix : '' );

					polls_api_request( 'polls', '#poll_wrapper', function( data )
					{
						var r = '<div id="' + prefix + 'polls_list">';

						r += '<ul>';
						for( var p in data.polls )
						{
							var poll = data.polls[p];
							r += '<li><a href="#" data-poll-id="' + poll.pqst_ID + '" data-prefix="' + prefix + '">' + poll.pqst_question_text + '</a></li>';
						}
						r += '</ul>';
						r += '</div>';

						jQuery( '#poll_wrapper' ).html( r );

						// Insert a poll short tag to textarea
						jQuery( document ).on( 'click', '#' + prefix + 'polls_list a[data-poll-id]', function()
							{
								if( typeof( tinyMCE ) != 'undefined' && typeof( tinyMCE.activeEditor ) != 'undefined' && tinyMCE.activeEditor )
								{
									tinyMCE.execCommand( 'mceFocus', false, tinyMCE.activeEditor.id );
								}

								var prefix = jQuery( this ).data( 'prefix' ) ? jQuery( this ).data( 'prefix' ) : '';

								// Insert tag text in area
								textarea_wrap_selection( window[ prefix + 'b2evoCanvas' ], '[poll:' + jQuery( this ).data( 'pollId' ) + ']', '', 0 );
								// Close main modal window
								closeModalWindow();

								// To prevent link default event
								return false;
							} );

					} );
				};

			window.polls_toolbar( config.toolbar_title, config.prefix );
		};

	if( typeof( evo_init_polls_toolbar_config ) != 'undefined' )
	{
		// Initialize each Polls Toolbar instance:
		var evo_temp_config = Object.values( evo_init_polls_toolbar_config );
		for( var i = 0; i < evo_temp_config.length; i++ )
		{
			( function() {
				window.evo_init_polls_toolbar( evo_temp_config[i] );
			} )();
		}
		delete evo_temp_config;
	}

} );
