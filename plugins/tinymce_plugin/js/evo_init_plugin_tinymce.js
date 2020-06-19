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
 * Depends on: jQuery, tinyMCE
 */
jQuery( document ).ready( function()
{
	window.evo_init_tinymce = function evo_init_tinymce( config )
		{
			if( config.toggle_editor )
			{
				/**
				 * Toggles the TinyMCE editor
				 */
				window.tinymce_plugin_toggleEditor = function tinymce_plugin_toggleEditor( content_id, force_enable )
					{
						var textarea = jQuery( '#' + content_id );
						jQuery( '[data-content-id="' + content_id + '"] [id^="tinymce_plugin_toggle_button_"]' ).removeClass( 'active' ).attr( 'disabled', 'disabled' );

						if( ! window['tinymce_plugin_init_done_' + content_id] )
						{
							window['tinymce_plugin_init_done_' + content_id] = true;

							// Call this method on init again, with "null" id, so that mceAddControl gets called.
							window.tinymce_plugin_init_tinymce( function()
								{
									window.tinymce_plugin_toggleEditor( content_id, true );
								} );

							return;
						}

						if( ! window.tinymce.get( content_id ) || force_enable )
						{	// Turn on WYSIWYG editor
							window.tinymce.execCommand( 'mceAddEditor', false, content_id );
							jQuery.get( config.toggle_editor.save_state_wysiwyg_url );
							jQuery( '#tinymce_plugin_toggle_button_wysiwyg' ).addClass( 'active' );
							jQuery( '#tinymce_plugin_toggle_button_html' ).removeAttr( 'disabled' );
							jQuery( '[name="editor_code"]').attr( 'value', config.plugin_code );
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

							if( content_id && textarea.prop( 'required' ) )
							{
								textarea.attr( 'data-required', true );
								textarea.removeAttr( 'required' );
							}
						}
						else
						{	// Hide the editor, Display only source HTML
							window.tinymce.execCommand( 'mceRemoveEditor', false, content_id );
							jQuery.get( config.toggle_editor.save_state_html_url );
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

							if( content_id && textarea.attr( 'data-required' ) )
							{
								textarea.removeAttr( 'data-required' );
								textarea.attr( 'required', true );
							}
						}
					};

				jQuery( '[id^=tinymce_plugin_toggle_button_]').click( function()
					{
						var content_id = jQuery( this ).parent().data( 'contentId' );

						if( ! content_id )
						{	// Content ID not found:
							return false;
						}

						window.tinymce_plugin_toggleEditor( content_id );
					} );
			}

			if( config.editor )
			{
				window.tinymce_plugin_displayed_error = window.tinymce_plugin_displayed_error || false;
				window['tinymce_plugin_init_done_' + config.content_id] = window['tinymce_plugin_init_done_' + config.content_id] || false;

				// Init array with all usernames from the page for autocomplete plugin
				window.tinymce_autocomplete_static_options = [];
				jQuery( '.user.login' ).each( function()
					{
						var login = jQuery( this ).text();
						if( login != '' && window.tinymce_autocomplete_static_options.indexOf( login ) == -1 )
						{
							if( login[0] == '@' )
							{
								login = login.substr( 1 );
							}
							window.tinymce_autocomplete_static_options.push( login );
						}
					} );
				window.tinymce_autocomplete_static_options = window.tinymce_autocomplete_static_options.join();

				window.tinymce_plugin_init_tinymce = function tinymce_plugin_init_tinymce( oninit )
					{
						// Init tinymce:
						if( typeof window.tinymce == "undefined" )
						{
							if( ! window.tinymce_plugin_displayed_error )
							{
								alert( config.editor.display_error_msg );
								window.tinymce_plugin_displayed_error = true;
							}
						}
						else
						{
							// Define oninit function for TinyMCE
							if( typeof config.editor.tmce_init.oninit != "undefined" )
							{	// Already defined:
								oninit = function() {
									config.editor.tmce_init.oninit();
									oninit();
								}
							}

							config.editor.tmce_init.oninit = function ()
								{
									// Why?
									oninit();

									// Provide hooks for textarea manipulation (where other plugins should hook into):
									var ed = window.tinymce.get( config.content_id );
									if( ed && typeof b2evo_Callbacks == "object" )
									{
										// add a callback, that returns the selected (raw) html:
										b2evo_Callbacks.register_callback( 'get_selected_text_for_' + config.content_id, function( value )
											{
												var inst = window.tinymce.get( config.content_id );
												if( ! inst ) return null;
												return inst.selection.getContent();
											}, true );

										// add a callback, that wraps a selection:
										b2evo_Callbacks.register_callback( 'wrap_selection_for_' + config.content_id, function( params )
											{
												var inst = window.tinymce.get( config.content_id );
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
										b2evo_Callbacks.register_callback( 'str_replace_for_' + config.content_id, function( params )
											{
												var inst = window.tinymce.get( config.content_id );
												if( ! inst ) return null;

												// Replace substring with new value
												inst.setContent( inst.getContent().replace( params.search, params.replace ) );

												return true;
											}, true );

										// add a callback, that lets us insert raw content:
										// DEPRECATED, used in b2evo 1.10.x
										b2evo_Callbacks.register_callback( 'insert_raw_into_' + config.content_id, function( value )
											{
												window.tinymce.execInstanceCommand( config.content_id, "mceInsertRawHTML", false, value );
												return true;
											}, true );
									}

									var textarea = jQuery( '#' + config.content_id );
									if( textarea.prop( 'required' ) )
									{
										textarea.attr( 'data-required', true );
										textarea.removeAttr( 'required' );
									}
								};

							// Try to add custom shortcuts from page:
							config.editor.tmce_init.init_instance_callback = function( ed )
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
								};

							// This option allows you to specify a callback that will be executed before the TinyMCE editor instance is rendered
							config.editor.tmce_init.setup = function( ed )
								{
									// Fired when the editor is fully initialized.
									ed.on( 'init', config.editor.tmce_init.oninit );
								};

							window.tinymce.on( 'AddEditor', function( e )
								{	// Switching to WYSIWYG mode:
									var textarea = jQuery( '#' + config.content_id );
									if( ! textarea.val().match( /<(p\s?|br\s?\/?)[^>]*>/i ) )
									{	// Try to apply "Auto P" plugin(if it is installed) in order to replace
										// new lines with <p> or <br> html tags if content has no them yet:
										jQuery.ajax(
										{
											type: 'POST',
											url: config.editor.update_content_url,
											data:
												{
													'content': textarea.val(),
													'crumb_tinymce': config.editor.crumb_tinymce,
												},
											success: function( result )
												{
													e.editor.setContent( result );
												}
										} );
									}
									return false;
								} );

							// Initialize TinyMCE:
							window.tinymce.init( config.editor.tmce_init );
						}
					};

				if( config.editor.use_tinymce == 1 )
				{
					window.tinymce_plugin_toggleEditor( config.content_id );
				}
	
				// Set editor code to current plugin code if JS is enabled and tinymce is used currently:
				jQuery( '[name="editor_code"]' ).attr( 'value', config.editor.use_tinymce == 1 ? config.plugin_code : 'html' );
			}
		};

	if( typeof( evo_tinymce_config ) != 'undefined' )
	{
		// Initialize each TinyMCE instance:
		var evo_temp_config = Object.values( evo_tinymce_config );
		for( var i = 0, n = evo_temp_config.length; i < n; i++ )
		{
			( function() {
				var config = evo_temp_config[i];

				// Init:
				window.evo_init_tinymce( config );
			} )();
		}
		delete evo_temp_config;
	}
} );
