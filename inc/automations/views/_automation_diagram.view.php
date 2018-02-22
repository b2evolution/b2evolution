<?php
/**
 * This file display the automation diagram view
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $edited_Automation;

// Display breadcrumb:
autm_display_breadcrumb();

?>
<div class="evo_automation__diagram_canvas jtk-surface jtk-surface-nopan" id="evo_automation__diagram_canvas">
	<div class="evo_automation__diagram_step_box" id="step_1"
		style="top:80px;left:50%"
		data-info-yes="YES (5s)"
		data-info-no="NO (1m)"
		data-info-error="ERROR (50mn)">
		<b>#1 IF Condition:</b><br>(Current date > "2018-01-23" AND User has tag = "moderator")</div>
	<div class="evo_automation__diagram_step_box" id="step_2"
		style="top:290px;left:33%"
		data-info-yes="Email SENT (0s)"
		data-info-no="Email was ALREADY sent (34s)"
		data-info-error="ERROR: Email cannot be sent (1mn)">
		<b>#2 Send Campaign</b><br>Markdown Example</div>
	<div class="evo_automation__diagram_step_box" id="step_3"
		style="top:42em;left:50%"
		data-info-yes="Notification SENT (23d)"
		data-info-error="ERROR: Notification cannot be sent (5m)">
		<b>#3 Notify Owner:</b><br>administrator</div>
	<div class="evo_automation__diagram_step_box" id="step_4"
		style="top: 23em;left: 67%"
		data-info-yes="Tag was added (5h)"
		data-info-no="User already has the tag (1y)">
		<b>#4 Add user tag:</b><br>"very long user tag name for testing"</div>
</div>

<script type="text/javascript">
jsPlumb.ready( function ()
{
	var instance = jsPlumb.getInstance(
	{
		DragOptions: { cursor: 'pointer', zIndex: 2000 },
		Container: 'evo_automation__diagram_canvas'
	} );

	// General properties for connectors and source points:
	var point_source = {
		endpoint: 'Dot',
		paintStyle: { radius: 7 },
		hoverPaintStyle: { radius: 10 },
		isSource: true,
		connector: [ 'Flowchart', { gap: 5, cornerRadius: 10, alwaysRespectStubs: true } ],
		connectorStyle: {
			strokeWidth: 4,
			joinstyle: 'round',
			outlineStroke: 'white',
			outlineWidth: 1
		},
		connectorHoverStyle: { strokeWidth: 7 },
		connectorOverlays: [
			[ 'Arrow', {
				location: 1,
				visible:true,
				id:'ARROW'
			} ],
			[ 'Label', {
				location: 0.3,
				id: 'label',
			} ]
		]
	};
	// "YES" green connector and source point:
	var point_source_yes = jQuery.extend( true, {}, point_source );
	point_source_yes.connectionType = 'yes';
	point_source_yes.paintStyle.fill = point_source_yes.connectorStyle.stroke = '#61bd4f';
	point_source_yes.connector[1].stub = [70, 70];
	point_source_yes.connectorOverlays[0][1].width = 20;
	point_source_yes.connectorOverlays[0][1].length = 20;
	point_source_yes.connectorOverlays[1][1].cssClass = 'jtk-label jtk-label-yes';
	// "NO" blue connector and source point:
	var point_source_no = jQuery.extend( true, {}, point_source );
	point_source_no.connectionType = 'no';
	point_source_no.paintStyle.fill = point_source_no.connectorStyle.stroke = '#0079bf';
	point_source_no.connector[1].stub = [50, 50];
	point_source_no.connectorOverlays[0][1].width = 15;
	point_source_no.connectorOverlays[0][1].length = 15;
	point_source_no.connectorOverlays[1][1].cssClass = 'jtk-label jtk-label-no';
	// "ERROR" red connector and source point:
	var point_source_error = jQuery.extend( true, {}, point_source );
	point_source_error.connectionType = 'error';
	point_source_error.paintStyle.fill = point_source_error.connectorStyle.stroke = '#eb5a46';
	point_source_error.connector[1].stub = [30, 30];
	point_source_error.connectorOverlays[0][1].width = 10;
	point_source_error.connectorOverlays[0][1].length = 10;
	point_source_error.connectorOverlays[1][1].cssClass = 'jtk-label jtk-label-error';
	// Target black point:
	var point_target = {
		endpoint: 'Dot',
		paintStyle: { fill: '#000', radius: 7 },
		hoverPaintStyle: { radius: 10 },
		maxConnections: -1,
		isTarget: true
	};

	// Initialise listen events:
	instance.batch( function ()
	{
		// listen for new connections; initialise them the same way we initialise the connections at startup.
		instance.bind( 'connection', function( connInfo, originalEvent )
		{
			connInfo.connection.getOverlay( 'label' ).setLabel( jQuery( '#' + connInfo.sourceId ).data( 'info-' + connInfo.sourceEndpoint.connectionType ) );
		} );

		// Make all step boxes draggable:
		instance.draggable( jsPlumb.getSelector( '.evo_automation__diagram_step_box' ), { grid: [20, 20] } );

		instance.bind( 'connectionDrag', function (connection)
		{
			b2evo_source_ID = connection.sourceId;
			b2evo_source_type = connection.endpoints[0].connectionType;
			console.log( 'connection ' + connection.id + ' is being dragged. suspendedElement is ', connection.suspendedElement, ' of type ', connection.suspendedElementType );
		});
		instance.bind( 'connectionDragStop', function (connection) {
			//alert( 'Source: ' + b2evo_source_ID + '(' + b2evo_source_type + ')' + '\n' + 'Target: ' + ( connection.target === null ? 'NULL' : connection.targetId ) );
			console.log( connection );
			console.log( 'connection ' + connection.id + ' was dragged' );
		});
		instance.bind( 'connectionMoved', function( params )
		{
			console.log( 'connection ' + params.connection.id + ' was moved' );
		} );
	} );

	function evo_jsplumb_init_step_box( toId )
	{
		if( jQuery( '#' + toId ).data( 'info-yes' ) != undefined )
		{	// Add "YES" red source point:
			instance.addEndpoint( toId, point_source_yes, { anchor: [ 0.33, 1, 0, 1 ], uuid: toId + '_yes' } );
		}
		if( jQuery( '#' + toId ).data( 'info-no' ) != undefined )
		{	// Add "NO" blue source point:
			instance.addEndpoint( toId, point_source_no, { anchor: [ 0.67, 1, 0, 1 ], uuid: toId + '_no' } );
		}
		if( jQuery( '#' + toId ).data( 'info-error' ) != undefined )
		{	// Add "ERROR" red source point:
			instance.addEndpoint( toId, point_source_error, { anchor: 'RightMiddle', uuid: toId + '_error' } );
		}
		// Add target black point:
		instance.addEndpoint( toId, point_target, { anchor: 'TopCenter', uuid: toId } );
		// Make whole step box is traget place to connect:
		instance.makeTarget( toId, { anchor: 'TopCenter', endpoint: 'Blank' } );
	};

	// Initialise step boxes:
	evo_jsplumb_init_step_box( 'step_1' );
	evo_jsplumb_init_step_box( 'step_2' );
	evo_jsplumb_init_step_box( 'step_3' );
	evo_jsplumb_init_step_box( 'step_4' );

	// Initialise connections between steps:
	instance.connect( { uuids: ['step_1_yes', 'step_2'] } );
	instance.connect( { uuids: ['step_1_no', 'step_4'] } );
	instance.connect( { uuids: ['step_1_error', 'step_1'] } );
	instance.connect( { uuids: ['step_2_yes', 'step_3'] } );
	instance.connect( { uuids: ['step_2_no', 'step_2'] } );
	instance.connect( { uuids: ['step_2_error', 'step_2'] } );
	instance.connect( { uuids: ['step_4_yes', 'step_3'] } );
	instance.connect( { uuids: ['step_4_no', 'step_3'] } );
} );
</script>