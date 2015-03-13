<?php
/**
 * This file displays the links attached to an Object, which can be an Item, Comment, ... (called within the attachment_frame)
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $Blog;

/**
 * Needed by functions
 * @var LinkOwner
 */
global $LinkOwner;

global $AdminUI, $current_User;

// Override $debug in order to keep the display of the iframe neat
global $debug;
$debug = 0;

if( empty( $Blog ) )
{
	$Blog = & $LinkOwner->get_Blog();
}

// Name of the iframe we want some actions to come back to:
param( 'iframe_name', 'string', '', true );

$SQL = $LinkOwner->get_SQL();

$Results = new Results( $SQL->get(), 'link_', '', 1000 );

$Results->title = T_('Attachments');

/*
 * Sub Type column
 */
function display_subtype( $link_ID )
{
	global $LinkOwner, $current_File;

	$Link = & $LinkOwner->get_link_by_link_ID( $link_ID );
	// Instantiate a File object for this line
	$current_File = $Link->get_File();

	return $Link->get_preview_thumb();
}
$Results->cols[] = array(
						'th' => T_('Icon/Type'),
						'td_class' => 'shrinkwrap',
						'td' => '%display_subtype( #link_ID# )%',
					);


/*
 * LINK column
 */
function display_link()
{
	/**
	 * @var File
	 */
	global $current_File;

	if( empty( $current_File ) )
	{
		return '?';
	}

	$r = '';

	// File relative path & name:
	if( $current_File->is_dir() )
	{ // Directory
		$r .= $current_File->dget( '_name' );
	}
	else
	{ // File
		if( $view_link = $current_File->get_view_link() )
		{
			$r .= $view_link;
			// Use this hidden field to solve the conflicts on quick upload
			$r .= '<input type="hidden" value="'.$current_File->get_root_and_rel_path().'" />';
		}
		else
		{ // File extension unrecognized
			$r .= $current_File->dget( '_name' );
		}
	}

	$title = $current_File->dget('title');
	if( $title !== '' )
	{
		$r .= '<span class="filemeta"> - '.$title.'</span>';
	}

	return $r;
}
$Results->cols[] = array(
						'th' => T_('Destination'),
						'td' => '%display_link()%',
						'td_class' => 'fm_filename',
					);

$Results->cols[] = array(
						'th' => T_('Link ID'),
						'td' => '$link_ID$',
						'th_class' => 'shrinkwrap',
						'td_class' => 'shrinkwrap',
					);

if( $current_User->check_perm( 'files', 'view', false, $Blog->ID ) )
{
	$Results->cols[] = array(
							'th' => T_('Actions'),
							'td_class' => 'shrinkwrap',
							'td' => '%link_actions( #link_ID#, {ROW_IDX_TYPE}, "'.$LinkOwner->type.'" )%',
						);
}

$Results->cols[] = array(
						'th' => T_('Position'),
						'td_class' => 'shrinkwrap',
						'td' => '%display_link_position( {row} )%',
					);

// Add attr "id" to handle quick uploader
$compact_results_params = $AdminUI->get_template( 'compact_results' );
$compact_results_params['body_start'] = str_replace( '<tbody', '<tbody id="filelist_tbody"', $compact_results_params['body_start'] );
$compact_results_params['no_results_start'] = str_replace( '<tbody', '<tbody id="filelist_tbody"', $compact_results_params['no_results_start'] );

$Results->display( $compact_results_params );

// Print out JavaScript to change a link position
echo_link_position_js();

if( $Results->total_pages == 0 )
{ // If no results we should get a template of headers in order to add it on first quick upload
	ob_start();
	$Results->display_col_headers();
	$table_headers = ob_get_clean();
}
else
{ // Headers are already on the page
	$table_headers = '';
}

// Display a button to quick upload the files by drag&drop method
echo '<div id="fileuploader_form">';
display_dragdrop_upload_button( array(
		'fileroot_ID'      => FileRoot::gen_ID( 'collection', $Blog->ID ),
		'path'             => '/quick-uploads/'.( $LinkOwner->type == 'item' ? 'p' : 'c' ).$LinkOwner->link_Object->ID.'/',
		'list_style'       => 'table',
		'template_filerow' => '<table><tr>'
					.'<td class="firstcol shrinkwrap qq-upload-image"><span class="qq-upload-spinner">&nbsp;</span></td>'
					.'<td class="qq-upload-file fm_filename">&nbsp;</td>'
					.'<td class="qq-upload-link-id shrinkwrap">&nbsp;</td>'
					.'<td class="qq-upload-link-actions shrinkwrap">'
						.'<div class="qq-upload-status">'
							.TS_('Uploading...')
							.'<span class="qq-upload-spinner"></span>'
							.'<span class="qq-upload-size"></span>'
							.'<a class="qq-upload-cancel" href="#">'.TS_('Cancel').'</a>'
						.'</div>'
					.'</td>'
					.'<td class="qq-upload-link-position lastcol shrinkwrap"></td>'
				.'</tr></table>',
		'display_support_msg'    => false,
		'additional_dropzone'    => '#filelist_tbody',
		'filename_before'        => '',
		'LinkOwner'              => $LinkOwner,
		'display_status_success' => false,
		'status_conflict_place'  => 'before_button',
		'conflict_file_format'   => 'full_path_link',
		'resize_frame'           => true,
		'table_headers'          => $table_headers,
	) );
echo '</div>';
?>