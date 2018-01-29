<?php
/**
 * This file implements the UI view for the Available skins.
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

global $skins_path, $admin_url, $redirect_to, $action, $kind, $blog, $skin_type;

$sel_skin_type = param( 'sel_skin_type', 'string', $skin_type );
$collection_kind = param( 'collection_kind', 'string', NULL );

if( $collection_kind !== NULL )
{	// Collection kind was changed, use this value instead of previous kind value:
	$kind = $collection_kind;
}
else
{
	$kind = param( 'kind', 'string', NULL );
}

/**
 * @var SkinCache
 */
$SkinCache = & get_SkinCache();
$SkinCache->load_all();

$block_item_Widget = new Widget( 'block_item' );

if( get_param( 'tab' ) == 'current_skin' )
{	// We are installing new skin for collection:
	$BlogCache = & get_BlogCache();
	$Collection = $Blog = & $BlogCache->get_by_ID( $blog );
	switch( $sel_skin_type )
	{
		case 'normal':
			$skin_type_title = /* TRANS: Skin type name */ T_('Normal');
			break;
		case 'mobile':
			$skin_type_title = /* TRANS: Skin type name */ T_('Mobile');
			break;
		case 'tablet':
			$skin_type_title = /* TRANS: Skin type name */ T_('Tablet');
			break;
		default:
			$skin_type_title = '';
			break;
	}
	$block_title = sprintf( T_('Install a new %s skin for %s:'), $skin_type_title, $Blog->get( 'name' ) );
}
else
{	// We are managing the skins:
	$block_title = T_('Skins available for installation');
}

$block_item_Widget->title = $block_title.get_manual_link( 'installing_skins' );

if( $current_User->check_perm( 'options', 'edit', false ) )
{ // We have permission to modify:
	$block_item_Widget->global_icon( T_('Cancel install!'), 'close', $redirect_to );
}

$block_item_Widget->disp_template_replaced( 'block_start' );

// Skin type selector:
$Form = new Form( $admin_url, '', 'get', 'blockspan' );
$Form->hidden_ctrl();
$Form->hidden( 'action', $action );
$Form->hidden( 'redirect_to', $redirect_to );
$Form->hidden( 'skin_type', get_param( 'skin_type' ) );
$Form->hidden( 'kind', get_param( 'kind' ) );
$Form->hidden( 'tab', get_param( 'tab' ) );
$Form->begin_form( 'skin_selector_filters' );
$Form->select_input_array( 'sel_skin_type', $sel_skin_type, array(
		''        => T_('All skins'),
		'normal'  => T_('Normal skins'),
		'mobile'  => T_('Mobile skins'),
		'tablet'  => T_('Tablet skins'),
		'feed'    => T_('Feed skins'),
		'sitemap' => T_('Sitemap skins'),
	), T_('Skin type'), '', array(
		'force_keys_as_values' => true,
		'onchange' => 'this.form.submit()'
	) );
echo ' &nbsp;';

if( $kind === NULL && isset( $Blog ) )
{	// Kind is not specified, use type of current collection instead:
	$kind = $Blog->type;
}

$Form->select_input_array( 'collection_kind', $kind, array(
		'' => T_('All'),
		'main' => T_('Main'),
		'std' => T_('Blog'),
		'photo' => T_('Photos'),
		'forum' => T_('Forums'),
		'manual' => T_('Manual'),
		'group' => T_('Tracker')
	), T_('Collection kind'), '', array(
		'force_keys_as_values' => true,
		'onchange' => 'this.form.submit()'
	) );
$Form->end_form();

$filename_params = array(
		'inc_files' => false,
		'recurse'   => false,
		'basename'  => true,
	);
// Get all skin folder names:
$skin_folders = get_filenames( $skins_path, $filename_params );

$Form = new Form( $admin_url, '', 'post', 'blockspan' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'install' );
if( isset($tab) ) $Form->hidden( 'tab', $tab ); // fp> do we need this?
$Form->hidden( 'redirect_to', $redirect_to );
$Form->add_crumb( 'skin' );

$Form->begin_form( 'skin_selector_form' );

echo '<div class="skin_selector_block">';

$skins_exist = false;
// Go through all skin folders:
sort( $skin_folders );

$skin_folders_data = array();
$skin_versions = array();

