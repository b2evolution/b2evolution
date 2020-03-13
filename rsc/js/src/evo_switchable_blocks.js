/**
 * This file is used to switch between blocks with condition
 * (used by widgets "Param Switcher", "Tabbed Items")
 */


/**
 * Click event for button to switch between blocks:
 * (this function change URL in browser address bar and make the clicked button to active style)
 *
 * @param object Params
 */
function evo_init_switchable_buttons( params )
{
	// Default params:
	params = jQuery.extend( {
		selector:     '', // Selector for buttons of the group
		class_normal: '', // Class for normal(not active) buttons
		class_active: '', // Class for active buttons
		defaults:     {}, // Default url params(May be specified per Item in "Switchable params")
		add_redir_no: false, // Add &redr=no to URLs
	}, params ),

	jQuery( params.selector ).click( function()
	{
		// Remove previous value from the URL:
		var regexp = new RegExp( '([\?&])((' + jQuery( this ).data( 'code' ) + '|redir)=[^&]*(&|$))+', 'g' );
		var url = location.href.replace( regexp, '$1' );
		url = url.replace( /[\?&]$/, '' );
		// Add param code with value of the clicked button:
		url += ( url.indexOf( '?' ) === -1 ? '?' : '&' );
		url += jQuery( this ).data( 'code' ) + '=' + jQuery( this ).data( 'value' );
		// Append default params:
		for( default_param in params.defaults )
		{
			regexp = new RegExp( '[\?&]' + default_param + '=', 'g' );
			if( ! url.match( regexp ) )
			{	// Append default param if it is not found in the current URL:
				url += '&' + default_param + '=' + params.defaults[ default_param ];
			}
		}
		if( params.add_redir_no )
		{	// Append this url param only when it is required:
			url += '&redir=no';
		}

		// Change URL in browser address bar:
		window.history.pushState( '', '', url );

		// Change active button:
		jQuery( params.selector ).attr( 'class', params.class_normal );
		jQuery( this ).attr( 'class', params.class_active );

		return false;
	} );
}


// Modifications to listen event when URL in browser address bar is changed:
history.pushState = ( f => function pushState(){
	var ret = f.apply(this, arguments);
	window.dispatchEvent(new Event('pushstate'));
	window.dispatchEvent(new Event('locationchange'));
	return ret;
})(history.pushState);

window.addEventListener( 'locationchange', function()
{	// Show/Hide custom fields by condition depending on current URL in browser address:
	var switchable_blocks = jQuery( '[data-display-condition]' );
	if( switchable_blocks.length == 0 )
	{	// No custom fields with display conditions:
		return false;
	}

	function get_url_params( url, multiple_values )
	{
		url = url.replace( /^.+\?/, '' ).split( '&' );
		var params = [];
		url.forEach( function( url_param )
		{
			url_param = url_param.split( '=' );
			params[ url_param[0] ] = multiple_values ? url_param[1].split( '|' ) : url_param[1];
		} );

		return params;
	}

	// Get params of the current URL:
	var url_params = get_url_params( location.href, false );

	// Show all custom fields by default:
	switchable_blocks.show();

	switchable_blocks.each( function()
	{	// Check each custom fields by display condition:
		var conditions = get_url_params( jQuery( this ).data( 'display-condition' ), true );
		for( var cond_param in conditions )
		{
			var url_param_value = ( typeof( url_params[ cond_param ] ) == 'undefined' ? '' : url_params[ cond_param ] );
			if( ( url_param_value === '' && conditions[ cond_param ].indexOf( '' ) === -1 ) ||
			    conditions[ cond_param ].indexOf( url_param_value ) === -1 )
			{	// Hide the custom field if at least one condition is not equal:
				jQuery( this ).hide();
				break;
			}
		}
	} );
} );