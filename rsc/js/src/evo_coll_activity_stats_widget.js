/**
 * This file initialize Collection Activity Statistics widget JS
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
function resize_coll_activity_stat_widget()
{
	var config = coll_activity_stats_widget_config;
	var	originalData = [], weekData = [], xLabels = [],
			displayed = config['time_period'],
			resizeTimer;

	if( plot == undefined )
	{
		plot = jQuery( '#canvasbarschart' ).data( 'plot' );
		xLabels = plot.axes.xaxis.ticks.slice(0);
		for( var i = 0; i < plot.series.length; i++ )
		{
			originalData.push( plot.series[i].data.slice(0) );
		}

		if( originalData[0].length == 7 )
		{
			weekData = originalData;
		}
		else
		{
			for( var i = 0; i < originalData.length; i++ )
			{
				var weekSeries = [];
				for( var j = 7, k = 1; j > 0; j--, k++ )
				{
					weekSeries.unshift( [ j, originalData[i][originalData[i].length - k][1] ] );
				}
				weekData.push( weekSeries );
			}
		}
	}

	if( jQuery( '#canvasbarschart' ).width() < 650 )
	{
		if( displayed != 'last_week' )
		{
			for( var i = 0; i < plot.series.length; i++ )
			{
				plot.series[i].data = weekData[i];
			}
			plot.axes.xaxis.ticks = xLabels.slice( -7 );
			displayed = 'last_week';
		}
	}
	else
	{
		if( displayed != 'last_month' )
		{
			for( var i = 0; i < plot.series.length; i++ )
			{
				plot.series[i].data = originalData[i];
			}
			plot.axes.xaxis.ticks = xLabels;
			displayed = 'last_month';
		}
	}
	
	plot.replot( { resetAxes: true } );
}

jQuery( window ).resize( function()
	{
		clearTimeout( resizeTimer );
		resizeTimer = setTimeout( resize_coll_activity_stat_widget, 100 );
	} );
