<?php
/**
 * This file implements the UI view for the Internal searches list.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.com/}
 *
 * @package admin
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * View funcs
 */
require_once dirname(__FILE__).'/_stats_view.funcs.php';

global $Blog, $admin_url, $rsc_url;
global $Session;

$internalsearches_keywords = param( 'isrch_keywords', 'string', NULL, true );

// Get list of all internal searches
$SQL = new SQL();
$SQL->SELECT( '*' );
$SQL->FROM( 'T_logs__internal_searches' );
if( !empty($internalsearches_keywords) ) // TODO: allow combine
{ // We want to filter on the goal name:
	$SQL->WHERE_and( 'isrch_keywords LIKE '.$DB->quote('%'.$internalsearches_keywords.'%') );
}
if( isset($Blog) ) 
{
	$SQL->WHERE_and( 'isrch_coll_ID = '.$Blog->ID );
}
$SQL->FROM_add( 'LEFT JOIN T_blogs ON isrch_coll_ID = blog_ID' );
$SQL->FROM_add( 'LEFT JOIN T_hitlog ON isrch_hit_ID = hit_ID' );
$SQL->FROM_add( 'LEFT JOIN T_sessions ON hit_sess_ID = sess_ID' );
$Results = new Results( $SQL->get(), 'internalsearches_', '-A' );

$Results->Cache = & get_InternalSearchesCache();

$Results->title = T_('Internal searches');
$Results->filter_area = array(
	'callback' => 'filter_internalsearches',
	'url_ignore' => 'isrch_keywords',
	'presets' => array(
		'all' => array( T_('All'), '?ctrl=stats&amp;tab=refsearches&amp;tab3=intsearches' ),
		)
	);




/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_internalsearches( & $Form )
{
	$Form->text_input( 'isrch_keywords', get_param('isrch_keywords'), 20, T_('Internal searches keywords starting with'), '', array( 'maxlength'=>50 ) );
}


$Results->cols[] = array(
		'th' => T_('ID'),
		'order' => 'isrch_ID',
		'td_class' => 'center',
		'td' => '$isrch_ID$',
	);

$Results->cols[] = array(
		'th' => T_('Blog'),
		'order' => 'isrch_coll_ID',
		'td' => '$blog_name$',
	);

$Results->cols[] = array(
		'th' => T_('Session'),
		'order' => 'sess_ID',
		'td_class' => 'right',
		'td' => '<strong><a href="admin.php?ctrl=stats&amp;tab=sessions&amp;tab3=hits&amp;blog=0&amp;sess_ID=$sess_ID$">$sess_ID$</a></strong>',
 	);
$Results->cols[] = array(
		'th' => T_('Hit date'),
		'order' => 'hit_datetime',
		'td' => '%mysql2localedatetime_spans( #hit_datetime#, "M-d" )%',
 	);


$Results->cols[] = array(
		'th' => T_('Keywords'),
		'order' => 'isrch_keywords',
		'td_class' => 'small',
		'td' => '$isrch_keywords$',
 	);


if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:

	$Results->cols[] = array(
							'th' => T_('Actions'),
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							'td' => '@action_icon("delete")@',
						);

}


// Display results:
$Results->display();

/*
 * $Log$
 * Revision 1.7  2011/09/14 21:04:06  fplanque
 * cleanup
 *
 * Revision 1.6  2011/09/12 16:44:33  lxndral
 * internal searches fix
 *
 * Revision 1.5  2011/09/09 23:42:26  lxndral
 * Internal search log (see email)
 * changes for displaying
 *
 * Revision 1.4  2011/09/09 23:05:08  lxndral
 * Search for "fp>al" in code to find my comments and please make requested changed
 *
 * Revision 1.3  2011/09/09 21:45:57  fplanque
 * doc
 *
 * Revision 1.2  2011/09/08 11:04:04  lxndral
 * fix for internal searches
 *
 * Revision 1.1  2011/09/07 12:00:20  lxndral
 * internal searches update
 *
 * Revision 1.0  2011/09/05 17:53:55  Alexander 
 * 
 *
 */
?>