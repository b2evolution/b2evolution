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

global $blog, $admin_url, $rsc_url;
global $Session;

$internalsearches_keywords = param( 'isrch_keywords', 'string', NULL, true );

// Get list of all internal searches
$SQL = new SQL();
$SQL->SELECT( '*' );
$SQL->FROM( 'T_logs__internal_searches' );
if( !empty($internalsearches_keywords) ) // TODO: allow combine
{ // We want to filter on the goal name:
	$SQL->WHERE_and( 'isrch_keywords LIKE %'.$DB->quote($internalsearches_keywords.'%') );
}
$SQL->FROM_add( 'JOIN T_sessions ON isrch_session_ID = sess_ID' );
$SQL->FROM_add( 'JOIN T_blogs ON isrch_coll_ID = blog_ID' );
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
		'order' => 'isrch_session_ID',
		'td' => '$sess_key$',
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
							'td' => '@action_icon("edit")@@action_icon("copy")@@action_icon("delete")@',
						);

  $Results->global_icon( T_('Create a internal search...'), 'new', regenerate_url( 'action', 'action=new' ), T_('New internal search').' &raquo;', 3, 4  );
}


// Display results:
$Results->display();

/*
 * $Log$
 * Revision 1.1  2011/09/07 12:00:20  lxndral
 * internal searches update
 *
 * Revision 1.0  2011/09/05 17:53:55  Alexander 
 * 
 *
 */
?>