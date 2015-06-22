/**
 * Server communication functions - widgets javascript interface
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 * @author yabs - http://innervisions.org.uk/
 */


/**
 * @TODO yabs > finish docs
 */


/**
 * @internal strings various <img> tags
 * these will be set during Init()
 */
var edit_icon_tag = ''; // edit icon image tag
var delete_icon_tag = ''; // delete icon image tag

/**
 * @internal string current_widgets
 * holds the list of current widgets
 */
var current_widgets = '';

/**
 * @internal object reorder_widgets_queue
 * re-order requests timer object
 */
var reorder_widgets_queue;

/**
 * @internal integer reorder_delay
 * time, in milliseconds, to buffer requests for
 * Does not work when set to 0 or even 20 !!
 */
var reorder_delay = 200;

/**
 * @internal integer reorder_delay_remaining
 * time in milliseconds before the request is sent
 */
var reorder_delay_remaining = 0;

/**
 * @internal string current_widgets
 * crumb to be added to urls
 */
var crumb_url = '';


/**
 * Init()
 *
 * Activates the new interface if javascript enabled
 */
jQuery(document).ready( function()
{
	// grab some constants -- fp> TODO: this is flawed. Fails when starting with an empty blog having ZERO widgets. Init that in .php
	edit_icon_tag = jQuery( '.edit_icon_hook' ).find( 'a' ).html();// grab the edit icon
	delete_icon_tag = jQuery( '.delete_icon_hook' ).find( 'a' ).html();// grab the delete icon
	//get crumb url from delete url and then add it in toggleWidget
	crumb_url = jQuery( '.delete_icon_hook' ).find( 'a' ).attr('href');
	crumb_url = crumb_url.match(/crumb_.*?$/);
	// Modify the current widgets screen
	// remove the "no widgets yet" placeholder:
	jQuery( ".new_widget" ).parent().parent().remove();
	// get rid of the odd/even classes and add our own class:
	jQuery( ".odd" ).addClass( "widget_row" ).removeClass( ".odd" );
	jQuery( ".even" ).addClass( "widget_row" ).removeClass( ".even" );

	// make container title droppable -- fp> This works but gives no visual feedback. It would actually be cool to drop 'after' the current line in which case dropping on the title would make sense
	jQuery( '.fieldset_title' ).each( function(){
		jQuery( this ).droppable(
		{
			accept: ".draggable_widget", // classname of objects that can be dropped
			hoverClass: "droppable-hover", // classname when object is over this one
			greedy: true, // stops propogation if over more than one
			tolerance : "pointer", // droppable active when cursor over
			delay: 1000,
			drop: function(ev, ui)
			{	// function called when object dropped
				jQuery( ".fade_me" ).removeClass( "fade_me" ); // remove any existing fades
				jQuery( '.available_widgets' ).removeClass( 'available_widgets_active' ); // close any open windows

				jQuery( ui.draggable ).appendTo( jQuery( '#container_' + jQuery( this ).find( '.container_name' ).html().replace( ' ', '_' ) ) ); // add the dragged widget to this container
				jQuery( ui.draggable ).addClass( "fade_me server_update" ); // add fade class
				jQuery( ui.draggable ).droppable( "enable" );	// enable dropping if disabled
				doFade( ".fade_me" ); // fade the widget
				colourWidgets();
				sendWidgetOrder(); // send the new order to the server
			}
		});
	});

	// grab the widget ID out of the "delete" url and add as ID to parent row:
	jQuery( '.widget_row td:nth-child(7)' ).each( function()
	{
		var widget_id = jQuery( this ).find( 'a' ).attr( "href" );
		widget_id = widget_id.match(/wi_ID=([0-9]+)/)[1] // extract ID
		jQuery( this ).parent().attr( "id", "wi_ID_"+widget_id ); // add ID to parent row
	});

	// Convert the tables:
	var the_widgets = new Array();
	jQuery( ".widget_container_list" ).each( function()
	{ // grab each container
		var container = jQuery( this ).attr( "id" );
		the_widgets[ container ] = new Array();
		jQuery( "#"+container+" .widget_row" ).each( function()
		{ // grab each widget in container
			var widget = jQuery( this ).attr( "id" );
			the_widgets[ container ][ widget ] = new Array();
			the_widgets[ container ][ widget ]["name"] = jQuery( '#' + widget ).find( '.widget_name' ).parent().html();
			the_widgets[ container ][ widget ]["class"] = jQuery( this ).attr( "className" );
			the_widgets[ container ][ widget ]["enabled"] = jQuery( '#' + widget + ' .widget_is_enabled' ).size();
			the_widgets[ container ][ widget ]["cache"] = jQuery( '#' + widget + ' .widget_cache_status [rel]' ).attr( 'rel' );
		} );
	});

	// create new container for each current container
	for( container in the_widgets )
	{	// loop through each container
		var is_droppable = !jQuery( '#'+container ).hasClass( "no-drop" );
		newContainer = jQuery( "<ul id=\"container_"+container+"\" class=\"widget_container\"></ul>" );
		if( !is_droppable )
		{	// container doesn't exist in skin
			jQuery( newContainer ).addClass( 'no-drop' );
		}
		jQuery( "#"+container ).replaceWith( newContainer );// replace table with new container

		// create widget entry for each widget in each container
		for( widget in the_widgets[container] )
		{	// loop through all widgets in this container
			createWidget( widget, container, 0, the_widgets[container][widget]["name"], the_widgets[container][widget]["class"], the_widgets[container][widget]["enabled"], the_widgets[container][widget]["cache"] );
		}
	}

	// disable dropping on empty containers:
	jQuery( '.no-drop .draggable_widget').droppable( "disable" );
	jQuery( '.draggable_widget' ).bind( 'mousedown', function()
	{ // hide any available widgets panes
		if( !jQuery( this ).hasClass( 'new_widget' ) )
		{	// we're dragging a current widget, close any open "available widgets" screens
			jQuery( '.available_widgets_active' ).removeClass( 'available_widgets_active' );
		}
	});

	colourWidgets(); // add odd/even classes to widgets

	convertAvailableList(); // converts available widgets list to something we can work with

	current_widgets = getWidgetOrder(); // save current widget order

	doFade( ".fadeout-ffff00" );// highlight any changed widgets

	// Actions for buttons to select several widgets to activate/deactivate them by one action
	jQuery( '#widget_button_check_all' ).click( function()
	{
		jQuery( this ).closest( 'form' ).find( 'input[type=checkbox]' ).prop( 'checked', true );
	} );
	jQuery( '#widget_button_uncheck_all' ).click( function()
	{
		jQuery( this ).closest( 'form' ).find( 'input[type=checkbox]' ).prop( 'checked', false );
	} );
	jQuery( '#widget_button_check_active' ).click( function()
	{
		jQuery( this ).closest( 'form' ).find( '.widget_checkbox.widget_checkbox_enabled input[type=checkbox]' ).prop( 'checked', true );
		jQuery( this ).closest( 'form' ).find( '.widget_checkbox:not(.widget_checkbox_enabled) input[type=checkbox]' ).prop( 'checked', false );
	} );
	jQuery( '#widget_button_check_inactive' ).click( function()
	{
		jQuery( this ).closest( 'form' ).find( '.widget_checkbox.widget_checkbox_enabled input[type=checkbox]' ).prop( 'checked', false );
		jQuery( this ).closest( 'form' ).find( '.widget_checkbox:not(.widget_checkbox_enabled) input[type=checkbox]' ).prop( 'checked', true );
	} );
} );


