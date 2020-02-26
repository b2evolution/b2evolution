<?php
/**
 * This file implements the function to draw canvas charts.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}.
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
 * @param string Javascript callback function to execute after rendering
 */
function CanvasBarsChart( $chart, $init_js_callback = NULL, $canvas_id = 'canvasbarschart' )
{
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
			$jqplot_data[] = $data;
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

	$jqplot_link_dates = array();
	if( isset( $chart['link_data'] ) )
	{
		foreach( $chart['dates'] as $date )
		{
			$jqplot_link_dates[] = urlencode( date( locale_datefmt(), $date ) );
		}
	}

	$jqplot_link_params = array();
	if( isset( $chart['link_data'] ) )
	{
		foreach( $chart['link_data']['params'] as $types )
		{
			$jqplot_link_params[] = "['".implode( "','", $types )."']";
		}
	}

	// Series color:
	$series_colors = array_map( function( $color )
		{
			return '#'.$color;
		}, $chart['series_color'] );

	$canvas_charts_config_data = array(
			'canvas_id'             => $canvas_id,
			'jqplot_data'           => $jqplot_data,
			'jqplot_labels'         => $jqplot_legend,
			'jqplot_ticks'          => $jqplot_ticks,
			'jqplot_canvas_objects' => $jqplot_canvas_objects,
			'jqplot_link_url'       => isset( $chart['link_data']['url'] ) ? $chart['link_data']['url'] : NULL,
			'jqplot_link_dates'     => $jqplot_link_dates,
			'jqplot_link_params'    => $jqplot_link_params,
			'series_colors'         => $series_colors,
			'number_rows'           => isset( $chart['legend_numrows'] ) ? (int) $chart['legend_numrows'] : 1,
			'draw_last_line'        => isset( $chart['draw_last_line'] ) && $chart['draw_last_line'],
			'init_js_callback'      => $init_js_callback,
			'link_data'             => isset( $chart['link_data'] ),
		);

	expose_var_to_js( 'canvas_charts_config_data', json_encode( $canvas_charts_config_data ) );

	echo '<div id="'.$canvas_id.'" style="height: '.$chart['canvas_bg']['height'].'px; width: '.$chart['canvas_bg']['width'].'px; margin: auto 35px;"></div>';
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
<script>
jQuery( window ).on( 'load', function()
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
