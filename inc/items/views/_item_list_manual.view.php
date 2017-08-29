<?php
/**
 * This file implements the recursive chapter list with posts inside.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


items_manual_results_block();

/* fp> TODO: maybe... (a general group move of posts would be more useful actually)
echo '<p class="note">'.T_('<strong>Note:</strong> Deleting a category does not delete posts from that category. It will just assign them to the parent category. When deleting a root category, posts will be assigned to the oldest remaining category in the same collection (smallest category number).').'</p>';
*/

global $Settings, $dispatcher, $ReqURI, $Collection, $Blog, $blog;

if( empty( $Blog ) )
{ // Set Blog
	$BlogCache = & get_BlogCache();
	$Collection = $Blog = $BlogCache->get_by_ID( $blog );
}

// Use a wrapper div to have margin around the form
echo '<div id="form_wrapper" style="margin: 2ex auto 1ex">';

$Form = new Form( NULL, 'cat_order_checkchanges', 'post', 'compact' );
$Form->begin_form( 'fform', T_('Category order').get_manual_link('categories_order') );
$Form->add_crumb( 'collection' );
$Form->hidden( 'ctrl', 'coll_settings' );
$Form->hidden( 'action', 'update' );
$Form->hidden( 'blog', $Blog->ID );
$Form->hidden( 'tab', 'chapters' );
$Form->hidden( 'redirect_to', regenerate_url( '', '', '', '&' ) );
$Form->radio_input( 'category_ordering', $Blog->get_setting('category_ordering'), array(
					array( 'value'=>'alpha', 'label'=>T_('Alphabetically') ),
					array( 'value'=>'manual', 'label'=>T_('Manually') ),
			 ), T_('Sort categories'), array( 'note'=>'('.T_('Note: can be overridden for sub-categories').')' ) );
$Form->end_form( array( array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' ) )  );

echo '</div>'; // form wrapper end

if( ! $Settings->get('allow_moving_chapters') )
{ // TODO: check perm
	echo '<p class="alert alert-info">'.sprintf( T_('<strong>Note:</strong> Moving categories across blogs is currently disabled in the %sblogs settings%s.'), '<a href="'.$dispatcher.'?ctrl=collections&tab=blog_settings#categories">', '</a>' ).'</p> ';
}

?>