/**
 * Makes the selector drag and drop .. because I'm lazy ;)
 *
 * @param mixed selector DOM ID or class or object
 */
function makeDragnDrop( selector )
{
	makeDraggable( selector );
	makeDroppable( selector );
}


/**
 * Makes an element / group of elements draggable
 *
 * @param mixed selector : the object to make draggable
 */
function makeDraggable( selector )
{
	jQuery( selector ).draggable(
	{
		helper: "clone", // use a copy of the image
		scroll: true, // scroll the window during dragging
		scrollSensitivity: 100, // distance from edge before scoll occurs
		zIndex: 999, // z-index whilst dragging
		opacity: .8, // opacity whilst dragging
		cursor: "move" // change the cursor whilst dragging
	}).addClass( "draggable_widget" ); // add our css class
}

/**
 * Makes an element / group of elements droppable
 *
 * @param mixed selector : the object to make droppable
 */
function makeDroppable( selector )
{
	jQuery( selector ).droppable(
	{
		accept: ".draggable_widget", // classname of objects that can be dropped
		hoverClass: "droppable-hover", // classname when object is over this one
		greedy: true, // stops propogation if over more than one
		tolerance : "pointer", // droppable active when cursor over
		delay: 1000,
		drop: function(ev, ui)
		{	// function called when object dropped
			jQuery( ".fade_me" ).removeClass( "fade_me" ); // remove any existing fades
			jQuery( '.available_widgets' ).removeClass( 'available_widgets_active' ); // close any open windows
			if( !jQuery( this ).hasClass( "available_widgets" ) )
			{	// we're not deleting it
				if( jQuery( ui.draggable ).hasClass( "new_widget" ) )
				{	// this is a new widget, we need to treat it diffently
					addNewWidget( ui.draggable, this ); // add as new widget
				}
				else
				{	// this is an existing widget, just move it
					jQuery( ui.draggable ).insertBefore( this ); // add the dragged widget before this widget
					jQuery( ui.draggable ).addClass( "fade_me server_update" ); // add fade class
					jQuery( ui.draggable ).droppable( "enable" );	// enable dropping if disabled
				}
			}
			else
			{ // we might be deleting the widget
				if(  !jQuery( ui.draggable ).hasClass( "new_widget" ) )
				{ // we're deleting it
					jQuery( ui.draggable ).remove();
				}
			}
			doFade( ".fade_me" ); // fade the widget
			colourWidgets();
			sendWidgetOrder();// send the new order to the server
		}
	});
}

