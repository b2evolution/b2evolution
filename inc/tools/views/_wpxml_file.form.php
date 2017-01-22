<?php
/**
 * This file display the 1st step of WordPress XML importer
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package admin
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $admin_url, $media_path;

$Form = new Form( NULL, '', 'post', NULL, 'multipart/form-data' );

$Form->begin_form( 'fform', T_('WordPress XML Importer') );

$Form->add_crumb( 'wpxml' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'import' );

// Get available files to import from the folder /media/import/
$import_files = wpxml_get_import_files();

$Table = new Table( NULL, 'import' );

$Table->cols = array(
	array( 'th' => T_('Import'), 'td_class' => 'shrinkwrap' ),
	array( 'th' => T_('File') ),
	array( 'th' => T_('Type') ),
	array( 'th' => T_('Date'), 'td_class' => 'shrinkwrap' ),
);

$Table->title = T_('Potential files to be imported').get_manual_link('file-importer');
$Table->title .= ' - '.action_icon( T_('Refresh'), 'refresh', $admin_url.'?ctrl=wpimportxml', T_('Refresh'), 3, 4 );

$FileRootCache = & get_FileRootCache();
$FileRoot = & $FileRootCache->get_by_type_and_ID( 'import', '0', true );
$import_perm_view = $current_User->check_perm( 'files', 'view', false, $FileRoot );
if( $import_perm_view )
{ // Current user must has access to the import dir
	if( $current_User->check_perm( 'files', 'edit_allowed', false, $FileRoot ) )
	{ // User has full access
		$import_title = T_('Upload/Manage import files');
	}
	else if( $current_User->check_perm( 'files', 'add', false, $FileRoot ) )
	{ // User can only upload the files to import root
		$import_title = T_('Upload import files');
	}
	else
	{ // Only view
		$import_title = T_('View import files');
	}
	$Table->title .= ' - '
		.action_icon( $import_title, 'folder', $admin_url.'?ctrl=files&amp;root=import_0', $import_title, 3, 4,
			array( 'onclick' => 'return import_files_window()' )
		).' <span class="note">(popup)</span>';
}
$Table->display_init();
// TABLE START:
$Table->display_list_start();
// TITLE:
$Table->display_head();

if( empty( $import_files ) )
{ // No files to import

	// BODY START:
	$Table->display_body_start();

	$Table->display_line_start();
	$Table->display_col_start();
	echo '<p class="center">'.T_('We have not found any suitable file to perform the blog import. Please read the details at the manual page.').get_manual_link('file-importer').'</p>';
	$Table->display_col_end();
	$Table->display_line_end();
}
else
{ // Display the files to import in table

	// COLUMN HEADERS:
	$Table->display_col_headers();
	// BODY START:
	$Table->display_body_start();

	foreach( $import_files as $import_file )
	{
		$Table->display_line_start();

		// Checkbox to import
		$Table->display_col_start();
		echo '<input type="radio" name="wp_file" value="'.$import_file['path'].'"'.( get_param( 'wp_file' ) == $import_file['path'] ? ' checked="checked"' : '' ).' />';
		$Table->display_col_end();

		// File
		$Table->display_col_start();
		echo basename( $import_file['path'] );
		$Table->display_col_end();

		// Type
		$Table->display_col_start();
		echo $import_file['type'];
		$Table->display_col_end();

		// File date
		$Table->display_col_start();
		echo date( locale_datefmt().' '.locale_timefmt(), filemtime( $import_file['path'] ) );
		$Table->display_col_end();

		$Table->display_line_end();

		evo_flush();
	}
}

// BODY END / TABLE END:
$Table->display_body_end();
$Table->display_list_end();


if( ! empty( $import_files ) )
{
	$Form->begin_fieldset( T_('Select a blog for import') );

	$BlogCache = & get_BlogCache();
	$BlogCache->load_all( 'shortname,name', 'ASC' );
	$BlogCache->none_option_text = '&nbsp;';

	$Form->select_input_object( 'wp_blog_ID', param( 'wp_blog_ID', 'integer', 0 ), $BlogCache, T_('Blog for import'), array(
			'note' => T_('This blog will be used for import.').' <a href="'.$admin_url.'?ctrl=collections&action=new">'.T_('Create new blog').' &raquo;</a>',
			'allow_none' => true,
			'required' => true,
			'loop_object_method' => 'get_extended_name' ) );

	$import_type = param( 'import_type', 'string', 'replace' );
	$Form->radio_input( 'import_type', $import_type, array(
				array(
					'value' => 'replace',
					'label' => T_('Replace existing contents'),
					'note'  => T_('WARNING: this option will permanently remove existing Posts, comments, categories and tags from the selected blog.'),
					'id'    => 'import_type_replace' ),
			), '', array( 'lines' => true ) );

	echo '<div id="checkbox_delete_files"'.( $import_type == 'replace' ? '' : ' style="display:none"' ).'>';
	$Form->checkbox_input( 'delete_files', param( 'delete_files', 'integer', 0 ), '', array(
		'input_suffix' => '<label for="delete_files">'.T_(' Also delete files that will no longer be referenced in the target blog after replacing its contents').'</label>',
		'input_prefix' => '<span style="margin-left:25px"></span>') );
	echo '</div>';

	$Form->radio_input( 'import_type', $import_type, array(
				array(
					'value' => 'append',
					'label' => T_('Append to existing contents'),
					'id'    => 'import_type_append' ),
			), '', array( 'lines' => true ) );

	$Form->end_fieldset();

	$Form->buttons( array( array( 'submit', 'submit', T_('Continue!'), 'SaveButton' ) ) );
}

$Form->end_form();

if( $import_perm_view )
{ // Current user must has access to the import dir

	// Initialize JavaScript to build and open window:
	echo_modalwindow_js();
}
?>
<script type="text/javascript">
jQuery( '.table_scroll td' ).click( function()
{
	jQuery( this ).parent().find( 'input[type=radio]' ).attr( 'checked', 'checked' );
} );
<?php
if( $import_perm_view )
{ // Current user must has access to the import dir
?>

function import_files_window()
{
	openModalWindow( '<span class="loader_img absolute_center" title="<?php echo T_('Loading...'); ?>"></span>',
		'90%', '80%', true, '<?php echo TS_('Add/Link files'); ?>', '', true );
	jQuery.ajax(
	{
		type: 'POST',
		url: '<?php echo get_htsrv_url(); ?>async.php',
		data:
		{
			'action': 'import_files',
			'crumb_import': '<?php echo get_crumb( 'import' ); ?>',
		},
		success: function( result )
		{
			openModalWindow( result, '90%', '80%', true, '<?php echo TS_('Upload/Manage import files'); ?>', '' );
		}
	} );
	return false;
}
<?php
}
?>
jQuery( 'input[name=import_type]' ).click( function()
{ // Show/Hide checkbox to delete files
	if( jQuery( this ).val() == 'replace' )
	{
		jQuery( '#checkbox_delete_files' ).show();
	}
	else
	{
		jQuery( '#checkbox_delete_files' ).hide();
	}
} );
</script>