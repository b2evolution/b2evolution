<?php
/**
 * This file implements the UI view for the Goal Hit list.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.com/}
 *
 * @package admin
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $blog, $admin_url, $rsc_url;
global $Session;

/**
 * View funcs
 */
require_once dirname(__FILE__).'/_stats_view.funcs.php';


$final = param( 'final', 'integer', 0, true );

// Create result set:
$sql = 'SELECT *
					FROM T_track__goal';
$count_sql = 'SELECT COUNT(goal_ID)
								FROM T_track__goal';

if( !empty($final) )
{	// We want to filter on the session ID:
	$sql .= ' WHERE goal_redir_url IS NULL';
	$count_sql .= ' WHERE goal_redir_url IS NULL';
}

$Results = & new Results( $sql, 'goals_', '--A', 20, $count_sql );

$Results->title = T_('Goals');

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_goals( & $Form )
{
	$Form->checkbox_basic_input( 'final', get_param('final'), T_('Final') );
}
$Results->filter_area = array(
	'callback' => 'filter_goals',
	'url_ignore' => 'results_goals_page,final',
	'presets' => array(
		'all' => array( T_('All'), '?ctrl=goals' ),
		'final' => array( T_('Final'), '?ctrl=goals&amp;final=1' ),
		)
	);

$Results->cols[] = array(
		'th' => T_('ID'),
		'order' => 'goal_ID',
		'td_class' => 'center',
		'td' => '$goal_ID$',
	);

$Results->cols[] = array(
		'th' => T_('Name'),
		'order' => 'goal_name',
		'td' => '$goal_name$',
	);

$Results->cols[] = array(
		'th' => T_('Key'),
		'order' => 'goal_key',
		'td' => '$goal_key$',
 	);


$Results->cols[] = array(
		'th' => T_('Redirect to'),
		'order' => 'goal_redir_url',
		'td_class' => 'small',
		'td' => '<a href="$goal_redir_url$">$goal_redir_url$</a>',
 	);

$Results->cols[] = array(
		'th' => T_('Def. val.'),
		'order' => 'goal_default_value',
		'td_class' => 'right',
		'td' => '$goal_default_value$',
	);

if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:

	$Results->cols[] = array(
							'th' => T_('Actions'),
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							'td' => action_icon( T_('Edit this goal...'), 'edit',
	                        '%regenerate_url( \'action\', \'goal_ID=$goal_ID$&amp;action=edit\')%' )
	                    .action_icon( T_('Duplicate this goal...'), 'copy',
	                        '%regenerate_url( \'action\', \'goal_ID=$goal_ID$&amp;action=copy\')%' )
	                    .action_icon( T_('Delete this file goal!'), 'delete',
	                        '%regenerate_url( \'action\', \'goal_ID=$goal_ID$&amp;action=delete\')%' ),
						);

  $Results->global_icon( T_('Create a new goal...'), 'new', regenerate_url( 'action', 'action=new' ), T_('New goal').' &raquo;', 3, 4  );
}


// Display results:
$Results->display();

/*
 * $Log$
 * Revision 1.1  2008/04/17 11:53:22  fplanque
 * Goal editing
 *
 * Revision 1.1  2008/03/22 19:58:18  fplanque
 * missing views
 *
 */
?>