<?php
/**
 * This file display the form to create sample hit data for testing
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$Form = new Form( NULL, 'create_hits', 'post', 'compact' );

$Form->global_icon( T_('Cancel!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform',  T_('Create sample data for hits testing') );

	$Form->add_crumb( 'tools' );

	$Form->text_input( 'days', 10, 3, T_( 'Days of stats to generate' ), '', array( 'required' => true ) );
	$Form->text_input( 'min_interval', 0, 5, T_( 'Minimal interval between 2 consecutive hits (sec)' ), '', array( 'required' => true ) );
	$Form->text_input( 'max_interval', 5000, 5, T_( 'Maximal interval between 2 consecutive hits (sec)' ), '', array('required' => true ) );

	$Form->hidden( 'ctrl', 'tools' );
	$Form->hidden( 'action',  'create_sample_hits' );
	$Form->hidden( 'tab3', get_param( 'tab3' ) );

$Form->end_form( array( array( 'submit', 'submit', T_('Generate'), 'SaveButton' ) ) );

?>