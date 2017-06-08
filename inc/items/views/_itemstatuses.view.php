<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2009-2016 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * @package evocore
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $admin_url;

// Create query
$SQL = new SQL();
$SQL->SELECT( '*' );
$SQL->FROM( 'T_items__status' );

// Create result set:
$Results = new Results( $SQL->get(), 'pst_' );

$Results->title = T_('Post Statuses').get_manual_link( 'managing-item-statuses' );


$Results->cols[] = array(
		'th' => T_('ID'),
		'order' => 'pst_ID',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
		'td' => '$pst_ID$',
	);


$Results->cols[] = array(
		'th' => T_('Name'),
		'order' => 'pst_name',
		'td' => '<strong><a href="'.$admin_url.'?ctrl=itemstatuses&amp;pst_ID=$pst_ID$&amp;action=edit">$pst_name$</a></strong>',
	);

if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
	$Results->cols[] = array(
			'th' => T_('Actions'),
			'th_class' => 'shrinkwrap',
			'td_class' => 'shrinkwrap',
			'td' => action_icon( T_('Edit this post status...'), 'edit', $admin_url.'?ctrl=itemstatuses&amp;pst_ID=$pst_ID$&amp;action=edit' )
					.action_icon( T_('Duplicate this post status...'), 'copy', $admin_url.'?ctrl=itemstatuses&amp;pst_ID=$pst_ID$&amp;action=new' )
					.action_icon( T_('Delete this post status!'), 'delete', regenerate_url( 'pst_ID,action', 'pst_ID=$pst_ID$&amp;action=delete&amp;'.url_crumb( 'itemstatus' ) ) ),
		);

	$Results->global_icon( T_('Create a new post status...'), 'new',
		regenerate_url( 'action', 'action=new' ), T_('New post status').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );
}

// Display results:
$Results->display();

?>