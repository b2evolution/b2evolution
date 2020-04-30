/**
 * This file initialize plugin "Markdown"
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
	window.evo_init_markdown_toolbar = function ( config )
		{
			window[config.js_prefix + 'markdown_btns'] = new Array();
			window[config.js_prefix + 'markdown_open_tags'] = new Array();

			window.markdown_btn = function markdown_btn( id, text, title, tag_start, tag_end, style, open, grp_pos )
				{
					this.id = id;               // used to name the toolbar button
					this.text = text;           // label on button
					this.title = title;         // title
					this.tag_start = tag_start; // open tag
					this.tag_end = tag_end;     // close tag
					this.style = style;         // style on button
					this.open = open;           // set to -1 if tag does not need to be closed
					this.grp_pos = grp_pos;     // position in the group, e.g. 'last'
				};

			if( config.enable_text_styles )
			{   // Show thess buttons only when plugin setting "Italic & Bold styles" is enabled
				window[config.js_prefix + 'markdown_btns'][window[config.js_prefix + 'markdown_btns'].length] = new markdown_btn(
						config.js_prefix + 'mrkdwn_bold', 'bold', config.btn_title_bold,
						'**', '**', 'font-weight:bold'
					);
				window[config.js_prefix + 'markdown_btns'][window[config.js_prefix + 'markdown_btns'].length] = new markdown_btn(
						config.js_prefix + 'mrkdwn_italic', 'italic', config.btn_title_italic,
						'*', '*', 'font-style:italic', -1, 'last'
					);
			}

			if( config.enable_links )
			{   // Show this button only when plugin setting "Links" is enabled
				window[config.js_prefix + 'markdown_btns'][window[config.js_prefix + 'markdown_btns'].length] = new markdown_btn(
						config.js_prefix + 'mrkdwn_link', 'link', config.btn_title_link,
						'', '', 'text-decoration:underline', -1, config.enable_images ? undefined : 'last'
					);
			}

			if( config.enable_images )
			{   // Show this button only when plugin setting "Images" is enabled
				window[config.js_prefix + 'markdown_btns'][window[config.js_prefix + 'markdown_btns'].length] = new markdown_btn(
						config.js_prefix + 'mrkdwn_img', 'img', config.btn_title_image,
						'', '', '', -1, 'last'
					);
			}


			window[config.js_prefix + 'markdown_btns'][window[config.js_prefix + 'markdown_btns'].length] = new markdown_btn(
					config.js_prefix + 'mrkdwn_h1', 'H1', config.btn_title_h1,
					'\n# ', '', '', -1
				);
			window[config.js_prefix + 'markdown_btns'][window[config.js_prefix + 'markdown_btns'].length] = new markdown_btn(
					config.js_prefix + 'mrkdwn_h2', 'H2', config.btn_title_h2,
					'\n## ', '', '', -1
				);
			window[config.js_prefix + 'markdown_btns'][window[config.js_prefix + 'markdown_btns'].length] = new markdown_btn(
					config.js_prefix + 'mrkdwn_h3', 'H3', config.btn_title_h3,
					'\n### ', '', '', -1
				);
			window[config.js_prefix + 'markdown_btns'][window[config.js_prefix + 'markdown_btns'].length] = new markdown_btn(
					config.js_prefix + 'mrkdwn_h4', 'H4', config.btn_title_h4,
					'\n#### ', '', '', -1
				);
			window[config.js_prefix + 'markdown_btns'][window[config.js_prefix + 'markdown_btns'].length] = new markdown_btn(
					config.js_prefix + 'mrkdwn_h5', 'H5', config.btn_title_h5,
					'\n##### ', '', '', -1
				);
			window[config.js_prefix + 'markdown_btns'][window[config.js_prefix + 'markdown_btns'].length] = new markdown_btn(
					config.js_prefix + 'mrkdwn_h6', 'H6', config.btn_title_h6,
					'\n###### ', '', '', -1, 'last'
				);

			window[config.js_prefix + 'markdown_btns'][window[config.js_prefix + 'markdown_btns'].length] = new markdown_btn(
					config.js_prefix + 'mrkdwn_li', 'li', config.btn_title_li,
					'\n* ', '', '', -1
				);
			window[config.js_prefix + 'markdown_btns'][window[config.js_prefix + 'markdown_btns'].length] = new markdown_btn(
					config.js_prefix + 'mrkdwn_ol', 'ol', config.btn_title_ol,
					'\n1. ', '', '', -1
				);
			window[config.js_prefix + 'markdown_btns'][window[config.js_prefix + 'markdown_btns'].length] = new markdown_btn(
					config.js_prefix + 'mrkdwn_blockquote', 'blockquote', config.btn_title_blockquote,
					'\n> ', '',	'', -1, 'last'
				);

			window[config.js_prefix + 'markdown_btns'][window[config.js_prefix + 'markdown_btns'].length] = new markdown_btn(
					config.js_prefix + 'mrkdwn_codespan', 'codespan', config.btn_title_codespan,
					'`', '`', '', -1
				);
			window[config.js_prefix + 'markdown_btns'][window[config.js_prefix + 'markdown_btns'].length] = new markdown_btn(
					config.js_prefix + 'mrkdwn_preblock', 'preblock', config.btn_title_preblock,
					'\n\t', '',	'', -1, 'last'
				);

			window[config.js_prefix + 'markdown_btns'][window[config.js_prefix + 'markdown_btns'].length] = new markdown_btn(
					config.js_prefix + 'mrkdwn_codeblock', 'codeblock', config.btn_title_codeblock,
					'\n```\n', '\n```\n', '', -1, 'last'
				);

			window[config.js_prefix + 'markdown_btns'][window[config.js_prefix + 'markdown_btns'].length] = new markdown_btn(
					config.js_prefix + 'mrkdwn_hr', 'hr', config.btn_title_hr,
					'\n---\n', '', '', -1
				);
			window[config.js_prefix + 'markdown_btns'][window[config.js_prefix + 'markdown_btns'].length] = new markdown_btn(
					config.js_prefix + 'mrkdwn_br', '<br>', config.btn_title_br,
					'  \n', '',	'', -1
				);

			window[config.js_prefix + 'markdown_get_btn'] = function ( button, i )
				{
					var r = '';
					if( button.id == window[config.js_prefix + 'mrkdwn_img'] )
					{	// Image
						r += '<input type="button" id="' + button.id + '" accesskey="' + button.access + '" title="' + button.title
							+ '" style="' + button.style + '" class="' + config.toolbar_button_class + '" data-func="' + config.js_prefix + 'markdown_insert_lnkimg|' + config.js_prefix + 'b2evoCanvas|img" value="' + button.text + '" />';
					}
					else if( button.id == config.js_prefix + 'mrkdwn_link' )
					{	// Link
						r += '<input type="button" id="' + button.id + '" accesskey="' + button.access + '" title="' + button.title
							+ '" style="' + button.style + '" class="' + config.toolbar_button_class + '" data-func="' + config.js_prefix + 'markdown_insert_lnkimg|' + config.js_prefix + 'b2evoCanvas" value="' + button.text + '" />';
					}
					else
					{	// Normal buttons:
						r += '<input type="button" id="' + button.id + '" accesskey="' + button.access + '" title="' + button.title
							+ '" style="' + button.style + '" class="' + config.toolbar_button_class + '" data-func="' + config.js_prefix + 'markdown_insert_tag|' + config.js_prefix + 'b2evoCanvas|' + i + '" value="' + button.text + '" />';
					}

					return r;
				};

			// Memorize a new open tag
			window[config.js_prefix + 'markdown_add_tag'] =	function ( button )
				{
					if( window[config.js_prefix + 'markdown_btns'][button].tag_end != '' )
					{
						window[config.js_prefix + 'markdown_open_tags'][window[config.js_prefix + 'markdown_open_tags'].length] = button;
						document.getElementById( window[config.js_prefix + 'markdown_btns'][button].id ).value = '/' + document.getElementById( window[config.js_prefix + 'markdown_btns'][button].id ).value;
					}
				};

			// Forget about an open tag
			window[config.js_prefix + 'markdown_remove_tag'] = function ( button )
				{
					for( i = 0; i < window[config.js_prefix + 'markdown_open_tags'].length; i++ )
					{
						if( window[config.js_prefix + 'markdown_open_tags'][i] == button )
						{
							window[config.js_prefix + 'markdown_open_tags'].splice( i, 1 );
							document.getElementById( window[config.js_prefix + 'markdown_btns'][button].id ).value = document.getElementById( window[config.js_prefix + 'markdown_btns'][button].id ).value.replace( '/', '' );
						}
					}
				};

			window[config.js_prefix + 'markdown_check_open_tags'] = function ( button )
				{
					var tag = 0;
					for( i = 0; i < window[config.js_prefix + 'markdown_open_tags'].length; i++ )
					{
						if( window[config.js_prefix + 'markdown_open_tags'][i] == button )
						{
							tag++;
						}
					}

					if( tag > 0 )
					{
						return true; // tag found
					}
					else
					{
						return false; // tag not found
					}
				};

			window[config.js_prefix + 'markdown_close_all_tags'] = function ()
				{
					var count = window[config.js_prefix + 'markdown_open_tags'].length;
					for( var o = 0; o < count; o++ )
					{
						window[config.js_prefix + 'markdown_insert_tag']( window[config.js_prefix + 'b2evoCanvas'], window[config.js_prefix + 'markdown_open_tags'][window[config.js_prefix + 'markdown_open_tags'].length - 1] );
					}
				};

			window[config.js_prefix + 'markdown_toolbar'] = function ( title )
				{
					var r = config.toolbar_title_before + title + config.toolbar_title_after + config.toolbar_group_before;
					for( var i = 0; i < window[config.js_prefix + 'markdown_btns'].length; i++ )
					{
						r += window[config.js_prefix + 'markdown_get_btn']( window[config.js_prefix + 'markdown_btns'][i], i );
						if( window[config.js_prefix + 'markdown_btns'][i].grp_pos == 'last' && ( i > 0 ) && ( i < window[config.js_prefix + 'markdown_btns'].length - 1 ) )
						{	// Separator between groups
							r += config.toolbar_group_after + config.toolbar_group_before;
						}
					}
					r += config.toolbar_group_after + config.toolbar_group_before
						+ '<input type="button" id="' + config.js_prefix + 'mrkdwn_close" class="' + config.toolbar_button_class + '" data-func="' + config.js_prefix + 'markdown_close_all_tags" title="' + config.btn_title_close_all_tags + '" value="X" />'
						+ config.toolbar_group_after;

					jQuery( '.' + config.js_prefix + config.plugin_code + '_toolbar' ).html( r );
				};

			window[config.js_prefix + 'markdown_insert_tag'] = function( field, i )
				{
					// we need to know if something is selected.
					// First, ask plugins, then try IE and Mozilla.
					var sel_text = b2evo_Callbacks.trigger_callback( "get_selected_text_for_" + field.id );
					var focus_when_finished = false; // used for IE

					if( sel_text == null )
					{	// detect selection:
						//IE support
						if( document.selection )
						{
							field.focus();
							var sel = document.selection.createRange();
							sel_text = sel.text;
							focus_when_finished = true;
						}
						//MOZILLA/NETSCAPE support
						else if( field.selectionStart || field.selectionStart == '0' )
						{
							var startPos = field.selectionStart;
							var endPos = field.selectionEnd;
							sel_text = ( startPos != endPos );
						}
					}


					if( sel_text )
					{	// some text selected
						textarea_wrap_selection( field, window[config.js_prefix + 'markdown_btns'][i].tag_start, window[config.js_prefix + 'markdown_btns'][i].tag_end, 0 );
					}
					else
					{
						if( ! window[config.js_prefix + 'markdown_check_open_tags'](i) || ( window[config.js_prefix + 'markdown_btns'][i].tag_end == '' ) )
						{
							textarea_wrap_selection( field, window[config.js_prefix + 'markdown_btns'][i].tag_start, '', 0 );
							window[config.js_prefix + 'markdown_add_tag'](i);
						}
						else
						{
							textarea_wrap_selection( field, '', window[config.js_prefix + 'markdown_btns'][i].tag_end, 0 );
							window[config.js_prefix + 'markdown_remove_tag'](i);
						}
					}
					if( focus_when_finished )
					{
						field.focus();
					}
				};


			window[config.js_prefix + 'markdown_insert_lnkimg'] = function ( field, type )
				{
					var url = prompt( config.prompt_url + ':', 'http://' );
					if( url )
					{
						url = '[' + prompt( config.prompt_text + ':', '') + ']'
							+ '(' + url;
						var title = prompt( config.prompt_title + ':', '' );
						if( title != '' )
						{
							url += ' "' + title + '"';
						}
						url += ')';
						if( typeof( type ) != 'undefined' && type == 'img' )
						{	// for <img> tag
							url = '!' + url;
						}
						textarea_wrap_selection( field, url, '', 1 );
					}
				};

			window[config.js_prefix + 'markdown_toolbar']( config.toolbar_title + ': ' );
		};

	if( typeof( evo_init_markdown_toolbar_config ) != 'undefined' )
	{
		// Initialize each Markdown Toolbar instance:
		var evo_temp_config = Object.values( evo_init_markdown_toolbar_config );
		for( var i = 0; i < evo_temp_config.length; i++ )
		{
			( function() {
				window.evo_init_markdown_toolbar( evo_temp_config[i] );
			} )();
		}
		delete evo_temp_config;
	}
} );
