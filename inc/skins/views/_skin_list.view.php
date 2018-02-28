<?php
/**
 * This file implements the UI view for the installed skins.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2018 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $template_action;

if( ! empty( $template_action ) )
{
	$block_item_Widget = new Widget( 'block_item' );

	// Turn off the output buffering to do the correct work of the function flush()
	@ini_set( 'output_buffering', 'off' );

	switch( $template_action )
	{
		case 'show_skin_updates':
			global $skin_updates;

			$block_item_Widget->title = T_('Updates are available');
			$block_item_Widget->disp_template_replaced( 'block_start' );
			evo_flush();
			echo T_('Select which update you want to download:');
			echo '<ul>';
			foreach( $skin_updates as $update )
			{
				$target_file = $update['class'].'-'.$update['version'].'.zip';
				$download_url = regenerate_url( 'skin_ID', 'action=download_use_skin&amp;file='.rawurlencode( $update['url'] ).'&amp;target='.rawurlencode( $target_file ).'&amp;'.url_crumb( 'skin' ) );
				echo '<li>'.action_icon( TS_('Download file'), 'download', $download_url ).'<a href="'.$download_url.'">'.$update['name'].'</a></li>';
			}
			echo '</ul>';
			break;

		case 'download_skin_update':
			$block_item_Widget->title = T_('Updating skin');
			$block_item_Widget->disp_template_replaced( 'block_start' );
			evo_flush();
			$download_url = param( 'file', 'url', true );
			$target = param( 'target', 'string', true );
			download_skin_update( $download_url, $target );
			break;
	}

	$block_item_Widget->disp_template_raw( 'block_end' );
}

// Create result set:
$SQL = new SQL();
$SQL->SELECT( 'T_skins__skin.*, SUM( IF( blog_normal_skin_ID = skin_ID, 1, 0 ) + IF( blog_mobile_skin_ID = skin_ID, 1, 0 ) + IF( blog_tablet_skin_ID = skin_ID, 1, 0 ) ) AS nb_blogs' );
$SQL->FROM( 'T_skins__skin LEFT JOIN T_blogs ON (skin_ID = blog_normal_skin_ID OR skin_ID = blog_mobile_skin_ID OR skin_ID = blog_tablet_skin_ID )' );
$SQL->GROUP_BY( 'skin_ID, skin_class, skin_name, skin_type, skin_folder' );

$count_SQL = new SQL();
$count_SQL->SELECT( 'COUNT( * )' );
$count_SQL->FROM( 'T_skins__skin' );

$Results = new Results( $SQL->get(), 'skin_', '', NULL, $count_SQL->get() );

$Results->Cache = & get_SkinCache();

$Results->title = T_('Installed skins').get_manual_link('installed_skins');

if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
	$Results->cols[] = array(
							'th' => T_('Name'),
							'order' => 'skin_name',
							'td' => '<strong><a href="'.regenerate_url( '', 'skin_ID=$skin_ID$&amp;action=edit' ).'" title="'.TS_('Edit skin properties...').'">$skin_name$</a></strong>',
						);
}
else
{ // We have NO permission to modify:
	$Results->cols[] = array(
							'th' => T_('Name'),
							'order' => 'skin_name',
							'td' => '<strong>$skin_name$</strong>',
						);
}

$Results->cols[] = array(
						'th' => T_('Version'),
						'td_class' => 'center',
						'td' => '%skin_td_version( #skin_ID# )%'
					);

$Results->cols[] = array(
						'th' => T_('Skin type'),
						'order' => 'skin_type',
						'td_class' => 'center',
						'td' => '$skin_type$',
					);

$Results->cols[] = array(
						'th' => T_('Collections'),
						'order' => 'nb_blogs',
						'default_dir' => 'D',
						'th_class' => 'shrinkwrap',
						'td_class' => 'center',
						'td' => '~conditional( (#nb_blogs# > 0), #nb_blogs#, \'&nbsp;\' )~',
					);

$Results->cols[] = array(
						'th' => T_('Skin Folder'),
						'order' => 'skin_folder',
						'td' => '$skin_folder$',
					);

if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
	$Results->cols[] = array(
							'th' => T_('Actions'),
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							'td' => '%skin_td_actions( {row} )%'
						);

	$ignore_params = ( get_param( 'tab' ) == 'system' ? 'action,blog' : 'action' );
	$Results->global_icon( T_('Install new skin...'), 'new', regenerate_url( $ignore_params, 'action=new'), T_('Install new').' &raquo;', 3, 4, array( 'class' => 'action_icon btn-primary' ) );
}

function skin_td_version( $skin_ID )
{
	$r = '<span class="skin_version">'.get_skin_version( $skin_ID ).'</span>';
	return $r;
}

function skin_td_actions( $row )
{
	$r = action_icon( TS_('Edit skin properties...'), 'properties', regenerate_url( '', 'skin_ID='.$row->skin_ID.'&amp;action=edit' ) )
			.action_icon( TS_('Reload containers').'!', 'reload', regenerate_url( '', 'skin_ID='.$row->skin_ID.'&amp;action=reload&amp;'.url_crumb( 'skin' ) ) );

	if( $row->nb_blogs < 1 )
	{
		$r .= action_icon( TS_('Uninstall this skin!'), 'delete', regenerate_url( '', 'skin_ID='.$row->skin_ID.'&amp;action=delete&amp;'.url_crumb( 'skin' ) ) );
	}
	else
	{
		$r .= get_icon( 'delete', 'noimg' );
	}

	$url_title = str_replace( '_', '-', strtolower( $row->skin_class ) );
	$r .= action_icon( TS_('Check for updates'), 'refresh', regenerate_url( '', 'skin_ID='.$row->skin_ID.'&amp;action=check_update&amp;'.url_crumb( 'skin' ) ) );

	return $r;
}

// $fadeout_array = array( 'skin_ID' => array(6) );
$fadeout_array = NULL;

$Results->display( NULL, 'session' );

?>