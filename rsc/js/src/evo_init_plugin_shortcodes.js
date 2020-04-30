/**
 * This file initialize plugin "Shortcodes"
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
	window.evo_init_shortcodes_toolbar = function( config )
		{
			window[config.js_prefix + 'shortcodes_buttons'] = new Array();

			window.shortcodes_button = function shortcodes_button( id, text, tag, title, style )
				{
					this.id = id;       // used to name the toolbar button
					this.text = text;   // label on button
					this.tag = tag;     // tag code to insert
					this.title = title; // title
					this.style = style; // style on button
				};

			// Toolbar buttons:
			window[config.js_prefix + 'shortcodes_buttons'][window[config.js_prefix + 'shortcodes_buttons'].length] = new shortcodes_button(
					'shortcodes_teaserbreak', '[teaserbreak]', '[teaserbreak]',
					config.btn_title_teaserbreak, ''
				);
			window[config.js_prefix + 'shortcodes_buttons'][window[config.js_prefix + 'shortcodes_buttons'].length] = new shortcodes_button(
					'shortcodes_pagebreak', '[pagebreak]', '[pagebreak]',
					config.btn_title_pagebreak, ''
				);

			window[config.js_prefix + 'shortcodes_toolbar'] = function ( title )
				{
					var r = config.toolbar_title_before + title + config.toolbar_title_after
						+ config.toolbar_group_before;
					for( var i = 0, n = window[config.js_prefix + 'shortcodes_buttons'].length; i < n; i++ )
					{
						var button = window[config.js_prefix + 'shortcodes_buttons'][i];
						r += '<input type="button" id="' + button.id + '" title="' + button.title + '"'
							+ ( typeof( button.style ) != 'undefined' ? ' style="' + button.style + '"' : '' ) + ' class="' + config.toolbar_button_class
							+ '" data-func="' + config.js_prefix + 'shortcodes_insert_tag|' + config.js_prefix + 'b2evoCanvas|' + i + '" value="' + button.text + '" />';
					}
					r += config.toolbar_group_after;

					jQuery( '.' + config.js_prefix + config.plugin_code + '_toolbar' ).html( r );
				};

			window[config.js_prefix + 'shortcodes_insert_tag'] = function ( canvas_field, i )
				{
					if( typeof( tinyMCE ) != 'undefined' && typeof( tinyMCE.activeEditor ) != 'undefined' && tinyMCE.activeEditor )
					{   // tinyMCE plugin is active now, we should focus cursor to the edit area
						tinyMCE.execCommand( 'mceFocus', false, tinyMCE.activeEditor.id );
					}
					// Insert tag text in area
					textarea_wrap_selection( canvas_field, window[config.js_prefix + 'shortcodes_buttons'][i].tag, '', 0 );
				};

			// Render toolbar:
			window[config.js_prefix + 'shortcodes_toolbar']( config.toolbar_title + ': ' );
		};

	if( typeof( evo_init_shortcodes_toolbar_config ) != 'undefined' )
	{
		// Initialize each Shortcodes toolbar instance:
		var evo_temp_config = Object.values( evo_init_shortcodes_toolbar_config );
		for( var i = 0; i < evo_temp_config.length; i++ )
		{
			( function() {
				window.evo_init_shortcodes_toolbar( evo_temp_config[i] );
			} )();
		}
		delete evo_temp_config;
	}
} );