/**
 * Fades the relevant object
 */
function doFade( selector )
{
	evoFadeSuccess( selector );
}


/**
 * Send the current widget containers and order to the server
 *
 * Successive calls within the buffer time resets the countdown
 * this reduces the number of server calls made
 */
function sendWidgetOrder()
{
	if( reorder_delay_remaining < 1 )
	{
		jQuery( '#server_messages' ).html( '<div class="log_container"><div class="log_error"></div></div>' );
	}
	// reset the clock
	reorder_delay_remaining = reorder_delay;
	bufferedServerCall();
}

/**
 * Callback funtion for sendWidgetOrder()
 *
 * Highlights the updated widgets and resets their odd/even style
 */
function sendWidgetOrderCallback( server_response )
{
	// alert( server_response+' vs '+blog );
	doFade( '.server_updating' ); // highlight updated widgets
	jQuery( '.server_updating' ).removeClass( 'server_updating' ); // remove "needs updating"
	colourWidgets(); // redo widget odd/even colours
}

/**
 * Buffered server call
 *
 * Waits until delay period is over and then sends new order to the server
 * only sends if the current widget order has changed since last update
 */
function bufferedServerCall()
{
	var new_widget_order = getWidgetOrder();
	if( new_widget_order != current_widgets )
	{	// widget order has changed, we need to update
		jQuery( '#server_messages' ).html( '<div class="action_messages container-fluid"><ul><li><div class="alert alert-dismissible alert-info fade in">'+T_( 'Saving changes' )+'</div></li></ul></div>' ); // inform user

		current_widgets = new_widget_order; // store current order
		//add crumbs here
		new_widget_order += '&' + crumb_url;
		jQuery( '.pending_update' ).removeClass( 'pending_update' ).addClass( 'server_updating' ); // change class to "updating"

		SendAdminRequest( 'widgets', 're-order', new_widget_order, false ); // send current order to server
	}
	else
	{	// widget order either hasn't changed or has been changed back to original order
		jQuery( '#server_messages' ).html( '<div class="action_messages container-fluid"><ul><li><div class="alert alert-dismissible alert-warning fade in">'
						+T_( 'Widget order unchanged' )+'</div></li></ul></div>' ); // inform user
		jQuery( '.pending_update' ).removeClass( 'pending_update' ); // remove "needs updating"
		colourWidgets(); // redo widget colours
	}
}


