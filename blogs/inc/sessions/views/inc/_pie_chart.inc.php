<?php
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

	$chart[ 'chart_type' ] = 'pie';

	$chart[ 'series_color' ] = array (
			'ff0000',
			'ff9900',
			'ffff00',
			'00ff99',
			'00ffff',
			'0099ff',
			'3333ff',
			'9900ff',
		);

	$chart[ 'canvas_bg' ] = array (
			'width'  => 780,
			'height' => 350,
			'color'  => $AdminUI->get_color( 'payload_background' )
		);

	$chart[ 'chart_rect' ] = array (
			'x'      => 60,
			'y'      => 50,
			'width'  => 250,
			'height' => 250
		);

	$chart[ 'legend_rect' ] = array (
			'x'      => 440,
			'y'      => 50,
			'width'  => 300,
			'height' => 250,
			'margin' => 6
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
			'size'    =>  14,
			// 'color'   =>  string,
			// 'alpha'   =>  int
		);

	/*$chart[ 'chart_border' ] = array (
				'color'=>"000000",
				'top_thickness'=>1,
				'bottom_thickness'=>1,
				'left_thickness'=>1,
				'right_thickness'=>1
		);*/

	$chart[ 'chart_bg' ] = array (
			'positive_color' => "ffffff",
			// 'negative_color'  =>  string,
			'positive_alpha' => 0,
			// 'negative_alpha'  =>  int
		);


	$chart [ 'series_explode' ] =  array ( 15 );

	/*$chart[ 'axis_category' ] = array (
			'font'  =>"arial",
			'bold'  =>true,
			'size'  =>11,
			'color' =>'000000',
			'alpha' =>75,
			'orientation' => 'diagonal_up',
			// 'skip'=>2
		 );*/

	/* $chart[ 'axis_value' ] = array (	// 'font'   =>"arial",
			// 'bold'   =>true,
			'size'   => 11,
			'color'  => '000000',
			'alpha'  => 75,
			'steps'  => 4,
			'prefix' => "",
			'suffix' => "",
			'decimals'=> 0,
			'separator'=> "",
			'show_min'=> false ); */

	$chart[ 'chart_value' ] = array (
			// 'prefix'         =>  string,
			// 'suffix'         =>  " views",
			// 'decimals'       =>  int,
			// 'separator'      =>  string,
			'position'       =>  "outside",
			'hide_zero'      =>  true,
			'as_percentage'  =>  false, // this would give a total of 100%
			'font'           =>  "arial",
			'bold'           =>  true,
			'size'           =>  15,
			'color'          =>  "000000",
			'alpha'          =>  75
		);

?>
