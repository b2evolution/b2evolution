<?php
/**
 * This file display the poll form
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $edited_Poll, $action, $admin_url;

// Get permission of current user if he can edit the edited Poll:
$perm_poll_edit = $current_User->check_perm( 'polls', 'edit', false, $edited_Poll );

// Determine if we are creating or updating:
$creating = is_create_action( $action );

$Form = new Form( NULL, 'poll_checkchanges', 'post', 'compact' );

$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action,pqst_ID' ) );

$Form->begin_form( 'fform', ( $creating ?  T_('New poll') : T_('Poll') ).get_manual_link( 'poll-form' ) );

	$Form->add_crumb( 'poll' );
	$Form->hidden( 'action',  $creating ? 'create' : 'update' );
	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',pqst_ID' : '' ) ) );

	if( $current_User->check_perm( 'polls', 'edit' ) )
	{	// Allow to change an owner if current user has a permission to edit all polls:
		$Form->username( 'pqst_owner_login', $edited_Poll->get_owner_User(), T_('Owner'), '', '', array( 'required' => true ) );
	}
	else
	{	// Current user has no permission to edit a poll owner, Display the owner as info field:
		$Form->info( T_('Owner'), get_user_identity_link( NULL, $edited_Poll->owner_user_ID ) );
	}

	if( $perm_poll_edit )
	{
		$Form->text_input( 'pqst_question_text', $edited_Poll->get( 'question_text' ), 10, T_('Question'), '', array( 'maxlength' => 2000, 'required' => true, 'class' => 'large' ) );
	}
	else
	{
		$Form->info( T_('Question'), $edited_Poll->get( 'question_text' ) );
	}

	if( $creating )
	{	// Suggest to enter 10 answer options on creating new poll:
		$answer_options = param( 'answer_options', 'array:string', array() );
		for( $i = 0; $i < 10; $i++ )
		{
			$Form->text_input( 'answer_options[]', ( isset( $answer_options[ $i ] ) ? $answer_options[ $i ] : '' ), 10, ( $i == 0 ? T_('Answer options') : '' ), '', array( 'maxlength' => 2000, 'style' => 'width:50%' ) );
		}
	}

$buttons = array();
if( $creating || $perm_poll_edit )
{	// Display a button to update the poll question only if current user has a permission:
	$buttons[] = array( 'submit', 'submit', ( $creating ? T_('Record') : T_('Save Changes!') ), 'SaveButton' );
}

$Form->end_form( $buttons );

// ---- Poll Answers - START ---- //
if( $edited_Poll->ID > 0 )
{	// Display the answers table only when poll question is already exist in DB:

	// Get an options count of the edited poll which has at least one answer:
	$count_SQL = new SQL();
	$count_SQL->SELECT( 'COUNT( pans_ID )' );
	$count_SQL->FROM( 'T_polls__answer' );
	$count_SQL->WHERE( 'pans_pqst_ID = '.$edited_Poll->ID );
	$poll_options_count = $DB->get_var( $count_SQL->get(), 0, NULL, 'Get an options count of the edited poll which has at least one answer' );
	if( $poll_options_count == 0 )
	{	// To don't devide by zero
		$poll_options_count = 1;
	}

	// Get all options of the edited poll:
	$SQL = new SQL();
	$SQL->SELECT( 'popt_ID, popt_pqst_ID, popt_option_text, popt_order,' );
	$SQL->SELECT_add( 'COUNT( pans_ID ) AS answers_count,' );
	$SQL->SELECT_add( 'ROUND( COUNT( pans_ID ) / '.$poll_options_count.' * 100 ) AS answers_percent' );
	$SQL->FROM( 'T_polls__option' );
	$SQL->FROM_add( 'LEFT JOIN T_polls__answer ON pans_popt_ID = popt_ID' );
	$SQL->WHERE( 'popt_pqst_ID = '.$edited_Poll->ID );
	$SQL->GROUP_BY( 'popt_ID' );

	// Get a count of all options for the edited poll:
	$count_SQL = new SQL();
	$count_SQL->SELECT( 'COUNT( popt_ID )' );
	$count_SQL->FROM( 'T_polls__option' );
	$count_SQL->WHERE( 'popt_pqst_ID = '.$edited_Poll->ID );

	// Create result set:
	$Results = new Results( $SQL->get(), 'pans_', 'A', NULL, $count_SQL->get() );

	$Results->title = T_('Answers').' ('.$Results->get_total_rows().')'.get_manual_link( 'polls-answers-list' );
	$Results->Cache = get_PollOptionCache();

	$Results->cols[] = array(
			'th'       => T_('Order'),
			'th_class' => 'shrinkwrap',
			'td_class' => 'shrinkwrap',
			'order'    => 'popt_order',
			'td'       => '$popt_order$',
		);

	/**
	 * Get the Poll question as text or as link if current user has a perm to view it
	 *
	 * @param object Poll
	 * @return string
	 */
	function poll_option_td_option( $PollOption )
	{
		global $edited_Poll, $current_User, $admin_url;

		$r = $PollOption->get_name();

		if( $current_User->check_perm( 'polls', 'edit', false, $edited_Poll ) )
		{	// Display the option text as link to edit the option details:
			$r = '<a href="'.$admin_url.'?ctrl=polls&amp;pqst_ID='.$edited_Poll->ID.'&amp;popt_ID='.$PollOption->ID.'&amp;action=edit_option'.'">'.$r.'</a>';
		}

		return $r;
	}
	$Results->cols[] = array(
			'th'    => T_('Option'),
			'order' => 'popt_option_text',
			'td'    => '%poll_option_td_option( {Obj} )%',
		);

	$Results->cols[] = array(
			'th'       => T_('Answers'),
			'th_class' => 'shrinkwrap',
			'td_class' => 'right',
			'order'    => 'answers_count',
			'td'       => '$answers_count$',
		);

	/**
	 * Get the Poll percent with bar
	 *
	 * @param object Poll
	 * @return string
	 */
	function poll_option_td_percent( $poll_option_percent, $max_percent )
	{
		// Calculate a percent for style relating on max percent:
		$style_percent = $max_percent > 0 ? ceil( $poll_option_percent / $max_percent * 100 ) : 0;

		$r = '<div class="evo_poll__percent_bar"><div style="width:'.$style_percent.'%"></div></div>';

		$r .= $poll_option_percent.'%';

		return $r;
	}
	$Results->cols[] = array(
			'th'       => '%',
			'th_class' => 'shrinkwrap',
			'td_class' => 'nowrap',
			'order'    => 'answers_percent',
			'td'       => '%poll_option_td_percent( #answers_percent#, '.$edited_Poll->get_max_poll_options_percent().' )%',
		);

	if( $perm_poll_edit )
	{	// Display a columnt with edit/delete icons only if current user has a perm to edit the Poll
		$Results->cols[] = array(
				'th' => T_('Actions'),
				'th_class' => 'shrinkwrap',
				'td_class' => 'shrinkwrap',
				'td' => action_icon( T_('Edit this poll option'), 'edit', $admin_url.'?ctrl=polls&amp;pqst_ID='.$edited_Poll->ID.'&amp;popt_ID=$popt_ID$&amp;action=edit_option' )
						.action_icon( T_('Delete this poll option!'), 'delete', regenerate_url( 'pqst_ID,action', 'pqst_ID='.$edited_Poll->ID.'&amp;popt_ID=$popt_ID$&amp;action=delete_option&amp;'.url_crumb( 'poll' ) ) )
			);
	}

	$Results->global_icon( T_('New poll option'), 'new', regenerate_url( 'action', 'action=new_option' ), T_('New poll option').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );

	$Results->display();
}
// ---- Poll Answers - END ---- //
?>