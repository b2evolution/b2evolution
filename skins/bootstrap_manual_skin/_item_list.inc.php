<?php
/**
 * This is the template that displays the item block in list
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template (or other templates)
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage bootstrap_manual
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Default params:
$params = array_merge( array(
		'post_navigation' => 'same_category', // Always navigate through category in this skin
		'before_title'    => '<h3>',
		'after_title'     => '</h3>',
		'before_content'  => '<div class="excerpt">',
		'after_content'   => '</div>',
		'Item'            => NULL
	), $params );

$curr_Item = $params['Item'];
if( empty( $curr_Item ) )
{
	global $Item;
	$curr_Item = $Item;
}

?>
<li><?php
		$item_edit_link = $curr_Item->get_edit_link( array(
				'class' => button_class( 'text' ),
			) );
		$curr_Item->title( array(
				'before'          => $params['before_title'],
				'after'           => $params['after_title'].$item_edit_link.'<div class="clear"></div>',
				'before_title'    => get_icon( 'file_message' ),
				//'after'      => ' <span class="red">'.( $Item->get('order') > 0 ? $Item->get('order') : 'NULL').'</span>'.$params['after_title'].$item_edit_link.'<div class="clear"></div>',
				'post_navigation' => $params['post_navigation'],
				'link_class'      => 'link',
			) );
		$curr_Item->excerpt( array(
				'before' => $params['before_content'],
				'after'  => $params['after_content'],
			) );
?></li>
