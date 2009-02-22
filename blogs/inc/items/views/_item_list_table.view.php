<?php
/**
 * This file implements the post browsing in tracker mode
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}.
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

if( $highlight = param( 'highlight', 'integer', NULL ) )
{	// There are lines we want to highlight:
	$result_fadeout = array( 'post_ID' => array($highlight) );
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


$ItemList->title = T_('Post list');

// Issue date:
$ItemList->cols[] = array(
		'th' => T_('Date'),
		'order' => 'datestart',
		'default_dir' => 'D',
		'th_class' => 'nowrap',
		'td_class' => 'nowrap',
		'td' => '@get_permanent_link( get_icon(\'permalink\') )@ <span class="date">@get_issue_date()@</span>',
	);


// Blog name:
if( $Blog->get_setting( 'aggregate_coll_IDs' ) )
{ // Aggregated blog: display name of blog
	$ItemList->cols[] = array(
			'th' => T_('Blog'),
			'th_class' => 'nowrap',
			'td_class' => 'nowrap',
			'td' => '@load_Blog()@<a href="¤regenerate_url( \'blog,results_order\', \'blog=@blog_ID@\' )¤">@Blog->dget(\'shortname\')@</a>',
		);
}


// Author:
$ItemList->cols[] = array(
		'th' => T_('Author'),
		'th_class' => 'nowrap',
		'td_class' => 'nowrap',
		'order' => 'creator_user_ID',
		'td' => '@get(\'t_author\')@',
	);


/**
 * Task title
 */
function task_title_link( $Item )
{
	global $current_User;

	$col = locale_flag( $Item->locale, 'w16px', 'flag', '', false ).' ';

	$Item->get_Blog();

	if( $Item->Blog->allowcomments != 'never' )
	{	// The current blog can have comments:
		$nb_comments = generic_ctp_number($Item->ID, 'feedback');
		$col .= '<a href="?ctrl=items&amp;blog='.$Item->blog_ID.'&amp;p='.$Item->ID.'"
						title="'.sprintf( T_('%d feedbacks'), $nb_comments ).'" class="">';
		if( $nb_comments )
		{
			$col .= get_icon( 'comments' );
		}
		else
		{
			$col .= get_icon( 'nocomment' );
		}
		$col .= '</a> ';
	}

	$col .= '<a href="?ctrl=items&amp;blog='.$Item->blog_ID.'&amp;p='.$Item->ID.'" class="" title="'.
								T_('View this post...').'">'.$Item->dget('title').'</a></strong>';

	return $col;
}
$ItemList->cols[] = array(
						'th' => T_('Title'),
						'order' => 'title',
						'td_class' => 'tskst_$post_pst_ID$',
						'td' => '<strong lang="@get(\'locale\')@">%task_title_link( {Obj} )%</strong>',
					);


/**
 * Visibility:
 */
function item_visibility( $Item )
{
	// Display publish NOW button if current user has the rights:
	$r = $Item->get_publish_link( ' ', ' ', get_icon( 'publish' ), '#', '' );

	// Display deprecate if current user has the rights:
	$r .= $Item->get_deprecate_link( ' ', ' ', get_icon( 'deprecate' ), '#', '' );

	if( empty($r) )
	{	// for IE
		$r = '&nbsp;';
	}

	return $r;
}
$ItemList->cols[] = array(
						'th' => T_('Visibility'),
						'order' => 'status',
						'td_class' => 'shrinkwrap',
						'td' => '%item_visibility( {Obj} )%',
				);
$ItemList->cols[] = array(
						'th' => T_('Visibility'),
						'order' => 'status',
						'td_class' => 'tskst_$post_pst_ID$ nowrap',
						'td' => '@get( \'t_status\' )@',
				);

$ItemList->cols[] = array(
						'th' => T_('Ord'),
						'order' => 'order',
						'td_class' => 'right',
						'td' => '$post_order$',
				);

$ItemList->cols[] = array(
	'th' => /* TRANS: abbrev for info */ T_('i'),
	'order' => 'datemodified',
	'default_dir' => 'D',
	'th_class' => 'shrinkwrap',
	'td_class' => 'shrinkwrap',
	'td' => '@history_info_icon()@',
);



/**
 * Edit Actions:
 *
 * @param Item
 */
