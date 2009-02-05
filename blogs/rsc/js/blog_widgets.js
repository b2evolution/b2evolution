/**
 *	Server communication functions
 *
 * This file implements the widgets javascript interface
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * @package main
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author yabs {@link http://innervisions.org.uk/ }
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
 */
var reorder_delay = 2000;

/**
 * @internal integer reorder_delay_remaining
 * time in milliseconds before the request is sent
 */
var reorder_delay_remaining = 0;


/**
 * Init()
 *
 * Activates the new interface if javascript enabled
 */
jQuery(document).ready(function(){
	// grab some constants
	edit_icon_tag = jQuery( '.edit_icon_hook' ).find( 'a' ).html();// grab the edit icon
	delete_icon_tag = jQuery( '.delete_icon_hook' ).find( 'a' ).html();// grab the delete icon

	// Modify the current widgets screen
	jQuery( ".new_widget" ).parent().parent().remove();// remove the "no widgets yet" placeholder
	jQuery( ".odd" ).addClass( "widget_row" ).removeClass( ".odd" ); // get rid of the odd class and add our own class
	jQuery( ".even" ).addClass( "widget_row" ).removeClass( ".even" ); // get rid of the even class and add our own class
	jQuery( ".fieldset_title_bg > span > a" ).each( function(){ // Intercept normal "add new widget" action
			jQuery( this ).replaceWith( '<span class="add_new_widget" id="'+( jQuery( this ).attr( "id" ) )+'" title="'+( jQuery( this ).attr( 'title' ) )+'">'+jQuery( this ).find( '.add_new_widget_text' ).html()+'</span>' );
	});
	jQuery( '.fieldset_title' ).each( function(){ // make container title droppable
		jQuery( this ).droppable({
		accept: ".draggable_widget", // classname of objects that can be dropped
		hoverClass: "droppable-hover", // classname when object is over this one
		greedy: true, // stops propogation if over more than one
		tolerance : "pointer", // droppable active when cursor over
		delay: 1000,
		drop: function(ev, ui) {	// function called when object dropped
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
	} );


	// grab the widget ID out of the "delete" url and add as ID to parent row
	jQuery( '.widget_row td:nth-child(5)' ).each( function(){
		var widget_id = jQuery( this ).find( 'a' ).attr( "href" );
		widget_id = widget_id.substr( widget_id.indexOf( "wi_ID=" ) + 6, widget_id.length ); // extract ID
		jQuery( this ).parent().attr( "id", "wi_ID_"+widget_id ); // add ID to parent row
	});

	// time to convert the tables
	var the_widgets = new Array();
	jQuery( ".grouped" ).each( function(){ // grab each container
		var container = jQuery( this ).attr( "id" );
		the_widgets[ container ] = new Array();
		jQuery( "#"+container+" .widget_row" ).each( function(){ // grab each widget in container
			var widget = jQuery( this ).attr( "id" );
			the_widgets[ container ][ widget ] = new Array();
			the_widgets[ container ][ widget ]["name"] = jQuery( "#"+widget).find('.widget_name' ).html();
			the_widgets[ container ][ widget ]["class"] = jQuery( this ).attr( "className" );
			the_widgets[ container ][ widget ]["enabled"] = jQuery( "#" + widget ).find( '.widget_is_enabled' ).size() > 0 ? 1 : 0;
		} );
	});

	// create new container for each currrent container
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
			createWidget( widget, container, 0, the_widgets[container][widget]["name"], the_widgets[container][widget]["class"], the_widgets[container][widget]["enabled"] );
		}
	}

	jQuery( '.no-drop .draggable_widget').droppable( "disable" ); // disable dropping on empty containers
	jQuery( '.draggable_widget' ).bind( 'mousedown', function(){ // hide any available widgets panes
				if( !jQuery( this ).hasClass( 'new_widget' ) )
				{	// we're dragging a current widget
					jQuery( '.available_widgets_active' ).removeClass( 'available_widgets_active' );// close any open "available widgets" screens
				}
	});

	colourWidgets(); // add odd/even classes to widgets

	convertAvailableList(); // converts available widgets list to something we can work with

	jQuery( 'body' ).append( '<div id="screen_mask"></div><div id="widget_settings"></div>' );// add placeholder for widgets settings form
	jQuery( '#screen_mask' ).bind( 'click', function(){
		jQuery( this ).removeClass( 'screen_mask_active' );
		jQuery( '#widget_settings' ).removeClass( 'widget_settings_active' );
	});
	current_widgets = getWidgetOrder(); // save current widget order

	doFade( ".fadeout-ffff00" );// highlight any changed widgets

	jQuery( '.widget_actions' ).bind( 'mouseover', function( e ){ // need to disable draggable to allow icons to be clicked :-S
		jQuery( this ).parent().draggable( 'disable' );
		return false;
	});

	jQuery( '.widget_actions' ).bind( 'mouseleave', function( e ){ // re-enable draggable functionality
		jQuery( this ).parent().draggable( 'enable' );
		return false;
	});

});


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
	jQuery( selector ).draggable({
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
	jQuery( selector ).droppable({
		accept: ".draggable_widget", // classname of objects that can be dropped
		hoverClass: "droppable-hover", // classname when object is over this one
		greedy: true, // stops propogation if over more than one
		tolerance : "pointer", // droppable active when cursor over
		delay: 1000,
		drop: function(ev, ui) {	// function called when object dropped
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
	jQuery( selector ).animate({
			backgroundColor: "#ffff00"
		},"fast" ).animate({
			backgroundColor: "#ffffff"
		},"fast" ).animate({
			backgroundColor: "#eeee88"
		},"fast", "", function(){jQuery( this ).removeAttr( "style" );
		});
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
function sendWidgetOrderCallback()
{
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
		if( reorder_delay_remaining > 0 )
		{ // we're still pending
			window.clearTimeout( reorder_widgets_queue );// reset the timeout
			reorder_delay_remaining -= 100; // reduce the delay
			jQuery( '#server_messages' ).html( '<div class="log_container"><div class="log_error">'+T_( 'Changes pending' )+' <strong>' + str_repeat( '.', reorder_delay_remaining / 100 )+'</strong></div></div>' ); // update pending message
			reorder_widgets_queue = window.setTimeout( 'bufferedServerCall()', 100 ); // set timeout
			jQuery( '.server_update' ).removeClass( 'server_update' ).addClass( 'pending_update' ); // set "needs updating"
			return; // continue waiting
		}

		jQuery( '#server_messages' ).html( '<div class="log_container"><div class="log_message">'+T_( 'Saving changes' )+'</div></a>' ); // inform user

		current_widgets = new_widget_order; // store current order
		jQuery( '.pending_update' ).removeClass( 'pending_update' ).addClass( 'server_updating' ); // change class to "updating"

		SendAdminRequest( 'widgets', 're-order', current_widgets ); // send current order to server
	}
	else
	{	// widget order either hasn't changed or has been changed back to original order
		jQuery( '#server_messages' ).html( '<div class="log_container"><div class="log_message">'+T_( 'WOW, you managed to shufffle the widgets back into their original order ..... well done .... update cancelled' )+'</div></a>' ); // inform user
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
	jQuery( '.widget_container' ).each(function(){
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
		containers_list += container+',';
		query_string += container + '=' + containers[container] + '&';
	}
	return query_string + 'container_list='+containers_list;
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
}

/**
 * Functions for playing with widget settings
 */

function editWidget( widget )
{
	jQuery( '#server_messages' ).html( '' );
	SendAdminRequest( "widgets", "edit", "wi_ID="+widget.substr( 6, widget.length ) );
}

function widgetSettings( the_html )
{
	jQuery( '#screen_mask' ).addClass( 'screen_mask_active' );
	jQuery( '#widget_settings' ).addClass( 'widget_settings_active' ).html( the_html );
	jQuery( '#widget_settings' ).prepend( jQuery( '#server_messages' ) );
	AttachServerRequest( 'form' ); // send form via hidden iframe
	jQuery( '#widget_settings > form > span > a' ).bind( 'click', function(){
		return closeWidgetSettings();
	});
}

function widgetSettingsCallback( the_widget, the_name )
{
	jQuery( '.fade_me' ).removeClass( 'fade_me' );
	jQuery( '#wi_ID_'+the_widget+' .widget_name' ).html( the_name );
	jQuery( '#wi_ID_'+the_widget ).addClass( 'fade_me' );

	doFade( '.fade_me' );
}

function closeWidgetSettings()
{
	jQuery( '#screen_mask' ).removeClass( 'screen_mask_active' );
	jQuery( '#widget_settings' ).removeClass( 'widget_settings_active' );
	jQuery( '#server_messages' ).insertBefore( '.available_widgets' );
	return false;
}


function T_( native_string )
{
	if( typeof( T_[ native_string ] ) == "undefined" )
	{ // we don't have a translation
		return native_string;
	}
	else
	{	// we have a translation
		return T_[ native_string ];
	}
}

/**
 * Converts the widget available list to something we can work with
 */
function convertAvailableList()
{
	jQuery( '.add_new_widget' ).bind( 'mouseover', function(e){
		offset = jQuery( this ).offset();
		jQuery( '.available_widgets' ).css( {top : offset.top, right: jQuery( 'body' ).width() - offset.left - jQuery( this ).width() } ).addClass( 'available_widgets_active' ).attr( 'id', 'available_'+jQuery( this ).attr( "id" ) );
	});

	jQuery( '.available_widgets' ).bind( 'mouseleave', function(e){
		jQuery( this ).removeClass( 'available_widgets_active' );
	});

	jQuery( ".available_widgets li" ).each( function(){ // shuffle things around
		jQuery( this ).addClass( "new_widget" ); // add hook for detecting new widgets
		var the_link = jQuery( this ).children( 'a' ).attr( 'href' ); // grab the url
		the_link = the_link.substr( the_link.indexOf( '&type' ) + 1, the_link.length );
		jQuery( this ).children( 'a' ).replaceWith( jQuery( this ).children( 'a' ).html() ); // remove the link
		jQuery( this ).find( 'strong' ).bind( 'click', function(){ // enable add new widget action
			addNewWidget( this, the_link );
		});
	});

	jQuery( '<div class="available_widgets_toolbar"></div>' ).prependTo( '.available_widgets' );
	jQuery( '.available_widgets_toolbar' ).html( jQuery( '.available_widgets > legend' ).html() );
	jQuery( '.available_widgets > legend' ).remove();
}


/**
 * Adds a new widget to a container
 */
function addNewWidget( widget_list_item, admin_call )
{
	jQuery( '.available_widgets' ).removeClass( 'available_widgets_active' );// close open windows
	var widget_id = jQuery( widget_list_item ).attr( "id" );
	jQuery( widget_list_item ).attr( "id", widget_id );

	var widget_name = jQuery( widget_list_item ).html();
	var destination = jQuery( '.available_widgets' ).attr( 'id' );
	destination = destination.substr( 18, destination.length ).replace( '_', ' ' );

	SendAdminRequest( 'widgets', 'create', admin_call+"&container="+destination );
}

/**
 * Adds a new widget to a container
 *
 * @param integer wi_ID Id of the new widget
 * @param string container Container to add widget to
 * @param intger wi_order ( unused atm ) Order of the widget on the server
 * @param string wi_name Name of the new widget
 */
function addNewWidgetCallback( wi_ID, container, wi_order, wi_name )
{
	jQuery( '.fade_me' ).removeClass( 'fade_me' ); // kill any active fades
	createWidget( 'wi_ID_'+wi_ID, container.replace( ' ', '_' ),wi_order, '<strong>'+wi_name+'</strong>', '', 1 );
	doFade( '#wi_ID_'+wi_ID );
	if( reorder_delay_remaining > 0 )
	{	// send outstanding updates
		reorder_delay_remaining = 0;
	}
	else
	{	// no outstanding updates, store current order
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
function createWidget( wi_ID, container, wi_order, wi_name, wi_class, wi_enabled )
{
//	window.alert( wi_ID + ' : ' + container + ' : ' + wi_name + ' : ' +wi_class );
	var newWidget = jQuery( '<li id="'+wi_ID+'" class="draggable_widget"><span class="widget_name">'+wi_name+'</span></li>' );
	if( wi_class )
	{ // add class
		jQuery( newWidget ).addClass( wi_class );
	}

	// Add state indicator:
	jQuery( newWidget ).prepend( jQuery( '<span class="widget_state"></span>' ).prepend( 
											wi_enabled ? enabled_icon_tag : disabled_icon_tag ) );

	var actionIcons = jQuery( '<span class="widget_actions"></span>' ); // container for action icons
	jQuery( actionIcons ).prepend( jQuery( delete_icon_tag ).attr( 'onclick', 'deleteWidget( "'+wi_ID+'" );' ) );	// add delete action
	jQuery( actionIcons ).prepend( jQuery( edit_icon_tag ).attr( 'onclick', 'editWidget( "'+wi_ID+'" );' ) );	// add edit action
	jQuery( actionIcons ).prepend( jQuery( '<span class="toggle_action">' + ( wi_enabled ? deactivate_icon_tag : activate_icon_tag ) + '</span>' ).attr( 'onclick', 'toggleWidget( "' + wi_ID + '" );' ) ); // add toggle action
	jQuery( newWidget ).prepend( actionIcons ); // add widget action icons

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
	//console.log( 'Toggling widget #' + wi_ID.substr( 6 ) );
	SendAdminRequest( 'widgets', 'toggle', 'wi_ID=' + wi_ID.substr( 6 ) );
}

/**
 * Callback for toggling a widget.
 *
 * @param integer Widget ID
 * @param integer new widget state
 */
function doToggle( wi_ID, wi_enabled )
{
	//console.log( 'Setting state of widget #' + wi_ID + ' to ' + ( wi_enabled ? 'enabled' : 'disabled' ) );

	jQuery( '#wi_ID_' + wi_ID + ' .widget_state' ).html( wi_enabled ? enabled_icon_tag : disabled_icon_tag );
	jQuery( '#wi_ID_' + wi_ID + ' .toggle_action' ).html( wi_enabled ? deactivate_icon_tag : activate_icon_tag );
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
