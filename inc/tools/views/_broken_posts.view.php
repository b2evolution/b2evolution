<?php
/**
 * This file display the broken post that have no matching category
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$SQL = new SQL();

$SQL->SELECT( 'post_ID, post_title, post_main_cat_ID, post_canonical_slug_ID' );
$SQL->FROM( 'T_items__item' );
$SQL->WHERE( 'post_main_cat_ID NOT IN (SELECT cat_ID FROM T_categories )' );

$Results = new Results( $SQL->get(), 'broken_posts_' );

$Results->title = T_( 'Broken items with no matching category' );
$Results->global_icon( T_('Cancel!'), 'close', regenerate_url( 'action' ) );

$Results->cols[] = array(
	'th' => T_('Item ID'),
	'th_class' => 'shrinkwrap',
	'td_class' => 'small center',
	'order' => 'post_ID',
	'td' => '$post_ID$',
);

$Results->cols[] = array(
	'th' => T_('Title'),
	'th_class' => 'nowrap',
	'order' => 'post_title',
	'td' => '$post_title$',
	'td_class' => 'small',
);

$Results->cols[] = array(
	'th' => T_('Main Cat ID'),
	'th_class' => 'shrinkwrap',
	'order' => 'post_main_cat_ID',
	'td' => '$post_main_cat_ID$',
	'td_class' => 'small center',
);
$Results->cols[] = array(
	'th' => T_('Canonical Slug ID'),
	'th_class' => 'shrinkwrap',
	'order' => 'post_canonical_slug_ID',
	'td' => '$post_canonical_slug_ID$',
	'td_class' => 'small center',
);

$Results->display( array(
		'page_url' => regenerate_url( 'blog,ctrl,action,results_'.$Results->param_prefix.'page', 'action='.param_action().'&amp;'.url_crumb( 'tools' ) )
	) );

if( ( $current_User->check_perm('options', 'edit', true) ) && ( $Results->get_num_rows() ) )
{ // display Delete link
	global $DB;
	$post_IDs = $DB->get_col( $SQL->get() );

	echo '<p>[<a href="'.regenerate_url( 'action', 'action=del_broken_posts&amp;posts='.implode( ',', $post_IDs ).'&amp;'.url_crumb( 'tools' ) ).'">'
		.T_( 'Delete these posts' ).'</a>]</p>';
}

?>