<?php
/**
 * This file implements the function to draw canvas charts.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
*
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package libs
 */
if( ! defined( 'EVO_MAIN_INIT' ) ) die( 'Please, do not access this page directly.' );

/**
 * Draw the canvas bars chart.
 *
 * @param array Chart bars data
 */
function CanvasBarsChart( $chart )
{
?>
<div id="canvasbarschart" style="height:<?php echo $chart['canvas_bg']['height']; ?>px;width:<?php echo $chart['canvas_bg']['width']; ?>px;margin:auto auto 35px;"></div>
<script type="text/javascript">
jQuery( window ).load( function()
{
	<?php
	// Init data for jqPlot format:
	$jqplot_data = array();
	$jqplot_legend = array();
	foreach( $chart['chart_data'] as $i => $data )
	{
		if( $i > 0 )
		{ 
			// Legend label
			$jqplot_legend[] = $data[0];
			// Data
			array_shift( $data );
			$jqplot_data[] = '['.implode( ',', $data ).']';
		}
	}

	// Ticks
	$jqplot_ticks = $chart['chart_data'][ 0 ];
	array_shift( $jqplot_ticks );

	// Weekend bands
	$jqplot_canvas_objects = array();
	foreach( $chart['dates'] as $d => $date )
	{
		$week_day = date( 'w', $date );
		if( $week_day == 0 || $week_day == 6 )
		{
			$jqplot_canvas_objects[] = '{rectangle: {
					xmin: '.( $d + 0.5 ).',
					xmax: '.( $d + 1.5 ).',
					xminOffset: "0",
					xmaxOffset: "0",
					yminOffset: "0",
					ymaxOffset: "0",
					color: "rgba(0,0,0,0.05)"
				}}';
		}
	}

	?>
	jQuery.jqplot.postDrawHooks.push( function()
	{
		jQuery( '.jqplot-overlayCanvas-canvas' ).css( 'z-index', '0' ); //send overlay canvas to back
		jQuery( '.jqplot-series-canvas' ).css( 'z-index', '1' ); //send series canvas to front
		jQuery( '.jqplot-highlighter-tooltip' ).css( 'z-index', '2' ); //make sure the tooltip is over the series
		jQuery( '.jqplot-event-canvas' ).css( 'z-index', '5' ); //must be on the very top since it is responsible for event catching and propagation
	} );

	var data = [<?php echo implode( ',', $jqplot_data ); ?>];
	jQuery.jqplot( 'canvasbarschart', data, {
		seriesColors: [ '#<?php echo implode( '\', \'#', $chart[ 'series_color' ] ); ?>' ],
		stackSeries: true,
		animate: !$.jqplot.use_excanvas,
		seriesDefaults:{
			renderer:$.jqplot.BarRenderer,
			rendererOptions: {
				highlightMouseOver: true,
				shadow: false,
				barMargin: 2,
				animation: { speed: 900 },
			}
		},
		<?php
		if( isset( $chart['draw_last_line'] ) && $chart['draw_last_line'] )
		{ // Draw a line from last data
		?>
		series: [<?php echo str_repeat( '{},', count( $jqplot_data ) - 1 ); ?>
		{
			disableStack : true,//otherwise it wil be added to values of previous series
			renderer: $.jqplot.LineRenderer,
			lineWidth: 3,
			pointLabels: { show: true },
			markerOptions: { size: 10 }
		}],
		<?php } ?>
		grid: {
			shadow: false,
			borderWidth: 1,
			borderColor: '#e5e5e5',
			gridLineColor: '#e5e5e5',
			background: '#fff'
		},
		axes: {
			xaxis: {
				renderer: $.jqplot.CategoryAxisRenderer,
				rendererOptions: {
					tickRenderer: $.jqplot.CanvasAxisTickRenderer
				},
				ticks: ['<?php echo implode( "','", $jqplot_ticks ); ?>'],
				tickOptions: {
					showGridline: false,
					angle: -45,
					fontFamily: 'Arial, Helvetica, sans-serif',
					fontSize: '13px',
				},
			},
			yaxis: { min: 0 }
		},
		legend: {
			renderer: $.jqplot.EnhancedLegendRenderer,
			rendererOptions: {
				numberRows: <?php echo ( isset( $chart['legend_numrows'] ) ? $chart['legend_numrows'] : '1' ); ?>
			},
			labels: ['<?php echo implode( "','", $jqplot_legend ); ?>'],
			show: true,
			location: 's',
			placement: 'outside',
			yoffset: 80
		},
		highlighter: {
			show: true,
			showMarker: false,
			tooltipAxes: 'y',
		},
		<?php
		if( ! empty( $jqplot_canvas_objects ) )
		{ // Draw overlay boxes for weekends
		?>
		canvasOverlay: {
			show: true,
			objects: [ <?php echo implode( ',', $jqplot_canvas_objects ); ?> ]
		}
		<?php } ?>
	} );

	// Highlight legend
	jQuery( '#canvasbarschart' ).bind( 'jqplotDataHighlight', function( ev, seriesIndex, pointIndex, data )
	{
		jQuery( '#canvasbarschart td.jqplot-table-legend' ).removeClass( 'legend-text-highlighted' );
		jQuery( '#canvasbarschart td.jqplot-table-legend' ).eq( seriesIndex * 2 + 1 ).addClass( 'legend-text-highlighted' )
			.prev().addClass( 'legend-text-highlighted' );
		<?php if( isset( $chart['link_data'] ) ) { ?>
		jQuery( '#canvasbarschart .jqplot-event-canvas' ).css( 'cursor', 'pointer' );
		<?php } ?>
	} );
	jQuery( '#canvasbarschart' ).bind( 'jqplotDataUnhighlight', function( ev, seriesIndex, pointIndex, data )
	{
		jQuery( '#canvasbarschart td.jqplot-table-legend' ).removeClass( 'legend-text-highlighted' );
		<?php if( isset( $chart['link_data'] ) ) { ?>
		jQuery( '#canvasbarschart .jqplot-event-canvas' ).css( 'cursor', 'auto' );
		<?php } ?>
		jQuery( '#canvasbarschart .jqplot-highlighter-tooltip' ).hide();
	} );
	var canvas_offset = jQuery( '#canvasbarschart' ).offset();
	jQuery( '#canvasbarschart' ).mousemove( function( ev )
	{
		jQuery( '#canvasbarschart .jqplot-highlighter-tooltip' ).css( {
			top: ev.pageY - canvas_offset.top - 16,
			left: ev.pageX - canvas_offset.left - 16
		} );
	} );

	<?php
	if( isset( $chart['link_data'] ) )
	{
		$chart_link_dates = array();
		foreach( $chart['dates'] as $date )
		{
			$chart_link_dates[] = urlencode( date( locale_datefmt(), $date ) );
		}
	?>
	// Open an url on click
	var jqplot_link_url = '<?php echo $chart['link_data']['url']; ?>';
	var jqplot_link_dates = ['<?php echo implode( "','", $chart_link_dates ); ?>'];
	var jqplot_link_params = [<?php
		$params = array();
		foreach( $chart['link_data']['params'] as $types )
		{
			$params[] = "['".implode( "','", $types )."']";
		}
		echo implode( ',', $params );
		?>];
	jQuery( '#canvasbarschart' ).bind( 'jqplotDataClick', function ( ev, seriesIndex, pointIndex, data )
	{
		if( typeof( jqplot_link_params[ seriesIndex ] ) == 'undefined' )
		{
			return false;
		}

		var url = jqplot_link_url.replace( /\$date\$/g, jqplot_link_dates[ pointIndex ] );
		url = url.replace( '$param1$', jqplot_link_params[ seriesIndex ][0] );
		url = url.replace( '$param2$', jqplot_link_params[ seriesIndex ][1] );

		//console.log( url );
		location.href = url;
	} );
	<?php } ?>
} );
</script>
<?php
}


