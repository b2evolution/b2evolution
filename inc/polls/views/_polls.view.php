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


global $current_User;

$SQL = new SQL();
$SQL->SELECT( 'pqst_ID, pqst_owner_user_ID, pqst_question_text' );
$SQL->FROM( 'T_polls__question' );
if( ! $current_User->check_perm( 'polls', 'view' ) )
{	// If current user has no permission to view all polls, Display only the owner's polls:
	$SQL->WHERE( 'pqst_owner_user_ID = '.$current_User->ID );
}

$count_SQL = new SQL();
$count_SQL->SELECT( 'COUNT( pqst_ID )' );
$count_SQL->FROM( 'T_polls__question' );
if( ! $current_User->check_perm( 'polls', 'view' ) )
{	// If current user has no permission to view all polls, Display only the owner's polls:
	$count_SQL->WHERE( 'pqst_owner_user_ID = '.$current_User->ID );
}

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


/**
 * Get the Poll question as text or as link if current user has a perm to view it
 *
 * @param object Poll
 * @return string
 */
function poll_td_question( $Poll )
{
	global $current_User, $admin_url;

	$r = $Poll->get_name();

	if( $current_User->check_perm( 'polls', 'view', false, $Poll ) )
	{	// Display the question text as link to view the details:
		$r = '<a href="'.$admin_url.'?ctrl=polls&amp;pqst_ID='.$Poll->ID.'&amp;action=edit'.'">'.$r.'</a>';
	}

	return $r;
}
$Results->cols[] = array(
		'th'    => T_('Question'),
		'order' => 'pqst_question_text',
		'td'    => '%poll_td_question( {Obj} )%',
	);

/**
 * Get action icons to view/edit/delete the Poll
 *
 * @param object Poll
 * @return string
 */
function poll_td_actions( $Poll )
{
	global $current_User, $admin_url;

	$r = '';

	if( $current_User->check_perm( 'polls', 'edit', false, $Poll ) )
	{	// Display the action icons to edit and delete the poll:
		$r .= action_icon( T_('Edit this poll'), 'edit', $admin_url.'?ctrl=polls&amp;pqst_ID='.$Poll->ID.'&amp;action=edit' );
		$r .= action_icon( T_('Delete this poll!'), 'delete', regenerate_url( 'pqst_ID,action', 'pqst_ID='.$Poll->ID.'&amp;action=delete&amp;'.url_crumb( 'poll' ) ) );
	}
	elseif( $current_User->check_perm( 'polls', 'view' ) )
	{	// Display the action icons to view the poll:
		$r .= action_icon( T_('View this poll'), 'magnifier', $admin_url.'?ctrl=polls&amp;pqst_ID='.$Poll->ID.'&amp;action=edit' );
	}

	return $r;
}
$Results->cols[] = array(
			'th' => T_('Actions'),
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
		'td' => '%poll_td_actions( {Obj} )%'
	);

$Results->global_icon( T_('New poll'), 'new', regenerate_url( 'action', 'action=new' ), T_('New poll').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );

$Results->display();

?>