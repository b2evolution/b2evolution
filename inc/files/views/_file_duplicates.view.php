<?php
/**
 * This file implements the Duplicates file list.
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
	$hash_SQL->FROM_add( 'LEFT JOIN T_links AS l ON l.link_file_ID = f.file_ID' );
	$hash_SQL->FROM_add( 'LEFT JOIN T_links__vote AS lv ON lv.lvot_link_ID = l.link_ID' );
	if( $min_inappropriate_votes > 0 )
	{	// Filter by minimum count of inappropriate votes
		$hash_SQL->HAVING_and( 'SUM( lvot_inappropriate ) >= '.$DB->quote( $min_inappropriate_votes ) );
	}
	if( $min_spam_votes > 0 )
	{	// Filter by minimum count of spam votes
		$hash_SQL->HAVING_and( 'SUM( lvot_spam ) >= '.$DB->quote( $min_spam_votes ) );
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
		SUM( IFNULL( lvot_like, 0 ) ) as total_like,
		SUM( IFNULL( lvot_inappropriate, 0 ) ) as total_inappropriate,
		SUM( IFNULL( lvot_spam, 0 ) ) as total_spam,
		( SELECT COUNT( file_ID ) FROM T_files AS f2 WHERE f.file_hash = f2.file_hash ) AS total_duplicates' );
	$SQL->FROM( 'T_files AS f' );
	$SQL->FROM_add( 'LEFT JOIN T_links AS l ON l.link_file_ID = f.file_ID' );
	$SQL->FROM_add( 'LEFT JOIN T_links__vote AS lv ON lv.lvot_link_ID = l.link_ID' );
	$SQL->WHERE( 'f.file_hash IN ( '.$DB->quote( $file_hash_values ).' )' );
	$SQL->GROUP_BY( 'f.file_ID, f.file_hash' );
	$SQL->ORDER_BY( 'f.file_hash, *, total_spam DESC, total_inappropriate DESC' );
}

$Results = new Results( $num_file_results ? $SQL->get() : NULL, 'fdupl_', $default_order, $UserSettings->get( 'results_per_page' ), $num_file_results );
$Results->Cache = & get_FileCache();
$Results->Cache->clear();
$Results->title = T_('Duplicate files').get_manual_link( 'file-moderation-duplicates' );

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

function td_file_duplicates_icon( $File )
{
	if( is_object( $File ) )
	{ // Check if File object is correct
		return $File->get_preview_thumb( 'fulltype', array( 'init' => true ) );
	}
	// Broken File object
	return T_('Not Found');
}
$Results->cols[] = array(
		'th' => T_('Icon/Type'),
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
		'td' => '%td_file_duplicates_icon( {Obj} )%',
	);

function td_file_duplicates_path( $File, $file_root_type, $file_root_ID, $file_path )
{
	if( is_object( $File ) )
	{ // Check if File object is correct
		global $current_User;
		$r = $File->get_view_link().' '.$File->get_target_icon();
		if( $current_User->check_perm( 'files', 'edit_allowed', false, $File->get_FileRoot() ) )
		{ // Allow to delete a file only if current user has an access
			global $admin_url;
			$r .= action_icon( T_('Delete'), 'file_delete',
				url_add_param( $File->get_linkedit_url(), 'action=delete&amp;confirmed=1&amp;fm_selected[]='.rawurlencode( $File->get_rdfp_rel_path() ).'&amp;redirect_to='.rawurlencode( regenerate_url( 'blog', '', '', '&' ) ).'&amp;'.url_crumb( 'file' ) ),
				NULL, NULL, NULL,
				array( 'onclick' => 'return confirm(\''.TS_('Are you sure want to delete this file?').'\');' ) );
		}
		return $r;
	}
	else
	{ // Broken File object
		if( empty( $file_path ) )
		{ // No file data exist in DB
			return T_('File no longer exists on disk.');
		}
		else
		{ // Display file info from DB
			return $file_root_type.'_'.$file_root_ID.':'.$file_path;
		}
	}
}
$Results->cols[] = array(
		'th' => T_('Path'),
		'td' => '%td_file_duplicates_path( {Obj}, #file_root_type#, #file_root_ID#, #file_path# )%',
		'order' => 'file_path'
	);

$Results->cols[] = array(
		'th' => T_('Attached To'),
		'td' => '%get_file_links( #file_ID# )%',
	);

function td_file_properties_link( $File, $link_text )
{
	global $current_User;
	if( is_object( $File ) && $current_User->check_perm( 'files', 'edit_allowed', false, $File->get_FileRoot() ) )
	{ // Check if File object is correct and current user has an access
		return '<a href="'.url_add_param( $File->get_linkedit_url(), 'action=edit_properties&amp;fm_selected[]='.rawurlencode( $File->get_rdfp_rel_path() ).'&amp;'.url_crumb( 'file' ) ).'">'.$link_text.'</a>';
	}
	else
	{
		return $link_text;
	}
}
$Results->cols[] = array(
		'th' => /* TRANS: Header for # of times photo has been liked */ T_('Likes'),
		'td' => '%td_file_properties_link( {Obj}, #total_like# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'center',
		'order' => 'total_like',
		'default_dir' => 'D',
	);

$Results->cols[] = array(
		'th' => /* TRANS: Header for # of times photo has been votes inappropriate */ T_('Inappropriate'),
		'td' => '%td_file_properties_link( {Obj}, #total_inappropriate# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'center',
		'order' => 'total_inappropriate',
		'default_dir' => 'D',
	);

$Results->cols[] = array(
		'th' => T_('Spam'),
		'td' => '%td_file_properties_link( {Obj}, #total_spam# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'center',
		'order' => 'total_spam',
		'default_dir' => 'D',
	);

$Results->display();

?>