/**
 * Gets the current widget order
 *
 * @return string widget order
 */
function getWidgetOrder()
{
	// need to get every container, then every widget in container and send the lot to the server
	var containers = new Array()
	jQuery( '.widget_container' ).each(function()
	{
		var container_name = jQuery( this ).attr('id');
		containers[ container_name ] = '';
		jQuery( '#'+container_name+' .draggable_widget' ).each( function(){
			if( jQuery( this ).attr( 'id' ) && jQuery( this ).attr( 'id' ) != 'undefined' )
			{	// this is a widget
				 containers[container_name] += jQuery( this ).attr( 'id' ) + ', ';
			}
		});
	});

	var query_string = '';
	var containers_list = '';
	for( container in containers )
	{
		query_string += container+'='+containers[container]+'&';
		containers_list += container+',';
	}

	var r = 'blog='+blog+'&'+query_string+'container_list='+containers_list;

	// console.log( r );

	return r;
}


/**
 * Redo odd / even classes
 */
function colourWidgets()
{
		jQuery( ".draggable_widget" ).removeClass( "odd" ); // remove any odd classes
		jQuery( ".draggable_widget" ).removeClass( "even" ); // remove any even classes
		var pos = false; // will be used as a toggle for odd / even rows
		jQuery( "#current_widgets .draggable_widget" ).each( function(){
				pos = !pos; // toggle
				jQuery(this).addClass( ( pos ? "even" : "odd" ) ); // add relevant new odd/even class
		});
}

/**
 * Delete widget
 */
function deleteWidget( widget )
{
	jQuery( '#wi_ID_'+widget.substr( 6, widget.length ) ).animate({
			backgroundColor: "#f88"
		},"fast", function(){
			jQuery( this ).remove(); // remove the widget
			colourWidgets(); // redo widget colours
			sendWidgetOrder(); // update the server
		});
	return false;
}

/**
 * Request edit screen from server...
 */
function editWidget( widget )
{
	jQuery( '#server_messages' ).html( '' );
	msg = "wi_ID="+widget.substr( 6, widget.length );
	SendAdminRequest( "widgets", "edit", msg, true );
	return false;
}

/*
 * This is called when we get the response from the server:
 */
function widgetSettings( the_html )
{
	// add placeholder for widgets settings form:
	jQuery( 'body' ).append( '<div id="screen_mask" onclick="closeWidgetSettings()"></div><div id="widget_settings" class="modal-content"></div>' );
	jQuery( '#screen_mask' ).fadeTo(1,0.5).fadeIn(200);
	jQuery( '#widget_settings' ).html( the_html ).addClass( 'widget_settings_active' );
	jQuery( '#widget_settings' ).prepend( jQuery( '#server_messages' ) );
	AttachServerRequest( 'form' ); // send form via hidden iframe

	// Create modal header for bootstrap skin
	var page_title = jQuery( '#widget_settings' ).find( 'h2.page-title:first' );
	if( page_title.length > 0 )
	{
		var page_title_icons = jQuery( '#widget_settings' ).find( 'span.pull-right:first' );
		var page_title_icons_html = '';
		if( page_title_icons.length > 0 )
		{
			page_title_icons.find( 'a.close_link' ).remove()
			page_title_icons_html = '<span class="pull-right">' + page_title_icons.html() + '</span>';
			page_title_icons.remove();
		}
		jQuery( '#widget_settings' ).prepend( '<div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>' + page_title_icons_html + '<h4 class="modal-title">' + page_title.html() + '</h4></div>' );
		page_title.remove();
		jQuery( '#widget_settings button.close' ).bind( 'click', closeWidgetSettings );
	}

	jQuery( '#widget_settings a.close_link' ).bind( 'click', closeWidgetSettings );

	// Close widget Settings if Escape key is pressed:
	var keycode_esc = 27;
	jQuery(document).keyup(function(e)
	{
		if( e.keyCode == keycode_esc )
		{
			closeWidgetSettings();
		}
	});
}

