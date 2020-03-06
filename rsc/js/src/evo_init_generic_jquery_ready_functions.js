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
		var config = evo_link_position_config['config'];
		var displayInlineReminder = config['display_inline_reminder'];
		var deferInlineReminder = config['defer_inline_reminder'];

		jQuery( document ).on( 'change', evo_link_position_config['selector'], {
				url: config['url'],
				crumb: config['crumb'],
			},
			function( event )
			{
				if( this.value == 'inline' && displayInlineReminder && !deferInlineReminder )
				{ // Display inline position reminder
					alert( config['alert_msg'] );
					displayInlineReminder = false;
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
		var b2evo_download_timer = evo_disp_download_delay_config;
		var downloadInterval = setInterval( function()
				{
					jQuery( "#download_timer" ).html( b2evo_download_timer );
					if( b2evo_download_timer == 0 )
					{	// Stop timer and download a file:
						clearInterval( downloadInterval );
						jQuery( "#download_help_url" ).show();
					}
					b2evo_download_timer--;
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

	// Parameter switcher widget
	if( typeof( evo_widget_param_switcher_config ) != 'undefined' )
	{
		for( var i = 0; i < evo_widget_param_switcher_config.length; i++ )
		{
			var config = evo_widget_param_switcher_config[i];

			jQuery( 'a[data-param-switcher=' + config['widget_id'] + ']' ).click( function()
				{
					var default_params = config['default_params'];

					// Remove previous value from the URL:
					var regexp = new RegExp( '([\?&])((' + jQuery( this ).data( 'code' ) + '|redir)=[^&]*(&|$))+', 'g' );
					var url = location.href.replace( regexp, '$1' );
					url = url.replace( /[\?&]$/, '' );
					// Add param code with value of the clicked button:
					url += ( url.indexOf( '?' ) === -1 ? '?' : '&' );
					url += jQuery( this ).data( 'code' ) + '=' + jQuery( this ).data( 'value' );
					for( default_param in default_params )
					{
						regexp = new RegExp( '[\?&]' + default_param + '=', 'g' );
						if( ! url.match( regexp ) )
						{	// Append defaul param if it is not found in the current URL:
							url += '&' + default_param + '=' + default_params[ default_param ];
						}
					}
					url += '&redir=no';

					// Change URL in browser address bar:
					window.history.pushState( '', '', url );

					// Change active button:
					jQuery( 'a[data-param-switcher=' + config['widget_id'] + ']' ).attr( 'class', config['link_class'] );
					jQuery( this ).attr( 'class', config['active_link_class'] );

					return false;
				} );
		}
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
	
} );