// First part - go through all folders and check for installed and multiple skin versions
foreach( $skin_folders as $skin_folder )
{
	$skin_folders_data[$skin_folder] = array(
			'version'    => 0,
			'installed'  => false,
			'status'     => 'ignore',
			'supported'  => NULL,
			'skin_type'  => NULL,
		);

	// Get base skin and skin version
	list( $base_skin, $skin_version ) = get_skin_folder_base_version( $skin_folder );
	$skin_folders_data[$skin_folder]['version'] = $skin_version;
	$skin_folders_data[$skin_folder]['base_skin'] = $base_skin;
	$skin_folders_data[$skin_folder]['skin_type'] = ( substr( $skin_folder, 0, 1 ) == '_' ? 'feed' : 'normal' );

	if( is_empty_directory( $skins_path.$skin_folder ) )
	{ // Empty skin folder:
		continue;
	}

	if( !strlen( $skin_folder ) || $skin_folder[0] == '.' || $skin_folder == 'CVS' )
	{	// Skip system folders:
		continue;
	}

	// What xxx_Skin class name do we expect from this skin?
	// (remove optional "_skin" and always append "_Skin"):
	$skin_class_name = preg_replace( '/_skin$/', '', $base_skin ).'_Skin';

	if( isset( $skin_versions[$base_skin] ) )
	{ // We already have a similar skin
		if( evo_version_compare( $skin_versions[$base_skin]['version'], $skin_version, '<' ) )
		{ // Current skin is the latest version, update previous latest version
			$skin_folders_data[$skin_versions[$base_skin]['folder']]['disp_params'] = array(
					'function' => 'broken',
					'msg' => T_('Old version')
				);
			$skin_folders_data[$skin_versions[$base_skin]['folder']]['status'] = 'old version';

			// Set current skin as latest version
			$skin_versions[$base_skin]['folder'] = $skin_folder;
			$skin_versions[$base_skin]['version'] = $skin_version;
		}
	}
	else
	{
		$skin_versions[$base_skin] = array(
				'folder'    => $skin_folder,
				'version'   => $skin_version,
				'supported' => NULL,
				'skin_type' => NULL
			);
	}

	if( $loop_Skin = & $SkinCache->get_by_folder( $skin_folder, false ) )
	{ // Skin version already installed:
		$supported = ( $kind == '' || $loop_Skin->supports_coll_kind( $kind ) == 'yes' );
		$skin_folders_data[$skin_folder]['installed'] = true;
		$skin_folders_data[$skin_folder]['supported'] = $supported;
		$skin_folders_data[$skin_folder]['skin_type'] = $loop_Skin->type;

		$skin_versions[$base_skin]['installed'] = array(
				'folder'    => $loop_Skin->folder,
				'version'   => $loop_Skin->version,
				'supported' => $supported,
				'skin_type' => $loop_Skin->type,
			);

		continue;
	}

	// Check if we already have such a skin
	if( class_exists( $skin_class_name ) )
	{	// This class already exists!
		$disp_params = array(
				'function'        => 'broken',
				'msg'             => T_('DUPLICATE SKIN NAME'),
			);
		$skin_folders_data[$skin_folder]['status'] = 'duplicate';
	}
	elseif( ! @$skin_class_file_contents = file_get_contents( $skins_path.$skin_folder.'/_skin.class.php' ) )
	{ 	// Could not load the contents of the skin file:
		$disp_params = array(
				'function'        => 'broken',
				'msg'             => T_('_skin.class.php NOT FOUND!'),
			);
		$skin_folders_data[$skin_folder]['status'] = 'missing class file';
	}
	elseif( strpos( $skin_class_file_contents, 'class '.$skin_class_name.' extends Skin' ) === false )
	{
		$disp_params = array(
				'function'        => 'broken',
				'msg'             => T_('MALFORMED _skin.class.php'),
			);
			$skin_folders_data[$skin_folder]['status'] = 'malformed class file';
	}
	else
	{ // We cannot proceed with checks that load the skin class as it will cause a fatal error if we redeclare a skin class.
		$skin_folders_data[$skin_folder]['status'] = 'check';
	}

	if( isset( $disp_params ) )
	{
		$skin_folders_data[$skin_folder]['disp_params'] = $disp_params;
	}

	$skins_exist = true;
}

