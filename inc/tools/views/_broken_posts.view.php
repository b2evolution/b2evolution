<?php
/**
 * This file display the broken post that have no matching category
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
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
$Results->global_icon( T_('Cancel').'!', 'close', regenerate_url( 'action' ) );

$Results->cols[] = array(
	'th' => T_('Item ID'),
	'th_class' => 'shrinkwrap',
	'td_class' => 'small center',
	'order' => 'post_ID',
	'td' => '$post_ID$',
);


/**
 * Get a link to edit post if current user has a permission
 *
 * @param integer Post ID
 * @param string Post title
 * @return string
 */
function broken_post_edit_link( $post_ID, $post_title )
{
	global $current_User, $blog;

	if( ! $current_User->check_perm( 'blogs', 'editall' ) )
	{ // User has no permission, Display only post title as text
		return $post_title;
	}

	if( empty( $blog ) )
	{ // Set this variable, otherwise super admin will see only debug die error and cannot edit the broken posts
		$blog = 1;
	}

	$ItemCache = & get_ItemCache();
	$Item = & $ItemCache->get_by_ID( $post_ID, false, false );

	// Display a link to edit a post
	return $Item->get_edit_link( array(
			'text' => $post_title,
		) );
}
$Results->cols[] = array(
	'th' => T_('Title'),
	'th_class' => 'nowrap',
	'order' => 'post_title',
	'td' => '%broken_post_edit_link( #post_ID#, #post_title# )%',
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