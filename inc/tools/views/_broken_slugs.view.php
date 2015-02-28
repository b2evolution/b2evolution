<?php
/**
 * This file display the broken slugs that have no matching target post
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

memorize_param( 'action', 'string', '', 'find_broken_slugs' );

$SQL = new SQL();

$SQL->SELECT( 'slug_ID, slug_title, slug_itm_ID' );
$SQL->FROM( 'T_slug' );
$SQL->WHERE( 'slug_type = "item" AND slug_itm_ID NOT IN (SELECT post_ID FROM T_items__item )' );

$Results = new Results( $SQL->get(), 'broken_slugs_' );

$Results->title = T_( 'Broken item slugs with no matching item' );
$Results->global_icon( T_('Cancel!'), 'close', regenerate_url( 'action' ) );

$Results->cols[] = array(
	'th' => T_('Slug ID'),
	'th_class' => 'shrinkwrap',
	'td_class' => 'small center',
	'order' => 'slug_ID',
	'td' => '$slug_ID$',
);

$Results->cols[] = array(
	'th' => T_('Title'),
	'th_class' => 'nowrap',
	'td_class' => 'small',
	'order' => 'slug_title',
	'td' => '$slug_title$',
);

$Results->cols[] = array(
	'th' => T_('Item ID'),
	'th_class' => 'shrinkwrap',
	'td_class' => 'small center',
	'order' => 'slug_itm_ID',
	'td' => '$slug_itm_ID$',
);

$Results->display( array(
		'page_url' => regenerate_url( 'blog,ctrl,action,results_'.$Results->param_prefix.'page', 'action='.param_action().'&amp;'.url_crumb( 'tools' ) )
	) );

if( ( $current_User->check_perm('options', 'edit', true) ) && ( $Results->get_num_rows() ) )
{ // display Delete link
	global $DB;
	$slug_IDs = $DB->get_col( $SQL->get() );

	echo '<p>[<a href="'.regenerate_url( 'action', 'action=del_broken_slugs&amp;slugs='.implode( ',', $slug_IDs ).'&amp;'.url_crumb( 'tools' ) ).'">'.T_( 'Delete these slugs' ).'</a>]</p>';
}

?>