<?php
/**
 * This file display the automation form
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


global $edited_Automation, $admin_url;

// Display breadcrumb:
autm_display_breadcrumb();

$SQL = new SQL( 'Get all users queued for automation #'.$edited_Automation->ID );
$SQL->SELECT( 'aust_autm_ID, aust_user_ID, aust_next_step_ID, aust_next_exec_ts, user_login, step_ID, IF( step_ID IS NULL, 2147483648, step_order ) AS step_order, step_label, step_type' );
$SQL->FROM( 'T_automation__user_state' );
$SQL->FROM_add( 'INNER JOIN T_users ON user_ID = aust_user_ID' );
$SQL->FROM_add( 'LEFT JOIN T_automation__step ON step_ID = aust_next_step_ID' );
$SQL->WHERE( 'aust_autm_ID = '.$edited_Automation->ID );

$count_SQL = new SQL( 'Get a count of users queued for automation #'.$edited_Automation->ID );
$count_SQL->SELECT( 'COUNT( aust_user_ID )' );
$count_SQL->FROM( 'T_automation__user_state' );
$SQL->WHERE( 'aust_autm_ID = '.$edited_Automation->ID );

$Results = new Results( $SQL->get(), 'aust_', '-A', NULL, $count_SQL->get() );

$Results->title = T_('Users queued').get_manual_link( 'automation-users-queued' );

$Results->cols[] = array(
		'th'    => T_('User'),
		'order' => 'aust_user_ID',
		'td'    => '%get_user_identity_link( "", #aust_user_ID# )%',
	);
$Results->cols[] = array(
		'th'    => T_('Step'),
		'order' => 'step_order, user_login',
		'td'    => '%autm_td_users_step( #aust_next_step_ID#, #step_order#, #step_label#, #step_type# )%',
	);

$Results->cols[] = array(
		'th'       => T_('Next execution time'),
		'order'    => 'aust_next_exec_ts',
		'td'       => '%mysql2localedatetime( #aust_next_exec_ts# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'nowrap',
	);

$Results->cols[] = array(
		'th'       => T_('Actions'),
		'td'       => '%autm_td_users_actions( #aust_autm_ID#, #aust_user_ID#, #user_login#, #step_ID# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
	);

$Results->display();

// Init JS for form to requeue automation:
echo_requeue_automation_js();
?>