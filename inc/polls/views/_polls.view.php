<?php
/**
 * This file display the polls list
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $current_User, $admin_url;

$SQL = new SQL();
$SQL->SELECT( 'pqst_ID, pqst_owner_user_ID, pqst_question_text' );
$SQL->FROM( 'T_polls__question' );

$count_SQL = new SQL();
$count_SQL->SELECT( 'COUNT( pqst_ID )' );
$count_SQL->FROM( 'T_polls__question' );

// Create result set:
$Results = new Results( $SQL->get(), 'poll_', 'A', NULL, $count_SQL->get() );

$Results->title = T_('Polls').' ('.$Results->get_total_rows().')'.get_manual_link( 'polls-list' );
$Results->Cache = get_PollCache();


$Results->cols[] = array(
		'th'       => T_('ID'),
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
		'order'    => 'pqst_ID',
		'td'       => '$pqst_ID$',
	);

$Results->cols[] = array(
		'th'          => T_('Owner'),
		'th_class'    => 'shrinkwrap',
		'td_class'    => 'shrinkwrap',
		'order'       => 'pqst_owner_user_ID',
		'td'          => '%get_user_identity_link( NULL, #pqst_owner_user_ID# )%',
	);

$Results->cols[] = array(
		'th'          => T_('Question'),
		'order'       => 'pqst_question_text',
		'td'          => '$pqst_question_text$',
	);

$Results->cols[] = array(
			'th' => T_('Actions'),
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
		'td' => action_icon( T_('Edit this poll'), 'edit', $admin_url.'?ctrl=polls&amp;pqst_ID=$pqst_ID$&amp;action=edit' )
				.action_icon( T_('Delete this poll!'), 'delete', regenerate_url( 'pqst_ID,action', 'pqst_ID=$pqst_ID$&amp;action=delete&amp;'.url_crumb( 'poll' ) ) ),
	);

$Results->global_icon( T_('New poll'), 'new', regenerate_url( 'action', 'action=new' ), T_('New poll').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );

$Results->display();

?>