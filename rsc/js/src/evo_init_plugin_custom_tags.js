/**
 * This file initialize plugin "Custom Tags"
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
	if( typeof( evo_init_custom_tags_toolbar_config ) == 'undefined' )
	{	// Don't execute code below because no config var is found:
		return;
	}

	var evo_init_custom_tags_toolbar_configs = Object.values( evo_init_custom_tags_toolbar_config );
	for( var i = 0; i < evo_init_custom_tags_toolbar_configs.length; i++ )
	{
		var config = evo_init_custom_tags_toolbar_configs[i];
		
		( function() {
			var js_code_prefix = config.js_prefix + config.plugin_code + '_';

			window[js_code_prefix + 'tagButtons'] = new Array();
			window[js_code_prefix + 'tagOpenTags'] = new Array();

			window[js_code_prefix + 'tagButton'] = function( id, display, style, tagStart, tagEnd, access, title, open )
				{
					this.id       = id;       // used to name the toolbar button
					this.display  = display;  // label on button
					this.style    = style;    // style on button
					this.tagStart = tagStart; // open tag
					this.tagEnd   = tagEnd;   // close tag
					this.access   = access;   // access key
					this.title    = title;    // title
					this.open     = open;     // set to -1 if tag does not need to be closed
				};

			var tag_buttons = Object.values( config.tag_buttons );

			for( var i = 0, n = tag_buttons.length; i  < n; i++ )
			{
				window[js_code_prefix + 'tagButtons'][window[js_code_prefix + 'tagButtons'].length] = new window[js_code_prefix + 'tagButton'](
						'tag_' + tag_buttons[i]['title'],
						tag_buttons[i]['name'], '',
						tag_buttons[i]['start'], tag_buttons[i]['end'], '',
						tag_buttons[i]['title']
					);
			}

			window[js_code_prefix + 'tagGetButton'] = function( button, i )
				{
					return '<input type="button" id="' + button.id + '" accesskey="' + button.access + '" title="' + button.title
							+ '" style="' + button.style + '" class="' + config.toolbar_button_class + '" data-func="' + js_code_prefix + 'tagInsertTag|' + config.js_prefix + 'b2evoCanvas|'+i+'" value="' + button.display + '" />';
				};

			// Memorize a new open tag
			window[js_code_prefix + 'tagAddTag'] = function( button )
				{
					if( window[js_code_prefix + 'tagButtons'][button].tagEnd != '' )
					{
						window[js_code_prefix + 'tagOpenTags'][window[js_code_prefix + 'tagOpenTags'].length] = button;
						document.getElementById( window[js_code_prefix + 'tagButtons'][button].id).style.fontWeight = 'bold';
					}
				};

			// Forget about an open tag
			window[js_code_prefix + 'tagRemoveTag'] = function( button )
				{
					for( i = 0, n = window[js_code_prefix + 'tagOpenTags'].length; i < n; i++ )
					{
						if( window[js_code_prefix + 'tagOpenTags'][i] == button )
						{
							window[js_code_prefix + 'tagOpenTags'].splice( i, 1 );
							document.getElementById( window[js_code_prefix + 'tagButtons'][button].id ).style.fontWeight = 'normal';
						}
					}
				};

			window[js_code_prefix + 'tagCheckOpenTags'] = function( button )
				{
					var tag = 0;
					for( i = 0, n = window[js_code_prefix + 'tagOpenTags'].length; i < n; i++ )
					{
						if( window[js_code_prefix + 'tagOpenTags'][i] == button )
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

			window[js_code_prefix + 'tagCloseAllTags'] = function()
				{
					var count = window[js_code_prefix + 'tagOpenTags'].length;
					for( i = 0; i < count; i++ )
					{
						window[js_code_prefix + 'tagInsertTag']( window[config.js_prefix + 'b2evoCanvas'], window[js_code_prefix + 'tagOpenTags'][window[js_code_prefix + 'tagOpenTags'].length - 1] );
					}
				};

			window[js_code_prefix + 'tagToolbar'] = function()
				{
					var tagcode_toolbar = config.toolbar_title_before + config.toolbar_label + ' ' + config.toolbar_title_after;
					tagcode_toolbar += config.toolbar_group_before;
					for( var i = 0, n = window[js_code_prefix + 'tagButtons'].length; i < n; i++ )
					{
						tagcode_toolbar += window[js_code_prefix + 'tagGetButton']( window[js_code_prefix + 'tagButtons'][i], i );
					}
					tagcode_toolbar += config.toolbar_group_after + config.toolbar_group_before;
					tagcode_toolbar += '<input type="button" id="tag_close" class="' + config.toolbar_button_class + '" data-func="' + js_code_prefix + 'tagCloseAllTags" title="' + config.btn_title_close_all_tags + '" value="X" />';
					tagcode_toolbar += config.toolbar_group_after;

					jQuery( '.' + js_code_prefix + 'toolbar' ).html( tagcode_toolbar );
				};

			/**
			 * insertion code
			 */
			window[js_code_prefix + 'tagInsertTag'] = function( myField, i )
				{
					// we need to know if something is selected.
					// First, ask plugins, then try IE and Mozilla.
					var sel_text = b2evo_Callbacks.trigger_callback( 'get_selected_text_for_' + myField.id );
					var focus_when_finished = false; // used for IE

					if( sel_text == null || sel_text == false )
					{	// detect selection:
						//IE support
						if(document.selection)
						{
							myField.focus();
							var sel = document.selection.createRange();
							sel_text = sel.text;
							focus_when_finished = true;
						}
						//MOZILLA/NETSCAPE support
						else if( myField.selectionStart || myField.selectionStart == '0' )
						{
							var startPos = myField.selectionStart;
							var endPos = myField.selectionEnd;
							sel_text = ( startPos != endPos );
						}
					}

					if( sel_text )
					{	// some text selected
						textarea_wrap_selection( myField, window[js_code_prefix + 'tagButtons'][i].tagStart, window[js_code_prefix + 'tagButtons'][i].tagEnd, 0 );
					}
					else
					{
						if( ! window[js_code_prefix + 'tagCheckOpenTags']( i ) || ( window[js_code_prefix + 'tagButtons'][i].tagEnd == '' ) )
						{
							textarea_wrap_selection( myField, window[js_code_prefix + 'tagButtons'][i].tagStart, '', 0 );
							window[js_code_prefix + 'tagAddTag']( i );
						}
						else
						{
							textarea_wrap_selection( myField, '', window[js_code_prefix + 'tagButtons'][i].tagEnd, 0 );
							window[js_code_prefix + 'tagRemoveTag']( i );
						}
					}
					if( focus_when_finished )
					{
						myField.focus();
					}
				};

			// Render the toolbar:
			window[js_code_prefix + 'tagToolbar']();
		} )();
	}
} );