// Second part - Load latest versions
foreach( $skin_folders_data as $skin_folder => $data )
{
	$base_skin = $skin_folders_data[$skin_folder]['base_skin'];
	if( $data['status'] == 'duplicate' || ( isset( $skin_versions[$base_skin]['installed'] ) && $data['status'] == 'old version' ) )
	{
		if( isset( $skin_versions[$base_skin]['installed'] ) )
		{
			// Assume same support and skin type from installed version
			$skin_folders_data[$skin_folder]['supported'] = $skin_versions[$base_skin]['installed']['supported'];
			$skin_folders_data[$skin_folder]['skin_type'] = $skin_versions[$base_skin]['installed']['skin_type'];

			$redirect_to_after_install = $redirect_to;
			/*
			$skin_compatible = ( empty( $kind ) || in_array( $folder_Skin->type, array( 'normal', 'feed', 'sitemap', 'mobile', 'tablet', 'rwd' ) ) );
			if( ! empty( $kind ) && $skin_folders_data[$skin_folder]['supported'] )
			{ // If we are installing skin for a new collection we're currently creating:
				$redirect_to_after_install = $admin_url.'?ctrl=collections&action=new-name&kind='.$kind.'&skin_ID=$skin_ID$';
			}
			*/
			switch( evo_version_compare( $skin_versions[$base_skin]['installed']['version'], $data['version'] ) )
			{
				case -1: // Upgrade
					$disp_params = array(
						'function'        => 'upgrade',
						'function_url'    => $admin_url.'?ctrl=skins&amp;action=upgrade&amp;tab='.get_param( 'tab' )
																.( empty( $blog ) ? '' : '&amp;blog='.$blog )
																.( empty( $skin_type ) ? '' : '&amp;skin_type='.$skin_type )
																.'&amp;skin_folder='.rawurlencode( $skin_folder )
																.'&amp;redirect_to='.rawurlencode( $redirect_to_after_install )
																.'&amp;'.url_crumb( 'skin' )
					);
					$skin_folders_data[$skin_folder]['status'] = 'upgrade';
					break;

				case 0: // Save version
					$disp_params = array(
						'function'        => 'broken',
						'msg'             => T_('DUPLICATE SKIN NAME'),
					);
					$skin_folders_data[$skin_folder]['status'] = 'duplicate';
					break;

				case 1: // Downgrade
					$disp_params = array(
						'function'        => 'downgrade',
						'function_url'    => $admin_url.'?ctrl=skins&amp;action=downgrade&amp;tab='.get_param( 'tab' )
																.( empty( $blog ) ? '' : '&amp;blog='.$blog )
																.( empty( $skin_type ) ? '' : '&amp;skin_type='.$skin_type )
																.'&amp;skin_folder='.rawurlencode( $skin_folder )
																.'&amp;redirect_to='.rawurlencode( $redirect_to_after_install )
																.'&amp;'.url_crumb( 'skin' )
					);
					$skin_folders_data[$skin_folder]['status'] = 'downgrade';
					break;
			}

			$skin_folders_data[$skin_folder]['disp_params'] = $disp_params;
		}
	}
	elseif( $data['status'] == 'check' )
	{
		if( ! $folder_Skin = & $SkinCache->new_obj( NULL, $skin_folder ) )
		{ // We could not load the Skin class:
			$disp_params = array(
					'function'        => 'broken',
					'msg'             => T_('_skin.class.php could not be loaded!'),
				);
			$skin_folders_data[$skin_folder]['status'] = 'cannot load class file';
		}
		else
		{	// Skin class seems fine...
			$skin_folders_data[$skin_folder]['skin_type'] = $folder_Skin->type;


			if( $kind != '' && $folder_Skin->supports_coll_kind( $kind ) != 'yes' )
			{ // Filter skin by support for collection type
				$skin_folders_data[$skin_folder]['supported'] = false;
				$skin_folders_data[$skin_folder]['status'] = 'ignore';
			}
			else
			{
				$skin_folders_data[$skin_folder]['supported'] = true;
			}

			if( isset( $skin_versions[$base_skin] ) && $skin_versions[$base_skin]['folder'] == $skin_folder )
			{
				$skin_versions[$base_skin]['skin_type'] = $skin_folders_data[$skin_folder]['skin_type'];
				$skin_versions[$base_skin]['supported'] = $skin_folders_data[$skin_folder]['supported'];
			}

			if( $skin_folders_data[$skin_folder]['status'] == 'ignore' )
			{
				continue;
			}

			if( ! empty( $sel_skin_type ) && $folder_Skin->type != $sel_skin_type &&
					( $folder_Skin->type != 'rwd' || ! in_array( $sel_skin_type, array( 'normal', 'mobile', 'tablet' ) ) ) )
			{	// Filter skin by selected type;
				// For normal, mobile, tablet skins also displays rwd skins:
				$skin_folders_data[$skin_folder]['status'] = 'ignore';
				continue;
			}

			$redirect_to_after_install = $redirect_to;
			$skin_compatible = ( empty( $kind ) || in_array( $folder_Skin->type, array( 'normal', 'feed', 'sitemap', 'mobile', 'tablet', 'rwd' ) ) );
			if( ! empty( $kind ) && $skin_compatible )
			{ // If we are installing skin for a new collection we're currently creating:
				$redirect_to_after_install = $admin_url.'?ctrl=collections&action=new-name&kind='.$kind.'&skin_ID=$skin_ID$';
			}

			if( $skin_compatible )
			{
				$disp_params = array(
					'function'        => 'install',
					'function_url'    => $admin_url.'?ctrl=skins&amp;action=create&amp;tab='.get_param( 'tab' )
															.( empty( $blog ) ? '' : '&amp;blog='.$blog )
															.( empty( $skin_type ) ? '' : '&amp;skin_type='.$skin_type )
															.'&amp;skin_folder='.rawurlencode( $skin_folder )
															.'&amp;redirect_to='.rawurlencode( $redirect_to_after_install )
															.'&amp;'.url_crumb( 'skin' )
				);
				$skin_folders_data[$skin_folder]['status'] = 'ok';
			}
			else
			{
				$disp_params = array(
						'function'      => 'broken',
						'msg'           => T_('Wrong Type!'),
						'help_info'     => sprintf( T_('The skin type %s is not supported by this version of b2evolution'), '&quot;'.$folder_Skin->type.'&quot;' )
				);
				$skin_folders_data[$skin_folder]['status'] = 'wrong type';
			}
		}

		$skin_folders_data[$skin_folder]['disp_params'] = $disp_params;
	}
	else
	{
		// Filter skin by support for collection type
		if( $kind != '' && isset( $data['supported'] ) && ! $data['supported'] )
		{
			$skin_folders_data[$skin_folder]['status'] = 'unsupported collection type';
			continue;
		}
	}
}

