<?php
/**
 * This file implements the Suspicious file list.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $DB, $UserSettings;

// set filter params
$min_inappropriate_votes = param( 'miv', 'integer', 1, true );
$min_spam_votes = param( 'msv', 'integer', 0, true );

// set default order
$default_order = ( $min_inappropriate_votes >= $min_spam_votes ) ? '----D' : '-----D';

// Create result set:
$SQL = new SQL();
$SQL->SELECT( 'f1.*,
	SUM( IFNULL( lvot_like, 0 ) ) as total_like,
	SUM( IFNULL( lvot_inappropriate, 0 ) ) as total_inappropriate,
	SUM( IFNULL( lvot_spam, 0 ) ) as total_spam,
	( SELECT COUNT( file_ID ) FROM T_files AS f2 WHERE f1.file_hash = f2.file_hash ) - 1 AS total_duplicates' );
$SQL->FROM( 'T_links__vote' );
$SQL->FROM_add( 'INNER JOIN T_links ON link_ID = lvot_link_ID' );
$SQL->FROM_add( 'INNER JOIN T_files AS f1 ON link_file_ID = file_ID' );
$SQL->GROUP_BY( 'link_file_ID' );
$SQL->ORDER_BY( '*, total_spam DESC, total_inappropriate DESC' );

// Set filters condition to SQL queries
if( $min_inappropriate_votes <= 1 && $min_spam_votes <= 1 && ( !( $min_inappropriate_votes && $min_spam_votes ) ) )
{ // We must show all votes or where is at least one spam vote or where is at least one inappropriate ( one filter must be 0 and none of them > 1 )
	if( $min_inappropriate_votes )
	{ // Min inappropriate filter is set to 1 but min spam is 0
		$sql_where = 'lvot_inappropriate = 1';
	}
	elseif( $min_spam_votes )
	{ // Min spam filter is set to 1 but min inappropriate is 0
		$sql_where = 'lvot_spam = 1';
	}
	else
	{ // We have to show all files which has any kind of spam vote
		$sql_where = '( lvot_inappropriate = 1 OR lvot_spam = 1 )';
	}
	// Set the main query where condition
	$SQL->WHERE_and( $sql_where );
	// Create count result query
	$count_SQL = new SQL();
	$count_SQL->SELECT( 'COUNT( DISTINCT( link_file_ID ) )' );
	$count_SQL->FROM( 'T_links__vote' );
	$count_SQL->FROM_add( 'INNER JOIN T_links ON link_ID = lvot_link_ID' );
	$count_SQL->WHERE_and( $sql_where );
	// count the number of filtered result
	$filtered_num_results = $DB->get_var( $count_SQL->get() );
}
else
{ // check to fit at least one of the minimum requirements
	// Set the main query having condition
	$SQL->HAVING( '( total_inappropriate >= '.$DB->quote( $min_inappropriate_votes ).' ) AND ( total_spam >= '.$DB->quote( $min_spam_votes ).' )' );
	// Create count result query
	$count_SQL = new SQL();
	$count_SQL->SELECT( 'link_file_ID' );
	$count_SQL->FROM( 'T_links__vote' );
	$count_SQL->FROM_add( 'INNER JOIN T_links ON link_ID = lvot_link_ID' );
	$count_SQL->GROUP_BY( 'link_file_ID' );
	$count_SQL->HAVING( '( SUM( lvot_inappropriate ) >= '.$DB->quote( $min_inappropriate_votes ).' ) AND ( SUM( lvot_spam ) >= '.$DB->quote( $min_spam_votes ).' )' );
	// count the number of filtered result ( we need subquery because we can't count all when we have group by )
	$filtered_num_results = $DB->get_var( "SELECT COUNT(*) FROM (". $count_SQL->get() ." )  AS TotalSelected " );
}

$Results = new Results( $SQL->get(), 'fsusp_', $default_order, $UserSettings->get( 'results_per_page' ), (int)$filtered_num_results );
$Results->Cache = & get_FileCache();
$Results->title = T_('Suspicious files');


/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function callback_filter_file_suspicious( & $Form )
{
	$Form->text( 'miv', get_param( 'miv' ), 5, T_('Minimun inappropriate votes'), '', 6 );

	$Form->text( 'msv', get_param( 'msv' ), 5, T_('Minimun spam votes'), '', 6 );
}

$filter_presets = array(
		'all' => array( T_('All'), '?ctrl=filemod&amp;miv=0&amp;msv=0' ),
		'inappropriate' => array( T_('Inappropriate'), '?ctrl=filemod&amp;miv=1&amp;msv=0' ),
		'spam' => array( T_('Spam'), '?ctrl=filemod&amp;miv=0&amp;msv=1' ),
	);

$Results->filter_area = array(
	'callback' => 'callback_filter_file_suspicious',
	'url_ignore' => 'results_fsusp_page',
	'presets' => $filter_presets,
	);

$Results->cols[] = array(
		'th' => T_('Icon/Type'),
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
		'td' => '% {Obj}->get_preview_thumb( "fulltype", array( "init" => true ) ) %',
	);

$Results->cols[] = array(
		'th' => T_('Path'),
		'td' => '% {Obj}->get_view_link() % % {Obj}->get_target_icon() %',
		'order' => 'file_path'
	);

$Results->cols[] = array(
		'th' => T_('Attached To'),
		'td' => '%get_file_links( #file_ID# )%',
	);

$Results->cols[] = array(
		'th' => /* TRANS: Header for # of times photo has been liked */ T_('Likes'),
		'td' => '$total_like$',
		'th_class' => 'shrinkwrap',
		'td_class' => 'center',
		'order' => 'total_like',
		'default_dir' => 'D',
	);

$Results->cols[] = array(
		'th' => /* TRANS: Header for # of times photo has been votes inappropriate */ T_('Inappropriate'),
		'td' => '$total_inappropriate$',
		'th_class' => 'shrinkwrap',
		'td_class' => 'center',
		'order' => 'total_inappropriate',
		'default_dir' => 'D',
	);

$Results->cols[] = array(
		'th' => T_('Spam'),
		'td' => '$total_spam$',
		'th_class' => 'shrinkwrap',
		'td_class' => 'center',
		'order' => 'total_spam',
		'default_dir' => 'D',
	);

$Results->cols[] = array(
		'th' => T_('Duplicates'),
		'td' => '~conditional( #total_duplicates# >= 1, "<a href=\"?ctrl=filemod&amp;tab=duplicates&amp;file_ID=#file_ID#\">'./* TRANS: "This file" in suspicious file view */ T_('this one').' + #total_duplicates#</a>", "'.T_('none').'")~',
		'th_class' => 'shrinkwrap',
		'td_class' => 'center',
		'order' => 'total_duplicates',
		'default_dir' => 'D',
	);

$Results->display();

?>