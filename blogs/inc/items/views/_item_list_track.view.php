<?php
/**
 * This file implements the post browsing in tracker mode
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $Blog;
/**
 * @var ItemList2
 */
global $ItemList;

global $edit_item_url, $delete_item_url;
global $Session;

if( $highlight = param( 'highlight', 'integer', NULL ) )
{	// There are lines we want to highlight:
	$result_fadeout = array( 'post_ID' => array($highlight) );
} 
elseif ( $highlight = $Session->get( 'highlight_id' ) )
{
	$result_fadeout = array( 'post_ID' => array($highlight) );
	$Session->delete( 'highlight_id' );
}
else
{	// Nothing to highlight
	$result_fadeout = NULL;
}


// Display title depending on selection params:
echo $ItemList->get_filter_title( '<h2>', '</h2>', '<br />', NULL, 'htmlbody' );

/*
	**
	 * Callback to add filters on top of the result set
	 *
	function filter_on_post_title( & $Form )
	{
		global $pagenow, $post_filter;

		$Form->hidden( 'filter_on_post_title', 1 );
		$Form->text( 'post_filter', $post_filter, 20, T_('Task title'), '', 60 );
	}
	$ItemList->filters_callback = 'filter_on_post_title';
*/


$ItemList->title = T_('Task list');

$ItemList->cols[] = array(
						'th' => /* TRANS: abbrev for Priority */ T_('P'),
						'order' => 'priority',
						'th_class' => 'shrinkwrap',
						'td_class' => 'center tskst_$post_pst_ID$',
						'td' => '$post_priority$',
					);

$ItemList->cols[] = array(
						'th' => T_('Task'),
						'order' => 'title',
						'td_class' => 'tskst_$post_pst_ID$',
						'td' => '<strong lang="@get(\'locale\')@">%task_title_link( {Obj} )%</strong>',
					);

$ItemList->cols[] = array(
						'th' => T_('Visibility'),
						'order' => 'status',
						'th_class' => 'shrinkwrap',
						'td_class' => 'shrinkwrap',
						'td' => '%item_visibility( {Obj} )%',
				);

$ItemList->cols[] = array(
						'th' => T_('Visibility'),
						'order' => 'status',
						'th_class' => 'shrinkwrap',
						'td_class' => 'tskst_$post_pst_ID$ nowrap',
						'td' => '@get( \'t_status\' )@',
				);

$ItemList->cols[] = array(
						'th' => T_('Status'),
						'order' => 'pst_ID',
						'th_class' => 'shrinkwrap',
						'td_class' => 'tskst_$post_pst_ID$ nowrap',
						'td' => '@get(\'t_extra_status\')@',
					);

$ItemList->cols[] = array(
						'th' => T_('Type'),
						'order' => 'ptyp_ID',
						'th_class' => 'shrinkwrap',
						'td_class' => 'tskst_$post_pst_ID$ nowrap',
						'td' => '@get(\'t_type\')@',
					);

$ItemList->cols[] = array(
						'th' => T_('ID'),
						'order' => 'ID',
						'th_class' => 'shrinkwrap',
						'td_class' => 'tskst_$post_pst_ID$ shrinkwrap',
						'td_class' => 'center',
						'td' => '$post_ID$',
					);

$ItemList->cols[] = array(
						'th' => T_('Assigned'),
						'order' => 'assigned_user_ID',
						'th_class' => 'shrinkwrap',
						'td' => '@get(\'t_assigned_to\')@',
					);


/**
 * Deadline
 */
/*
function deadline( $date )
{
	global $localtimenow;

	$timestamp = mysql2timestamp( $date );

 	if( $timestamp <= 0 )
	{
		return '&nbsp;';	// IE needs that crap in order to display cell border :/
	}

	$output = mysql2localedate( $date );

	if( $timestamp < $localtimenow )
	{
		$output =  '<span class="past_deadline">! '.$output.'</span>';
	}

	return $output;
}
$ItemList->cols[] = array(
						'th' => T_('Deadline'),
						'order' => 'post_datedeadline',
						'td_class' => 'center tskst_$post_pst_ID$',
						'td' => '%deadline( #post_datedeadline# )%',
					);
*/

$ItemList->cols[] = array(
		'th' => /* TRANS: abbrev for info */ T_('i'),
		'order' => 'datemodified',
		'default_dir' => 'D',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
		'td' => '@history_info_icon()@',
	);

$ItemList->cols[] = array(
		'th' => T_('Actions'),
		'td_class' => 'shrinkwrap',
		'td' => '%item_edit_actions( {Obj} )%',
	);

if( $ItemList->is_filtered() )
{	// List is filtered, offer option to reset filters:
	$ItemList->global_icon( T_('Reset all filters!'), 'reset_filters', '?ctrl=items&amp;blog='.$Blog->ID.'&amp;filter=reset', T_('Reset filters') );
}

if( $current_User->check_perm( 'blog_post_statuses', 'edit', false, $Blog->ID ) )
{	// We have permission to add a post with at least one status:
	$ItemList->global_icon( T_('Create a new task...'), 'new', '?ctrl=items&amp;action=new&amp;blog='.$Blog->ID, T_('New task').' &raquo;', 3 ,4 );
}


// EXECUTE the query now:
$ItemList->restart();

// Initialize funky display vars now:
global $postIDlist, $postIDarray;
$postIDlist = $ItemList->get_page_ID_list();
$postIDarray = $ItemList->get_page_ID_array();

// DISPLAY table now:
$ItemList->display( NULL, $result_fadeout );


/*
 * $Log$
 * Revision 1.16  2013/11/06 08:04:24  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>