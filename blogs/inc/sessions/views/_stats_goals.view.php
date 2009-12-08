<?php
/**
 * This file implements the UI view for the Goal Hit list.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.com/}
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
$s = param( 's', 'string', '', true );

// Create query:
$SQL = & new SQL();
$SQL->SELECT( '*' );
$SQL->FROM( 'T_track__goal' );

if( !empty($final) )
{	// We want to filter on final goals only:
	$SQL->WHERE_and( 'goal_redir_url IS NULL' );
}

if( !empty($s) )
{	// We want to filter on search keyword:
	// Note: we use CONCAT_WS (Concat With Separator) because CONCAT returns NULL if any arg is NULL
	$SQL->WHERE_and( 'CONCAT_WS( " ", goal_name, goal_key, goal_redir_url ) LIKE "%'.$DB->escape($s).'%"' );
}

// Create result set:
$Results = & new Results( $SQL->get(), 'goals_', '-A' );

$Results->Cache = & get_GoalCache();

$Results->title = T_('Goals');

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_goals( & $Form )
{
	$Form->checkbox_basic_input( 'final', get_param('final'), /* TODO: please add context for translators.. */ T_('Final only').' &bull;' );
	$Form->text( 's', get_param('s'), 30, T_('Search'), '', 255 );
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
		'td' => '@action_link( "edit", #goal_key# )@',
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
							'td' => '@action_icon("edit")@@action_icon("copy")@@action_icon("delete")@',
						);

  $Results->global_icon( T_('Create a new goal...'), 'new', regenerate_url( 'action', 'action=new' ), T_('New goal').' &raquo;', 3, 4  );
}


// Display results:
$Results->display();

/*
 * $Log$
 * Revision 1.8  2009/12/08 00:07:23  blueyed
 * todo@fp
 *
 * Revision 1.7  2009/09/19 20:49:51  fplanque
 * Cleaner way of implementing permissions.
 *
 * Revision 1.6  2009/09/12 00:20:59  fplanque
 * search cleanup
 *
 * Revision 1.5  2009/08/30 20:58:10  tblue246
 * Goals ctrl: 1. Do not use localized messages to determine action. 2. Removed redundant "copy" action (always use "new" action with goal_ID).
 *
 * Revision 1.4  2009/05/16 00:28:31  fplanque
 * edit link
 *
 * Revision 1.3  2009/03/08 23:57:45  fplanque
 * 2009
 *
 * Revision 1.2  2008/04/24 01:56:08  fplanque
 * Goal hit summary
 *
 * Revision 1.1  2008/04/17 11:53:22  fplanque
 * Goal editing
 *
 * Revision 1.1  2008/03/22 19:58:18  fplanque
 * missing views
 *
 */
?>
