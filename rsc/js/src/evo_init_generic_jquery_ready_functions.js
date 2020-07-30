/**
 * This file has generic functions that are initialized on jQuery ready function
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
	// Scroll to Top
	if( typeof( evo_init_scroll_to_top ) != 'undefined' )
	{
		// browser window scroll ( in pixels ) after which the "scroll to top" link is show
		// setting this variable directly under window as this is also referenced by slidedown.init.js
		window.offset = 400,
		// browser window scroll (in pixels) after which the "scroll to top" link opacity is reduced
		offset_opacity = 1200,
		// duration of the top scrolling animatiion (in ms)
		scroll_top_duration = 700,
		// grab the "back to top" link
		$slide_top = jQuery( '.slide-top' );
		
		// hide or show the "scroll to top" link
		jQuery( window ).scroll( function()
		{
			( jQuery( this ).scrollTop() > offset ) ? $slide_top.addClass( 'slide-top-visible' ) : $slide_top.removeClass( 'slide-top-visible' );
		});

		// Smooth scroll to top
		$slide_top.on( 'click', function(event)
		{
			event.preventDefault();
			jQuery( 'body, html' ).animate(
			{
				scrollTop: 0,
			}, scroll_top_duration );
		} );
	}
	
	// Datepicker
	if( typeof( evo_init_datepicker ) != 'undefined' )
	{
		jQuery( evo_init_datepicker['selector'] ).datepicker( evo_init_datepicker['config'] );
	}

	// Change Link Position JS
	if( typeof( evo_link_position_config ) != 'undefined' )
	{
		window.displayInlineReminder = evo_link_position_config['display_inline_reminder'];
		window.deferInlineReminder   = evo_link_position_config['defer_inline_reminder'];

		jQuery( document ).on( 'change', evo_link_position_config['selector'], {
				url: evo_link_position_config['url'],
				crumb: evo_link_position_config['crumb'],
			},
			function( event )
			{
				if( this.value == 'inline' && window.displayInlineReminder && !window.deferInlineReminder )
				{ // Display inline position reminder
					alert( evo_link_position_config['alert_msg'] );
					window.displayInlineReminder = false;
				}
				evo_link_change_position( this, event.data.url, event.data.crumb );
			} );
	}

	// Item Text Renderers
	if( typeof( evo_itemform_renderers__click ) != 'undefined' )
	{
		jQuery( "#itemform_renderers .dropdown-menu" ).on( "click", function( e ) { e.stopPropagation() } );
	}

	// Comment Text Renderers
	if( typeof( evo_commentform_renderers__click ) != 'undefined' )
	{
		jQuery( "#commentform_renderers .dropdown-menu" ).on( "click", function( e ) { e.stopPropagation() } );
	}

	// Comment Reply
	if( typeof( evo_init_comment_reply_config ) != 'undefined' )
	{
		jQuery( document ).on( 'click', 'a.comment_reply', function()
		{	// The click action for the links "Reply to this comment"
			var comment_ID = jQuery( this ).attr( 'rel' );

			// Remove data of a previous comment
			jQuery( 'a.comment_reply_current' ).remove();
			jQuery( 'input[name=reply_ID]' ).remove();
			jQuery( 'a.comment_reply' ).removeClass( 'active' ).html( evo_init_comment_reply_config.reply_button_msg );

			// Add data for a current comment
			var link_back_comment = '<a href="' + evo_init_comment_reply_config.link_back_url + '#c' + comment_ID + '" class="comment_reply_current" rel="' + comment_ID + '">'
					+ evo_init_comment_reply_config.link_back_specific_comment_msg + '</a>';
			var hidden_reply_ID = '<input type="hidden" name="reply_ID" value="' + comment_ID + '" />';
			jQuery( '#evo_comment_form_id_' + evo_init_comment_reply_config.item_ID ).prepend( link_back_comment + hidden_reply_ID );
			jQuery( this ).addClass( 'active' ).html( evo_init_comment_reply_config.link_back_current_comment_msg );

			// Scroll to the comment form
			jQuery( window ).scrollTop( jQuery( '#evo_comment_form_id_' + evo_init_comment_reply_config.item_ID ).offset().top - 30 );

			return false;
		} );

		jQuery( document ).on( 'click', 'a.comment_reply_current', function()
		{	// The click action for a link "You are currently replying to a specific comment"
			var comment_ID = jQuery( this ).attr( 'rel' );

			// Scroll to the comment
			jQuery( window ).scrollTop( jQuery( 'a#c' + comment_ID ).offset().top - 10 );

			return false;
		} );
	}

	// disp=download
	if( typeof( evo_disp_download_delay_config ) != 'undefined' )
	{
		window.b2evo_download_timer = evo_disp_download_delay_config;
		window.downloadInterval = setInterval( function()
			{
				jQuery( "#download_timer" ).html( window.b2evo_download_timer );
				if( window.b2evo_download_timer == 0 )
				{	// Stop timer and download a file:
					clearInterval( window.downloadInterval );
					jQuery( "#download_help_url" ).show();
				}
				window.b2evo_download_timer--;
			}, 1000 );

		jQuery( "#download_timer_js" ).show();
	}

	// Bootstrap Forums skin: Add click event to quote button
	if( typeof( evo_skin_bootstrap_forum__quote_button_click ) != 'undefined' )
	{
		jQuery( '.quote_button' ).click( function()
			{	// Submit a form to save the already entered content
				var form = jQuery( 'form[id^=evo_comment_form_id_]' );
				if( form.length == 0 )
				{	// No form found, Use an url of this link
					return true;
				}
				// Set an action as url of this link and submit a form
				form.attr( 'action', jQuery( this ).attr( 'href' ) );
				form.submit();
				return false;
			} );
	}

	// Ajax Form
	if( typeof( evo_ajax_form_config ) != 'undefined' )
	{
		var evo_ajax_forms = Object.values( evo_ajax_form_config );
		for( var i = 0; i < evo_ajax_forms.length; i++ )
		{
			( function() {
				var config = evo_ajax_forms[i];

				window['ajax_form_offset_' + config.form_number]         = jQuery( '#ajax_form_number_' + config.form_number ).offset().top;
				window['request_sent_' + config.form_number]             = false;
				window['ajax_form_loading_number_' + config.form_number] = 0;

				var get_form_func_name = 'get_form_' + config.form_number;
				window[get_form_func_name] = function()
					{
						var form_id = '#ajax_form_number_' + config.form_number;
						window['ajax_form_loading_number_' + config.form_number]++;
						jQuery.ajax({
							url: htsrv_url + 'anon_async.php',
							type: 'POST',
							data: config.json_params,
							success: function( result )
								{
									jQuery( form_id ).html( ajax_debug_clear( result ) );
								},
							error: function( jqXHR, textStatus, errorThrown )
								{
									jQuery( '.loader_ajax_form', form_id ).after( '<div class="red center">' + errorThrown + ': ' + jqXHR.responseText + '</div>' );
									if( window['ajax_form_loading_number_' + config.form_number] < 3 )
									{	// Try to load 3 times this ajax form if error occurs:
										setTimeout( function()
											{	// After 1 second delaying:
												jQuery( '.loader_ajax_form', form_id ).next().remove();
												window[get_form_func_name]();
											}, 1000 );
									}
								}
						});
					}

				var check_and_show_func_name = 'check_and_show_' + config.form_number;
				window[check_and_show_func_name] = function( force_load )
					{
						if( window['request_sent_' + config.form_number] )
						{	// Don't load the form twice:
							return;
						}
						var load_form = ( typeof force_load == undefined ) ? false : force_load;
						if( ! load_form )
						{	// Check if the ajax form is visible, or if it will be visible soon ( 20 pixel ):
							load_form = jQuery( window ).scrollTop() >= window['ajax_form_offset_' + config.form_number] - jQuery( window ).height() - 20;
						}
						if( load_form )
						{	// Load the form only if it is forced or allowed because page is scrolled down to the form position:
							window['request_sent_' + config.form_number] = true;
							window[get_form_func_name]();
						}
					};

				jQuery( window ).scroll( function() {
						window[check_and_show_func_name]();
					});
		
				jQuery(window).resize( function() {
						window[check_and_show_func_name]();
					});

				window[check_and_show_func_name]( config.load_ajax_form_on_page_load );
			} )();
		}
	}

	// Thread Form
	if( typeof( evo_thread_form_config ) != 'undefined' )
	{
		/**
		 * Show the multiple recipients radio selection if the number of recipients is more than one
		 */
		window.check_multiple_recipients = function check_multiple_recipients()
			{
				if( jQuery( 'input[name="thrd_recipients_array[login][]"]' ).length > 1 )
				{
					jQuery( '#multiple_recipients' ).show();
				}
				else
				{
					jQuery( '#multiple_recipients' ).hide();
				}
			}

		/**
		 * Check form fields before send a thread data
		 *
		 * @return boolean TRUE - success filling of the fields, FALSE - some erros, stop a submitting of the form
		 */
		window.check_form_thread = function check_form_thread()
			{
				if( jQuery( 'input#token-input-thrd_recipients' ).val() != '' )
				{	// Don't submit a form with incomplete username
					alert( evo_thread_form_config['missing_username_msg'] );
					jQuery( 'input#token-input-thrd_recipients' ).focus();
					return false;
				}

				return true;
			};

		// TokenInput config:
		evo_thread_form_config.token_input_config.tokenFormatter = function( user )
			{
				return '<li>' + user[evo_thread_form_config.username_display] +
						'<input type="hidden" name="thrd_recipients_array[id][]" value="' + user.id + '" />' +
						'<input type="hidden" name="thrd_recipients_array[login][]" value="' + user.login + '" />' +
					'</li>';
			};
		evo_thread_form_config.token_input_config.resultsFormatter = function( user )
			{
				var title = user.login;
				if( user.fullname != null && user.fullname !== undefined )
				{
					title += '<br />' + user.fullname;
				}
				return '<li>' +
						user.avatar +
						'<div>' +
							title +
						'</div><span></span>' +
					'</li>';
			};
		evo_thread_form_config.token_input_config.onAdd = function()
			{
				window.check_multiple_recipients();
			};
		evo_thread_form_config.token_input_config.onDelete = function()
			{
				window.check_multiple_recipients();
			};

		evo_thread_form_config.token_input_config.onReady = function()
			{
				if( evo_thread_form_config.thrd_recipients_has_error )
				{	// Mark this field as error
					jQuery( '.token-input-list-facebook' ).addClass( 'token-input-list-error' );
				}
				// Remove required attribute to prevent unfocusable field error during validation checking when the field is hidden:
				jQuery( '#thrd_recipients' ).removeAttr( 'required' );
			};

		jQuery( '#thrd_recipients' ).tokenInput(
				restapi_url + 'users/recipients',
				evo_thread_form_config.token_input_config
			);

		// Run check on multiple recipients
		window.check_multiple_recipients();
	}

	// Comment Form: Preview Button
	if( typeof( evo_comment_form_preview_button_config ) != 'undefined' )
	{
		jQuery( "input[type=submit].preview.btn-info" ).val( evo_comment_form_preview_button_config.button_value );
	}

	// Userlist filter callback JS
	if( typeof( evo_user_func__callback_filter_userlist ) != 'undefined' )
	{
		jQuery( '#country' ).change( function()
			{
				var this_obj = jQuery( this );
				jQuery.ajax( {
				type: 'POST',
				url: htsrv_url + 'anon_async.php',
				data: 'action=get_regions_option_list&ctry_id=' + jQuery( this ).val(),
				success: function( result )
					{
						jQuery( '#region' ).html( ajax_debug_clear( result ) );
						if( jQuery( '#region option' ).length > 1 )
						{
							jQuery( '#region_filter' ).show();
						}
						else
						{
							jQuery( '#region_filter' ).hide();
						}
						window.load_subregions( 0 ); // Reset sub-regions
					}
				} );
			} );

		jQuery( '#region' ).change( function ()
			{	// Change option list with sub-regions
				window.load_subregions( jQuery( this ).val() );
			} );

		jQuery( '#subregion' ).change( function ()
			{	// Change option list with cities
				window.load_cities( jQuery( '#country' ).val(), jQuery( '#region' ).val(), jQuery( this ).val() );
			} );

		window.load_subregions = function load_subregions( region_ID )
			{	// Load option list with sub-regions for seleted region
				jQuery.ajax( {
				type: 'POST',
				url: htsrv_url + 'anon_async.php',
				data: 'action=get_subregions_option_list&rgn_id=' + region_ID,
				success: function( result )
					{
						jQuery( '#subregion' ).html( ajax_debug_clear( result ) );
						if( jQuery( '#subregion option' ).length > 1 )
						{
							jQuery( '#subregion_filter' ).show();
						}
						else
						{
							jQuery( '#subregion_filter' ).hide();
						}
						load_cities( jQuery( '#country' ).val(), region_ID, 0 );
					}
				} );
			};

		window.load_cities = function load_cities( country_ID, region_ID, subregion_ID )
			{	// Load option list with cities for seleted region or sub-region
				if( typeof( country_ID ) == 'undefined' )
				{
					country_ID = 0;
				}

				jQuery.ajax( {
				type: 'POST',
				url: htsrv_url + 'anon_async.php',
				data: 'action=get_cities_option_list&ctry_id=' + country_ID + '&rgn_id=' + region_ID + '&subrg_id=' + subregion_ID,
				success: function( result )
					{
						jQuery( '#city' ).html( ajax_debug_clear( result ) );
						if( jQuery( '#city option' ).length > 1 )
						{
							jQuery( '#city_filter' ).show();
						}
						else
						{
							jQuery( '#city_filter' ).hide();
						}
					}
				} );
			};
	}

	// Collection Activity Stats widget
	if( typeof( coll_activity_stats_widget_config ) != 'undefined' )
	{
		window.coll_activity_stats_widget_resize_timer;

		window.resize_coll_activity_stat_widget = function resize_coll_activity_stat_widget()
			{
				var charts = Object.values( window.activity_stats_widgets );
				for( var m = 0; m < charts.length; m++ )
				{
					var plot = charts[m];

					if( plot._original == undefined )
					{
						
						plot._display = coll_activity_stats_widget_config['time_period'];
						plot._original = {
							data: [],
							xLabels: [],
						};

						for( var i = 0; i < plot.series.length; i++ )
						{
							plot._original.data.push(plot.series[i].data);
						}
						plot._original.xLabels = plot.axes.xaxis.ticks;
					}

					if( plot._week == undefined )
					{
						plot._week = {
							data: [],
							xLabels: [],
						}
	
						if( plot._original.xLabels.length == 7 )
						{	// Already weekly data:
							plot._week = plot._original;
						}
						else
						{	// Extract last week data:
							for( var i = 0; i < plot._original.data.length; i++ )
							{
								var weekSeries = [];
								for( var j = 7, k = 1; j > 0; j--, k++ )
								{
									weekSeries.unshift( [ j, plot._original.data[i][plot._original.data[i].length - k][1] ] );
								}
								plot._week.data.push( weekSeries );
								plot._week.xLabels = plot._original.xLabels.slice( -7 );
							}
						}
					}

					if( jQuery( plot.target ).width() < 650 )
					{	// Switch to last week's view:
						if( plot._display != 'last_week' )
						{
							for( var i = 0; i < plot.series.length; i++ )
							{
								plot.series[i].data = plot._week.data[i];
							}
							plot.axes.xaxis.ticks = plot._week.xLabels;
							plot._display = 'last_week';
						}
					}
					else
					{	// Switch to last month's view:
						if( plot._display != 'last_month' )
						{
							for( var i = 0; i < plot.series.length; i++ )
							{
								plot.series[i].data = plot._original.data[i];
							}
							plot.axes.xaxis.ticks = plot._original.xLabels;
							plot._display = 'last_month';
						}
					}

					plot.replot( { resetAxes: true } );
				}
			};

		jQuery( window ).resize( function()
			{
				clearTimeout( window.coll_activity_stats_widget_resize_timer );
				window.coll_activity_stats_widget_resize_timer = setTimeout( window.resize_coll_activity_stat_widget, 100 );
			} );
	}

	// Item Tags widget
	if( typeof( evo_item_tags_widget_config ) != 'undefined' )
	{
		window.add_quick_tag = function ( input_ID, obj )
			{
				var item_tag = jQuery( obj ).text();
				jQuery( '#item_tags_' + input_ID ).tokenInput( 'add', { id: item_tag, name: item_tag } );
			};

		window.init_edit_item_tags_icon = function ( input_ID, widget_ID )
			{
				jQuery( '#evo_widget_item_tags_edit_icon_' + widget_ID ).click( function()
					{
						jQuery( '#evo_widget_item_tags_edit_form_' + widget_ID ).show();
						jQuery( 'input#token-input-' + input_ID, '#evo_widget_item_tags_edit_form_' + widget_ID ).focus();
						jQuery( '#evo_widget_item_tags_list_' + widget_ID ).hide();
						return false;
					} );
			};

		var evo_temp_configs = Object.values( evo_item_tags_widget_config );
		for( var i = 0; i < evo_temp_configs.length; i++ )
		{
			( function() {
				var config = evo_temp_configs[i];
				window.init_edit_item_tags_icon( config.input_ID, config.widget_ID );
			} )();
		}
		delete evo_temp_configs;
	}

	// Workflow Properties widget
	if( typeof( evo_workflow_properties_widget_config ) != 'undefined' )
	{
		jQuery( '[id^=evo_widget_item_workflow_properties_] a' ).click( function() {
				var workflow_properties = jQuery( this ).closest( '[id^=evo_widget_item_workflow_properties_]' ),
					workflow_form = workflow_properties.next( '[id^=evo_widget_item_workflow_form_]' );

				workflow_form.show();
				workflow_properties.hide();

				return false;
			} );
	}

	// Display attachments fieldset
	if( typeof( evo_display_attachments_fieldset_config ) != 'undefined' )
	{
		( function() {
			var evo_display_attachments_fieldset_configs = Object.values( evo_display_attachments_fieldset_config );
			for( var i = 0; i < evo_display_attachments_fieldset_configs.length; i++ )
			{
				jQuery( '#' + evo_display_attachments_fieldset_configs[i].fieldset_prefix + evo_display_attachments_fieldset_configs[i].form_id ).show();
			}
		} )();
	}

	// Tabs AJAX load JS
	if( typeof( evo_tabs_ajax_load__click ) != 'undefined' )
	{
		jQuery( '[data-toggle="tabajax"]' ).click( function( e ) {
			var $this = jQuery( this ),
				loadurl = $this.attr( 'href' ),
				target =  $this.attr( 'data-target' );

			if( ! $this.closest( 'li' ).hasClass( 'active' ) )
			{
				jQuery.get( loadurl, function( data ) {
						jQuery( target ).html( data );
					} );
				$this.tab( 'show' );
			}
			return false;
		} );
	}

	// Contact List view
	if( typeof( evo_contact_list_view_config ) != 'undefined' )
	{
		window.get_selected_users = function get_selected_users()
			{
				var users = '';
				jQuery( 'input[name^=contacts]' ).each( function()
				{
					if( jQuery( this ).is( ':checked' ) )
					{
						users += jQuery( this ).val() + ',';
					}
				} );

				if( users.length > 0 )
				{	// Delete last comma
					users = users.substr( 0, users.length-1 );
				}

				return users;
			};

		jQuery( '#send_selected_recipients' ).click( function()
			{	// Add selected users to this link
				var recipients_param = '';
				var recipients = window.get_selected_users();
				if( recipients.length > 0 )
				{
					recipients_param = '&recipients=' + recipients;
				}

				location.href = evo_contact_list_view_config.recipients_link_url + recipients_param;
				
				return false;
			} );

		jQuery( '#add_group_contacts' ).submit( function()
			{
				jQuery( 'input[name=users]' ).val( window.get_selected_users() );
			} );
	}

	// User Identity Form
	if( typeof( evo_user_identity_form_config ) != 'undefined' )
	{
		( function() {

			var config = evo_user_identity_form_config;

			window.replace_form_params = function replace_form_params( result, field_id )
				{
					field_id = ( typeof( field_id ) == 'undefined' ? '' : ' id="' + field_id + '"' );
					return result
						.replace( '#fieldstart#', config.fieldstart )
						.replace( '#fieldend#', config.fieldend )
						.replace( '#labelclass#', config.labelclass )
						.replace( '#labelstart#', config.labelstart )
						.replace( '#labelend#', config.labelend )
						.replace( '#inputstart#', config.inputstart )
						.replace( '#inputend#', config.inputend );
				};

			jQuery( '#button_add_field' ).click( function ()
				{	// Action for the button when we want to add a new field in the Additional info
					var field_id = jQuery( this ).prev().find( 'option:selected' ).val();

					if( field_id == '' )
					{	// Mark select element of field types as error
						window.field_type_error( config.msg_select_field_type );
						// We should to stop the ajax request without field_id
						return false;
					}
					else
					{	// Remove an error class from the field
						window.field_type_error_clear();
					}

					var params = config.params;

					jQuery.ajax({
						type: 'POST',
						url: htsrv_url + 'anon_async.php',
						data: 'action=get_user_new_field&user_id=' + config.user_ID + '&field_id=' + field_id + params,
						success: function( result )
							{
								result = ajax_debug_clear( result );
								if( result == '[0]' )
								{	// This field(not duplicated) already exists for current user
									window.field_type_error( config.msg_field_already_added );
								}
								else
								{
									result = window.replace_form_params( result );
									var field_duplicated = parseInt( result.replace( /^\[(\d+)\](.*)/, '$1' ) );
									if( field_duplicated == 0 )
									{	// This field is NOT duplicated
										var field_id = parseInt( result.replace( /(.*)fieldset id="ffield_uf_add_(\d+)_(.*)/, '$2' ) );
										// Remove option from select element
										jQuery( '#new_field_type option[value='+field_id+']').remove();
										if( jQuery( '[id^=uf_new_' + field_id + '], [id^=uf_add_' + field_id + ']' ).length > 0 )
										{	// This field already exists(on the html form, not in DB) AND user cannot add a duplicate
											window.field_type_error( config.msg_field_already_added );
											return false;
										}
									}
									// Print out new field on the form
									jQuery( '#ffield_new_field_type' ).before( result.replace( /^\[\d+\](.*)/, '$1' ) );
									// Show a button 'Add(+)' with new field
									jQuery( 'span[rel^=add_ufdf_]' ).show();

									bind_autocomplete( jQuery( '#ffield_new_field_type' ).prev().prev().find( 'input[id^=uf_add_][autocomplete=on]' ) );
								}
							}
						} );

					return false;
				} );

			jQuery( document ).on( 'focus', '[rel^=ufdf_]', function ()
				{	// Auto select the value for the field of type
					var field_id = parseInt( jQuery( this ).attr( 'rel' ).replace( /^ufdf_(\d+)$/, '$1' ) );
					if( field_id > 0 )
					{	// Select an option with current field type
						jQuery( '#new_field_type' ).val( field_id );
						window.field_type_error_clear();
					}
				} );

			jQuery( '#new_field_type' ).change( function ()
				{	// Remove all errors messages from field "Add a field of type:"
					window.field_type_error_clear();
				} );

			window.field_type_error = function field_type_error( message )
				{	// Add an error message for the "field of type" select
					jQuery( 'select#new_field_type' ).addClass( 'field_error' );
					var span_error = jQuery( 'select#new_field_type' ).next().find( 'span.field_error' );
					if( span_error.length > 0 )
					{	// Replace a content of the existing span element
						span_error.html( message );
					}
					else
					{	// Create a new span element for error message
						jQuery( 'select#new_field_type' ).next().append( '<span class="field_error">' + message + '</span>' );
					}
				};

			window.field_type_error_clear = function field_type_error_clear()
				{	// Remove an error style from the "field of type" select
					jQuery( 'select#new_field_type' ).removeClass( 'field_error' ).next().find( 'span.field_error' ).remove();
				};

			/*
			jQuery( 'span[rel^=add_ufdf_]' ).each( function()
				{	// Show only last button 'Add(+)' for each field type
					// These buttons is hidden by default to ignore browsers without javascript
					jQuery( 'span[rel=' + jQuery( this ).attr( 'rel' ) + ']:last' ).show();
				} );
			*/ 

			// Show a buttons 'Add(+)' for each field
			// These buttons is hidden by default to ignore a browsers without javascript
			jQuery( 'span[rel^=add_ufdf_]' ).show();

			jQuery( document ).on( 'click', 'span[rel^=add_ufdf_]', function()
				{	// Click event for button 'Add(+)'
					var this_obj = jQuery( this );
					var field_id = this_obj.attr( 'rel' ).replace( /^add_ufdf_(\d+)$/, '$1' );
					var params = config.params;

					jQuery.ajax( {
						type: 'POST',
						url: htsrv_url + 'anon_async.php',
						data: 'action=get_user_new_field&user_id=' + config.user_ID + '&field_id=' + field_id + params,
						success: function( result )
							{
								result = ajax_debug_clear( result );
								if( result == '[0]' )
								{	// This field(not duplicated) already exists for current user
									window.field_type_error( config.msg_field_already_added );
								}
								else
								{
									result = window.replace_form_params( result );
									var field_duplicated = parseInt( result.replace( /^\[(\d+)\](.*)/, '$1' ) );
									if( field_duplicated == 0 )
									{	// This field is NOT duplicated
										window.field_type_error( config.msg_field_already_added );
										return false;
									}
									var cur_fieldset_obj = this_obj.parent().parent().parent();
									
									/*
									// Remove current button 'Add(+)' and then we will show button with new added field
									this_obj.remove();
									*/

									// Print out new field on the form
									cur_fieldset_obj.after( result.replace( /^\[\d+\](.*)/, '$1' ) )
									// Show a button 'Add(+)' with new field
																	.next().find( 'span[rel^=add_ufdf_]' ).show();

									var new_field = cur_fieldset_obj.next().find( 'input[id^=uf_add_]' );
									if( new_field.attr( 'autocomplete' ) == 'on' )
									{	// Bind autocomplete event
										bind_autocomplete( new_field );
									}
									// Set auto focus on new created field
									new_field.focus();
								}
							}
						} );
				} );

			jQuery( document ).on( 'mouseover', 'span[rel^=add_ufdf_]', function()
				{	// Grab event from input to show bubbletip
					jQuery( this ).parent().prev().focus();
					jQuery( this ).css( 'z-index', jQuery( this ).parent().prev().css( 'z-index' ) );
				} );

			jQuery( document ).on( 'mouseout', 'span[rel^=add_ufdf_]', function()
				{	// Grab event from input to hide bubbletip
					var input = jQuery( this ).parent().prev();
					if( input.is( ':focus' ) )
					{	// Don't hide bubbletip if current input is focused
						return false;
					}
					input.blur();
				} );

		
			// JS code to add new organization for user
			var org_fieldset_selector = '[id^="ffield_organizations_"]';
			var max_organizations = config.max_organizations;
			var user_org_num = 0;

			jQuery( document ).on( 'click', 'span.add_org', function()
				{	// Add new organization select box
					var this_obj = jQuery( this );
					var params = config.params;

					params += ( typeof( remove_obj_after_org_adding ) != 'undefined' ? '&first_org=1' : '' );
					jQuery.ajax( {
							type: 'POST',
							url: htsrv_url + 'anon_async.php',
							data: 'action=get_user_new_org' + params,
							success: function( result )
							{
								result = window.replace_form_params( ajax_debug_clear( result ), 'ffield_organizations_' + user_org_num );
								var cur_fieldset_obj = this_obj.closest( org_fieldset_selector );
								cur_fieldset_obj.after( result );

								if( typeof( remove_obj_after_org_adding ) != 'undefined' )
								{ // Delete last fieldset
									remove_obj_after_org_adding.remove();
									delete remove_obj_after_org_adding;
								}

								if( jQuery( org_fieldset_selector ).length >= max_organizations )
								{ // It was last organization, Hide all "add" buttons
									jQuery( 'span.add_org' ).hide();
								}

								// Show/Hide all "remove" buttons
								( jQuery( org_fieldset_selector ).length > 1 ) ?
									jQuery( 'span.remove_org' ).show() :
									jQuery( 'span.remove_org' ).hide();

								user_org_num++;
							}
						} );
				} );

			jQuery( document ).on( 'click', 'span.remove_org', function()
				{	// Remove organization select box
					if( jQuery( org_fieldset_selector ).length > 1 )
					{
						jQuery( this ).closest( org_fieldset_selector ).remove();
					}
					else
					{ // Add a form to select an organization
						remove_obj_after_org_adding = jQuery( this ).closest( org_fieldset_selector );
						jQuery( this ).parent().find( 'span.add_org' ).click();
					}

					if( jQuery( org_fieldset_selector ).length < max_organizations )
					{ // Show the "add" buttons
						jQuery( 'span.add_org' ).show();
					}

					// Show/Hide all "remove" buttons
					( jQuery( org_fieldset_selector ).length > 0 ) ?
						jQuery( 'span.remove_org' ).show() :
						jQuery( 'span.remove_org' ).hide();
				} );

			window.bind_autocomplete = function bind_autocomplete( field_objs )
				{	// Bind autocomplete plugin event
					if( field_objs.length > 0 )
					{	// If selected elements are exists
						field_objs.autocomplete( {
							source: function(request, response) {
								jQuery.getJSON( htsrv_url + 'anon_async.php?action=get_user_field_autocomplete', {
									term: request.term, attr_id: this.element[0].getAttribute( 'id' )
								}, response);
							},
						} );
					}
				};

			// Plugin jQuery(...).live() doesn't work with autocomplete
			// We should assign an autocomplete event for each new added field
			bind_autocomplete( jQuery( 'input[id^=uf_][autocomplete=on]' ) );
		} )();
	}

	// User Organization JS
	if( typeof( evo_user_organization_config ) != 'undefined' )
	{
		jQuery( document ).on( 'click', 'span[rel^=org_status_]', function()
			{	// Change an accept status of organization
				var this_obj = jQuery( this );
				var params = evo_user_organization_config.params;

				jQuery.ajax( {
						type: 'POST',
						url: htsrv_url + 'anon_async.php',
						data: 'action=change_user_org_status&status=' + this_obj.attr( 'rel' ) + '&crumb_userorg=' + evo_user_organization_config.crumb_userorg + params,
						success: function( result )
						{
							this_obj.after( ajax_debug_clear( result ) ).remove();
						}
					} );
			} );
	}

	// Item Type Change Buttons JS
	if( typeof( evo_item_type_change_buttons_config ) != 'undefined' )
	{
		jQuery( "button[data-item-type]" ).on( "click", function()
			{
				jQuery( "[required]" ).removeAttr( "required" );
				jQuery( "input[name=item_typ_ID]" ).val( jQuery( this ).data( "item-type" ) );
				jQuery( this ).closest( "form" )
					.append( '<input type="hidden" name="action" value="' + evo_item_type_change_buttons_config.action + '">' )
					.submit();
			} );
	}

	// Item/Comment Status Dropdown button JS
	if( typeof( evo_status_dropdown_button_config ) != 'undefined' )
	{
		var evo_status_dropdown_button_configs = Object.values( evo_status_dropdown_button_config );
		for( var i = 0; i < evo_status_dropdown_button_configs.length; i++ )
		{
			( function() {
				var config = evo_status_dropdown_button_configs[i];
				jQuery( '.' + config.type + '_status_dropdown li a' ).click( function()
					{
						var item_status_tooltips = config.tooltip_titles_js_array;
						var item = jQuery( this ).parent();
						var status = item.attr( 'rel' );
						var btn_group = item.parent().parent();
						var btn_wrapper = btn_group.parent().parent();
						var dropdown_buttons = btn_group.find( 'button' );
						var first_button = dropdown_buttons.parent().find( 'button:first' );
						var save_buttons = btn_wrapper.find( 'input[type="submit"]:not(.quick-publish)' ).add( dropdown_buttons );

						if( status == 'published' )
						{	// Hide button "Publish!" if current status is already the "published":
							btn_wrapper.find( '.quick-publish' ).hide();
						}
						else
						{	// Show button "Publish!" only when another status is selected:
							btn_wrapper.find( '.quick-publish' ).show();
						}

						save_buttons.each( function()
							{	// Change status class name to new changed for all buttons
								jQuery( this ).attr( 'class', jQuery( this ).attr( 'class' ).replace( /btn-status-[^\s]+/, 'btn-status-' + status ) );
							} );
			
						first_button.find( 'span:first' ).html( item.find( 'span:last' ).html() ); // update selector button to status title
						jQuery( 'input[type=hidden][name=' + config.type + '_status]' ).val( status ); // update hidden field to new status value
						btn_group.removeClass( 'open' ); // hide dropdown menu

						if( first_button.attr( 'type' ) == 'submit' )
						{	// Submit form if current dropdown button is used to submit form
							first_button.click();
						}

						// Change tooltip based on selected status
						btn_group.tooltip( 'hide' ).attr( 'data-original-title', item_status_tooltips[status] ).tooltip( 'show' );

						return false;
					} );
			} )();
		}
	}

	// New Category Name Input: onChange
	if( typeof( evo_init_onchange_newcat ) != 'undefined' )
	{
		jQuery( '#new_category_name' ).keypress( function()
			{
				var newcategory_radio = jQuery( '#sel_maincat_new' );
				if( ! newcategory_radio.attr('checked') )
				{
					newcategory_radio.attr('checked', true);
					jQuery( '#sel_extracat_new' ).attr('checked', true);
				}
			} );
	}

	// Autocomplete Tags JS
	if( typeof( evo_autocomplete_tags_config ) != 'undefined' )
	{
		window.init_autocomplete_tags = function init_autocomplete_tags( selector, params )
			{
				if( ! params && window.evo_autocomplete_input_tag_configs[selector] )
				{	// No params specified, use cached params:
					params = window.evo_autocomplete_input_tag_configs[selector];
				}

				if( params )
				{	// We have params we can use to initialize the autocomplete:
					var tags = jQuery( selector ).val();
					var tags_json = new Array();
					if( tags && tags.length > 0 )
					{	// Get tags from <input>
						tags = tags.split( ',' );
						for( var t in tags )
						{
							tags_json.push( { id: tags[t].trim(), name: tags[t].trim() } );
						}
						params.token_input_params.prePopulate = tags_json;
					}
					
					if( params.update_by_ajax )
					{	// Update the item tags by AJAX:
						params.token_input_params.onAdd = function( obj )
							{
								if( params.use_quick_tags )
								{
									window.evo_update_item_quick_tags( obj );
								}
								window.evo_update_item_tags_by_ajax( params.item_ID, selector, obj, 'add' );
							};
						params.token_input_params.onDelete = function( obj )
							{
								window.evo_update_item_tags_by_ajax( params.item_ID, selector, obj, 'delete' );
							};
					}

					jQuery( selector ).tokenInput( restapi_url + 'tags', params.token_input_params );
				}
			};

		window.evo_update_item_quick_tags = function evo_update_item_quick_tags( tag_object )
			{
				var item_tag = tag_object.name.trim();
				var quick_item_tags = jQuery.cookie( 'quick_item_tags' );

				if( quick_item_tags == null || quick_item_tags.length == 0 )
				{
					quick_item_tags = [];
				}
				else
				{
					quick_item_tags = quick_item_tags.split( ',' );
				}

				var tag_index = quick_item_tags.indexOf( item_tag );

				if( tag_index === -1 )
				{
					quick_item_tags.push( item_tag );
				}
				else
				{
					quick_item_tags.splice( tag_index, 1 );
					quick_item_tags.push( item_tag );
				}

				quick_item_tags = quick_item_tags.splice( -5 );
				jQuery.cookie( 'quick_item_tags', quick_item_tags.join( ',' ), {
						domain: evo_autocomplete_tags_config.cookie_domain,
						path: evo_autocomplete_tags_config.cookie_path,
					} );
			};

		window.evo_update_item_tags_by_ajax = function evo_update_item_tags_by_ajax( item_ID, tags_selector, tag_object, operation, use_quick_tags )
			{
				// Mark input background with yellow color during AJAX updating:
				var token_input = jQuery( '.token-input-' + tags_selector.substr( 1 ) );
				token_input.removeClass( 'token-input-list-error' ).addClass( 'token-input-list-process' );
				jQuery.ajax( {
						type: 'POST',
						url: htsrv_url + 'action.php',
						data: {
								'mname': 'collections',
								'action': 'update_tags',
								'item_ID': item_ID,
								'item_tags': jQuery( tags_selector ).val(),
								'crumb_collections_update_tags': evo_autocomplete_tags_config.crumb_collections_update_tags,
							},
						success: function()
							{	// Remove yellow background from input after success AJAX updating:
								token_input.removeClass( 'token-input-list-process' );
							},
						error: function()
							{	// Mark input background with red color after fail AJAX updating:
								token_input.removeClass( 'token-input-list-process' ).addClass( 'token-input-list-error' );
							}
					} );
			};

		// Initialize autocomplete input tags:
		window.evo_autocomplete_input_tag_configs = window.evo_autocomplete_input_tag_configs || {};
		var evo_temp_config = Object.values( evo_autocomplete_input_tags_config );
		for( var i = 0, n = evo_temp_config.length; i < n; i++ )
		{
			( function() {
				var config = evo_temp_config[i];
				var input_ID = '#' + config.input_ID;

				// Cache configuration for later initialization, see "init_autocomplete_tags()":
				window.evo_autocomplete_input_tag_configs[input_ID] = config;

				if( jQuery( '#suggest_item_tags' ).length == 0 || jQuery( '#suggest_item_tags' ).is( ':checked' ) )
				{
					window.init_autocomplete_tags( input_ID, config );
				}

				jQuery( '#suggest_item_tags' ).click( function()
					{
						if( jQuery( this ).is( ':checked' ) )
						{	// Use plugin to suggest tags
							jQuery( input_ID ).hide();
							window.init_autocomplete_tags( input_ID );
						}
						else
						{	// Remove autocomplete tags plugin
							jQuery( input_ID ).show();
							jQuery( input_ID ).parent().find( 'ul.token-input-list-facebook' ).remove();
						}
					} );

				// Don't submit form on keypress Enter when user is editing the tags:
				evo_prevent_key_enter( '#token-input-' + config.input_ID );
			} )();
		}
		delete evo_temp_config;
	}

	// Fieldset Folding JS
	if( typeof( evo_fieldset_folding_config ) != 'undefined' )
	{
		jQuery( document ).on( 'click', 'span[id^=icon_folding_], span[id^=title_folding_]', function()
			{
				var is_icon = jQuery( this ).attr( 'id' ).match( /^icon_folding_/ );
				var wrapper_obj = jQuery( this ).closest( '.fieldset_wrapper' );
				var value_obj = is_icon ? jQuery( this ).prev() : jQuery( this ).prev().prev();

				if( wrapper_obj.length == 0 || value_obj.length == 0 )
				{	// Invalid layout
					return false;
				}

				if( value_obj.val() == '1' )
				{	// Collapse
					wrapper_obj.removeClass( 'folded' );
					value_obj.val( '0' );
				}
				else
				{	// Expand
					wrapper_obj.addClass( 'folded' );
					value_obj.val( '1' );
				}

				// Change icon image
				var clickimg = is_icon ? jQuery( this ) : jQuery( this ).prev();
				if( clickimg.hasClass( 'fa' ) || clickimg.hasClass( 'glyphicon' ) )
				{	// Fontawesome icon | Glyph bootstrap icon
					if( clickimg.data( 'toggle' ) != '' )
					{	// This icon has a class name to toggle
						var icon_prefix = ( clickimg.hasClass( 'fa' ) ? 'fa' : 'glyphicon' );
						if( clickimg.data( 'toggle-orig-class' ) == undefined )
						{	// Store original class name in data
							clickimg.data( 'toggle-orig-class', clickimg.attr( 'class' ).replace( new RegExp( '^'+icon_prefix+' (.+)$', 'g' ), '$1' ) );
						}
						if( clickimg.hasClass( clickimg.data( 'toggle-orig-class' ) ) )
						{	// Replace original class name with exnpanded
							clickimg.removeClass( clickimg.data( 'toggle-orig-class' ) )
								.addClass( icon_prefix + '-' + clickimg.data( 'toggle' ) );
						}
						else
						{	// Revert back original class
							clickimg.removeClass( icon_prefix + '-' + clickimg.data( 'toggle' ) )
								.addClass( clickimg.data( 'toggle-orig-class' ) );
						}
					}
				}
				else
				{	// Sprite icon
					var icon_bg_pos = clickimg.css( 'background-position' );
					clickimg.css( 'background-position', clickimg.data( 'xy' ) );
					clickimg.data( 'xy', icon_bg_pos );
				}

				// Toggle title
				var title = clickimg.attr( 'title' );
				clickimg.attr( 'title', clickimg.data( 'title' ) );
				clickimg.data( 'title', title );
			} );

		jQuery( 'input[type=hidden][id^=folding_value_]' ).each( function()
			{	// Check each feildset is folded correctly after refresh a page
				var wrapper_obj = jQuery( this ).closest( '.fieldset_wrapper' );
				if( jQuery( this ).val() == '1' )
				{	// Collapse
					wrapper_obj.addClass( 'folded' );
				}
				else
				{	// Expand
					wrapper_obj.removeClass( 'folded' );
				}
			} );

		// Expand all fieldsets that have the fields with error
		jQuery( '.field_error' ).closest( '.fieldset_wrapper.folded' ).find( 'span[id^=icon_folding_]' ).click();
	}

	// Clipboard JS - Item List
	if( typeof( evo_init_item_list_clipboard_js ) != 'undefined' )
	{
		window.clipboard = new ClipboardJS( 'a.clipboard-copy' );
	}

	// Item Checklist JS - inc/items/views/_item_expert.form.php
	if( typeof( evo_item_checklist_config ) != 'undefined' )
	{
		window.update_checklist_tab_badge = function() 
			{
				var checklist_badge = document.getElementById( 'checklist_counter' );
				var unchecked_line_count = document.querySelectorAll( '.checklist_lines .checklist_line input[type="checkbox"]:not(:checked)' ).length;
				
				if( checklist_badge )
				{
					if( unchecked_line_count > 0 )
					{	// Update checklist counter badge:
						checklist_badge.innerHTML = unchecked_line_count;
					}
					else
					{	// Remove checklist counter badge:
						checklist_badge.remove();
					}
				}
				else if( unchecked_line_count > 0 )
				{	// Create checklist counter badge:
					var checklist_tab = document.querySelector( 'a[href="#checklist"][data-toggle="tab"]' );
					if( checklist_tab )
					{
						checklist_badge = document.createElement( 'span' );
						checklist_badge.classList.add( 'badge', 'badge-important' );
						checklist_badge.innerHTML = unchecked_line_count;
						checklist_badge.setAttribute( 'id', 'checklist_counter' );
						checklist_tab.appendChild( document.createTextNode( ' ' ) );
						checklist_tab.appendChild( checklist_badge );
					}
				}
			};

		jQuery( '.checklist_lines' ).on( 'click', '.checklist_line input[type="checkbox"]', window.update_checklist_tab_badge );
	}
} );