/**
 * Draw the canvas donut chart.
 *
 * @param array Chart donut data
 */
function CanvasDonutChart( $chart )
{
?>
<div id="canvasdonutchart" style="height:<?php echo $chart['height']; ?>px;width:<?php echo $chart['width']; ?>px;"></div>
<script type="text/javascript">
jQuery( window ).load( function()
{
<?php
	$data_vars = array();
	foreach( $chart[ 'data' ] as $d => $data_level )
	{ // Init the chart data
		$data = array();
		foreach( $data_level as $label => $value )
		{
			$data[] = '[\''.$label.'\','.$value.']';
		}
		echo '	var data'.$d.' = ['.implode( ',', $data ).'];'."\n";
		$data_vars[] = 'data'.$d;
	}
	?>

	jQuery.jqplot( 'canvasdonutchart', [<?php echo implode( ',', $data_vars ); ?>], {
		series: [
		<?php
		foreach( $chart[ 'series_color' ] as $colors )
		{ // Init the colors for each level
		?>
			{ seriesColors: [ '#<?php echo implode( '\', \'#', $colors ); ?>' ] },
		<?php } ?>
		],
		seriesDefaults: {
			renderer:$.jqplot.DonutRenderer,
			rendererOptions: {
				sliceMargin: 1,
				startAngle: -90,
				shadow: false,
				innerDiameter: 100,
			},
			highlighter: {
				tooltipAxes: 'y',
				useAxesFormatters: false
			}
		},
		grid: {
			shadow: false,
			borderWidth: 1,
			borderColor: '#e5e5e5',
			background: '#fff'
		},
		legend: {
			renderer: $.jqplot.EnhancedLegendRenderer,
			rendererOptions: {
				numberRows: <?php echo ( isset( $chart['legend_numrows'] ) ? $chart['legend_numrows'] : '1' ); ?>
			},
			labels: ['<?php echo implode( "','", $chart['legends'] ); ?>'],
			show: true,
			predraw: true,
			location: 'e',
			placement: 'outside',
			yoffset: 10
		},
		highlighter: { show: true },
	} );

	// Highlight legend
	jQuery( '#canvasdonutchart' ).bind( 'jqplotDataHighlight', function( ev, seriesIndex, pointIndex, data )
	{
		//console.log( ev, seriesIndex, pointIndex, data );
		jQuery( '#canvasdonutchart td.jqplot-table-legend' ).removeClass( 'legend-text-highlighted' );
		if( data[0].length == 1 )
		{
			var x = data[0] == 'F' ? 0 : ( data[0] == 'M' ? 1 : 2 );
			var obj = jQuery( '#canvasdonutchart tr:eq(' + ( x * 3 ) + '), #canvasdonutchart tr:eq(' + ( x * 3 + 1) + '), #canvasdonutchart tr:eq(' + ( x * 3 + 2 ) + ')' );
			obj.children( 'td' ).addClass( 'legend-text-highlighted' );
		}
		else
		{
			var x = 0;
			var y = 0;
			var td_selector = 'td';
			switch( data[0][0] )
			{
				case 'M': y = 1; break;
				case 'G': y = 2; break;
			}
			switch( data[0][1] )
			{
				case 'i': x = 1; break;
				case 'c': x = 2; break;
			}

			if( data[0].length == 3 )
			{
				var z = 0;
				if( data[0][2] == 'n' )
				{
					z = 2;
				}
				td_selector = 'td:eq(' + z + '), td:eq(' + ( z + 1 ) + ')';
			}

			var obj = jQuery( '#canvasdonutchart tr:eq(' + ( x + ( y * 3 ) ) + ')' );
			obj.children( td_selector ).addClass( 'legend-text-highlighted' );
		}
	} );
	jQuery( '#canvasdonutchart' ).bind( 'jqplotDataUnhighlight', function( ev, seriesIndex, pointIndex, data )
	{
		jQuery( '#canvasdonutchart td.jqplot-table-legend' ).removeClass( 'legend-text-highlighted' );
	} );
} );
</script>
<?php
}
?>