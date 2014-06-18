<?php
/**
 * charts.php v1.6
 * ------------------------------------------------------------------------
 * Copyright (c) 2004, maani.us
 * ------------------------------------------------------------------------
 * This file is part of "PHP/SWF Charts"
 *
 * PHP/SWF Charts is a shareware. See http://www.maani.us/charts/ for
 * more information.
 * ------------------------------------------------------------------------
 * @version $Id: _swfcharts.php 3328 2013-03-26 11:44:11Z yura $
 * @package libs
 */
if( ! defined( 'EVO_MAIN_INIT' ) ) die( 'Please, do not access this page directly.' );

/**
 * Draw the SWF chart.
 *
 * @param array Chart data
 */
function DrawChart( $chart )
{
	// by fplanque:
	global $rsc_url;
	$path = $rsc_url;

	// defaults:
	if ( ! isset( $chart['canvas_bg']['width'] ) )
	{
		$chart['canvas_bg']['width'] = 400;
	}
	if ( ! isset( $chart['canvas_bg']['height'] ) )
	{
		$chart['canvas_bg']['height'] = 250;
	}
	if ( ! isset( $chart['canvas_bg']['color'] ) )
	{
		$chart['canvas_bg']['color'] = '666666';
	}

	$params  = '';
	foreach ( $chart as $k => $v )
	{
		$count = is_array( $v ) ? count( $v ) : 0;
		switch( $k )
		{
			case 'chart_data':
				$params .= 'rows='.$count.'&'
						  .'cols='.count( $v[0] ).'&';
				for ( $r = 0; $r < $count; ++$r )
				{
					$params .= 'r'.$r.'='.implode( ';', $v[$r] ).'&';
				}
				break;

			case 'draw_text':
				for ( $r = 0; $r < $count; ++$r )
				{
					$params .= 'text_'.$r.'=';
					$first   = true;
					foreach ( $v[$r] as $tk => $tv )
					{
						if ( $first )
						{
							$first = false;
						}
						else
						{
							$params .= ';';
						}
						$params .= $tk.':'.$tv;
					}
					$params .= '&';
				}
				break;

			case 'link':
				for ( $r = 0; $r < $count; ++$r )
				{
					$params .= 'link_'.$r.'=';
					$first   = true;
					foreach ( $v as $lk => $lt )
					{
						if ( $first )
						{
							$first = false;
						}
						else
						{
							$params .= ';';
						}
						$params .= $lk.':'.$lt;
					}
					$params .= '&';
				}
				break;

			default:
				if ( is_array( $v ) )
				{
					$params .= $k.'=';
					$first   = true;
					foreach( $v as $dk => $dv )
					{
						if ( $first )
						{
							$first = false;
						}
						else
						{
							$params .= ';';
						}
						$params .= $dk.':'.$dv;
					}
					$params .= '&';
				}
				else
				{
					$params .= $k.'='.$v.'&';
				}
				break;
		}
	}
?>
<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"
		codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0"
		width="<?php echo $chart['canvas_bg']['width']; ?>"
		height="<?php echo $chart['canvas_bg']['height']; ?>"
		id="charts">
	<PARAM NAME="movie" VALUE="<?php echo $path.'charts.swf'; ?>?<?php echo $params; ?>" />
	<PARAM NAME="quality" VALUE="high" />
	<PARAM NAME="bgcolor" VALUE="<?php echo $chart['canvas_bg']['color']; ?>" />

	<EMBED src="<?php echo $path.'charts.swf'; ?>?<?php echo $params; ?>"
			quality="high"
			NAME="charts"
			TYPE="application/x-shockwave-flash"
			PLUGINSPAGE="http://www.macromedia.com/go/getflashplayer"
			bgcolor="<?php echo $chart['canvas_bg']['color']; ?>"
			WIDTH="<?php echo $chart['canvas_bg']['width']; ?>"
			HEIGHT="<?php echo $chart['canvas_bg']['height']; ?>"></EMBED>
</object>
<?php
}

?>