function widgetSettingsCallback( wi_ID, wi_name, wi_cache_status )
{
	jQuery( '#wi_ID_' + wi_ID + ' .widget_name' ).html( wi_name );
	jQuery( '#wi_ID_' + wi_ID + ' .widget_cache_status' ).html( getWidgetCacheIcon( 'wi_ID_'+wi_ID, wi_cache_status ) );
}

function closeWidgetSettings()
{
	jQuery( '#widget_settings' ).hide(); // removeClass( 'widget_settings_active' );
	jQuery( '#server_messages' ).insertBefore( '.available_widgets' );
	jQuery( '#widget_settings' ).remove();
	jQuery( '#screen_mask' ).remove();
	return false;
}

function showMessagesWidgetSettings()
{
	jQuery( '#widget_settings' ).animate( {
			scrollTop: jQuery( '#widget_settings' ).scrollTop() +  + jQuery( '#server_messages' ).position().top - 20
		}, 100 );
	return false;
}


function T_( native_string )
{
	if( typeof( T_arr[ native_string ] ) == "undefined" )
	{ // we don't have a translation
		return native_string;
	}
	else
	{	// we have a translation
		return T_arr[ native_string ];
	}
}

/**
 * Converts the widget available list to something we can work with
 */
function convertAvailableList()
{
	// Open list on click, not on hover!
	jQuery( ".fieldset_title > span > a[id^='add_new']" ).attr( 'href', '#' ).bind( 'click', function(e)
	{
		// add placeholder for widgets settings form:
		jQuery( 'body' ).append( '<div id="screen_mask" onclick="closeAvailableWidgets()"></div>' );
		jQuery( '#screen_mask' ).fadeTo(1,0.5).fadeIn(200);
		offset = jQuery( this ).offset();
		var y = offset.top;
		// can't dislay any lower than this!:
		// var max_y = jQuery( window ).height() - jQuery( '.available_widgets' ).height(); // this doesn't work when window is scrolled :(
		var max_y = jQuery( document ).height() - 10 - jQuery( '.available_widgets' ).height();
		if( max_y < 20 ) { max_y = 20 };
		if( y > max_y ) { y = max_y };
		jQuery( '.available_widgets' ).addClass( 'available_widgets_active' ).attr( 'id', 'available_'+jQuery( this ).attr( "id" ) );

		// cancel default href action:
		return false;
	});

	// Close action:
	jQuery( '.available_widgets_toolbar > a' ).bind( 'click', function(e)
	{
		closeAvailableWidgets();
		// cancel default href action:
		return false;
	});

	// Close Overlay if Escape key is pressed:
	var keycode_esc = 27;
	jQuery(document).keyup(function(e)
	{
		if( e.keyCode == keycode_esc )
		{
			closeAvailableWidgets();
			return false;
		}
	});

	jQuery( ".available_widgets li" ).each( function()
	{ // shuffle things around
		jQuery( this ).addClass( "new_widget" ); // add hook for detecting new widgets

		var the_link = jQuery( this ).children( 'a' ).attr( 'href' ); // grab the url
		the_link = the_link.substr( the_link.indexOf( '&type' ) + 1, the_link.length );

		// replace href with JS addnewwidget action:
		jQuery( this ).children( 'a:first' ).attr( 'href', '#' ).bind( 'click', function(){
			addNewWidget( this, the_link );
			// cancel default href action:
			return false;
		});
	});
}

