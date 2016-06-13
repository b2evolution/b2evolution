<?php
/**
 * This file implements the UI view for the A/B Variation Tests.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Create query:
$SQL = new SQL();
$SQL->SELECT( 'vtst_ID, vtst_name' );
$SQL->FROM( 'T_vtest__test' );

// Create result set:
$Results = new Results( $SQL->get(), 'vtest_', '-A' );

$Results->Cache = & get_VariationTestCache();

$Results->title = T_('A/B Variation Tests').get_manual_link( 'variation-testing' );

$Results->cols[] = array(
		'th' => T_('ID'),
		'order' => 'vtst_ID',
		'td_class' => 'shrinkwrap',
		'td' => '$vtst_ID$',
	);

$Results->cols[] = array(
		'th' => T_('Name'),
		'order' => 'vtst_name',
		'td' => '$vtst_name$',
	);

if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:

	$Results->cols[] = array(
							'th' => T_('Actions'),
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							'td' => '@action_icon("edit")@@action_icon("delete")@',
						);

	$Results->global_icon( T_('Create a new variation test...'), 'new', regenerate_url( 'action', 'action=new' ), T_('New variation test').' &raquo;', 3, 4  );
}


// Display results:
$Results->display();

?>