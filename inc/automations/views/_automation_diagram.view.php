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


global $edited_Automation, $admin_url;

// Print out HTML boxes for steps and Initialise steps data to build connectors between steps by JS code below:
echo '<div class="evo_automation__diagram_canvas jtk-surface jtk-surface-nopan" id="evo_automation__diagram_canvas">';
$steps = $edited_Automation->get_diagram_steps_data();
foreach( $steps as $step )
{
	// Print box of step with data:
	echo '<div'.get_field_attribs_as_string( $step['attrs'] ).'>'
		.'<b>#'.$step['order'].' '.step_get_type_title( $step['type'] ).':</b><br>'
			.$step['label']
		.'</div>'."\n";
	
}
echo '</div>';
?>
<script type="text/javascript">
jQuery( document ).ready( function()
{	// CSS fix to make diagram canvas full height:
	jQuery( '#evo_automation__diagram_canvas' ).css( 'height', jQuery( window ).height() - 305 );
	/*var evo_diagram_canvas_parent = jQuery( '#evo_automation__diagram_canvas' ).parent();
	while( evo_diagram_canvas_parent.length > 0 )
	{
		evo_diagram_canvas_parent.css( 'height', '100%' );
		evo_diagram_canvas_parent = evo_diagram_canvas_parent.parent();
	}*/
} );

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
	point_source_no.connectorOverlays[1][1].location = 0.7;
	// "ERROR" red connector and source point:
	var point_source_error = jQuery.extend( true, {}, point_source );
	point_source_error.connectionType = 'error';
	point_source_error.paintStyle.fill = point_source_error.connectorStyle.stroke = '#eb5a46';
	point_source_error.connector[1].stub = [30, 30];
	point_source_error.connectorOverlays[0][1].width = 10;
	point_source_error.connectorOverlays[0][1].length = 10;
	point_source_error.connectorOverlays[1][1].cssClass = 'jtk-label jtk-label-error';
	point_source_error.connectorOverlays[1][1].location = 0.5;
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
		instance.draggable( jsPlumb.getSelector( '.evo_automation__diagram_step_box' ),
		{
			grid: [20, 20],
			stop: function( e )
			{
				jQuery.ajax(
				{
					type: 'POST',
					url: '<?php echo $admin_url; ?>',
					data:
					{
						'ctrl': 'automations',
						'action': 'update_step_position',
						'step_ID': e.el.id.replace( 'step_', '' ),
						'pos': e.pos,
						'crumb_automationstep': '<?php echo get_crumb( 'automationstep' ); ?>',
					}
				} );
			}
		} );

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

	var evo_jsplumb_init_step_box = function( step_box_ID )
	{
		if( jQuery( '#' + step_box_ID ).data( 'info-yes' ) != undefined )
		{	// Add "YES" red source point:
			instance.addEndpoint( step_box_ID, point_source_yes, { anchor: [ 0.33, 1, 0, 1 ], uuid: step_box_ID + '_yes' } );
		}
		if( jQuery( '#' + step_box_ID ).data( 'info-no' ) != undefined )
		{	// Add "NO" blue source point:
			instance.addEndpoint( step_box_ID, point_source_no, { anchor: [ 0.67, 1, 0, 1 ], uuid: step_box_ID + '_no' } );
		}
		if( jQuery( '#' + step_box_ID ).data( 'info-error' ) != undefined )
		{	// Add "ERROR" red source point:
			instance.addEndpoint( step_box_ID, point_source_error, { anchor: 'RightMiddle', uuid: step_box_ID + '_error' } );
		}
		// Add target black point:
		instance.addEndpoint( step_box_ID, point_target, { anchor: 'TopCenter', uuid: step_box_ID } );
		// Make whole step box is traget place to connect:
		instance.makeTarget( step_box_ID, { anchor: 'TopCenter', endpoint: 'Blank' } );
	};

	// Initialise step boxes:
	<?php
	foreach( $steps as $step_ID => $step )
	{
		echo 'evo_jsplumb_init_step_box( \'step_'.$step_ID.'\' );'."\n\t";
	}
	?>

	// Initialise connections between steps:
	<?php
	foreach( $steps as $step_ID => $step )
	{
		foreach( $step['next_steps'] as $next_step_type => $next_step_ID )
		{
			echo 'instance.connect( { uuids: [\'step_'.$step_ID.'_'.$next_step_type.'\', \'step_'.$next_step_ID.'\'] } );'."\n\t";
		}
	}
	?>
} );
</script>