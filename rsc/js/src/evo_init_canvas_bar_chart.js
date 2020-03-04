/**
 * This file initialize Canvas Bar Chart JS
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois PLANQUE - {@link http://fplanque.com/}
 * 
 * Depends on jQuery
 */
jQuery( document ).ready( function() 
{
	if( typeof( canvas_charts_config_data ) == 'undefined' )
	{	// Don't execute code below because no config var is found:
		return;
	}

	var config = canvas_charts_config_data;
	
	jQuery.jqplot.postDrawHooks.push( function()
	{
		jQuery( '.jqplot-overlayCanvas-canvas' ).css( 'z-index', '0' ); //send overlay canvas to back
		jQuery( '.jqplot-series-canvas' ).css( 'z-index', '1' ); //send series canvas to front
		jQuery( '.jqplot-highlighter-tooltip' ).css( 'z-index', '2' ); //make sure the tooltip is over the series
		jQuery( '.jqplot-event-canvas' ).css( 'z-index', '5' ); //must be on the very top since it is responsible for event catching and propagation
	} );

	var data = config['jqplot_data'];
	var plot_config = {
			seriesColors: config['series_colors'],
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
					ticks: config['jqplot_ticks'],
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
					numberRows: config['number_rows'],
				},
				labels: config['jqplot_labels'],
				show: true,
				location: 's',
				placement: 'outsideGrid',
				yoffset: 80
			},
			highlighter: {
				show: true,
				showMarker: false,
				tooltipAxes: 'y',
			},
		};

	if( config['draw_last_line'] )
	{
		var temp_series = [];
		var n = config['jqplot_data'].length;
		for( var i = 0; i < n; i++ )
		{
			temp_series.push( {} );
		}
		temp_series.push( {
				disableStack : true,//otherwise it wil be added to values of previous series
				renderer: $.jqplot.LineRenderer,
				lineWidth: 3,
				pointLabels: { show: true },
				markerOptions: { size: 10 }
			} );

		plot_config['series'] = temp_series;
	}

	if( config['jqplot_canvas_objects'] )
	{
		plot_config['canvasOverlay'] = {
			show: true,
			objects: config['jqplot_canvas_objects'],
		}
	}

	plot = jQuery.jqplot( config['canvas_id'], data, plot_config );

	jQuery( config['canvas_id'] ).data( 'plot', plot );

	if( window[config['init_js_callback']] && typeof window[config['init_js_callback'] == 'function'] )
	{
		window[config['init_js_callback']]();
	}
	
	// Highlight legend
	jQuery( '#' + config['canvas_id'] ).bind( 'jqplotDataHighlight', function( ev, seriesIndex, pointIndex, data )
		{
			jQuery( '#' + config['canvas_id'] + ' td.jqplot-table-legend' ).removeClass( 'legend-text-highlighted' );
			jQuery( '#' + config['canvas_id'] + ' td.jqplot-table-legend' ).eq( seriesIndex * 2 + 1 ).addClass( 'legend-text-highlighted' )
				.prev().addClass( 'legend-text-highlighted' );

			if( config['link_data'] )
			{
				jQuery( '#' + config['canvas_id'] + ' .jqplot-event-canvas' ).css( 'cursor', 'pointer' );
			}
		} );

	jQuery( '#' + config['canvas_id'] ).bind( 'jqplotDataUnhighlight', function( ev, seriesIndex, pointIndex, data )
		{
			jQuery( '#' + config['canvas_id'] + ' td.jqplot-table-legend' ).removeClass( 'legend-text-highlighted' );
			if( config['link_data'] )
			{
				jQuery( '#' + config['canvas_id'] + ' .jqplot-event-canvas' ).css( 'cursor', 'auto' );
			}
			jQuery( '#' + config['canvas_id'] + ' .jqplot-highlighter-tooltip' ).hide();
		} );

	var canvas_offset = jQuery( '#' + config['canvas_id'] ).offset();
	jQuery( '#' + config['canvas_id'] ).mousemove( function( ev )
		{
			jQuery( '#' + config['canvas_id'] + ' .jqplot-highlighter-tooltip' ).css( {
				top: ev.pageY - canvas_offset.top - 16,
				left: ev.pageX - canvas_offset.left - 16
			} );
		} );

	// Open an url on click
	var jqplot_link_url = config['jqplot_link_url'];
	var jqplot_link_dates = config['jqplot_link_dates'];
	var jqplot_link_params = config['jqplot_link_params'];
	jQuery( '#' + config['canvas_id'] ).bind( 'jqplotDataClick', function ( ev, seriesIndex, pointIndex, data )
		{
			if( typeof( jqplot_link_params[ seriesIndex ] ) == 'undefined' )
			{
				return false;
			}

			var url = jqplot_link_url.replace( /\$date\$/g, jqplot_link_dates[ pointIndex ] );
			url = url.replace( '$param1$', jqplot_link_params[ seriesIndex ][0] );
			url = url.replace( '$param2$', jqplot_link_params[ seriesIndex ][1] );

			location.href = url;
		} );

} );
