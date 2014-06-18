<?php
/**
 * This file implements the trash comments display
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-asimo: Attila Simo.
 *
 * @version $Id: _trash_comments.view.php 6286 2014-03-21 08:37:59Z attila $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $current_User;

$current_User->check_perm( 'blogs', 'editall', true );

param_action( 'emptytrash', true );

$SQL = new SQL();

$SQL->SELECT( 'DISTINCT(blog_ID), blog_name, count(comment_ID) as comments_number' ); // select target_title for sorting
$SQL->FROM( 'T_blogs LEFT OUTER JOIN T_categories ON blog_ID = cat_blog_ID' );
$SQL->FROM_add( 'LEFT OUTER JOIN T_items__item ON cat_ID = post_main_cat_ID' );
$SQL->FROM_add( 'LEFT OUTER JOIN T_comments ON post_ID = comment_item_ID' );
$SQL->WHERE( 'comment_status = "trash"');
$SQL->GROUP_BY( 'blog_ID' );

$count_SQL = new SQL();
$count_SQL->SELECT( 'COUNT( comment_ID )' );
$count_SQL->FROM( 'T_comments' );
$count_SQL->WHERE( 'comment_status = "trash"');

// Create result set:
$Results = new Results( $SQL->get(), 'emptytrash_', '', NULL, $count_SQL->get() );

$Results->title = T_('Comment recycle bins').' ('.$Results->get_total_rows().')';

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

?>