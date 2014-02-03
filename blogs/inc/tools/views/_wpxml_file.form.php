<?php
/**
 * This file display the 1st step of WordPress XML importer
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
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
	array( 'th' => T_('Uploaded'), 'td_class' => 'shrinkwrap' ),
);

$Table->title = T_('Potential files to be imported').get_manual_link('file-importer');
$Table->title .= ' - '.action_icon( T_('Refresh'), 'refresh', $admin_url.'?ctrl=wpimportxml', T_('Refresh'), 3, 4 );
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

	$Form->radio_input( 'import_type', param( 'import_type', 'string', 'replace' ), array(
				array(
					'value' => 'replace',
					'label' => T_('Replace existing contents'),
					'note'  => T_('WARNING: this option will permanently remove existing Posts, comments, categories and tags from the selected blog.') ),
				array(
					'value' => 'append',
					'label' => T_('Append to existing contents') ),
			), '', array( 'lines' => true ) );

	$Form->end_fieldset();

	$Form->buttons( array( array( 'submit', 'submit', T_('Continue !'), 'SaveButton' ),
											 array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
}

$Form->end_form();

?>
<script type="text/javascript">
jQuery( '.table_scroll td' ).click( function()
{
	jQuery( this ).parent().find( 'input[type=radio]' ).attr( 'checked', 'checked' );
} );
</script>