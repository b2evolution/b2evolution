<?php
/**
 * This file implements the Duplicates file list.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $DB, $UserSettings;

// set filter params
$min_inappropriate_votes = param( 'miv', 'integer', 0, true );
$min_spam_votes = param( 'msv', 'integer', 0, true );
$file_ID = param( 'file_ID', 'integer', 0, true );

// set default order
$default_order = ( $min_inappropriate_votes >= $min_spam_votes ) ? '----D' : '-----D';

// Create SQL query to find all file hashes that satisfy to filters
$hash_SQL = new SQL();
$hash_SQL->SELECT( 'f.file_hash, COUNT( f.file_ID )' );
$hash_SQL->FROM( 'T_files AS f' );
$hash_SQL->FROM_add( 'INNER JOIN (
		SELECT file_hash
		  FROM T_files
		 GROUP BY file_hash
		HAVING COUNT( file_ID ) > 1
	) AS fd ON fd.file_hash = f.file_hash' );
$hash_SQL->GROUP_BY( 'f.file_hash' );

if( ( $min_inappropriate_votes > 0 ) || ( $min_spam_votes > 0 ) )
{ // we need to join files vote table to be able to filter by votes
	$hash_SQL->FROM_add( 'LEFT JOIN T_files__vote AS fv ON fv.fvot_file_ID = f.file_ID' );
	if( $min_inappropriate_votes > 0 )
	{	// Filter by minimum count of inappropriate votes
		$hash_SQL->HAVING_and( 'SUM( fvot_inappropriate ) >= '.$DB->quote( $min_inappropriate_votes ) );
	}
	if( $min_spam_votes > 0 )
	{	// Filter by minimum count of spam votes
		$hash_SQL->HAVING_and( 'SUM( fvot_spam ) >= '.$DB->quote( $min_spam_votes ) );
	}
}
if( $file_ID > 0 )
{	// Filter by hash of File ID
	$hash_SQL->WHERE_and( '( SELECT fh.file_hash FROM T_files AS fh WHERE fh.file_ID ='.$DB->quote( $file_ID ).' ) = f.file_hash' );
}

// Get all distinct hash values from what we have duplicates and at least one file from duplicates corresponds to filters
$hash_results = $DB->get_assoc( $hash_SQL->get() );
$file_hash_values = array_keys( $hash_results );
// set the number of count all files which will be displayed
$num_file_results = array_sum( $hash_results );

if( $num_file_results > 0 )
{ // Create SQL query to build a results table
	$SQL = new SQL();
	$SQL->SELECT( 'f.*,
		SUM( IFNULL( fvot_like, 0 ) ) as total_like,
		SUM( IFNULL( fvot_inappropriate, 0 ) ) as total_inappropriate,
		SUM( IFNULL( fvot_spam, 0 ) ) as total_spam,
		( SELECT COUNT( file_ID ) FROM T_files AS f2 WHERE f.file_hash = f2.file_hash ) AS total_duplicates' );
	$SQL->FROM( 'T_files AS f' );
	$SQL->FROM_add( 'LEFT JOIN T_files__vote AS fv ON fv.fvot_file_ID = f.file_ID' );
	$SQL->WHERE( 'f.file_hash IN ( "'.implode( '","', $file_hash_values ).'" )' );
	$SQL->GROUP_BY( 'f.file_ID, f.file_hash' );
	$SQL->ORDER_BY( 'f.file_hash, *, total_spam DESC, total_inappropriate DESC' );
}

$Results = new Results( $num_file_results ? $SQL->get() : NULL, 'fdupl_', $default_order, $UserSettings->get( 'results_per_page' ), $num_file_results );
$Results->Cache = & get_FileCache();
$Results->title = T_('Duplicate files');

/*
 * Grouping params:
 */
$Results->group_by = 'file_hash';

/*
 * Group columns:
 */
$Results->grp_cols[] = array(
		'td_class' => 'firstcol'.($current_User->check_perm( 'users', 'edit', false ) ? '' : ' lastcol' ),
		'td_colspan' => 0,
		'td' => sprintf( T_('%s duplicates'), '$total_duplicates$' ),
	);

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function callback_filter_file_duplicated( & $Form )
{
	$Form->text( 'miv', get_param( 'miv' ), 5, T_('Minimun inappropriate votes'), '', 6 );

	$Form->text( 'msv', get_param( 'msv' ), 5, T_('Minimun spam votes'), '', 6 );

	$Form->hidden( 'file_ID', get_param( 'file_ID' ) );
}

$filter_presets = array(
		'all' => array( T_('All'), '?ctrl=filemod&amp;tab=duplicates&amp;miv=0&amp;msv=0' ),
		'inappropriate' => array( T_('Inappropriate'), '?ctrl=filemod&amp;tab=duplicates&amp;miv=1&amp;msv=0' ),
		'spam' => array( T_('Spam'), '?ctrl=filemod&amp;tab=duplicates&amp;miv=0&amp;msv=1' ),
	);

$Results->filter_area = array(
	'callback' => 'callback_filter_file_duplicated',
	'url_ignore' => 'results_fdupl_page',
	'presets' => $filter_presets,
	);

$Results->cols[] = array(
		'th' => T_('Icon/Type'),
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
		'td' => '% {Obj}->get_preview_thumb( "fulltype", true ) %',
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
		'th' => T_('Like'),
		'td' => '$total_like$',
		'th_class' => 'shrinkwrap',
		'td_class' => 'center',
		'order' => 'total_like',
		'default_dir' => 'D',
	);

$Results->cols[] = array(
		'th' => T_('Inappropriate'),
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

$Results->display();


/*
 * $Log$
 * Revision 1.3  2013/11/06 09:08:48  efy-asimo
 * Update to version 5.0.2-alpha-5
 *
 */
?>