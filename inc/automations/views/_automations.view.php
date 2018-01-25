<?php
/**
 * This file display the automations list
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $admin_url;

$SQL = new SQL( 'Get all automations' );
$SQL->SELECT( 'autm_ID, autm_name, autm_status' );
$SQL->FROM( 'T_automation__automation' );

$Results = new Results( $SQL->get(), 'autm_', 'A', NULL );

$Results->global_icon( T_('New automation'), 'new', regenerate_url( 'action', 'action=new' ), T_('New automation').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );

$Results->title = T_('Automations').get_manual_link( 'automations-list' );

$Results->cols[] = array(
		'th'       => T_('ID'),
		'order'    => 'autm_ID',
		'td'       => '$autm_ID$',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
	);

$Results->cols[] = array(
		'th'    => T_('Name'),
		'order' => 'autm_name',
		'td'    => ( $current_User->check_perm( 'options', 'edit' )
			? '<a href="'.$admin_url.'?ctrl=automations&amp;action=edit&amp;autm_ID=$autm_ID$"><b>$autm_name$</b></a>'
			: '$autm_name$' ),
	);

$Results->cols[] = array(
		'th'       => T_('Status'),
		'order'    => 'autm_status',
		'td'       => '%autm_td_status( #autm_ID#, #autm_status# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
	);

if( $current_User->check_perm( 'options', 'edit' ) )
{	// Display actions column only if current user has a permission to edit options:
	$Results->cols[] = array(
			'th'       => T_('Actions'),
			'td'       => action_icon( T_('Edit this automation'), 'edit', $admin_url.'?ctrl=automations&amp;action=edit&amp;autm_ID=$autm_ID$' )
			             .action_icon( T_('Delete this automation!'), 'delete', regenerate_url( 'autm_ID,action', 'autm_ID=$autm_ID$&amp;action=delete&amp;'.url_crumb( 'automation' ) ) ),
			'th_class' => 'shrinkwrap',
			'td_class' => 'shrinkwrap',
		);
}

$Results->display( NULL, 'session' );

?>