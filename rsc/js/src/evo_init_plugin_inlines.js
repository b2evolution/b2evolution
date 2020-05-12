/**
 * This file initialize plugin "Inlines"
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
	window.evo_init_inlines_toolbar = function( config )
		{
			var target_ID      = config.target_ID;
			var temp_ID        = config.temp_ID;
			var target_type    = config.target_type;
			var inline_buttons = new Array();

			window.inline_button = function inline_button( id, text, type, title, style )
				{
					this.id    = id;    // used to name the toolbar button
					this.text  = text;  // label on button
					this.type  = type;  // type of inline
					this.title = title; // title
					this.style = style; // style on button
				};

			inline_buttons[inline_buttons.length] = new window.inline_button( 'inline_image', 'image', 'image', config.button_title, '' );

			window.inline_toolbar = function inline_toolbar( title, prefix )
				{
					var r = config.toolbar_title_before + title + config.toolbar_title_after
						+ config.toolbar_group_before;
					for( var i = 0; i < inline_buttons.length; i++ )
					{
						var button = inline_buttons[i];
						r += '<input type="button" id="' + button.id + '" title="' + button.title + '"'
							+ ( typeof( button.style ) != 'undefined' ? ' style="' + button.style + '"' : '' )
							+ ' class="' + config.button_class + '" data-func="insert_inline|' + button.type + '" value="' + button.text + '" />';
					}
					r += config.toolbar_group_after;

					jQuery( '.' + prefix + config.plugin_code + '_toolbar' ).html( r );
				};

			window.insert_inline = function insert_inline()
				{
					switch( target_type )
					{
						case 'Item':
							if( ! target_ID && ! temp_ID )
							{
								alert( evo_js_lang_alert_before_insert_item  );
								return false;
							}
							break;

						case 'Comment':
							if( ! target_ID )
							{
								alert( evo_js_lang_alert_before_insert_comment );
								return false;
							}
							break;

						case 'EmailCampaign':
							if( ! target_ID )
							{
								alert( evo_js_lang_alert_before_insert_emailcampaign );
								return false;
							}
							break;

						case 'Message':
							if( ! target_ID && ! temp_ID )
							{
								alert( evo_js_lang_alert_before_insert_message );
								return false;
							}
							break;
					}

					if( typeof( tinyMCE ) != 'undefined' && typeof( tinyMCE.activeEditor ) != 'undefined' && tinyMCE.activeEditor )
					{	// tinyMCE plugin is active now, we should focus cursor to the edit area
						tinyMCE.execCommand( 'mceFocus', false, tinyMCE.activeEditor.id );
						tinyMCE.execCommand( 'evo_view_edit_inline', false, tinyMCE.activeEditor.id );
					}
					else
					{
						openModalWindow( '<span class="loader_img loader_user_report absolute_center" title="' + evo_js_lang_loading + '..."></span>',
								'80%', '', true, evo_js_lang_select_image_insert, '', true );

						jQuery.ajax( {
								type: 'POST',
								url: config.insert_inline_url,
								success: function( result )
									{
										var param_target_type, param_target_ID;
										if( temp_ID == undefined )
										{
											param_target_type = target_type;
											param_target_ID = target_ID
										}
										else
										{
											param_target_type = 'temporary';
											param_target_ID = temp_ID;
										}
										openModalWindow( result, '90%', '80%', true, 'Select image', '', '', '', '', '', function() {
													evo_link_refresh_list( param_target_type, param_target_ID, 'refresh' );
													evo_link_fix_wrapper_height();
												} );
									}
							} );
					}
				};

			window.inline_toolbar( config.toolbar_title, config.prefix );
		};

	if( typeof( evo_init_inlines_toolbar_config ) != 'undefined' )
	{
		// Initialize each Inline toolbar instance:
		var evo_temp_config = Object.values( evo_init_inlines_toolbar_config );
		for( var i = 0; i < evo_temp_config.length; i++ )
		{
			( function() {
				window.evo_init_inlines_toolbar( evo_temp_config[i] );
			} )();
		}
		delete evo_temp_config;
	}

} );
