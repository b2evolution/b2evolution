<?php
/**
 * This file implements the Comment List (table) view.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}.
*
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * EVO FACTORY grants Francois PLANQUE the right to license
 * EVO FACTORY contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author asimo: Evo Factory / Attila Simo
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $Blog;
/**
 * @var CommentList
 */
global $CommentList;

/*
 * Display comments:
 */
$CommentList->query();

// Display title depending on selection params:
echo $CommentList->get_filter_title( '<h2>', '</h2>', '<br />', NULL, 'htmlbody' );

$CommentList->title = T_('Comment List');

if( $CommentList->is_filtered() )
{	// List is filtered, offer option to reset filters:
	$CommentList->global_icon( T_('Reset all filters!'), 'reset_filters', '?ctrl=comments&amp;blog='.$Blog->ID.'&amp;tab3=listview&amp;filter=reset', T_('Reset filters'), 3, 3 );
}

// Issue date:
$CommentList->cols[] = array(
		'th' => T_('Date'),
		'order' => 'date',
		'default_dir' => 'D',
		'th_class' => 'nowrap',
		'td_class' => 'nowrap',
		'td' => '@get_permanent_link( get_icon(\'permalink\') )@ <span class="date">@date()@</span>',
	);

/*
 * Get comment type. Return '---' if user has no permission to edit this comment
 */
function get_type( $Comment )
{
	global $current_User, $Blog;
	if( $current_User->check_perm( $Comment->blogperm_name(), 'edit', false, $Blog->ID ) )
	{
		return $Comment->get( 'type' );
	}
	else
	{
		return '<span class="dimmed">---</span>';
	}
}

// Comment kind:
$CommentList->cols[] = array(
		'th' => T_('Kind'),
		'order' => 'type',
		'th_class' => 'nowrap',
		'td' => '%get_type( {Obj} )%',
	);

/*
 * Get comment author. Return '---' if user has no permission to edit this comment
 */
function get_author( $Comment )
{
	global $current_User, $Blog;
	if( $current_User->check_perm( $Comment->blogperm_name(), 'edit', false, $Blog->ID ) ||
		$Comment->get('status') == 'published' )
	{
		return $Comment->get_author();
	}
	else
	{
		return '<span class="dimmed">---</span>';
	}
}

// Comment author:
$CommentList->cols[] = array(
		'th' => T_('Author'),
		'order' => 'author',
		'th_class' => 'nowrap',
		'td' => '%get_author( {Obj} )%',
	);

/*
 * Get comment author url. Return '---' if user has no permission to edit this comment
 */
function get_url( $Comment )
{
	global $current_User, $Blog;
	if( $current_User->check_perm( $Comment->blogperm_name(), 'edit', false, $Blog->ID ) )
	{
		return $Comment->author_url_with_actions( NULL, false );
	}
	else
	{
		return '<span class="dimmed">---</span>';
	}
}

// Comment url:
$CommentList->cols[] = array(
		'th' => T_('URL'),
		'order' => 'author_url',
		'th_class' => 'nowrap',
		//'td_class' => 'nowrap',
		'td' => '%get_url( {Obj} )%',
	);

/*
 * Get comment author email. Return '---' if user has no permission to edit this comment
 */
function get_author_email( $Comment )
{
	global $current_User, $Blog;
	if( $current_User->check_perm( $Comment->blogperm_name(), 'edit', false, $Blog->ID ) )
	{
		return $Comment->get_author_email();
	}
	else
	{
		return '<span class="dimmed">---</span>';
	}
}

// Comment author email:
$CommentList->cols[] = array(
		'th' => T_('Email'),
		'order' => 'author_email',
		'th_class' => 'nowrap',
		'td_class' => 'nowrap',
		'td' => '%get_author_email( {Obj} )%',
	);

/*
 * Get comment author ip. Return '---' if user has no permission to edit this comment
 */
function get_author_ip( $Comment )
{
	global $current_User, $Blog;
	if( $current_User->check_perm( $Comment->blogperm_name(), 'edit', false, $Blog->ID ) )
	{
		return $Comment->get( 'author_IP' );
	}
	else
	{
		return '<span class="dimmed">---</span>';
	}
}

// Comment author IP:
$CommentList->cols[] = array(
		'th' => T_('IP'),
		'order' => 'author_IP',
		'th_class' => 'nowrap',
		'td_class' => 'nowrap',
		'td' => '%get_author_ip( {Obj} )%',
	);

/*
 * Get comment spam karma. Return '---' if user has no permission to edit this comment
 */
function get_spam_karma( $Comment )
{
	global $current_User, $Blog;
	if( $current_User->check_perm( $Comment->blogperm_name(), 'edit', false, $Blog->ID ) )
	{
		return $Comment->get( 'spam_karma' );
	}
	else
	{
		return '<span class="dimmed">---</span>';
	}
}

// Comment spam karma
$CommentList->cols[] = array(
		'th' => T_('Spam karma'),
		'order' => 'spam_karma',
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
		'td' => '%get_spam_karma( {Obj} )%'
	);

/*
 * Get comment status. Return '---' if user has no permission to edit this comment
 */
function get_colored_status( $Comment ) {
	return '<span class="tdComment'.$Comment->get('status').'">'.$Comment->get('t_status').'</span>';
}

// Comment visibility:
$CommentList->cols[] = array(
		'th' => T_('Visibility'),
		'order' => 'status',
		'th_class' => 'nowrap',
		'td_class' => 'nowrap',
		'td' => '%get_colored_status( {Obj} )%',
	);

/**
 * Edit Actions:
 *
 * @param Item
 */
function comment_edit_actions( $Comment )
{
	global $Blog, $current_User;

	// Display edit and delete button if current user has the rights:
	if( $current_User->check_perm( $Comment->blogperm_name(), 'edit', false, $Blog->ID ))
	{
		$redirect_to = rawurlencode( regenerate_url( 'comment_ID,action', 'filter=restore', '', '&' ) );

		$r = action_icon( TS_('Edit this comment...'), 'properties',
		  'admin.php?ctrl=comments&amp;comment_ID='.$Comment->ID.'&amp;action=edit&amp;redirect_to='.$redirect_to );

		$r .=  action_icon( T_('Delete this comment!'), 'delete',
			'admin.php?ctrl=comments&amp;comment_ID='.$Comment->ID.'&amp;action=delete&amp;'.url_crumb('comment')
			.'&amp;redirect_to='.$redirect_to, NULL, NULL, NULL,
			array( 'onclick' => "return confirm('".TS_('You are about to delete this comment!\\nThis cannot be undone!')."')") );

		return $r;
	}
	return '';
}
$CommentList->cols[] = array(
			'th' => T_('Actions'),
			'th_class' => 'shrinkwrap',
			'td_class' => 'shrinkwrap',
			'td' => '%comment_edit_actions( {Obj} )%' );

$CommentList->display();


/*
 * $Log$
 * Revision 1.6  2010/08/05 08:04:12  efy-asimo
 * Ajaxify comments on itemList FullView and commentList FullView pages
 *
 * Revision 1.5  2010/07/26 06:52:16  efy-asimo
 * MFB v-4-0
 *
 */
?>