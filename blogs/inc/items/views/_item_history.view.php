<?php
/**
 * This file implements the Item history view
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}.
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


$sql = 'SELECT "'.$edited_Item->mod_date.'" AS iver_edit_datetime, "'.$edited_Item->lastedit_user_ID.'" AS user_login, "Current version" AS action
UNION
	SELECT iver_edit_datetime, user_login,  "Archived version" AS action
		FROM T_items__version LEFT JOIN T_users ON iver_edit_user_ID = user_ID
		WHERE iver_itm_ID = '.$edited_Item->ID;
// fp> not actually necessary:
// UNION
// SELECT "'.$edited_Item->datecreated.'" AS iver_edit_datetime, "'.$edited_Item->creator_user_ID.'" AS user_login, "First version" AS action';

$count_sql = 'SELECT COUNT(*)+1
FROM T_items__version
WHERE iver_itm_ID = '.$edited_Item->ID;

// Create result set:
$Results = & new Results( $sql, 'iver_', 'D', NULL, $count_sql );

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
 * Revision 1.1  2009/02/25 01:02:10  fplanque
 * Basic version history of post edits
 *
 */
?>