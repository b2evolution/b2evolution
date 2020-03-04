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
	
} );
