/**
 * This file initialize plugin regional JS
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois PLANQUE - {@link http://fplanque.com/}
 * 
 * Depends on: jQuery
 */
jQuery( document ).ready( function()
{
	if( typeof( evo_regional_config ) == 'undefined' )
	{	// Don't execute code below because no config var is found:
		return;
	}

	/**
	 * Disable HTML attribute "required" if the regional selector has no locations for given parent location:
	 */
	window.check_regional_required_fields = function check_regional_required_fields( prefix )
		{
			jQuery( '#' + prefix + '_rgn_ID, #' + prefix + '_subrg_ID, #' + prefix + '_city_ID' ).each( function()
				{
					if( typeof( jQuery( this ).attr( 'required' ) ) != 'undefined' || jQuery( this ).data( 'required' ) === true )
					{	// If this regional field should be required:
						if( jQuery( this ).find( 'option' ).length > 1 )
						{	// Require if parent regional location has at least one child location:
							jQuery( this ).attr( 'required', 'required' );
						}
						else
						{	// Don't require if there are no child regional locations:
							jQuery( this ).removeAttr( 'required' );
						}
						// Store original state of attribute "required":
						jQuery( this ).data( 'required', true );
					}
					else
					{	// Store original state of attribute "required":
						jQuery( this ).data( 'required', false );
					}
				} );
		};

	window.load_regions = function load_regions( country_ID, region_ID, prefix )
		{	// Load option list with regions for seleted country
			jQuery( '#' + prefix + '_rgn_ID' ).next().find( 'button' ).hide().next().show();
			jQuery.ajax( {
				type: 'POST',
				url: htsrv_url + 'anon_async.php',
				data: 'action=get_regions_option_list&page=edit&mode=load_all&ctry_id=' + country_ID + '&rgn_id=' + region_ID,
				success: function( result )
					{
						jQuery( '#' + prefix + '_rgn_ID' ).next().find( 'button' ).show().next().hide();

						result = ajax_debug_clear( result );
						var options = result.split( '-##-' );

						jQuery( '#' + prefix + '_rgn_ID' ).html( options[0] );
						jQuery( '#' + prefix + '_subrg_ID' ).html( options[1] );
						jQuery( '#' + prefix + '_city_ID' ).html( options[2] );
						window.check_regional_required_fields( prefix );
					}
				} );
		};

	window.load_subregions = function load_subregions( country_ID, region_ID, prefix )
		{	// Load option list with sub-regions for seleted region
			jQuery( '#' + prefix + '_subrg_ID' ).next().find( 'button' ).hide().next().show();
			jQuery.ajax( {
				type: 'POST',
				url: htsrv_url + 'anon_async.php',
				data: 'action=get_subregions_option_list&page=edit&mode=load_all&ctry_id=' + country_ID + '&rgn_id=' + region_ID,
				success: function( result )
					{
						jQuery( '#' + prefix + '_subrg_ID' ).next().find( 'button' ).show().next().hide();

						result = ajax_debug_clear( result );
						var options = result.split( '-##-' );

						jQuery( '#' + prefix + '_subrg_ID' ).html( options[0] );
						jQuery( '#' + prefix + '_city_ID' ).html( options[1] );
						window.check_regional_required_fields( prefix );
					}
				} );
		};

	window.load_cities = function load_cities( country_ID, region_ID, subregion_ID, prefix )
		{	// Load option list with cities for seleted region or sub-region
			jQuery( '#' + prefix + '_city_ID' ).next().find( 'button' ).hide().next().show();
			jQuery.ajax( {
				type: 'POST',
				url: htsrv_url + 'anon_async.php',
				data: 'action=get_cities_option_list&page=edit&ctry_id=' + country_ID + '&rgn_id=' + region_ID + '&subrg_id=' + subregion_ID,
				success: function( result )
					{
						jQuery( '#' + prefix + '_city_ID' ).html( ajax_debug_clear( result ) );
						jQuery( '#' + prefix + '_city_ID' ).next().find( 'button' ).show().next().hide();
						window.check_regional_required_fields();
					}
				} );
		};

	var evo_regional_configs = Object.values( evo_regional_config );
	for( var i = 0; i < evo_regional_configs.length; i++ )
	{
		( function() {
			var config = evo_regional_configs[i];

			window.check_regional_required_fields( config.prefix );

			jQuery( '#' + config.prefix + '_ctry_ID' ).change( function ()
				{	// Load option list with regions for seleted country
					window.load_regions( jQuery( this ).val(), 0, config.prefix );
				} );

			jQuery( '#' + config.prefix + '_rgn_ID' ).change( function ()
				{	// Change option list with sub-regions
					window.load_subregions( jQuery( '#' + config.prefix + '_ctry_ID' ).val(), jQuery( this ).val(), config.prefix );
				} );

			jQuery( '#' + config.prefix + '_subrg_ID' ).change( function ()
				{	// Change option list with cities
					window.load_cities( jQuery( '#' + config.prefix + '_ctry_ID' ).val(), jQuery( '#' + config.prefix + '_rgn_ID' ).val(), jQuery( this ).val(), config.prefix );
				} );

			jQuery( '#button_refresh_region' ).click( function ()
				{	// Button - Refresh regions
					window.load_regions( jQuery( '#' + config.prefix + '_ctry_ID' ).val(), 0, config.prefix );
					return false;
				} );

			jQuery( '#button_refresh_subregion' ).click( function ()
				{	// Button - Refresh sub-regions
					window.load_subregions( jQuery( '#' + config.prefix + '_ctry_ID' ).val(), jQuery( '#' + config.prefix + '_rgn_ID' ).val(), config.prefix );
					return false;
				} );

			jQuery( '#button_refresh_city' ).click( function ()
				{	// Button - Refresh cities
					window.load_cities( jQuery( '#' + config.prefix + '_ctry_ID' ).val(), jQuery( '#' + config.prefix + '_rgn_ID' ).val(),
							jQuery( '#' + config.prefix + '_subrg_ID' ).val(), config.prefix );
					return false;
				} );
		} )();
	}
} );