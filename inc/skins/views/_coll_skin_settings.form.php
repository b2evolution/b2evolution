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


global $Collection, $Blog, $Settings, $current_User, $skin_type, $admin_url, $mode;

if( $mode == 'customizer' )
{	// Display tabs to switch between site and collection skins in special div on customizer mode:
	if( empty( $Blog ) )
	{	// Get last working collection:
		$BlogCache = & get_BlogCache();
		$tab_Blog = & $BlogCache->get_by_ID( get_working_blog(), false, false );
	}
	else
	{	// Use current collection:
		$tab_Blog = $Blog;
		set_working_blog( $tab_Blog->ID );
	}

	echo '<div class="evo_customizer__tabs">';

	echo '<ul class="nav nav-tabs">';
	if( $Settings->get( 'site_skins_enabled' ) &&
	    $current_User->check_perm( 'options', 'edit' ) )
	{	// If current User can edit site skin settings:
		echo '<li'.( ! isset( $Blog ) ? ' class="active"' : '' ).'><a href="'.$admin_url.'?ctrl=customize&amp;view=site_skin">'.T_('Site').'</a></li>';
	}
	if( $current_User->check_perm( 'blog_properties', 'edit', false, $tab_Blog->ID ) )
	{	// If current User can edit current collection skin settings:
		echo '<li'.( isset( $Blog ) ? ' class="active"' : '' ).'><a href="'.$admin_url.'?ctrl=customize&amp;view=coll_skin&amp;blog='.$tab_Blog->ID.'">'.$tab_Blog->get( 'shortname' ).'</a></li>';
	}
	echo '</ul>';

	echo '</div>';
}

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
		$Form->hidden( 'mode', $mode );
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

	$link_select_skin = action_icon( T_('Select another skin...'), 'choose',
			regenerate_url( 'action,mode', 'skinpage=selection&amp;skin_type='.$skin_type ),
			' '.T_('Choose a different skin').' &raquo;', 3, 4, array(
				'class' => $mode == 'customizer' ? 'small' : 'action_icon btn btn-info btn-sm',
				'target' => $mode == 'customizer' ? '_top' : '',
		) );
	$link_reset_params = '';
	$fieldset_title_links = '<span class="panel_heading_action_icons pull-right">'.$link_select_skin;

	if( $skin_ID && $current_User->check_perm( 'options', 'view' ) )
	{	// Display "Reset params" button only when skin ID has a real value ( when $skin_ID = 0 means it must be the same as the normal skin value ):
		$link_reset_url = regenerate_url( 'ctrl,action', 'ctrl=skins&amp;skin_ID='.$skin_ID.'&amp;skin_type='.$skin_type.'&amp;blog='.( isset( $Blog ) ? $Blog->ID : '0' ).'&amp;action=reset&amp;'.url_crumb( 'skin' ) );
		$link_reset_params = action_icon( T_('Reset params'), 'reload',
				$link_reset_url,
				' '.T_('Reset params'), 3, 4, array(
					'class'   => $mode == 'customizer' ? 'small' : 'action_icon btn btn-default btn-sm',
					'onclick' => 'return evo_confirm_skin_reset()',
					'target' => $mode == 'customizer' ? 'evo_customizer__backoffice' : '',
			) );
		$fieldset_title_links .= $link_reset_params;
	}
	$fieldset_title_links .= '</span><div class="clearfix"></div>';
	display_skin_fieldset( $Form, $skin_ID, array( 'fieldset_title' => $fieldset_title, 'fieldset_links' => $fieldset_title_links ) );

$buttons = array();
if( $skin_ID )
{	// Allow to update skin params only when it is really selected (Don't display this button to case "Same as normal skin."):
	if( isset( $Blog ) ||
	    ( $Settings->get( 'site_skins_enabled' ) && $current_User->check_perm( 'options', 'edit' ) ) )
	{	// If current User can edit skin settings of site or collection:
		$buttons[] = array( 'submit', 'save', ( $mode == 'customizer' ? T_('Apply Changes!') : T_('Save Changes!') ), 'SaveButton' );
	}
}

if( $mode == 'customizer' )
{	// Display buttons in special div on customizer mode:
	echo '<div class="evo_customizer__buttons">';
	$Form->buttons( $buttons );
	echo '<div class="evo_customizer__links">'.$link_select_skin.$link_reset_params.'</div>';
	echo '</div>';
	// Clear buttons to don't display them twice:
	$buttons = array();
}

$Form->end_form( $buttons );

if( isset( $link_reset_url ) )
{	// Initialize JS to confirm skin reset action if current user has a permission:
	$skin_reset_confirmation_msg = TS_( 'This will reset all the params to the defaults recommended by the skin.\nYou will lose your custom settings.\nAre you sure?' );
?>
<script type="text/javascript">
function evo_confirm_skin_reset()
{
<?php
if( $mode == 'customizer' )
{	// If skin customizer mode:
?>
	window.parent.openModalWindow( '<form action="<?php echo str_replace( '&amp;', '&', $link_reset_url ); ?>" method="post" target="evo_customizer__backoffice" onsubmit="closeModalWindow()">' +
				'<span class="text-danger"><?php echo $skin_reset_confirmation_msg; ?></span>' +
				'<input type="submit" value="<?php echo TS_('Reset params'); ?>" />' +
			'</form>',
		'500px', '100px', true, '<?php echo TS_('Reset params'); ?>', [ '<?php echo TS_('Reset params'); ?>', 'btn btn-danger' ] );
	return false;
<?php
}
else
{	// Normal back-office mode:
?>
	return confirm( '<?php echo $skin_reset_confirmation_msg; ?>' );
<?php
}
?>
}
</script>
<?php
}
?>