/**
 * Close available widgets overlay
 */
function closeAvailableWidgets()
{
	jQuery('.available_widgets').removeClass( 'available_widgets_active' );
	jQuery( '#screen_mask' ).remove();
}


/**
 * Adds a new widget to a container
 */
function addNewWidget( widget_list_item, admin_call )
{
	closeAvailableWidgets()

	var widget_id = jQuery( widget_list_item ).attr( "id" );
	jQuery( widget_list_item ).attr( "id", widget_id );

	var widget_name = jQuery( widget_list_item ).html();
	var destination = jQuery( '.available_widgets' ).attr( 'id' );
	destination = destination.substr( 18, destination.length ).replace( /_/g, ' ' ).replace( /-/g, ':' );

	SendAdminRequest( 'widgets', 'create', admin_call+"&blog="+blog+"&container="+destination, true );
}


/**
 * Adds a new widget to a container
 *
 * @param integer wi_ID Id of the new widget
 * @param string container Container to add widget to
 * @param intger wi_order ( unused atm ) Order of the widget on the server
 * @param string wi_name Name of the new widget
 * @param string wi_cache_status Cache status
 */
function addNewWidgetCallback( wi_ID, container, wi_order, wi_name, wi_cache_status )
{
	jQuery( '.fade_me' ).removeClass( 'fade_me' ); // kill any active fades
	createWidget( 'wi_ID_'+wi_ID, container.replace( / /g, '_' ).replace( /:/g, '-' ), wi_order, wi_name, '', 1, wi_cache_status );
	doFade( '#wi_ID_'+wi_ID );
	if( reorder_delay_remaining > 0 )
	{ // send outstanding updates
		reorder_delay_remaining = 0;
	}
	else
	{ // no outstanding updates, store current order
		current_widgets = getWidgetOrder(); // store current order
	}
}

/**
 * Create a new widget in a container
 *
 * @param integer wi_ID Id of the new widget
 * @param string container Container to add widget to
 * @param integer wi_order ( unused atm ) Order of the widget on the server
 * @param string wi_name Name of the new widget
 * @param boolean wi_enabled Is the widget enabled?
 */
function createWidget( wi_ID, container, wi_order, wi_name, wi_class, wi_enabled, wi_cache_status )
{
	var newWidget = jQuery( '<li id="'+wi_ID+'" class="draggable_widget"><span>'+wi_name+'</span></li>' );
	newWidget.find( 'a.widget_name' ).click( function()
	{
		return editWidget( wi_ID );
	} );
	if( wi_class )
	{ // add class
		jQuery( newWidget ).addClass( wi_class );
	}

	// Add state indicator:
	jQuery( newWidget ).prepend( jQuery( '<span class="widget_state">'+
			'<a href="#" class="toggle_action" onclick="return toggleWidget( \''+wi_ID+'\' );">'+
				( wi_enabled ? enabled_icon_tag : disabled_icon_tag )+
			'</a>'+
		'</span>' ) );

	// Add icon to toggle cache status:
	var cacheIcon = jQuery( '<span class="widget_cache_status">' + getWidgetCacheIcon( wi_ID, wi_cache_status ) + '</span>' );
	jQuery( newWidget ).prepend( cacheIcon ); // add widget action icons

	// Add action icons:
	var actionIcons = jQuery( '<span class="widget_actions"><a href="#" class="toggle_action" onclick="return toggleWidget( \''+wi_ID+'\' );">'
				+( wi_enabled ? deactivate_icon_tag : activate_icon_tag )+'</a><a href="#" onclick="return editWidget( \''+wi_ID+'\' );">'
				+edit_icon_tag+'</a><a href="#" onclick="return deleteWidget( \''+wi_ID+'\' );">'
				+delete_icon_tag+'</a></span>' );
	jQuery( newWidget ).prepend( actionIcons ); // add widget action icons

	// Add checkbox:
	jQuery( newWidget ).prepend( jQuery( '<span class="widget_checkbox'+( wi_enabled ? ' widget_checkbox_enabled' : '' )+'">'+
			'<input type="checkbox" name="widgets[]" value="'+wi_ID.replace( 'wi_ID_', '' )+'" />'+
		'</span>' ) );

	jQuery( '#container_'+container ).append( newWidget );	// add widget to container

	makeDragnDrop( '#'+wi_ID );
	colourWidgets();	// recolour the widgets
}

