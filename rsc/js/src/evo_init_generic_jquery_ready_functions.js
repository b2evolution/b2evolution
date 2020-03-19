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
				{ // No form found, Use an url of this link
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
			var config = evo_ajax_forms[i];

			window['ajax_form_offset_' + config['form_number']] = jQuery( '#ajax_form_number_' + config['form_number'] ).offset().top;
			window['request_sent_' + config['form_number']] = false;
			window['ajax_form_loading_number_' + config['form_number']] = 0;

			var get_form_func_name = 'get_form' + config['form_number']
			window[get_form_func_name] = function()
				{
					var form_id = '#ajax_form_number_' + config['form_number'];
					window['ajax_form_loading_number_' + config['form_number']]++;
					jQuery.ajax({
						url: htsrv_url + 'anon_async.php',
						type: 'POST',
						data: config['json_params'],
						success: function(result)
						{
							jQuery( form_id ).html( ajax_debug_clear( result ) );

							if( config['json_params'].action == 'get_comment_form' )
							{	// Call function to render the star ratings for AJAX comment forms:
								evo_render_star_rating();
							}
						},
						error: function( jqXHR, textStatus, errorThrown )
						{
							jQuery( '.loader_ajax_form', form_id ).after( '<div class="red center">' + errorThrown + ': ' + jqXHR.responseText + '</div>' );
							if( window['ajax_form_loading_number_' + config['form_number']] < 3 )
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

			var check_and_show_func_name = 'check_and_show_' + config['form_number']
			window[check_and_show_func_name] = function( force_load )
				{
					if( window['request_sent_' + config['form_number']] )
					{	// Don't load the form twice:
						return;
					}
					var load_form = ( typeof force_load == undefined ) ? false : force_load;
					if( ! load_form )
					{	// Check if the ajax form is visible, or if it will be visible soon ( 20 pixel ):
						load_form = jQuery( window ).scrollTop() >= window['ajax_form_offset_' + config['form_number']] - jQuery(window).height() - 20;
					}
					if( load_form )
					{	// Load the form only if it is forced or allowed because page is scrolled down to the form position:
						window['request_sent_' + config['form_number']] = true;
						window[get_form_func_name]();
					}
				}

			jQuery( window ).scroll( function() {
				window[check_and_show_func_name]();
			});
	
			jQuery(window).resize( function() {
				window[check_and_show_func_name]();
			});

			window[check_and_show_func_name]( config['load_ajax_form_on_page_load'] );
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
						load_subregions( 0 ); // Reset sub-regions
					}
				} );
			} );

		jQuery( '#region' ).change( function ()
			{	// Change option list with sub-regions
				load_subregions( jQuery( this ).val() );
			} );

		jQuery( '#subregion' ).change( function ()
			{	// Change option list with cities
				load_cities( jQuery( '#country' ).val(), jQuery( '#region' ).val(), jQuery( this ).val() );
			} );

		window['load_subregions'] = function load_subregions( region_ID )
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

		window['load_cities'] = function load_cities( country_ID, region_ID, subregion_ID )
			{ // Load option list with cities for seleted region or sub-region
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
		var resizeTimer;

		window['resize_coll_activity_stat_widget'] = function resize_coll_activity_stat_widget()
			{
				var config = coll_activity_stats_widget_config;
				var	originalData = [], weekData = [], xLabels = [],
						displayed = config['time_period'];

				if( plot == undefined )
				{
					plot = jQuery( '#canvasbarschart' ).data( 'plot' );
					xLabels = plot.axes.xaxis.ticks.slice(0);
					for( var i = 0; i < plot.series.length; i++ )
					{
						originalData.push( plot.series[i].data.slice(0) );
					}

					if( originalData[0].length == 7 )
					{
						weekData = originalData;
					}
					else
					{
						for( var i = 0; i < originalData.length; i++ )
						{
							var weekSeries = [];
							for( var j = 7, k = 1; j > 0; j--, k++ )
							{
								weekSeries.unshift( [ j, originalData[i][originalData[i].length - k][1] ] );
							}
							weekData.push( weekSeries );
						}
					}
				}

				if( jQuery( '#canvasbarschart' ).width() < 650 )
				{
					if( displayed != 'last_week' )
					{
						for( var i = 0; i < plot.series.length; i++ )
						{
							plot.series[i].data = weekData[i];
						}
						plot.axes.xaxis.ticks = xLabels.slice( -7 );
						displayed = 'last_week';
					}
				}
				else
				{
					if( displayed != 'last_month' )
					{
						for( var i = 0; i < plot.series.length; i++ )
						{
							plot.series[i].data = originalData[i];
						}
						plot.axes.xaxis.ticks = xLabels;
						displayed = 'last_month';
					}
				}
				
				plot.replot( { resetAxes: true } );
			}

		jQuery( window ).resize( function()
			{
				clearTimeout( resizeTimer );
				resizeTimer = setTimeout( resize_coll_activity_stat_widget, 100 );
			} );
	}
	

	// Link function: Link Sortable JS
	if( typeof( evo_link_sortable_js_config ) != 'undefined' )
	{
		var evo_link_sortable_js_configs = Object.values( evo_link_sortable_js_config );
		for( var i = 0; i < evo_link_sortable_js_configs.length; i++ )
		{
			jQuery( '#' + evo_link_sortable_js_configs[i].fieldset_prefix + 'attachments_fieldset_table table' ).sortable(
				{
					containerSelector: 'table',
					itemPath: '> tbody',
					itemSelector: 'tr',
					placeholder: jQuery.parseHTML( '<tr class="placeholder"><td colspan="5"></td></tr>' ),
					onMousedown: function( $item, _super, event )
						{
							if( ! event.target.nodeName.match( /^(a|img|select|span)$/i ) )
							{	// Ignore a sort action when mouse is clicked on the tags <a>, <img>, <select> or <span>
								event.preventDefault();
								return true;
							}
						},
					onDrop: function( $item, container, _super )
						{
							jQuery( '#' + evo_link_sortable_js_configs[i].fieldset_prefix + 'attachments_fieldset_table table tr' ).removeClass( 'odd even' );
							jQuery( '#' + evo_link_sortable_js_configs[i].fieldset_prefix + 'attachments_fieldset_table table tr:odd' ).addClass( 'even' );
							jQuery( '#' + evo_link_sortable_js_configs[i].fieldset_prefix + 'attachments_fieldset_table table tr:even' ).addClass( 'odd' );
				
							var link_IDs = '';
							jQuery( '#' + evo_link_sortable_js_configs[i].fieldset_prefix + 'attachments_fieldset_table table tr' ).each( function()
								{
									var link_ID_cell = jQuery( this ).find( '.link_id_cell > span[data-order]' );
									if( link_ID_cell.length > 0 )
									{
										link_IDs += link_ID_cell.html() + ',';
									}
								} );
							link_IDs = link_IDs.slice( 0, -1 );
				
							jQuery.ajax(
							{
								url: htsrv_url + 'anon_async.php',
								type: 'POST',
								data:
									{
										'action': 'update_links_order',
										'links': link_IDs,
										'crumb_link': evo_link_sortable_js_configs[i].crumb_link,
									},
								success: function( data )
									{
										link_data = JSON.parse( ajax_debug_clear( data ) );
										// Update data-order attributes
										jQuery( '#' + evo_link_sortable_js_configs[i].fieldset_prefix + 'attachments_fieldset_table table tr' ).each( function()
										{
											var link_ID_cell = jQuery( this ).find( '.link_id_cell > span[data-order]' );
											if( link_ID_cell.length > 0 )
											{
												link_ID_cell.attr( 'data-order', link_data[link_ID_cell.html()] );
											}
										} );
										evoFadeSuccess( $item );
									}
							} );
				
							$item.removeClass( container.group.options.draggedClass ).removeAttr("style");
						}
				} );
		}
	}

	// Link initialize fieldset
	if( typeof( evo_link_initialize_fieldset_config ) != 'undefined' )
	{
		var evo_link_initialize_fieldset_configs = Object.values( evo_link_initialize_fieldset_config );
		for( var i = 0; i < evo_link_initialize_fieldset_configs.length; i++ )
		{
			evo_link_initialize_fieldset( evo_link_initialize_fieldset_configs[i].fieldset_prefix );
		}
	}
} );
