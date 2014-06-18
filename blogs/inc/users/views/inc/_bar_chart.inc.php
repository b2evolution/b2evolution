<?php
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

	$chart[ 'chart_type' ] = 'stacked column';

	$chart[ 'series_color' ] = array (
			'990066',
			'ff66cc',
			'003399',
			'6699ff',
			'666666',
			'cccccc'
		);

	$chart[ 'series_gap' ] = array ( 'set_gap'=>0, 'bar_gap'=>0 );

	$chart[ 'axis_category' ] = array (
			'font'  =>"arial",
			'bold'  =>true,
			'size'  =>11,
			'color' =>'000000',
			'alpha' =>75,
			'orientation' => 'diagonal_up',
			// 'skip'=>2
		);

	$chart[ 'axis_value' ] = array (
			// 'font'   =>"arial",
			// 'bold'   =>true,
			'size'   => 11,
			'color'  => '000000',
			'alpha'  => 75,
			'steps'  => 4,
			'prefix' => "",
			'suffix' => "",
			'decimals'=> 0,
			'separator'=> "",
			'show_min'=> false );

	$chart[ 'canvas_bg' ] = array (
			'width'  => 780,
			'height' => 355,
			'color'  => $AdminUI->get_color( 'payload_background' )
		);

	$chart[ 'chart_rect' ] = array (
			'x'      => 50,
			'y'      => 5,
			'width'  => 720,
			'height' => 250
		);

	$chart[ 'legend_rect' ] = array (
			'x'      => 50,
			'y'      => 320,
			'width'  => 720,
			'height' => 8,
			'margin' => 6
		);


	$chart[ 'chart_bg' ] = array (
			'positive_color' => "ffffff",
			// 'negative_color'  =>  string,
			'positive_alpha' => 100,
			// 'negative_alpha'  =>  int
		);

	$chart [ 'legend_bg' ] = array (
			'bg_color'          =>  "ffffff",
			'bg_alpha'          =>  100,
			// 'border_color'      =>  "000000",
			// 'border_alpha'      =>  100,
			// 'border_thickness'  =>  1
		);

	$chart [ 'legend_label' ] = array(
			// 'layout'  =>  "horizontal",
			// 'font'    =>  string,
			// 'bold'    =>  boolean,
			'size'    =>  10,
			// 'color'   =>  string,
			// 'alpha'   =>  int
		);


	$chart[ 'chart_border' ] = array (
			'color'=>"cccccc",
			'top_thickness'=>1,
			'bottom_thickness'=>1,
			'left_thickness'=>1,
			'right_thickness'=>1
		);

	$chart[ 'chart_value' ] = array (
			// 'prefix'         =>  string,
			// 'suffix'         =>  " views",
			// 'decimals'       =>  int,
			// 'separator'      =>  string,
			'position'       =>  "cursor",
			'hide_zero'      =>  true,
			// 'as_percentage'  =>  boolean,
			'font'           =>  "arial",
			'bold'           =>  true,
			'size'           =>  14,
			'color'          =>  "000000",
			'alpha'          =>  75
		);


?>
