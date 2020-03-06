/**
 * This file initialize plugin "Table Contents"
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois PLANQUE - {@link http://fplanque.com/}
 * 
 * Depends on: jQuery
 */

jQuery( document ).ready( function()
{
	if( typeof( evo_plugin_tinymce_config__toggle_switch_warning ) != 'undefined' )
	{
		( function() {
			var config = evo_plugin_tinymce_config__toggle_switch_warning;

			window.toggle_switch_warning = function toggle_switch_warning( state )
				{
					var activate_link = config['activate_link'];
					var deactivate_link = config['deactivate_link'];

					jQuery.get( ( state ? activate_link : deactivate_link ),
							function( data )
							{	// Fire wysiwyg warning state change event
								jQuery( document ).trigger( 'wysiwyg_warning_changed', [ state ] );
							} );

					return false;
				}

		} )();
	}

	if( typeof( evo_plugin_tinymce_config__quicksettings ) != 'undefined' )
	{
		( function() {
			var config = evo_plugin_tinymce_config__quicksettings;

			var quicksetting_switch = jQuery( '#' + config['item_id'] );
			jQuery( document ).on( 'wysiwyg_warning_changed', function( event, state )
				{
					quicksetting_switch.html( state ? config['deactivate_warning_link'] : config['activate_warning_link'] );
				} );
		} )();
	}

	if( typeof( evo_plugin_tinymce_config__toggle_editor ) != 'undefined' )
	{
		( function() {
			var config = evo_plugin_tinymce_config__toggle_editor;

			window.displayWarning = config['display_warning'];

			window.confirm_switch = function confirm_switch()
				{
					if( jQuery( 'input[name=hideWarning]' ).is(':checked') )
					{	// Do not show warning again
						window.toggle_switch_warning( false );
					}

					// switch to WYSIWYG
					window.tinymce_plugin_toggleEditor( config['content_id'] );

					// close the modal window
					closeModalWindow();

					return false;
				}

			window.tinymce_plugin_toggleEditor = function tinymce_plugin_toggleEditor( id )
				{
					var textarea = jQuery( '#' + config['content_id'] );

					jQuery( '[id^=tinymce_plugin_toggle_button_]' ).removeClass( 'active' ).attr( 'disabled', 'disabled' );

					if( ! window.tinymce_plugin_init_done )
					{
						window.tinymce_plugin_init_done = true;
						// call this method on init again, with "null" id, so that mceAddControl gets called.
						window.tinymce_plugin_init_tinymce( function()
							{
								window.tinymce_plugin_toggleEditor( null );
							} );
						return;
					}

					if( ! window.tinymce.get( id ) )
					{	// Turn on WYSIWYG editor
						window.tinymce.execCommand( 'mceAddEditor', false, id );
						jQuery.get( config['save_editor_state_url'] );
						jQuery( '#tinymce_plugin_toggle_button_wysiwyg' ).addClass( 'active' );
						jQuery( '#tinymce_plugin_toggle_button_html' ).removeAttr( 'disabled' );
						jQuery( '[name="editor_code"]').attr( 'value', config['plugin_code'] );
						// Hide the plugin toolbars that allow to insert html tags
						jQuery( '.quicktags_toolbar, .evo_code_toolbar, .evo_prism_toolbar, .b2evMark_toolbar, .evo_mermaid_toolbar' ).hide();
						jQuery( '#block_renderer_evo_code, #block_renderer_evo_prism, #block_renderer_b2evMark, #block_renderer_evo_mermaid' ).addClass( 'disabled' );
						jQuery( 'input#renderer_evo_code, input#renderer_evo_prism, input#renderer_b2evMark, input#renderer_evo_mermaid' ).each( function()
						{
							if( jQuery( this ).is( ':checked' ) )
							{
								jQuery( this ).addClass( 'checked' );
							}
							jQuery( this ).attr( 'disabled', 'disabled' ).removeAttr( 'checked' );
						} );

						if( id && textarea.prop( 'required' ) )
						{
							textarea.attr( 'data-required', true );
							textarea.removeAttr( 'required' );
						}
					}
					else
					{ // Hide the editor, Display only source HTML
						window.tinymce.execCommand( 'mceRemoveEditor', false, id );
						jQuery.get( config['save_editor_state_url'] );
						jQuery( '#tinymce_plugin_toggle_button_html' ).addClass( 'active' );
						jQuery( '#tinymce_plugin_toggle_button_wysiwyg' ).removeAttr( 'disabled' );
						jQuery( '[name="editor_code"]' ).attr( 'value', 'html' );
						// Show the plugin toolbars that allow to insert html tags
						jQuery( '.quicktags_toolbar, .evo_code_toolbar, .evo_prism_toolbar, .b2evMark_toolbar, .evo_mermaid_toolbar' ).show();
						jQuery( '#block_renderer_evo_code, #block_renderer_evo_prism, #block_renderer_b2evMark, #block_renderer_evo_mermaid' ).removeClass( 'disabled' );
						jQuery( 'input#renderer_evo_code, input#renderer_evo_prism, input#renderer_b2evMark, input#renderer_evo_mermaid' ).each( function()
						{
							if( jQuery( this ).hasClass( 'checked' ) )
							{
								jQuery( this ).attr( 'checked', 'checked' ).removeClass( 'checked' );
							}
							jQuery( this ).removeAttr( 'disabled' );
						} );

						if( id && textarea.attr( 'data-required' ) )
						{
							textarea.removeAttr( 'data-required' );
							textarea.attr( 'required', true );
						}
					}
				}

			jQuery( document ).on( 'wysiwyg_warning_changed', function( event, state ) {
					window.displayWarning = state;
				} );

			jQuery( '[id^=tinymce_plugin_toggle_button_]').click( function()
				{
					if( jQuery( this ).val() == 'WYSIWYG' )
					{
						if( window.displayWarning )
						{
							evo_js_lang_close = config['cancel_btn_label'];
							openModalWindow( '<p>' + config['toggle_warning_msg'] + '</p>'
								+ '<form>'
								+ '<input type="checkbox" name="hideWarning" value="1"> ' + config['wysiwyg_checkbox_label']
								+ '<input type="submit" name="submit" onclick="return confirm_switch();">'
								+ '</form>',
								'500px', '', true,
								'<span class="text-danger">' + config['warning_text'] + '</span>',
								[ config['ok_btn_label'], 'btn-primary' ], true );
						}
						else
						{
							window.tinymce_plugin_toggleEditor( config['content_id'] );
						}
					}
					else
					{
						window.tinymce_plugin_toggleEditor( config['content_id'] );
					}
				} );
		} )();
	}

	if( typeof( evo_plugin_tinymce_config__init ) != 'undefined' )
	{
		( function() {
			var config = evo_plugin_tinymce_config__init;

			// Init array with all usernames from the page for autocomplete plugin
			window.autocomplete_static_options = [];
			jQuery( '.user.login' ).each( function()
				{
					var login = jQuery( this ).text();
					if( login != '' && window.autocomplete_static_options.indexOf( login ) == -1 )
					{
						if( login[0] == '@' )
						{
							login = login.substr( 1 );
						}
						window.autocomplete_static_options.push( login );
					}
				} );
			window.autocomplete_static_options = window.autocomplete_static_options.join();

			window.tmce_init = config['tmce_init'];
			window.tinymce_plugin_displayed_error = false;
			window.tinymce_plugin_init_done = false;
			// window.evo = window.evo || {};

			window['tinymce_plugin_init_tinymce'] = function tinymce_plugin_init_tinymce( oninit )
				{
					// Init tinymce:
					if( typeof window.tinymce == "undefined" )
					{
						if( ! window.tinymce_plugin_displayed_error )
						{
							alert( config['display_error_msg'] );
							window.tinymce_plugin_displayed_error = true;
						}
					}
					else
					{
						// Define oninit function for TinyMCE
						if( typeof window.tmce_init.oninit != "undefined" )
						{
							oninit = function() {
								window.tmce_init.oninit();
								oninit();
							}
						}

						window.tmce_init.oninit = function ()
							{
								oninit();

								// Provide hooks for textarea manipulation (where other plugins should hook into):
								var ed = window.tinymce.get( config['content_id'] );
								if( ed && typeof b2evo_Callbacks == "object" )
								{
									// add a callback, that returns the selected (raw) html:
									b2evo_Callbacks.register_callback( 'get_selected_text_for_' + config['content_id'], function( value )
										{
											var inst = window.tinymce.get( config['content_id'] );
											if( ! inst ) return null;
											return inst.selection.getContent();
										}, true );

									// add a callback, that wraps a selection:
									b2evo_Callbacks.register_callback( 'wrap_selection_for_' + config['content_id'], function( params )
										{
											var inst = window.tinymce.get( config['content_id'] );
											if( ! inst ) return null;
											var sel = inst.selection.getContent();

											if( params.replace )
											{
												var value = params.before + params.after;
											}
											else
											{
												var value = params.before + sel + params.after;
											}
											inst.selection.setContent( value );

											return true;
										}, true );

									// add a callback, that replaces a string
									b2evo_Callbacks.register_callback( 'str_replace_for_' + config['content_id'], function( params )
										{
											var inst = window.tinymce.get( config['content_id'] );
											if( ! inst ) return null;

											// Replace substring with new value
											inst.setContent( inst.getContent().replace( params.search, params.replace ) );

											return true;
										}, true );

									// add a callback, that lets us insert raw content:
									// DEPRECATED, used in b2evo 1.10.x
									b2evo_Callbacks.register_callback( 'insert_raw_into_' + config['content_id'], function( value )
										{
											window.tinymce.execInstanceCommand( config['content_id'], "mceInsertRawHTML", false, value );
											return true;
										}, true );
								}

								var textarea = jQuery( '#' + config['content_id'] );
								if( textarea.prop( 'required' ) )
								{
									textarea.attr( 'data-required', true );
									textarea.removeAttr( 'required' );
								}
							}

						// Try to add custom shortcuts from page:
						window.tmce_init.init_instance_callback = function( ed )
							{
								if( window.shortcut_keys )
								{
									for( var i = 0; i < window.shortcut_keys.length; i++ )
									{
										var key = window.shortcut_keys[i];
										ed.shortcuts.add( key, 'b2evo shortcut key: ' + key, function()
											{
												window.shortcut_handler( key );
											} );
									}
								}
							}

						window.tmce_init.setup = function( ed )
							{
								ed.on( 'init', window.tmce_init.oninit );
							}

						window.tinymce.on( 'AddEditor', function( e )
							{	// Switching to WYSIWYG mode:
								var textarea = jQuery( '#' + config['content_id'] );
								if( ! textarea.val().match( /<(p\s?|br\s?\/?)[^>]*>/i ) )
								{	// Try to apply "Auto P" plugin(if it is installed) in order to replace
									// new lines with <p> or <br> html tags if content has no them yet:
									jQuery.ajax(
									{
										type: 'POST',
										url: config['update_content_url'],
										data:
										{
											'content': textarea.val(),
										},
										success: function( result )
										{
											e.editor.setContent( result );
										}
									} );
								}
								return false;
							} );
						window.tinymce.init( window.tmce_init );
					}
				}

			if( config['use_tinymce'] )
			{
				window.tinymce_plugin_toggleEditor( config['content_id'] );
			}

			jQuery( '[name="editor_code"]' ).attr( 'value', config['editor_code'] );
		} )();
	}
} );