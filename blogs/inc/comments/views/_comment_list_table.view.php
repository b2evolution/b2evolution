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
		'order' => 'datestart',
		'default_dir' => 'D',
		'th_class' => 'nowrap',
		'td_class' => 'nowrap',
		'td' => '@get_permanent_link( get_icon(\'permalink\') )@ <span class="date">@date()@</span>',
	);

// Comment title:
$CommentList->cols[] = array(
		'th' => T_('Comment title'),
		'th_class' => 'nowrap',
		'td' => '@get_title()@',
	);

// Comment url:
$CommentList->cols[] = array(
		'th' => T_('URL'),
		'th_class' => 'nowrap',
		//'td_class' => 'nowrap',
		'td' => '@author_url_with_actions( NULL, false )@'
	);

// Comment author email:
$CommentList->cols[] = array(
		'th' => T_('Email'),
		'th_class' => 'nowrap',
		'td_class' => 'nowrap',
		'td' => '@get_author_email()@',
	);

// Comment author IP:
$CommentList->cols[] = array(
		'th' => T_('IP'),
		'th_class' => 'nowrap',
		'td_class' => 'nowrap',
		'td' => '@author_ip()@',
	);

// Comment spam karma
$CommentList->cols[] = array(
		'th' => T_('Spam karma'),
		'th_class' => 'shrinkwrap',
		'td_class' => 'shrinkwrap',
		'td' => '@spam_karma()@',
	);

// Comment visibility:
$CommentList->cols[] = array(
		'th' => T_('Visibility'),
		'th_class' => 'nowrap',
		'td_class' => 'nowrap',
		'td' => '@status()@',
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
		$redirect_to = rawurlencode( regenerate_url( 'comment_ID,action', 'tab3=listview', '', '&' ) );

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
			'th_class' => 'shrinkwrap small',
			'td_class' => 'shrinkwrap',
			'td' => '%comment_edit_actions( {Obj} )%' );

$CommentList->display();
?>