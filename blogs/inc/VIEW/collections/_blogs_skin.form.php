<?php
/**
 * This file implements the UI view for the Advanced blog properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog;


$Form = & new Form( NULL, 'blogadvanced_checkchanges' );

$Form->begin_form( 'fform', T_('Choose a skin') );

	$Form->hidden_ctrl();
	$Form->hidden( 'action', 'update' );
	$Form->hidden( 'tab', 'skin' );
	$Form->hidden( 'blog',$edited_Blog->ID );

	$Form->begin_fieldset( T_('Available skins') );

	for( skin_list_start(); skin_list_next(); )
	{
		$skin_name = skin_list_iteminfo( 'name', false );
		$skin_path = skin_list_iteminfo( 'path', false );
		$skin_url = skin_list_iteminfo( 'url', false );
		$preview_url = url_add_param($edited_Blog->get('blogurl'),'tempskin='.rawurlencode($skin_name));
		echo '<div class="skinshot">';
		echo '<div class="skinshot_placeholder';
		if( $skin_name == $edited_Blog->default_skin )
		{
			echo ' current';
		}
		echo '">';
		if( file_exists( $skin_path.'/skinshot.jpg' ) )
		{
			echo '<a href="'.$preview_url.'" target="_blank" title="'.T_('Preview blog with this skin in a new window').'">';
			echo '<img src="'.$skin_url.'/skinshot.jpg" width="240" height="180" alt="'.$skin_name.'" /></a>';
		}
		else
		{
			echo '<div class="skinshot_noshot">'.T_('No skinshot available for').'</div>';
			echo '<a href="'.$preview_url.'" target="_blank" title="'.T_('Preview blog with this skin in a new window').'">';
			echo '<div class="skinshot_name">'.$skin_name.'</div>';
			echo '</a>';
		}
		echo '</div>';
		echo '<div class="legend">';
		echo '<div class="actions">';
		if( $skin_name == $edited_Blog->default_skin )
		{
			echo T_('Selected');
		}
		else
		{
			echo '<a href="?ctrl=coll_settings&tab=skin&blog='.$edited_Blog->ID.'&amp;action=update&amp;blog_default_skin='.rawurlencode($skin_name).'">'.T_('Select').'</a>';
		}
		echo '</div>';
		echo '<strong>'.$skin_name.'</strong></div>';
		echo '</div>';
	}

	echo '<div class="clear"></div>';

	$Form->end_fieldset( );

$Form->end_form();
?>