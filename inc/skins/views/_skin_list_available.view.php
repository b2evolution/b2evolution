<?php
/**
 * This file implements the UI view for the Available skins.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
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
	switch( $skin_type )
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
foreach( $skin_folders as $skin_folder )
{
	if( !strlen( $skin_folder ) || $skin_folder[0] == '.' || $skin_folder == 'CVS' )
	{	// Skip system folders:
		continue;
	}
	if( $SkinCache->get_by_folder( $skin_folder, false ) )
	{ // Skip already installed:
		continue;
	}

	// Display skinshot:
	Skin::disp_skinshot_new( $skin_folder, array(
			'kind'        => $kind,
			'redirect_to' => $redirect_to,
		) );

	$skins_exist = true;
}

echo '</div>';
echo '<div class="clear"></div>';

// Display a button to quick upload the files by drag&drop method
display_dragdrop_upload_button( array(
		'button_text_full' => TS_('Drag & Drop skins to upload here <br /><span>or click to manually *_skin.zip files...</span>'),
		'button_text_man'  => TS_('Click to manually *_skin.zip files...'),
		'fileroot_ID'         => FileRoot::gen_ID( 'skins', 0 ),
		'path'                => '',
		'listElement'         => 'jQuery( ".skin_selector_block" ).get(0)',
		'extensions'          => array( 'zip' ),
		'template_filerow' => '<div class="skinshot">'
				.'<span class="qq-upload-file"></span>'
				.'<span class="qq-upload-spinner"></span>'
				.'<span class="qq-upload-size"></span>'
				.'<a class="qq-upload-cancel" href="#">'.TS_('Cancel').'</a>'
				.'<span class="qq-upload-failed-text">'.TS_('Failed').'</span>'
			.'</div>',
		'display_support_msg' => false,
		'additional_dropzone' => '.skin_selector_block',
		'upload_type'         => 'skin',
		'tab'                 => get_param( 'tab' ),
		'skin_type'           => get_param( 'skin_type' ),
	) );

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