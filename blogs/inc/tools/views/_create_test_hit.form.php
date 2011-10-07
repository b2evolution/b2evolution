<?php
/**
 * This file display the form to create sample hit data for testing
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * @version $Id$
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
	$Form->hidden( 'action',  'create_test_hit' );

$Form->end_form( array( array( 'submit', 'submit', T_('Generate'), 'SaveButton' ) ) );

/*
 * $Log$
 * Revision 1.3  2011/10/07 01:52:12  fplanque
 * more fixes
 *
 * Revision 1.2  2011/09/29 06:22:36  efy-vitalij
 * add config params to statistic generator form
 *
 * Revision 1.1  2011/09/26 15:38:08  efy-vitalij
 * add test hit information
 */
?>