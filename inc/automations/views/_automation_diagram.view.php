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

// Get data of all steps for diagram view:
$steps = $edited_Automation->get_diagram_steps_data();

// Check if current automation is active:
$is_automation_active = ( $edited_Automation->get( 'status' ) == 'active' );

// Display a button to play/pause current Automation:
if( $is_automation_active )
{	// To pause:
	echo action_icon( T_('Pause'), 'pause',
		$admin_url.'?ctrl=automations&amp;tab='.get_param( 'tab' ).'&amp;action=status_paused&amp;autm_ID='.$edited_Automation->ID.'&amp;'.url_crumb( 'automation' ),
		' '.T_('Pause'), 3, 4, array( 'class' => 'btn btn-danger' ) )
	.' <span class="red">('.T_('RUNNING').')</span>';
}
else
{	// To play:
	echo action_icon( T_('Play'), 'play',
		$admin_url.'?ctrl=automations&amp;tab='.get_param( 'tab' ).'&amp;action=status_active&amp;autm_ID='.$edited_Automation->ID.'&amp;'.url_crumb( 'automation' ),
		' '.T_('Play'), 3, 4, array( 'class' => 'btn btn-success' ) )
	.' <span class="orange">('.T_('PAUSED').')</span>';
}

if( count( $steps ) > 0 )
{	// Display a button to reset a diagram layout to default positions:
	echo '<a href="'.$admin_url.'?ctrl=automations&amp;action=reset_diagram&amp;autm_ID='.$edited_Automation->ID.'&amp;'.url_crumb( 'automationstep' ).'"'
				.' class="btn btn-danger pull-right" onclick="return confirm( \''.TS_('Are you sure you want to reset step positions to the default layout?').'\' )">'
			.T_('Reset layout')
		.'</a>';
}

// Print out HTML boxes for steps and Initialise steps data to build connectors between steps by JS code below:
echo '<div class="evo_automation__diagram_canvas jtk-surface jtk-surface-nopan clear" id="evo_automation__diagram_canvas">';
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
	jQuery( '#evo_automation__diagram_canvas' ).css( 'height', jQuery( window ).height() - 343	 );
} );

jsPlumb.ready( function ()
{
	var instance = jsPlumb.getInstance(
	{
		DragOptions: { cursor: 'pointer', zIndex: 2000 },
		Container: 'evo_automation__diagram_canvas',
		<?php echo $is_automation_active ? 'ConnectionsDetachable: false,' : ''; ?>
	} );

	// General properties for connectors and source points:
	var point_source = {
		endpoint: 'Dot',
		paintStyle: { radius: 7 },
		hoverPaintStyle: { radius: 10 },
		isSource: <?php echo $is_automation_active ? 'false' : 'true'; ?>,
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
			{	// Store the changed step box position in DB by AJAX:
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
		{	// Store vars to know what connector has been dragged in the event "connectionDragStop" below:
			b2evo_diagram_source_step_ID = connection.sourceId.replace( /^step_/, '' );
			b2evo_diagram_connection_type = connection.endpoints[0].connectionType;
			b2evo_diagram_target_step_ID = ( connection.target === null || ! connection.targetId.match( /^step_/g ) ? 0 : connection.targetId.replace( /^step_/, '' ) );
		} );

		instance.bind( 'connectionDragStop', function (connection)
		{
			// Get new target step ID:
			var updated_target_step_ID = ( connection.target === null || ! connection.targetId.match( /^step_/g ) ? 0 : connection.targetId.replace( /^step_/, '' ) );

			if( b2evo_diagram_target_step_ID != updated_target_step_ID )
			{	// If the target step has been really changed to another:
				jQuery.ajax(
				{	// Store the changed steps connection in DB by AJAX:
					type: 'POST',
					url: '<?php echo $admin_url; ?>',
					data:
					{
						'ctrl': 'automations',
						'action': 'update_step_connection',
						'step_ID': b2evo_diagram_source_step_ID,
						'connection_type': b2evo_diagram_connection_type,
						'target_step_ID': updated_target_step_ID,
						'crumb_automationstep': '<?php echo get_crumb( 'automationstep' ); ?>',
					}
				} );
			}
		} );

		<?php
		if( $is_automation_active )
		{	// Display an alert message if user tries to change a connector for active Automation:
		?>
		instance.bind( 'endpointClick', function()
		{	// Display an alert message if user tries to change a connector for active Automation:
			alert( '<?php echo TS_('You should pause this Automation in order to edit it.'); ?>' );
		} );
		<?php } ?>

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
		// Make whole step box is a target place to connect:
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