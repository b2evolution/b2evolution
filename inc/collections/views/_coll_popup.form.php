<?php
/**
 * This file implements the UI view for the Collection features popup properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
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


$Form->begin_fieldset( T_('Marketing Popup').get_manual_link( 'marketing-popup' ) );

	$Form->radio( 'marketing_popup_using', $edited_Blog->get_setting( 'marketing_popup_using' ), array(
			array( 'never', T_('Never') ),
			array( 'anonymous', T_('For anonymous users') ),
			array( 'all', T_('For all users') ),
		), T_('Use at exit intent'), true );

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
		), T_('Animation') );

	$container_disps = array( 'front', 'posts', 'single', 'page', 'catdir' );
	foreach( $container_disps as $container_disp )
	{
		$Form->text_input( 'marketing_popup_container_'.$container_disp, $edited_Blog->get_setting( 'marketing_popup_container_'.$container_disp ), 30, sprintf( T_('Container for %s'), 'disp='.$container_disp ) );
	}
	$Form->text_input( 'marketing_popup_container_other_disps', $edited_Blog->get_setting( 'marketing_popup_container_other_disps' ), 30, T_('Container for other disps') );

$Form->end_fieldset();


$Form->end_form( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );
?>