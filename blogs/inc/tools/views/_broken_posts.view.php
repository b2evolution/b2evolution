<?php
/**
 * This file display the broken slugs that have no matching target post
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
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

$SQL = new SQL();

$SQL->SELECT( 'post_ID, post_title, post_canonical_slug_ID' );
$SQL->FROM( 'T_items__item' );
$SQL->WHERE( 'post_canonical_slug_ID NOT IN (SELECT slug_ID FROM T_slug )' );

$Results = new Results( $SQL->get() );

$Results->title = T_( 'Broken posts' );
$Results->global_icon( T_('Cancel!'), 'close', regenerate_url( 'action' ) );

$Results->cols[] = array(
	'th' => T_('Item ID'),
	'th_class' => 'shrinkwrap',
	'order' => 'post_ID',
	'td' => '$post_ID$',
	'td_class' => 'small',
);

$Results->cols[] = array(
	'th' => T_('Title'),
	'th_class' => 'nowrap',
	'order' => 'post_title',
	'td' => '$post_title$',
	'td_class' => 'small center',
);

$Results->cols[] = array(
	'th' => T_('Slug ID'),
	'th_class' => 'shrinkwrap',
	'order' => 'post_canonical_slug_ID',
	'td' => '$post_canonical_slug_ID$',
	'td_class' => 'small',
);

$Results->display();

if( ( $current_User->check_perm('options', 'edit', true) ) && ( $Results->get_num_rows() ) )
{ // display Delete link
	$redirect_to = regenerate_url( 'action', 'action=del_broken_posts&'.url_crumb( 'tools' ) );
	echo '<p>[<a href="'.$redirect_to.'">'.T_( 'Delete these posts' ).'</a>]</p>';
}

/*
 * $Log$
 * Revision 1.3  2011/09/04 22:13:21  fplanque
 * copyright 2011
 *
 * Revision 1.2  2010/11/12 15:13:31  efy-asimo
 * MFB:
 * Tool 1: "Find all broken posts that have no matching category"
 * Tool 2: "Find all broken slugs that have no matching target post"
 * Tool 3: "Create sample comments for testing moderation"
 *
 */
?>