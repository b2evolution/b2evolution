<?php
// charts.php v1.6
// ------------------------------------------------------------------------
// Copyright (c) 2004, maani.us
// ------------------------------------------------------------------------
// This file is part of "PHP/SWF Charts"
//
// PHP/SWF Charts is a shareware. See http://www.maani.us/charts/ for
// more information.
// ------------------------------------------------------------------------

function DrawChart($chart)
{
	// fplanque:
	global $rsc_url;
	$path = $rsc_url;

	//defaults
	if(!isset($chart[ 'canvas_bg' ]['width' ])){$chart[ 'canvas_bg' ]['width' ] =400;}
	if(!isset($chart[ 'canvas_bg' ]['height' ])){$chart[ 'canvas_bg' ]['height' ] =250;}
	if(!isset($chart[ 'canvas_bg' ]['color' ])){$chart[ 'canvas_bg' ]['color' ] ="666666";}
								
	$params="";
	$allKeys= array_keys($chart);
	for ($i=0;$i<count($allKeys);$i++)
	{
		switch($allKeys[$i]){
			case "chart_data":
			$params=$params."rows=".count($chart[ 'chart_data' ])."&";
			$params=$params."cols=".count($chart[ 'chart_data' ][0])."&";
			for ($r=0;$r<count($chart[ 'chart_data' ]);$r++){
				$params=$params."r".$r."=";
				for ($c=0;$c<count($chart[ 'chart_data' ][$r]);$c++)
				{
					$params=$params.$chart[ 'chart_data' ][$r][$c];
					if($c==count($chart[ 'chart_data' ][$r])-1){$params=$params."&";}
					else{$params=$params.";";}
				}
			}
			break;
				
			case "draw_text":
			for ($r=0;$r<count($chart[ 'draw_text' ]);$r++){
				$params=$params."text_".$r."=";
				$allKeys2= array_keys($chart[ 'draw_text' ][$r]);
				for ($k2=0;$k2<count($allKeys2);$k2++){
					$params=$params.$allKeys2[$k2].":".$chart[ 'draw_text' ][$r][$allKeys2[$k2]];
					if($k2<count($allKeys2)-1){$params=$params.";";}
				}
				$params=$params."&";
			}
			break;
			
			case "link":
			for ($r=0;$r<count($chart[ 'link' ]);$r++){
				$params=$params."link_".$r."=";
				$allKeys2= array_keys($chart[ 'link' ][$r]);
				for ($k2=0;$k2<count($allKeys2);$k2++){
					$params=$params.$allKeys2[$k2].":".$chart[ 'link' ][$r][$allKeys2[$k2]];
					if($k2<count($allKeys2)-1){$params=$params.";";}
				}
				$params=$params."&";
			}
			break;
			
			default:
			if(gettype($chart[$allKeys[$i]])=="array" ){
				$params=$params.$allKeys[$i]."=";
				$allKeys2= array_keys($chart[$allKeys[$i]]);
				for ($k2=0;$k2<count($allKeys2);$k2++){
					$params=$params.$allKeys2[$k2].":".$chart[$allKeys[$i]][$allKeys2[$k2]];
					if($k2<count($allKeys2)-1){$params=$params.";";}
				}
				$params=$params."&";
			}else{
				$params=$params.$allKeys[$i]."=".$chart[$allKeys[$i]]."&";
			}
		}
	}
?>
<object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"
				codebase="http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=6,0,0,0"
				width="<?php print $chart[ 'canvas_bg' ]['width' ]; ?>"
				height="<?php print $chart[ 'canvas_bg' ]['height' ]; ?>"
				id="charts">

	<PARAM NAME="movie" VALUE="<?php print $path."charts.swf"; ?>?<?php print $params; ?>" />
	<PARAM NAME="quality" VALUE="high" />
	<PARAM NAME="bgcolor" VALUE="<?php print $chart[ 'canvas_bg' ]['color' ]; ?>" />

	<EMBED src="<?php print $path."charts.swf"; ?>?<?php print $params; ?>"
				quality="high"
				NAME="charts"
				TYPE="application/x-shockwave-flash"
				PLUGINSPAGE="http://www.macromedia.com/go/getflashplayer"
				bgcolor="<?php print $chart[ 'canvas_bg' ]['color' ]; ?>"
				WIDTH="<?php print $chart[ 'canvas_bg' ]['width' ]; ?>"
				HEIGHT="<?php print $chart[ 'canvas_bg' ]['height' ]; ?>"></EMBED>

</object>

<?php
}
?>
