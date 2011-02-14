<?php
/**
 * This file implements the trash comments display
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
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

$Results->title = T_('Trash comments').' ('.$Results->total_rows.')';

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
			'th' => T_('Trash comments number'),
			'th_class' => 'shrinkwrap',
			'order' => 'comments_number',
			'td' => '$comments_number$',
			'td_class' => 'shrinkwrap',
		);

$Results->cols[] = array(
			'th' => T_('Actions'),
			'th_class' => 'shrinkwrap',
			'td_class' => 'shrinkwrap',
			'td' => action_icon( TS_('Empty blog\'s trash'), 'delete',
	        		/*'admin.php?ctrl=comments&amp;blog_ID=$blog_ID$&amp;action=trash_delete&amp;redirect_to='.*/regenerate_url( 'action', 'blog_ID=$blog_ID$&amp;action=trash_delete' ).'&amp;'.url_crumb('comment') ),
		);

$Results->global_icon( T_('Cancel empty trash'), 'close', regenerate_url( 'action', 'action=list&filter=reset'), 3, 4  );

echo '<p>[<a href="'.regenerate_url( 'action,blog_ID', 'action=trash_delete' ).'&amp;'.url_crumb('comment').'">'.T_( 'Empty all blog\'s trash' ).'</a>]</p>';

$Results->display();


/*
 * $Log$
 * Revision 1.1  2011/02/14 14:15:23  efy-asimo
 * Comments trash status
 *
 */
?>
