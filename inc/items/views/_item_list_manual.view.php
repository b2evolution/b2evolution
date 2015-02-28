<?php
/**
 * This file implements the recursive chapter list with posts inside.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
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