// Third part - Go through all skin folders and display each one depending on skin meta data
foreach( $skin_folders as $skin_folder )
{
	$base_skin = $skin_folders_data[$skin_folder]['base_skin'];

	if( ! isset( $skin_folders_data[$skin_folder]['supported'] ) && isset( $skin_versions[$base_skin]['supported'] ) )
	{ // Assume same support from latest version
		$skin_folders_data[$skin_folder]['supported'] = $skin_versions[$base_skin]['supported'];
	}

	if( $skin_folders_data[$skin_folder]['status'] == 'ignore' || $skin_folders_data[$skin_folder]['installed'] )
	{
		continue;
	}

	if( $kind != '' && $skin_folders_data[$skin_folder]['supported'] === false )
	{
		continue;
	}

	if( ! empty( $sel_skin_type ) && $skin_folders_data[$skin_folder]['skin_type'] != $sel_skin_type &&
			( $skin_folders_data[$skin_folder]['skin_type'] != 'rwd' || ! in_array( $sel_skin_type, array( 'normal', 'mobile', 'tablet' ) ) ) )
	{	// Filter skin by selected type;
		// For normal, mobile, tablet skins also displays rwd skins:
		continue;
	}

	// Display skinshot:
	if( ! empty( $skin_folders_data[$skin_folder] ) )
	{
		Skin::disp_skinshot( $skin_folder, $skin_folder, $skin_folders_data[$skin_folder]['disp_params'] );
	}
}

echo '<div class="clear"></div>';
echo '</div>';

if( $skins_exist && empty( $kind ) && get_param( 'tab' ) != 'current_skin' )
{	// Display form buttons only when at least one skin exists for installation:
	// Don't enabled this feature on new collection creating and on selecting new skin for the colleciton:
	$form_buttons = array(
		array( 'type' => 'button', 'id'  => 'check_all_skins', 'value' => T_('Check All'), 'class' => 'btn btn-default' ),
		array( 'type' => 'submit', 'value' => T_('Install Checked'), 'class' => 'btn btn-primary' ),
	);
}
else
{
	$form_buttons = array();
}
$Form->end_form( $form_buttons );

$block_item_Widget->disp_template_replaced( 'block_end' );

?>
<script type="text/javascript">
jQuery( '#check_all_skins' ).click( function() {
	jQuery( 'input[name="skin_folders[]"]' ).attr( 'checked', 'checked' );
} );
</script>