/**
 * Toggle the widget state.
 *
 * @param string Widget ID.
 */
function toggleWidget( wi_ID )
{
	SendAdminRequest( 'widgets', 'toggle', 'wi_ID=' + wi_ID.substr( 6 ) + '&' + crumb_url, true );
	return false;
}

/**
 * Callback for toggling a widget.
 *
 * @param integer Widget ID
 * @param integer new widget state
 */
function doToggle( wi_ID, wi_enabled )
{
	jQuery( '#wi_ID_' + wi_ID + ' .widget_state' ).html( '<a href="#" class="toggle_state" onclick="return toggleWidget( \'wi_ID_'+wi_ID+'\', \''+crumb_url+'\' );">'+
				( wi_enabled ? enabled_icon_tag : disabled_icon_tag )+
			'</a>' );
	if( wi_enabled )
	{
		jQuery( '#wi_ID_' + wi_ID + ' .widget_checkbox' ).addClass( 'widget_checkbox_enabled' );
	}
	else
	{
		jQuery( '#wi_ID_' + wi_ID + ' .widget_checkbox' ).removeClass( 'widget_checkbox_enabled' );
	}
	jQuery( '#wi_ID_' + wi_ID + ' .toggle_action' ).html( wi_enabled ? deactivate_icon_tag : activate_icon_tag );

	evoFadeBg( jQuery( '#wi_ID_' + wi_ID ), new Array( '#FFFF33' ), { speed: 3000 } );
}

/**
 * Toggle the widget cache status.
 *
 * @param string Widget ID.
 * @param string Action: 'enable', 'disable'
 */
function toggleCacheWidget( wi_ID, action )
{
	SendAdminRequest( 'widgets', 'cache_' + action, 'wi_ID=' + wi_ID.substr( 6 ) + '&' + crumb_url, true );
	return false;
}

/**
 * Callback for toggling a widget cache status.
 *
 * @param integer Widget ID
 * @param integer new widget cache status
 */
function doToggleCache( wi_ID, wi_cache_status )
{
	jQuery( '#wi_ID_' + wi_ID + ' .widget_cache_status' ).html( getWidgetCacheIcon( 'wi_ID_'+wi_ID, wi_cache_status ) );

	evoFadeBg( jQuery( '#wi_ID_' + wi_ID ), new Array( '#FFFF33' ), { speed: 3000 } );
}

/**
 * replicates PHP's str_repeat() function
 *
 * @param string data string to repeat
 * @param integer multiplier number of repeats required
 *
 * @return the multiplied string
 */
function str_repeat( data, multiplier )
{
	return new Array( multiplier + 1 ).join( data );
}


/**
 * Get icon for widget cache status
 *
 * @param string Widget ID
 * @param string Widget cache status
 * @return string
 */
function getWidgetCacheIcon( wi_ID, wi_cache_status )
{
	switch( wi_cache_status )
	{
		case 'enabled':
			return '<a href="#" class="cache_action" onclick="return toggleCacheWidget( \''+wi_ID+'\', \'disable\' );">' + cache_enabled_icon_tag + '</a>';

		case 'disabled':
			return '<a href="#" class="cache_action" onclick="return toggleCacheWidget( \''+wi_ID+'\', \'enable\' );">' + cache_disabled_icon_tag + '</a>';

		case 'disallowed':
			return cache_disallowed_icon_tag;

		case 'denied':
			return '<a href="?ctrl=coll_settings&amp;tab=advanced&amp;blog=' + blog + '#fieldset_wrapper_caching">' + cache_denied_icon_tag + '</a>';
	}
}