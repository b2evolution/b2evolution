<?php
/**
 * This file implements Poll handling functions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


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


/**
 * Get the Poll answers as link
 *
 * @param object Poll
 */
function poll_td_answers( $Poll )
{
	global $admin_url;
	return '<a href="'.$admin_url.'?ctrl=users&amp;poll='.$Poll->pqst_ID.'&amp;filter=new">'.$Poll->answers_count.'</a>';
}


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
		$r .= action_icon( T_('Delete this poll!'), 'delete', $admin_url.'?ctrl=polls&action=delete&amp;pqst_ID='.$Poll->ID.'&amp;'.url_crumb( 'poll' ) );
	}
	elseif( $current_User->check_perm( 'polls', 'view', false, $Poll ) )
	{	// Display the action icons to view the poll:
		$r .= action_icon( T_('View this poll'), 'magnifier', $admin_url.'?ctrl=polls&amp;pqst_ID='.$Poll->ID.'&amp;action=edit' );
	}

	return $r;
}


/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function poll_filters_callback( & $Form )
{
	$poll_owner_login = get_param( 'owner' );
	if( ! empty( $poll_owner_login ) )
	{	// Get user by login:
		$UserCache = & get_UserCache();
		$poll_filter_User = & $UserCache->get_by_login( $poll_owner_login );
	}
	else
	{	// No filter by owner:
		$poll_filter_User = NULL;
	}

	$Form->username( 'owner', $poll_filter_User, T_('Owner') );
}


/**
 * Display polls results table
 *
 * @param array Params
 */
function polls_results_block( $params = array() )
{
	global $current_User, $admin_url, $DB;

	$params = array_merge( array(
			'edited_User'          => NULL,
			'results_param_prefix' => 'poll_',
			'results_title'        => T_('Polls'),
			'manual_link'          => 'polls-list',
			'display_filters'      => true,
			'display_owner'        => true,
			'display_btn_add'      => true,
			'display_btn_user_del' => false,
		), $params );

	if( !is_logged_in() )
	{	// Only logged in users can access to this function:
		return;
	}

	if( ! $current_User->check_perm( 'polls', 'create' ) )
	{	// Check minimum permission:
		return;
	}

	// Get permission of current user if all polls are available to view:
	$perm_poll_view = $current_User->check_perm( 'polls', 'view' );

	if( ! empty( $params['edited_User'] ) )
	{	// Use a filter user ID from params:
		$edited_User = $params['edited_User'];
		$poll_filter_user_ID = $edited_User->ID;
	}
	else
	{	// Get a filter by poll owner from request:
		$poll_filter_user_ID = NULL;
		$poll_owner_login = param( 'owner', 'string', '', true );
		if( ! empty( $poll_owner_login ) )
		{	// Get user by login:
			$UserCache = & get_UserCache();
			if( $poll_filter_User = & $UserCache->get_by_login( $poll_owner_login ) )
			{	// Set user ID to filter then below:
				$poll_filter_user_ID = $poll_filter_User->ID;
			}
			else
			{	// Set this to display empty list when the netered login is wrong:
				$poll_filter_user_ID = -1;
			}
		}
	}


	$SQL = new SQL();
	$SQL->SELECT( 'pqst_ID, pqst_owner_user_ID, pqst_question_text, pqst_max_answers, COUNT( DISTINCT pans_user_ID ) AS answers_count' );
	$SQL->FROM( 'T_polls__question' );
	$SQL->FROM_add( 'LEFT JOIN T_polls__answer ON pans_pqst_ID = pqst_ID' );
	$SQL->GROUP_BY( 'pqst_ID, pqst_owner_user_ID, pqst_question_text, pqst_max_answers' );
	if( ! $perm_poll_view )
	{	// If current user has no permission to view all polls, Display only the owner's polls:
		$SQL->WHERE( 'pqst_owner_user_ID = '.$DB->quote( $current_User->ID ) );
	}
	elseif( $poll_filter_user_ID !== NULL )
	{	// Filter by owner:
		$SQL->WHERE( 'pqst_owner_user_ID = '.$DB->quote( $poll_filter_user_ID ) );
	}

	$count_SQL = new SQL();
	$count_SQL->SELECT( 'COUNT( pqst_ID )' );
	$count_SQL->FROM( 'T_polls__question' );
	if( ! $perm_poll_view )
	{	// If current user has no permission to view all polls, Display only the owner's polls:
		$count_SQL->WHERE( 'pqst_owner_user_ID = '.$DB->quote( $current_User->ID ) );
	}
	elseif( $poll_filter_user_ID !== NULL )
	{	// Filter by owner:
		$count_SQL->WHERE( 'pqst_owner_user_ID = '.$DB->quote( $poll_filter_user_ID ) );
	}


	// Create result set:
	$polls_Results = new Results( $SQL->get(), $params['results_param_prefix'], 'A', NULL, $count_SQL->get() );

	$polls_Results->title = $params['results_title'].' ('.$polls_Results->get_total_rows().')'
		.( empty( $params['manual_link'] ) ? '' : get_manual_link( $params['manual_link'] ) );
	$polls_Results->Cache = get_PollCache();

	if( $perm_poll_view && $params['display_filters'] )
	{	// Allow to filter by owner only if current user has a perm to view the polls of all users:
		$polls_Results->filter_area = array(
			'callback' => 'poll_filters_callback',
			'url_ignore' => 'owner,results_poll_page',
			'presets' => array(
				'all' => array( T_('All'), $admin_url.'?ctrl=polls' ),
				)
			);
	}

	$polls_Results->cols[] = array(
			'th'       => T_('ID'),
			'th_class' => 'shrinkwrap',
			'td_class' => 'shrinkwrap',
			'order'    => 'pqst_ID',
			'td'       => '$pqst_ID$',
		);

	if( $params['display_owner'] )
	{	// Display owner:
		$polls_Results->cols[] = array(
				'th'       => T_('Owner'),
				'th_class' => 'shrinkwrap',
				'td_class' => 'shrinkwrap',
				'order'    => 'pqst_owner_user_ID',
				'td'       => '%get_user_identity_link( NULL, #pqst_owner_user_ID# )%',
			);
	}

	$polls_Results->cols[] = array(
			'th'    => T_('Question'),
			'order' => 'pqst_question_text',
			'td'    => '%poll_td_question( {Obj} )%',
		);

	$polls_Results->cols[] = array(
			'th'       => T_('Answers'),
			'th_class' => 'shrinkwrap',
			'order'    => 'users_count',
			'td'       => '%poll_td_answers( {row} )%',
			'td_class' => 'shrinkwrap'
	);

	$polls_Results->cols[] = array(
			'th'       => T_('Actions'),
			'th_class' => 'shrinkwrap',
			'td_class' => 'shrinkwrap',
			'td'       => '%poll_td_actions( {Obj} )%'
		);

	if( $params['display_btn_add'] )
	{	// Display button to add new poll:
		$polls_Results->global_icon( T_('New poll'), 'new', $admin_url.'?ctrl=polls&action=new', T_('New poll').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );
	}

	if( $params['display_btn_user_del'] && isset( $edited_User ) && $polls_Results->get_total_rows() > 0 )
	{	// Display button to delete user polls:
		$polls_Results->global_icon( sprintf( T_('Delete all polls owned by %s'), $edited_User->login ), 'delete', $admin_url.'?ctrl=user&amp;user_tab=activity&amp;action=delete_all_polls&amp;user_ID='.$edited_User->ID.'&amp;'.url_crumb( 'user' ), ' '.T_('Delete all'), 3, 4 );
	}

	$polls_Results->display();
}
?>