<?php
/**
 * This file implements the Skin properties form.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 *
 * @author fplanque: Francois PLANQUE.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $Collection, $Blog, $Settings, $current_User, $skin_type, $admin_url;

$Form = new Form( NULL, 'skin_settings_checkchanges' );

$Form->begin_form( 'fform' );

	$Form->hidden_ctrl();

	if( isset( $Blog ) )
	{
		$Form->add_crumb( 'collection' );
		$Form->hidden( 'tab', 'skin' );
		$Form->hidden( 'skin_type', $skin_type );
		$Form->hidden( 'action', 'update' );
		$Form->hidden( 'blog', $Blog->ID );
	}
	else
	{
		$Form->add_crumb( 'siteskin' );
		$Form->hidden_ctrl();
		$Form->hidden( 'tab', 'site_skin' );
		$Form->hidden( 'skin_type', $skin_type );
		$Form->hidden( 'action', 'update_site_skin' );
	}

	switch( $skin_type )
	{
		case 'normal':
			$skin_ID = isset( $Blog ) ? $Blog->get_setting( 'normal_skin_ID' ) : $Settings->get( 'normal_skin_ID' );
			$fieldset_title = isset( $Blog ) ? T_('Default Collection skin') : T_('Default Site skin');
			break;

		case 'mobile':
			$skin_ID = isset( $Blog ) ? $Blog->get_setting( 'mobile_skin_ID', true ) : $Settings->get( 'mobile_skin_ID', true );
			$fieldset_title = isset( $Blog ) ? T_('Default Collection mobile phone skin') : T_('Default Site mobile phone skin');
			break;

		case 'tablet':
			$skin_ID = isset( $Blog ) ? $Blog->get_setting( 'tablet_skin_ID', true ) : $Settings->get( 'tablet_skin_ID', true );
			$fieldset_title = isset( $Blog ) ? T_('Default Collection tablet skin') : T_('Default Site tablet skin');
			break;

		default:
			debug_die( 'Wrong skin type: '.$skin_type );
	}

	// Initialize a link to go to site/collection skin settings:
	if( isset( $Blog ) )
	{	// If collection skin page is opened currently:
		if( $current_User->check_perm( 'options', 'view' ) )
		{	// If current user has a permission to view site skin:
			$goto_link_url = $admin_url.'?ctrl=collections&amp;tab=site_skin'.( $skin_type == 'mobile' || $skin_type == 'tablet' ? '&amp;skin_type='.$skin_type : '' );
			$goto_link_title = T_('Go to Site skin');
		}
		// Append manual/doc link:
		$fieldset_title .= get_manual_link( 'blog-skin-settings' );
	}
	else
	{	// If site skin page is opened currently:
		if( ( $working_coll_ID = get_working_blog() ) &&
		    $current_User->check_perm( 'blog_properties', 'edit', false, $working_coll_ID ) )
		{	// If working collection is set and current user has a permission to edit the collection skin:
			$goto_link_url = $admin_url.'?ctrl=coll_settings&amp;tab=skin&amp;blog='.$working_coll_ID.( $skin_type == 'mobile' || $skin_type == 'tablet' ? '&amp;skin_type='.$skin_type : '' );
			$goto_link_title = T_('Go to Collection skin');
		}
		// Append manual/doc link:
		$fieldset_title .= get_manual_link( 'site-skin-settings' );
	}
	if( isset( $goto_link_url ) )
	{
		$fieldset_title .= ' <span class="panel_heading_action_icons"><a href="'.$goto_link_url.'" class="btn btn-sm btn-info">'.$goto_link_title.' &raquo;</a></span>';
	}

	$fieldset_title_links = '<span class="floatright panel_heading_action_icons">&nbsp;'.action_icon( T_('Select another skin...'), 'choose', regenerate_url( 'action', 'skinpage=selection&amp;skin_type='.$skin_type ), ' '.T_('Choose a different skin').' &raquo;', 3, 4, array( 'class' => 'action_icon btn btn-info btn-sm' ) );
	if( $skin_ID && $current_User->check_perm( 'options', 'view' ) )
	{	// Display "Reset params" button only when skin ID has a real value ( when $skin_ID = 0 means it must be the same as the normal skin value ):
		$fieldset_title_links .= action_icon( T_('Reset params'), 'reload',
				regenerate_url( 'action', 'ctrl=skins&amp;skin_ID='.$skin_ID.'&amp;skin_type='.$skin_type.'&amp;blog='.( isset( $Blog ) ? $Blog->ID : '0' ).'&amp;action=reset&amp;'.url_crumb( 'skin' ) ),
				' '.T_('Reset params'), 3, 4, array(
					'class'   => 'action_icon btn btn-default btn-sm',
					'onclick' => 'return confirm( \''.TS_( 'This will reset all the params to the defaults recommended by the skin.\nYou will lose your custom settings.\nAre you sure?' ).'\' )',
			) );
	}
	$fieldset_title_links .= '</span>';
	display_skin_fieldset( $Form, $skin_ID, array( 'fieldset_title' => $fieldset_title, 'fieldset_links' => $fieldset_title_links ) );

$buttons = array();
if( $skin_ID )
{	// Allow to update skin params only when it is really selected (Don't display this button to case "Same as normal skin."):
	$buttons[] = array( 'submit', 'submit', T_('Save Changes!'), 'SaveButton' );
}

$Form->end_form( $buttons );

?>