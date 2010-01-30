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


$final = param( 'final', 'integer', 0, true );
$goal_name = param( 'goal_name', 'string', NULL, true );

// Get all goal hits:
$sql = 'SELECT DATE_FORMAT( hit_datetime, "%Y-%m-%d" ) as day, ghit_goal_ID, COUNT(ghit_ID) as count
					FROM T_track__goalhit INNER JOIN T_hitlog ON ghit_hit_ID = hit_ID
				 GROUP BY day DESC, ghit_goal_ID';
$hitgroup_rows = $DB->get_results( $sql, OBJECT, 'Get hits by day and goal' );

$hitgroup_array = array();
foreach( $hitgroup_rows as $hitgroup_row )
{
	$hitgroup_array[$hitgroup_row->day][$hitgroup_row->ghit_goal_ID] = $hitgroup_row->count;
}


// Get list of all goals
$SQL = new SQL();
$SQL->SELECT( 'goal_ID, goal_name' );
$SQL->FROM( 'T_track__goal' );
if( !empty($final) )
{	// We want to filter on final goals only:
	$SQL->WHERE_and( 'goal_redir_url IS NULL' );
}
if( !empty($goal_name) ) // TODO: allow combine
{ // We want to filter on the goal name:
	$SQL->WHERE_and( 'goal_name LIKE '.$DB->quote($goal_name.'%') );
}
$SQL->ORDER_BY( 'goal_name' );
$goal_rows = $DB->get_results( $SQL->get(), OBJECT, 'Get list of all goals' );


$Table = new Table( NULL, 'ghs_' );

$Table->title = T_('Goal hit summary');

$Table->cols = array(
	array( 'th' => T_('Date') )
);
foreach( $goal_rows as $goal_row )
{ // For each named goal, display name:
$Table->cols[] = array(
		'th' => $goal_row->goal_name,
		'td_class' => 'right',
	);
}
$Table->cols[] = array(
		'th' => T_('Total'),
		'td_class' => 'right',
	);




/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_hits( & $Form )
{
	$Form->checkbox_basic_input( 'final', get_param('final'), T_('Final') );
	$Form->text_input( 'goal_name', get_param('goal_name'), 20, T_('Goal names starting with'), '', array( 'maxlength'=>50 ) );
}
$Table->filter_area = array(
	'callback' => 'filter_hits',
	'url_ignore' => 'final,goal_name',
	'presets' => array(
		'all' => array( T_('All'), '?ctrl=goals&amp;tab3=stats' ),
		'final' => array( T_('Final'), '?ctrl=goals&amp;tab3=stats&amp;final=1' ),
		)
	);


$Table->display_init();

$Table->display_list_start();

// TITLE / COLUMN HEADERS:
$Table->display_head();

// BODY START:
$Table->display_body_start();

foreach( $hitgroup_array as $day=>$hitday_array )
{
	$Table->display_line_start( false, false );

	$Table->display_col_start();
	echo $day;
	$Table->display_col_end();

	$line_total = 0;
	foreach( $goal_rows as $goal_row )
	{ // For each named goal, display count:
		$Table->display_col_start();
		if( isset( $hitday_array[$goal_row->goal_ID] ) )
		{
			echo '<a href="?blog=0&amp;ctrl=stats&amp;tab=goals&amp;tab3=hits&amp;goal_name='.rawurlencode($goal_row->goal_name).'">'.$hitday_array[$goal_row->goal_ID].'</a>';
			$line_total += $hitday_array[$goal_row->goal_ID];
		}
		else
		{
			echo '&nbsp;';
		}
		$Table->display_col_end();
	}

	$Table->display_col_start();
	echo $line_total;
	$Table->display_col_end();

	$Table->display_line_end();
}

// BODY END:
$Table->display_body_end();

$Table->display_list_end();


/*
 * $Log$
 * Revision 1.3  2010/01/30 18:55:33  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.2  2009/03/08 23:57:45  fplanque
 * 2009
 *
 * Revision 1.1  2008/04/24 01:56:08  fplanque
 * Goal hit summary
 *
 * Revision 1.1  2008/03/22 19:58:18  fplanque
 * missing views
 *
 */
?>