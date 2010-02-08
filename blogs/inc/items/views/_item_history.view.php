<?php
/**
 * This file implements the Item history view
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 * @var Item
 */
global $edited_Item;

$sql1 = 'SELECT "'.$edited_Item->mod_date.'" AS iver_edit_datetime, "'.$edited_Item->lastedit_user_ID.'" AS user_login, "Current version" AS action';
$SQL = new SQL();
$SQL->SELECT( 'iver_edit_datetime, user_login,  "Archived version" AS action' );
$SQL->FROM( 'T_items__version LEFT JOIN T_users ON iver_edit_user_ID = user_ID' );
$SQL->WHERE( 'iver_itm_ID = ' . $edited_Item->ID );
// fp> not actually necessary:
// UNION
// SELECT "'.$edited_Item->datecreated.'" AS iver_edit_datetime, "'.$edited_Item->creator_user_ID.'" AS user_login, "First version" AS action';

$CountSQL = new SQL();
$CountSQL->SELECT( 'COUNT(*)+1' );
$CountSQL->FROM( 'T_items__version' );
$CountSQL->WHERE( $SQL->get_where( '' ) );

// Create result set:
$Results = new Results( $sql1 . ' UNION ' . $SQL->get(), 'iver_', 'D', NULL, $CountSQL->get() );

$Results->title = T_('Item history (experimental) for:').' '.$edited_Item->get_title();

$Results->cols[] = array(
						'th' => T_('Date'),
						'order' => 'iver_edit_datetime',
						'default_dir' => 'D',
						'th_class' => 'shrinkwrap',
						'td_class' => 'shrinkwrap',
						'td' => '$iver_edit_datetime$',
					);

$Results->cols[] = array(
						'th' => T_('User'),
						'order' => 'user_login',
						'th_class' => 'shrinkwrap',
						'td_class' => 'shrinkwrap',
						'td' => '$user_login$',
					);

$Results->cols[] = array(
						'th' => T_('Note'),
						'order' => 'action',
						'td' => '$action$',
					);


$Results->display();


/*
 * $Log$
 * Revision 1.5  2010/02/08 17:53:16  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.4  2010/01/30 18:55:31  blueyed
 * Fix "Assigning the return value of new by reference is deprecated" (PHP 5.3)
 *
 * Revision 1.3  2009/09/25 13:09:36  efy-vyacheslav
 * Using the SQL class to prepare queries
 *
 * Revision 1.2  2009/03/08 23:57:44  fplanque
 * 2009
 *
 * Revision 1.1  2009/02/25 01:02:10  fplanque
 * Basic version history of post edits
 *
 */
?>
