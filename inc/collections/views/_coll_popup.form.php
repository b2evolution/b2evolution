<?php
/**
 * This file implements the UI view for the Collection features popup properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}.
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog;


$Form = new Form( NULL, 'coll_popup_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'popup' );
$Form->hidden( 'blog', $edited_Blog->ID );


$Form->begin_fieldset( TB_('Marketing Popup').get_manual_link( 'marketing-popup' ) );

	$Form->radio( 'marketing_popup_using', $edited_Blog->get_setting( 'marketing_popup_using' ), array(
			array( 'never', TB_('Never') ),
			array( 'anonymous', TB_('For anonymous users') ),
			array( 'all', TB_('For all users') ),
		), TB_('Use at exit intent'), true );

	$Form->select_input_array( 'marketing_popup_animation', $edited_Blog->get_setting( 'marketing_popup_animation' ), array( 'random',
			'bounce', 'flash', 'pulse', 'rubberBand',
			'shake', 'headShake',
			'swing', 'tada', 'wobble', 'jello',
			'bounceIn', 'bounceInDown', 'bounceInLeft', 'bounceInRight', 'bounceInUp', 'bounceOut', 'bounceOutDown', 'bounceOutLeft', 'bounceOutRight', 'bounceOutUp',
			'fadeIn', 'fadeInDown', 'fadeInDownBig', 'fadeInLeft', 'fadeInLeftBig', 'fadeInRight', 'fadeInRightBig', 'fadeInUp', 'fadeInUpBig',
			'fadeOut', 'fadeOutDown', 'fadeOutDownBig', 'fadeOutLeft', 'fadeOutLeftBig', 'fadeOutRight', 'fadeOutRightBig', 'fadeOutUp', 'fadeOutUpBig',
			'flipInX', 'flipInY', 'flipOutX', 'flipOutY',
			'lightSpeedIn', 'lightSpeedOut',
			'rotateIn', 'rotateInDownLeft', 'rotateInDownRight', 'rotateInUpLeft', 'rotateInUpRight',
			'rotateOut', 'rotateOutDownLeft', 'rotateOutDownRight', 'rotateOutUpLeft', 'rotateOutUpRight',
			'hinge', 'jackInTheBox',
			'rollIn', 'rollOut',
			'zoomIn', 'zoomInDown', 'zoomInLeft', 'zoomInRight', 'zoomInUp',
			'zoomOut', 'zoomOutDown', 'zoomOutLeft', 'zoomOutRight', 'zoomOutUp',
			'slideInDown', 'slideInLeft', 'slideInRight', 'slideInUp',
			'slideOutDown', 'slideOutLeft', 'slideOutRight', 'slideOutUp',
			'heartBeat'
		), TB_('Animation') );

	$container_disps = array( 'front', 'posts', 'single', 'page', 'catdir' );
	foreach( $container_disps as $container_disp )
	{
		$Form->text_input( 'marketing_popup_container_'.$container_disp, $edited_Blog->get_setting( 'marketing_popup_container_'.$container_disp ), 30, sprintf( TB_('Container for %s'), 'disp='.$container_disp ) );
	}
	$Form->text_input( 'marketing_popup_container_other_disps', $edited_Blog->get_setting( 'marketing_popup_container_other_disps' ), 30, TB_('Container for other disps') );

	$Form->checkbox_input( 'marketing_popup_show_repeat', $edited_Blog->get_setting( 'marketing_popup_show_repeat' ), TB_('Repeat a showing'), array( 'note' => TB_('Repeat to show the marketing popup window on the same page even if it was already closed once.') ) );

	// Input and selector for 3rd option for "Frequency of a showing":
	$Form->output = false;
	$Form->switch_layout( 'none' );
	$period_inputs = $Form->text_input( 'marketing_popup_show_period_val', $edited_Blog->get_setting( 'marketing_popup_show_period_val' ), 3, '', '', array( 'type' => 'number', 'min' => 1, 'max' => 1000 ) )
		.$Form->select_input_array( 'marketing_popup_show_period_unit', $edited_Blog->get_setting( 'marketing_popup_show_period_unit' ), array(
			'hr'  => TB_('hours'),
			'day' => TB_('days'),
		), '' );
	$Form->switch_layout( NULL );
	$Form->output = true;

	$Form->radio( 'marketing_popup_show_frequency', $edited_Blog->get_setting( 'marketing_popup_show_frequency' ), array(
			array( 'always', TB_('Always'), TB_('Pop up is shown each time the page is loaded and the user tries to exit.') ),
			array( 'session', TB_('Session'), TB_('Pop up is shown once per browser session site wide.') ),
			array( 'period', sprintf( TB_('Show once every %s'), $period_inputs ) ),
		), TB_('Frequency of a showing'), true );

$Form->end_fieldset();


$Form->end_form( array( array( 'submit', 'submit', TB_('Save Changes!'), 'SaveButton' ) ) );
?>