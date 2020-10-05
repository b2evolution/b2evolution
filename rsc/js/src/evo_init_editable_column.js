/**
 * This file initializes the editable column JS
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
	if( typeof( evo_init_editable_column_config ) == 'undefined' )
	{	// Don't execute code below because no config var is found:
		return;
	}

	var keys = Object.keys( evo_init_editable_column_config );
	for( var i = 0; i < keys.length; i++ )
	{
		( function() {
			var config = evo_init_editable_column_config[keys[i]];

			if( jQuery( config.column_selector ).length > 0 )
			{	// Initialize only when the requested element exists on the current page:
				jQuery( config.column_selector ).editable( config.ajax_url, {
						data: function( value, settings ) {
								value = ajax_debug_clear( value );
								if( config.field_type == 'select' )
								{
									var result = value.match( /rel="([^"]*)"/ );
									if( config.options_eval )
									{	// This is a hack for a specific case:
										return eval( config.options_eval );
									}
									else
									{
										if( config.options instanceof Object )
										{
											return jQuery.extend( {}, config.options, { 'selected': result[1] } );
										}
										else
										{
											return config.options;
										}
									}
								}
								else
								{
									var result = value.match( />\s*([^<]+)\s*</ );
									return result[1] == config.null_text ? '' : result[1];
								}
							},
						type: config.field_type,
						class_name: config.field_class,
						name: config.new_field_name,
						tooltip: config.tooltip,
						event: 'click',
						onblur: config.field_type == 'text' ? 'submit' : 'cancel', // Set onblur action to 'submit' when type is 'text' in order to don't miss the selected user login from autocomplete list
						onedit: function( settings, original )
							{
								// Set width to fix value to don't change it on selector displaying:
								var wrapper_width = jQuery( original ).width();
								jQuery( original ).css( { 'width': wrapper_width, 'max-width': wrapper_width } );
							},
						callback: function( settings, original )
							{
								if( config.colored_cells )
								{	// Use different color for each value
									jQuery( this ).html( ajax_debug_clear( settings ) );
									var link = jQuery( this ).find( 'a' );
									jQuery( this ).css( 'background-color', link.attr( 'color' ) == 'none' ? 'transparent' : link.attr( 'color' ) );
									link.removeAttr( 'color' );
								}
								else
								{	// Use simple fade effect
									if( typeof( evoFadeSuccess ) == 'function' )
									{
										evoFadeSuccess( this );
									}
								}
								// Execute additional code:
								if( config.callback_code )
								{	// TODO: this is dangerous, we should look for a safer way to execute a callback
									eval( config.callback_code );
								}
							},
						submitdata: function( value, settings )
							{
								var return_obj = {};
								return_obj[config.ID_name] = eval( config.ID_value ); // TODO: this is dangerous
								return return_obj;
							},
						onerror : function( settings, original, xhr )
							{
								if( typeof( evoFadeFailure ) == 'function' )
								{
									evoFadeFailure( original );
								}
								var input = jQuery( original ).find( 'input' );
								if( input.length > 0 )
								{
									jQuery( original ).find( 'span.field_error' ).remove();
									input.addClass( 'field_error' );
									if( typeof( xhr.responseText ) != 'undefined' )
									{
										input.after( '<span class="note field_error">' + xhr.responseText + '</span>' );
									}
								}
							}
					} );
			}
		} )();
	}
} );