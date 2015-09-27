<?php
/**
 * This file implements the Likes file list.
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


global $DB, $UserSettings, $min_likes, $min_dislikes;

// set filter params
$min_likes = param( 'min_likes', 'integer', 0, true );
$min_dislikes = param( 'min_dislikes', 'integer', 0, true );

// Deny negative values for these filters
$min_likes = ( $min_likes < 0 ) ? 0 : $min_likes;
$min_dislikes = ( $min_dislikes < 0 ) ? 0 : $min_dislikes;

// Create result set:
$SQL = new SQL();
$SQL->SELECT( 'f1.*,
	SUM( IF( lvot_like > 0, lvot_like, 0 ) ) AS total_like,
	ABS( SUM( IF( lvot_like < 0, lvot_like, 0 ) ) ) AS total_dislike' );
$SQL->FROM( 'T_links__vote' );
$SQL->FROM_add( 'INNER JOIN T_links ON link_ID = lvot_link_ID' );
$SQL->FROM_add( 'INNER JOIN T_files AS f1 ON link_file_ID = file_ID' );
$SQL->GROUP_BY( 'link_file_ID' );
$SQL->HAVING( '( total_like >= '.$DB->quote( $min_likes ).' ) AND ( total_dislike >= '.$DB->quote( $min_dislikes ).' )' );

// Create count result query
$count_SQL = new SQL();
$count_SQL->SELECT( 'link_file_ID,
	SUM( IF( lvot_like > 0, lvot_like, 0 ) ) AS total_like,
	ABS( SUM( IF( lvot_like < 0, lvot_like, 0 ) ) ) AS total_dislike' );
$count_SQL->FROM( 'T_links__vote' );
$count_SQL->FROM_add( 'INNER JOIN T_links ON link_ID = lvot_link_ID' );
$count_SQL->GROUP_BY( 'link_file_ID' );
$count_SQL->HAVING( '( total_like >= '.$DB->quote( $min_likes ).' ) AND ( total_dislike >= '.$DB->quote( $min_dislikes ).' )' );
// count the number of filtered result ( we need subquery because we can't count all when we have group by )
$filtered_num_results = $DB->get_var( 'SELECT COUNT( link_file_ID ) FROM ( '. $count_SQL->get() .' ) AS TotalSelected' );

$Results = new Results( $SQL->get(), 'flike_', '---D', $UserSettings->get( 'results_per_page' ), (int)$filtered_num_results );
$Results->Cache = & get_FileCache();
$Results->title = T_('Liked files').get_manual_link( 'file-moderation-likes' );


/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function callback_filter_file_suspicious( & $Form )
{
	$Form->text( 'min_likes', get_param( 'min_likes' ), 5, T_('Minimum likes'), '', 6 );

	$Form->text( 'min_dislikes', get_param( 'min_dislikes' ), 5, T_('Minimum dislikes'), '', 6 );
}

$filter_presets = array(
		'all' => array( T_('All'), '?ctrl=filemod' ),
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
		'th' => /* TRANS: Header for # of times photo has been disliked */ T_('Dislikes'),
		'td' => '%td_file_properties_link( {Obj}, #total_dislike# )%',
		'th_class' => 'shrinkwrap',
		'td_class' => 'center',
		'order' => 'total_dislike',
		'default_dir' => 'D',
	);

$Results->display();

?>