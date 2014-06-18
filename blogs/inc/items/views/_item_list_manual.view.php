<?php
/**
 * This file implements the recursive chapter list with posts inside.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _item_list_manual.view.php 2193 2012-10-19 11:01:47Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


items_manual_results_block();

/* fp> TODO: maybe... (a general group move of posts would be more useful actually)
echo '<p class="note">'.T_('<strong>Note:</strong> Deleting a category does not delete posts from that category. It will just assign them to the parent category. When deleting a root category, posts will be assigned to the oldest remaining category in the same collection (smallest category number).').'</p>';
*/

global $Settings, $dispatcher, $ReqURI, $blog;

echo '<p class="note">'.sprintf( T_('<strong>Note:</strong> Ordering of categories is currently set to %s in the %sblogs settings%s.'),
	$Settings->get('chapter_ordering') == 'manual' ? /* TRANS: Manual here = "by hand" */ T_('Manual ') : T_('Alphabetical'), '<a href="'.$dispatcher.'?ctrl=collections&tab=blog_settings#categories">', '</a>' ).'</p> ';

if( ! $Settings->get('allow_moving_chapters') )
{ // TODO: check perm
	echo '<p class="note">'.sprintf( T_('<strong>Note:</strong> Moving categories across blogs is currently disabled in the %sblogs settings%s.'), '<a href="'.$dispatcher.'?ctrl=collections&tab=blog_settings#categories">', '</a>' ).'</p> ';
}

?>