/**
 * This file initialize plugin "Widescroll"
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
	if( typeof( evo_init_widescroll_toolbar_config ) == 'undefined' )
	{	// Don't execute code below because no config var is found:
		return;
	}

	var evo_init_widescroll_toolbar_configs = Object.values( evo_init_widescroll_toolbar_config );
	for( var i = 0; i < evo_init_widescroll_toolbar_configs.length; i++ )
	{
		var config = evo_init_widescroll_toolbar_configs[i];

		( function() {

			window[config.js_prefix + 'widescroll_buttons'] = new Array();

			function widescroll_button( id, text, tag_open, tag_close, title, style )
				{
					this.id = id;               // used to name the toolbar button
					this.text = text;           // label on button
					this.tag_open = tag_open;   // tag code to insert
					this.tag_close = tag_close; // tag code to insert
					this.title = title;         // title
					this.style = style;         // style on button
				};

			window[config.js_prefix + 'widescroll_buttons'][window[config.js_prefix + 'widescroll_buttons'].length] = new widescroll_button(
					'widescroll', 'wide scroll', '<div class="wide_scroll">', '</div>',
					config.btn_title_teaserbreak, ''
				);

			window[config.js_prefix + 'widescroll_toolbar'] = function ( title )
				{
					var r = config.toolbar_title_before + title + config.toolbar_title_after + config.toolbar_group_before;
					for( var i = 0, n = window[config.js_prefix + 'widescroll_buttons'].length; i < n; i++ )
					{
						var button = window[config.js_prefix + 'widescroll_buttons'][i];
						r += '<input type="button" id="' + button.id + '" title="' + button.title + '"'
							+ ( typeof( button.style ) != 'undefined' ? ' style="' + button.style + '"' : '' )
							+ ' class="' + config.toolbar_button_class
							+ '" data-func="' + config.js_prefix + 'widescroll_insert_tag|' + config.js_prefix + 'b2evoCanvas|' + i + '" value="' + button.text + '" />';
					}
					r += config.toolbar_group_after;

					jQuery( '.' + config.js_prefix + config.plugin_code + '_toolbar' ).html( r );
				};

			window[config.js_prefix + 'widescroll_insert_tag'] = function ( canvas_field, i )
				{
					if( typeof( tinyMCE ) != 'undefined' && typeof( tinyMCE.activeEditor ) != 'undefined' && tinyMCE.activeEditor )
					{	// tinyMCE plugin is active now, we should focus cursor to the edit area
						tinyMCE.execCommand( 'mceFocus', false, tinyMCE.activeEditor.id );
					}
					// Insert tag text in area
					textarea_wrap_selection( canvas_field, window[config.js_prefix + 'widescroll_buttons'][i].tag_open, window[config.js_prefix + 'widescroll_buttons'][i].tag_close, 0 );
				};

			// Render toolbar:
			window[config.js_prefix + 'widescroll_toolbar']( config.toolbar_title + ': ' );
		} )();
	}
} );