function item_edit_actions( $Item )
{
	global $Blog;

	$r = action_icon( T_('Edit linked files...'), 'folder',
					url_add_param( $Blog->get_filemanager_link(), 'fm_mode=link_item&amp;item_ID='.$Item->ID ), T_('Files') );

	// Display edit button if current user has the rights:
	$r .= $Item->get_edit_link( array(
		'before' => ' ',
		'after' => ' ',
		'text' => get_icon( 'edit' ),
		'title' => '#',
		'class' => '' ) );

	// Display delete button if current user has the rights:
	$r .= $Item->get_delete_link( ' ', ' ', get_icon( 'delete' ), '#', '', false );

	return $r;
}
$ItemList->cols[] = array(
		'th' => T_('Act.'),
		'td_class' => 'shrinkwrap',
		'td' => '%item_edit_actions( {Obj} )%',
	);


if( $ItemList->is_filtered() )
{	// List is filtered, offer option to reset filters:
	$ItemList->global_icon( T_('Reset all filters!'), 'reset_filters', '?ctrl=items&amp;blog='.$Blog->ID.'&amp;filter=reset', T_('Reset filters'), 3, 3 );
}

if( $current_User->check_perm( 'blog_post_statuses', 'edit', false, $Blog->ID ) )
{	// We have permission to add a post with at least one status:
	global $tab;
	switch( $tab )
	{
		case 'pages':
			$label = T_('New page');
			$title = T_('Create a new page...');
			$new_ptyp_ID = 1000;
			break;

		case 'intros':
			$label = T_('New intro');
			$title = T_('Write a new intro text...');
			$new_ptyp_ID = 1600;
			break;

		case 'podcasts':
			$label = T_('New episode');
			$title = T_('Package a new podcast episode...');
			$new_ptyp_ID = 2000;
			break;

		case 'links':
			$label = T_('New link');
			$title = T_('Add a link to the linkroll...');
			$new_ptyp_ID = 2;
			break;

		default:
			$label = T_('New post');
			$title = T_('Write a new post...');
			$new_ptyp_ID = 1;
			break;
	}
	$ItemList->global_icon( $title, 'new', '?ctrl=items&amp;action=new&amp;blog='.$Blog->ID.'&amp;item_typ_ID='.$new_ptyp_ID, $label.' &raquo;', 3, 4 );
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
 * Revision 1.11  2009/02/22 23:20:19  fplanque
 * partial rollback of stuff that can't be right...
 *
 * Revision 1.10  2009/01/24 00:29:27  waltercruz
 * Implementing links in the blog itself, not in a linkblog, first attempt
 *
 * Revision 1.9  2009/01/21 22:26:26  fplanque
 * Added tabs to post browsing admin screen All/Posts/Pages/Intros/Podcasts/Comments
 *
 * Revision 1.8  2008/02/09 20:13:08  fplanque
 * minor
 *
 * Revision 1.7  2008/02/09 03:04:01  fplanque
 * usability shortcut
 *
 * Revision 1.6  2008/02/09 02:56:00  fplanque
 * explicit order by field
 *
 * Revision 1.5  2008/01/21 09:35:31  fplanque
 * (c) 2008
 *
 * Revision 1.4  2007/11/15 23:51:18  blueyed
 * Use new API for Item::get_edit_link; Props Afwas
 *
 * Revision 1.3  2007/09/26 20:26:36  fplanque
 * improved ItemList filters
 *
 * Revision 1.2  2007/09/08 20:23:04  fplanque
 * action icons / wording
 *
 * Revision 1.1  2007/06/25 11:00:31  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.24  2007/06/11 01:54:39  fplanque
 * minor
 *
 * Revision 1.23  2007/05/28 01:33:22  fplanque
 * permissions/fixes
 *
 * Revision 1.22  2007/04/26 00:11:06  fplanque
 * (c) 2007
 *
 * Revision 1.21  2007/03/21 02:21:37  fplanque
 * item controller: highlight current (step 2)
 *
 * Revision 1.20  2007/03/05 02:12:56  fplanque
 * minor
 *
 * Revision 1.19  2007/01/19 10:57:46  fplanque
 * UI
 *
 * Revision 1.18  2006/12/19 20:33:35  blueyed
 * doc/todo
 *
 * Revision 1.17  2006/12/17 23:42:39  fplanque
 * Removed special behavior of blog #1. Any blog can now aggregate any other combination of blogs.
 * Look into Advanced Settings for the aggregating blog.
 * There may be side effects and new bugs created by this. Please report them :]
 *
 * Revision 1.16  2006/12/12 02:53:57  fplanque
 * Activated new item/comments controllers + new editing navigation
 * Some things are unfinished yet. Other things may need more testing.
 *
 * Revision 1.15  2006/12/07 23:59:31  fplanque
 * basic dashboard stuff
 *
 * Revision 1.14  2006/12/07 22:29:26  fplanque
 * reorganized menus / basic dashboard
 */
?>
