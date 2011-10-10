<?php
/**
 * This file implements the trash comments display
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-asimo: Attila Simo.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $current_User;

$current_User->check_perm( 'blogs', 'editall', true );

param_action( 'emptytrash', true );

$SQL = new SQL();

$SQL->SELECT( 'DISTINCT(blog_ID), blog_name, count(comment_ID) as comments_number' ); // select target_title for sorting
$SQL->FROM( 'T_blogs LEFT OUTER JOIN T_categories ON blog_ID = cat_blog_ID' );
$SQL->FROM_add( 'LEFT OUTER JOIN T_items__item ON cat_ID = post_main_cat_ID' );
$SQL->FROM_add( 'LEFT OUTER JOIN T_comments ON post_ID = comment_post_ID' );
$SQL->WHERE( 'comment_status = "trash"');
$SQL->GROUP_BY( 'blog_ID' );

// Create result set:
$Results = new Results( $SQL->get() );

$Results->title = T_('Comment recycle bins').' ('.$Results->total_rows.')';

$Results->cols[] = array(
			'th' => T_('Blog ID'),
			'th_class' => 'shrinkwrap',
			'order' => 'blog_ID',
			'td' => '$blog_ID$',
			'td_class' => 'shrinkwrap',
		);

$Results->cols[] = array(
			'th' => T_('Blog name'),
			'order' => 'blog_name',
			'td' => '$blog_name$',
		);

$Results->cols[] = array(
			'th' => T_('Comments in recycle bin'),
			'th_class' => 'shrinkwrap',
			'order' => 'comments_number',
			'td' => '$comments_number$',
			'td_class' => 'shrinkwrap',
		);

$Results->cols[] = array(
			'th' => T_('Actions'),
			'th_class' => 'shrinkwrap',
			'td_class' => 'shrinkwrap',
			'td' => action_icon( TS_('Empty blog\'s recycle bin'), 'recycle_empty',
	        			regenerate_url( 'action', 'blog_ID=$blog_ID$&amp;action=trash_delete' ).'&amp;'.url_crumb('comment') ),
		);

$Results->global_icon( T_('Cancel empty recycle bin'), 'close', regenerate_url( 'action', 'action=list&filter=reset'), 3, 4  );

echo '<p>[<a href="'.regenerate_url( 'action,blog_ID', 'action=trash_delete' ).'&amp;'.url_crumb('comment').'">'.T_( 'Empty all blogs\' recycle bin' ).'</a>]</p>';

$Results->display();


/*
 * $Log$
 * Revision 1.6  2011/10/10 19:48:31  fplanque
 * i18n & login display cleaup
 *
 * Revision 1.5  2011/09/06 00:54:38  fplanque
 * i18n update
 *
 * Revision 1.4  2011/09/04 22:13:15  fplanque
 * copyright 2011
 *
 * Revision 1.3  2011/02/24 07:42:27  efy-asimo
 * Change trashcan to Recycle bin
 *
 * Revision 1.2  2011/02/20 22:31:39  fplanque
 * minor / doc
 *
 * Revision 1.1  2011/02/14 14:15:23  efy-asimo
 * Comments trash status
 *
 */
?>
