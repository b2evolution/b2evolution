<?php
/**
 * This file implements the Skin properties form.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 *
 * @author fplanque: Francois PLANQUE.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Skin
 */

global $Collection, $Blog, $current_User;

$Form = new Form( NULL, 'skin_settings_checkchanges' );

$Form->begin_form( 'fform' );

	$Form->add_crumb( 'collection' );
	$Form->hidden_ctrl();
	$Form->hidden( 'tab', 'skin' );
	$Form->hidden( 'action', 'update' );
	$Form->hidden( 'blog', $Blog->ID );

	$skin_type_params = array(
		'normal' => array(
			'skin_ID' => $Blog->get_setting( 'normal_skin_ID' ),
			'fieldset_title' => T_('Default skin'),
		),
		'mobile' => array(
			'skin_ID' => $Blog->get_setting( 'mobile_skin_ID', true ),
			'fieldset_title' => T_('Default mobile phone skin'),
		),
		'tablet' => array(
			'skin_ID' => $Blog->get_setting( 'tablet_skin_ID', true ),
			'fieldset_title' => T_('Default tablet skin'),
		),
	);

	foreach( $skin_type_params as $type => $params )
	{
		$fieldset_title_links = '<span class="floatright">&nbsp;'.action_icon( T_('Select another skin...'), 'choose', regenerate_url( 'action', 'ctrl=coll_settings&amp;skinpage=selection&amp;skin_type='.$type ), ' '.T_('Choose a different skin').' &raquo;', 3, 4, array( 'class' => 'action_icon btn btn-info btn-sm' ) );
		if( $current_User->check_perm( 'options', 'view' ) && ( $params[ 'skin_ID' ] ) )
		{ // display Reset params only when skin ID has a real value ( when skin_ID = 0 means it must be the same as the normal skin value )
			$fieldset_title_links .= action_icon( T_('Reset params'), 'reload',
					regenerate_url( 'action', 'ctrl=skins&amp;skin_ID='.$params[ 'skin_ID' ].'&amp;blog='.$Blog->ID.'&amp;action=reset&amp;'.url_crumb('skin') ),
					' '.T_('Reset params'), 3, 4, array(
						'class'   => 'action_icon btn btn-default btn-sm',
						'onclick' => 'return confirm( \''.TS_( 'This will reset all the params to the defaults recommended by the skin.\nYou will lose your custom settings.\nAre you sure?' ).'\' )',
				) );
		}
		$fieldset_title_links .= '</span>';
		display_skin_fieldset( $Form, $params[ 'skin_ID' ], array( 'fieldset_title' => $params[ 'fieldset_title' ], 'fieldset_links' => $fieldset_title_links ) );
	}

$Form->end_form( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) ) );

?>