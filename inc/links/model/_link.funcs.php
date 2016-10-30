<?php
/**
 * This file implements Link handling functions.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'links/model/_linkowner.class.php', 'LinkOwner' );
load_class( 'links/model/_linkcomment.class.php', 'LinkComment' );
load_class( 'links/model/_linkitem.class.php', 'LinkItem' );
load_class( 'links/model/_linkuser.class.php', 'LinkUser' );

/**
 * Get a link owner object from link_type and object ID
 *
 * @param string link type ( item, comment, ... )
 * @param integer the corresponding object ID
 */
function & get_link_owner( $link_type, $object_ID )
{
	switch( $link_type )
	{
		case 'item':
			// create LinkItem object
			$ItemCache = & get_ItemCache();
			$Item = $ItemCache->get_by_ID( $object_ID, false );
			$LinkOwner = new LinkItem( $Item );
			break;

		case 'comment':
			// create LinkComment object
			$CommentCache = & get_CommentCache();
			$Comment = $CommentCache->get_by_ID( $object_ID, false );
			$LinkOwner = new LinkComment( $Comment );
			break;

		case 'user':
			// create LinkUser object
			$UserCache = & get_UserCache();
			$User = $UserCache->get_by_ID( $object_ID, false );
			$LinkOwner = new LinkUser( $User );
			break;

		default:
			$LinkOwner = NULL;
	}
	return $LinkOwner;
}


/**
 * Compose screen: display link files iframe
 *
 * @param object Form
 * @param object LinkOwner object
 * @param string iframe name
 * @param boolean true if creating new owner object, false otherwise
 * @param boolean true to allow folding for this fieldset, false otherwise
 */
function attachment_iframe( & $Form, & $LinkOwner, $iframe_name = NULL, $creating = false, $fold = false )
{
	global $admin_url;
	global $current_User, $action;

	if( $LinkOwner->type == 'item' && ! $LinkOwner->Item->get_type_setting( 'allow_attachments' ) )
	{ // Attachments are not allowed for current post type
		return;
	}

	if( ! isset( $GLOBALS[ 'files_Module' ] ) )
	{
		return;
	}

	// Set title for modal window:
	$window_title = TS_('Attach files');
	if( $LinkOwner->type == 'item' )
	{ // Item
		$window_title = format_to_js( sprintf( T_('Attach files to "%s"'), $LinkOwner->Item->get( 'title' ) ) );
	}
	elseif( $LinkOwner->type == 'comment' )
	{ // Comment
		$window_title = format_to_js( sprintf( T_('Attach files to comment #%s'), $LinkOwner->Comment->ID ) );
	}

	$fieldset_title = T_( 'Images &amp; Attachments' );

	if( $creating )
	{ // Creating new Item
		$fieldset_title .= ' '.get_manual_link( 'images-attachments-panel' ).' - <a id="title_file_add" href="#" class="action_icon">'.get_icon( 'folder' ).' '.T_('Attach existing files').'</a>';

		$Form->begin_fieldset( $fieldset_title, array( 'id' => 'itemform_createlinks', 'fold' => $fold ) );

		$Form->submit( array( 'actionArray[create_edit]', /* TRANS: This is the value of an input submit button */ T_('Save post to start uploading files'), 'SaveEditButton' ) );

		if( get_param( 'p' ) > 0 )
		{	// Display a button to duplicate the attachments to new item:
			$Form->submit( array( 'actionArray[create_link]', /* TRANS: This is the value of an input submit button */ T_('Save & Link files from original'), 'SaveEditButton' ) );
		}

		$Form->end_fieldset();

		return;
	}

	// Editing link owner
	$Blog = & $LinkOwner->get_Blog();

	if( $iframe_name == NULL )
	{
		$iframe_name = 'attach_'.generate_random_key( 16 );
	}

	$fieldset_title .= ' '.get_manual_link( 'images-attachments-panel' );

	if( $current_User->check_perm( 'files', 'view', false, $Blog->ID )
		&& $LinkOwner->check_perm( 'edit', false ) )
	{ // Check that we have permission to edit owner:
		$attach_files_url = $admin_url.'?ctrl=files&amp;fm_mode=link_object&amp;link_type=item&amp;link_object_ID='.$LinkOwner->get_ID();
		if( $linkowner_FileList = $LinkOwner->get_attachment_FileList( 1 ) )
		{	// Get first file of the Link Owner:
			$linkowner_File = & $linkowner_FileList->get_next();
			if( ! empty( $linkowner_File ) && $current_User->check_perm( 'files', 'view', false, $linkowner_File->get_FileRoot() ) )
			{	// Obtain and use file root of first file:
				$linkowner_FileRoot = & $linkowner_File->get_FileRoot();
				$attach_files_url .= '&amp;root='.$linkowner_FileRoot->ID;
				$attach_files_url .= '&amp;path='.dirname( $linkowner_File->get_rdfs_rel_path() ).'/';
			}
		}
		$fieldset_title .= ' - '
			.action_icon( T_('Attach existing files'), 'folder', $attach_files_url,
				T_('Attach existing files'), 3, 4,
				array( 'onclick' => 'return link_attachment_window( \''.$iframe_name.'\', \''.$LinkOwner->type.'\', \''.$LinkOwner->get_ID().'\' )' ) )
			.action_icon( T_('Attach existing files'), 'permalink', $attach_files_url,
				T_('Attach existing files'), 1, 0,
				array( 'target' => '_blank' ) );
	}

	$fieldset_title .= '<span class="floatright">&nbsp;'
			.action_icon( T_('Refresh'), 'refresh', $admin_url
					.'?ctrl=links&amp;action=edit_links&amp;link_type='.$LinkOwner->type.'&amp;mode=iframe&amp;iframe_name='.$iframe_name.'&amp;link_object_ID='.$LinkOwner->get_ID(),
					T_('Refresh'), 3, 4, array( 'target' => $iframe_name, 'class' => 'action_icon btn btn-default btn-sm' ) )

					.action_icon( T_('Sort'), 'ascending', $admin_url
					.'?ctrl=links&amp;action=sort_links&amp;link_type='.$LinkOwner->type.'&amp;mode=iframe&amp;iframe_name='.$iframe_name.'&amp;link_object_ID='.$LinkOwner->get_ID().'&amp;'.url_crumb( 'link' ),
					T_('Sort'), 3, 4, array( 'target' => $iframe_name, 'class' => 'action_icon btn btn-default btn-sm' ) )

			.'</span>';

	// Get a count of links in order to deny folding when there is at least one link
	$links_count = count( $LinkOwner->get_Links() );

	$Form->begin_fieldset( $fieldset_title, array(
			'id' => $LinkOwner->type == 'comment' ? 'cmntform_links' : 'itemform_links',
			'fold' => $fold,
			'deny_fold' => ( $links_count > 0 )
		) );

	echo '<div id="attachmentframe_wrapper">'
				.'<iframe src="'.$admin_url.'?ctrl=links&amp;link_type='.$LinkOwner->type
					.'&amp;action=edit_links&amp;mode=iframe&amp;iframe_name='.$iframe_name
					.'&amp;link_object_ID='.$LinkOwner->get_ID().'" name="'.$iframe_name.'"'
					.' width="100%" marginwidth="0" height="100%" marginheight="0" align="top" scrolling="auto" frameborder="0" id="attachmentframe"></iframe>'
			.'</div>';

	$Form->end_fieldset();

	// Initialize JavaScript to build and open window:
	echo_modalwindow_js();
?>
<script type="text/javascript">
function link_attachment_window( iframe_name, link_owner_type, link_owner_ID, root, path, fm_highlight )
{
	openModalWindow( '<span class="loader_img loader_user_report absolute_center" title="<?php echo T_('Loading...'); ?>"></span>',
		'90%', '80%', true, '<?php echo $window_title; ?>', '', true );
	jQuery.ajax(
	{
		type: 'POST',
		url: '<?php echo get_htsrv_url(); ?>async.php',
		data:
		{
			'action': 'link_attachment',
			'iframe_name': iframe_name,
			'link_owner_type': link_owner_type,
			'link_owner_ID': link_owner_ID,
			'crumb_link': '<?php echo get_crumb( 'link' ); ?>',
			'root': typeof( root ) == 'undefined' ? '' : root,
			'path': typeof( path ) == 'undefined' ? '' : path,
			'fm_highlight': typeof( fm_highlight ) == 'undefined' ? '' : fm_highlight
		},
		success: function(result)
		{
			openModalWindow( result, '90%', '80%', true, '<?php echo $window_title; ?>', '' );
		}
	} );
	return false;
}

jQuery( document ).ready( function()
{
	function update_attachment_frame_height()
	{
		var body_height = jQuery( '#attachmentframe' ).contents().find( 'body' ).height();
		if( body_height == 0 )
		{ // Some browsers cannot get iframe body height correctly, Use this default min value:
			body_height = 91;
		}

		if( body_height > jQuery( '#attachmentframe_wrapper' ).height() )
		{ // Expand the frame height if it is more than wrapper height (but max height is 320px):
			jQuery( '#attachmentframe_wrapper' ).css( 'height', body_height < 320 ? body_height : 320 );
		}
		// Set max-height on each iframe reload in order to avoid a space after upload button:
		jQuery( '#attachmentframe_wrapper' ).css( 'max-height', body_height );
	}

	var attachmentframe_is_loaded = false;
	jQuery( '#attachmentframe' ).bind( 'load', function()
	{ // Set proper height on frame loading:
		if( ! attachmentframe_is_loaded )
		{ // Only on first loading
			update_attachment_frame_height();
			attachmentframe_is_loaded = true;
		}
	} );

	jQuery( '#icon_folding_itemform_links, #title_folding_itemform_links' ).click( function()
	{ // Use this hack to fix frame height on show attachments fieldset if it was hidden before:
		update_attachment_frame_height();
	} );

	jQuery( '#attachmentframe_wrapper' ).resizable(
	{ // Make the frame wrapper resizable
		minHeight: 80,
		handles: 's',
		start: function( e, ui )
		{ // Create a temp div to disable the mouse over events inside the frame
			ui.element.append( '<div id="attachmentframe_disabler"></div>' );
		},
		stop: function( e, ui )
		{ // Remove the temp div element
			ui.element.find( '#attachmentframe_disabler' ).remove();
		},
		resize: function( e, ui )
		{ // Limit max height
			jQuery( '#attachmentframe_wrapper' ).resizable( 'option', 'maxHeight', jQuery( '#attachmentframe' ).contents().find( 'body' ).height() );
		}
	} );
	jQuery( document ).on( 'click', '#attachmentframe_wrapper .ui-resizable-handle', function()
	{ // Increase height on click
		jQuery( '#attachmentframe_wrapper' ).css( 'height', jQuery( '#attachmentframe_wrapper' ).height() + 80 );
	} );
} );
</script>
<?php
}


/**
 * Display a table with the attached files
 *
 * @param object LinkOwner
 * @param array display params
 */
function display_attachments( & $LinkOwner, $params = array() )
{
	global $current_User, $redirect_to;

	$params = array_merge( array(
			'block_start' => '<div class="attachment_list">',
			'block_end'   => '</div>',
			'table_start' => '<table class="grouped" cellspacing="0" cellpadding="0">',
			'table_end'   => '</table>',
		), $params );

	$links = $LinkOwner->get_Links();

	if( count( $links ) < 1 )
	{ // there are no attachments
		return;
	}

	$redirect_to = urlencode( empty( $redirect_to ) ? regenerate_url( '', '', '', '&' ) : $redirect_to );

	echo $params['block_start'];
	echo $params['table_start'];
	echo '<thead>';
	echo '<th class="firstcol shrinkwrap"><span>'.T_('Icon/Type').'</span></th>';
	echo '<th class="nowrap"><span>'.T_('Path').'</span></th>';
	echo '<th class="lastcol shrinkwrap"><span>'.T_('Actions').'</span></th>';
	echo '</thead><tbody>';
	$row_style = '';
	foreach( $links as $Link )
	{ // display each link attachment in a row
		if( ! ( $link_File = & $Link->get_File() ) )
		{ // No File object
			global $Debuglog;
			$Debuglog->add( sprintf( 'Link ID#%d does not have a file object!', $Link->ID ), array( 'error', 'files' ) );
			continue;
		}
		$row_style = ( $row_style == 'even' ) ? 'odd' : 'even';
		echo '<tr class="'.$row_style.'"><td class="firstcol">';
		echo $link_File->get_preview_thumb( 'fulltype' );
		echo '</td><td class="nowrap left">';
		echo $link_File->get_view_link();
		echo '</td><td class="lastcol shrinkwrap">';
		if( $current_User->check_perm( 'files', 'edit' ) )
		{ // display delete link action
			$delete_url = get_htsrv_url().'action.php?mname=collections&amp;action=unlink&amp;link_ID='.$Link->ID.'&amp;crumb_collections_unlink='.get_crumb( 'collections_unlink' ).'&amp;redirect_to='.$redirect_to;
			echo action_icon( T_('Remove'), 'remove', $delete_url );
		}
		echo '</td></tr>';
	}
	echo '</tbody>';
	echo $params['table_end'];
	echo $params['block_end'];
}


/*
 * Get a link destination
 *
 * @return string
 */
function link_destination()
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
		$r .= $current_File->get_view_link();
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


/**
 * Display link actions
 *
 * @param integer Link ID
 * @param string Index type of current row:
 *               'single' - when only one row in list
 *               'first'  - Current row is first in whole list
 *               'last'   - Current row is last in whole list
 *               'middle' - Current row is not first and not last
 * @param string Link type
 * @return string
 */
function link_actions( $link_ID, $row_idx_type = '', $link_type = 'item' )
{
	/**
	 * @var File
	 */
	global $current_File;
	global $LinkOwner, $current_User;
	global $iframe_name, $admin_url, $blog;

	$r = '';

	$blog_param = empty( $blog ) ? '' : '&amp;blog='.$blog;

	// Change order.
	if( $LinkOwner->check_perm( 'edit' ) )
	{ // Check that we have permission to edit LinkOwner object:

		// Allow to move up all rows except of first, This action icon is hidden by CSS for first row
		$r .= action_icon( T_('Move upwards'), 'move_up',
						$admin_url.'?ctrl=links&amp;link_ID='.$link_ID.'&amp;action=link_move_up'.$blog_param.'&amp;'.url_crumb( 'link' ), NULL, NULL, NULL,
						array( 'class' => 'action_icon_link_move_up' ) );

		// Allow to move down all rows except of last, This action icon is hidden by CSS for last row
		$r .= ' '.action_icon( T_('Move down'), 'move_down',
						$admin_url.'?ctrl=links&amp;link_ID='.$link_ID.'&amp;action=link_move_down'.$blog_param.'&amp;'.url_crumb( 'link' ), NULL, NULL, NULL,
						array( 'class' => 'action_icon_link_move_down' ) );
	}

	if( $current_File && $current_User->check_perm( 'files', 'view', false, $current_File->get_FileRoot() ) )
	{ // Locate file
		$title = $current_File->dir_or_file( T_('Locate this directory!'), T_('Locate this file!') );
		$url = $current_File->get_linkedit_url( $LinkOwner->type, $LinkOwner->get_ID() );
		$rdfp_path = ( $current_File->is_dir() ? $current_File->get_rdfp_rel_path() : dirname( $current_File->get_rdfp_rel_path() ) ).'/';

		// A link to open file manager in modal window:
		$r .= ' <a href="'.$url.'" onclick="return window.parent.link_attachment_window( \''.$iframe_name.'\', \''.$LinkOwner->type.'\', \''.$LinkOwner->get_ID().'\', \''.$current_File->get_FileRoot()->ID.'\', \''.$rdfp_path.'\', \''.rawurlencode( $current_File->get_name() ).'\' )"'
					.' target="_parent" title="'.$title.'">'
					.get_icon( 'locate', 'imgtag', array( 'title' => $title ) ).'</a> ';

		// A link to open file manager in new window:
		$r .= '<a href="'.$url.'" target="_blank" title="'.$title.'">'
					.get_icon( 'permalink', 'imgtag', array( 'title' => $title ) ).'</a> ';
	}

	// Unlink/Delete icons:
	if( $LinkOwner->check_perm( 'edit' ) )
	{	// If current user has a permission to edit LinkOwner object
		// Unlink icon:
		$r .= action_icon( T_('Delete this link!'), 'unlink',
					$admin_url.'?ctrl=links&amp;link_ID='.$link_ID.'&amp;action=unlink'.$blog_param.'&amp;'.url_crumb( 'link' ), NULL, NULL, NULL,
					array( 'onclick' => 'item_unlink('.$link_ID.')' ) );
		// Delete icon:
		$LinkCache = & get_LinkCache();
		$Link = & $LinkCache->get_by_ID( $link_ID, false, false );
		if( ! $current_File->is_dir() && $Link && $Link->can_be_file_deleted() )
		{	// If current user has a permission to delete a file(not folder) completely
			$File = & $Link->get_File();
			$r .= action_icon( T_('Delete this file!'), 'delete',
						$admin_url.'?ctrl=links&amp;link_ID='.$link_ID.'&amp;action=delete'.$blog_param.'&amp;'.url_crumb( 'link' ), NULL, NULL, NULL,
						array( 'onclick' => 'return confirm( \''
								.sprintf( TS_('Are you sure want to DELETE the file &laquo;%s&raquo;?\nThis CANNOT be reversed!'), utf8_strip_tags( link_destination() ) )
								.'\' ) && item_unlink('.$link_ID.')' ) );
		}
		else
		{	// If current user can only unlink the attachment (probably it is linked to several objects)
			$r .= get_icon( 'delete', 'imgtag', array( 'class' => 'action_icon empty_placeholder' ) );
		}
	}

	return $r;
}


/**
 * Display link position edit action
 *
 * @param $row
 */
function display_link_position( & $row )
{
	global $LinkOwner;
	global $current_File;
	// TODO: fp>dh: can you please implement cumbs in here? I don't clearly understand your code.
	// TODO: dh> only handle images

	$id = 'display_position_'.$row->link_ID;

	// NOTE: dh> using method=get so that we can use regenerate_url (for non-JS).
	$r = '<form action="" method="post">
		<select id="'.$id.'" name="link_position">'
		.Form::get_select_options_string( $LinkOwner->get_positions( $row->file_ID ), $row->link_position, true).'</select>';

	if( $current_File )
	{ // Display icon to insert image|video into post inline
		$type = $current_File->get_file_type();

		// valid file types: audio, video, image, other. See @link File::set_file_type()
		switch( $type )
		{
			case 'audio':
			  // no inline audio, show file download link using [file:] short tag
				$type = 'file';
				break;

			case 'video':
				break;

			case 'image':
				break;

			case 'other':
				$type = 'file';
				break;
		}

		if( $type == 'image' )
		{
			$r .= ' '.get_icon( 'add', 'imgtag', array(
						'title'   => sprintf( T_('Insert %s tag into the post'), '['.$type.':]' ),
						'onclick' => 'insert_inline_link( \'image\', '.$row->link_ID.', \'\' )',
						'style'   => 'cursor:default;'
					) );
		}

		if( $type == 'image' )
		{
			$r .= ' '.get_icon( 'add__yellow', 'imgtag', array(
						'title'   => T_('Insert [thumbnail:] tag into the post'),
						'onclick' => 'insert_inline_link( \'thumbnail\', '.$row->link_ID.', \'medium:left\' )',
						'style'   => 'cursor:default;'
					) );
		}

		if( $type == 'audio' || $type == 'video'  || $type == 'file' )
		{
			$r .= ' '.get_icon( 'add__blue', 'imgtag', array(
						'title'   => sprintf( T_('Insert %s tag into the post'), '['.$type.':]' ),
						'onclick' => 'insert_inline_link( \''.$type.'\', '.$row->link_ID.', \'\' )',
						'style'   => 'cursor:default;'
					) );
		}

	}

	$r .= '<noscript>';
	// Add hidden fields for non-JS
	$url = regenerate_url( 'p,itm_ID,action', 'link_ID='.$row->link_ID.'&action=set_link_position&'.url_crumb('link'), '', '&' );
	$params = explode('&', substr($url, strpos($url, '?')+1));

	foreach($params as $param)
	{
		list($k, $v) = explode('=', $param);
		$r .= '<input type="hidden" name="'.htmlspecialchars($k).'" value="'.htmlspecialchars($v).'" />';
	}
	$r .= '<input class="SaveButton" type="submit" value="&raquo;" />';
	$r .= '</noscript>';
	$r .= '</form>';

	return str_replace( array( "\r", "\n" ), '', $r );
}


/**
 * Print out JavaScript to change a link position
 */
function echo_link_position_js()
{
	global $Session;
?>
<script type="text/javascript">
var displayInlineReminder = <?php echo $Session->get( 'display_inline_reminder', 'true' );?>;
var deferInlineReminder = false;

jQuery( document ).on( 'change', 'select[id^=display_position_]', {
		url:   '<?php echo get_htsrv_url(); ?>',
		crumb: '<?php echo get_crumb( 'link' ); ?>',
}, function( event )
{
	if( this.value == 'inline' && displayInlineReminder && !deferInlineReminder )
	{ // Display inline position reminder
		alert( '<?php echo T_('You can use the (+) icons to change the position to inline and automatically insert a short tag at the current cursor position.');?>' );
		displayInlineReminder = false;
	}
	evo_display_position_onchange( this, event.data.url, event.data.crumb );
} );
</script>
<?php
}


/**
 * Print out JavaScript to make the links table sortable
 */
function echo_link_sortable_js()
{
?>
<script type="text/javascript">
jQuery( document ).ready( function()
{
	jQuery( '#link_ajax_content table' ).sortable(
	{
		containerSelector: 'table',
		itemPath: '> tbody',
		itemSelector: 'tr',
		placeholder: '<tr class="placeholder"/>',
		onDrop: function( $item, container, _super )
		{
			jQuery( '#link_ajax_content table tr' ).removeClass( 'odd even' );
			jQuery( '#link_ajax_content table tr:odd' ).addClass( 'even' );
			jQuery( '#link_ajax_content table tr:even' ).addClass( 'odd' );

			var link_IDs = '';
			jQuery( '#link_ajax_content table tr' ).each( function()
			{
				var link_ID_cell = jQuery( this ).find( '.link_id_cell' );
				if( link_ID_cell.length > 0 )
				{
					link_IDs += link_ID_cell.html() + ',';
				}
			} );
			link_IDs = link_IDs.slice( 0, -1 );

			jQuery.ajax(
			{
				url: '<?php echo get_htsrv_url(); ?>async.php',
				type: 'POST',
				data:
				{
					'action': 'update_links_order',
					'links': link_IDs,
					'crumb_link': '<?php echo get_crumb( 'link' ); ?>',
				}
			} );
		}
	} );
} );
</script>
<?php
}


/**
 * Get all links where file is used
 *
 * @param integer File ID
 * @param array Params
 * @return string The links to that posts, comments and users where the file is used
 */
function get_file_links( $file_ID, $params = array() )
{
	global $DB, $current_User, $baseurl, $admin_url;

	$params = array_merge( array(
			'separator'       => '<br />',
			'post_prefix'     => T_('Post').' - ',
			'comment_prefix'  => T_('Comment on').' - ',
			'user_prefix'     => T_('Profile picture').' - ',
			'current_link_ID' => 0,
			'current_before'  => '<b>',
			'current_after'   => '</b>',
		), $params );

	// Create result array
	$attached_to = array();

	// Get all links with posts and comments
	$links_SQL = new SQL();
	$links_SQL->SELECT( 'link_ID, link_itm_ID, link_cmt_ID, link_usr_ID' );
	$links_SQL->FROM( 'T_links' );
	$links_SQL->WHERE( 'link_file_ID = '.$DB->quote( $file_ID ) );
	$links = $DB->get_results( $links_SQL->get() );

	if( !empty( $links ) )
	{ // File is linked with some posts or comments
		$ItemCache = & get_ItemCache();
		$CommentCache = & get_CommentCache();
		$UserCache = & get_UserCache();
		$LinkCache = & get_LinkCache();
		foreach( $links as $link )
		{
			$link_object_ID = 0;
			$r = '';
			if( $params['current_link_ID'] == $link->link_ID )
			{
				$r .= $params['current_before'];
			}
			if( !empty( $link->link_itm_ID ) )
			{ // File is linked to a post
				if( $Item = & $ItemCache->get_by_ID( $link->link_itm_ID, false ) )
				{
					$Blog = $Item->get_Blog();
					if( $current_User->check_perm( 'item_post!CURSTATUS', 'view', false, $Item ) )
					{ // Current user can edit the linked post
						$r .= $params['post_prefix'].'<a href="'.url_add_param( $admin_url, 'ctrl=items&amp;blog='.$Blog->ID.'&amp;p='.$link->link_itm_ID ).'">'.$Item->get( 'title' ).'</a>';
					}
					else
					{ // No access to edit the linked post
						$r .= $params['post_prefix'].$Item->get( 'title' );
					}
					$link_object_ID = $link->link_itm_ID;
				}
			}
			elseif( !empty( $link->link_cmt_ID ) )
			{ // File is linked to a comment
				if( $Comment = & $CommentCache->get_by_ID( $link->link_cmt_ID, false ) )
				{
					$Item = $Comment->get_Item();
					if( $current_User->check_perm( 'comment!CURSTATUS', 'moderate', false, $Comment ) )
					{ // Current user can edit the linked Comment
						$r .= $params['comment_prefix'].'<a href="'.url_add_param( $admin_url, 'ctrl=comments&amp;action=edit&amp;comment_ID='.$link->link_cmt_ID ).'">'.$Item->get( 'title' ).'</a>';
					}
					else
					{ // No access to edit the linked Comment
						$r .= $params['comment_prefix'].$Item->get( 'title' );
					}
					$link_object_ID = $link->link_cmt_ID;
				}
			}
			elseif( !empty( $link->link_usr_ID ) )
			{ // File is linked to user
				if( $User = & $UserCache->get_by_ID( $link->link_usr_ID, false ) )
				{
					if( $current_User->ID != $User->ID && !$current_User->check_perm( 'users', 'view' ) )
					{ // No permission to view other users in admin form
						$r .= $params['user_prefix'].'<a href="'.url_add_param( $baseurl, 'disp=user&amp;user_ID='.$User->ID ).'">'.$User->login.'</a>';
					}
					else
					{ // Build a link to display a user in admin form
						$r .= $params['user_prefix'].'<a href="?ctrl=user&amp;user_tab=profile&amp;user_ID='.$User->ID.'">'.$User->login.'</a>';
					}
					$link_object_ID = $link->link_usr_ID;
				}
			}

			if( ! empty( $link_object_ID ) )
			{ // Action icon to unlink file from object
				if( ( $edited_Link = & $LinkCache->get_by_ID( $link->link_ID, false, false ) ) !== false &&
				    ( $LinkOwner = & $edited_Link->get_LinkOwner() ) !== false && $LinkOwner->check_perm( 'edit', false ) )
				{ // Allow to unlink only if current user has an permission
					$r .= ' '.action_icon( T_('Delete this link!'), 'unlink',
						$admin_url.'?ctrl=links&amp;link_ID='.$link->link_ID.'&amp;link_type=item&amp;link_object_ID='.$link->link_usr_ID.'&amp;action=unlink&amp;redirect_to='.rawurlencode( regenerate_url( 'blog', '', '', '&' ) ).'&amp;'.url_crumb( 'link' ),
						NULL, NULL, NULL,
						array( 'onclick' => 'return confirm(\''.TS_('Are you sure want to unlink this file?').'\');' ) );
				}
			}

			if( $params['current_link_ID'] == $link->link_ID )
			{
				$r .= $params['current_after'];
			}
			if( !empty( $r ) )
			{
				$attached_to[] = $r;
			}
		}
	}

	return implode( $params['separator'], $attached_to );
}


/**
 * Save a vote for the link of file by user
 *
 * @param string Link ID
 * @param integer User ID
 * @param string Action of the voting ( 'like', 'noopinion', 'dontlike', 'inappropriate', 'spam' )
 * @param integer 1 = checked, 0 = unchecked (for checkboxes: 'Inappropriate' & 'Spam' )
 */
function link_vote( $link_ID, $user_ID, $vote_action, $checked = 1 )
{
	global $DB;

	// Set modified field name and value
	switch( $vote_action )
	{
		case 'like':
			$field_name = 'lvot_like';
			$field_value = '1';
			break;

		case 'noopinion':
			$field_name = 'lvot_like';
			$field_value = '0';
			break;

		case 'dontlike':
			$field_name = 'lvot_like';
			$field_value = '-1';
			break;

		case 'inappropriate':
			$field_name = 'lvot_inappropriate';
			$field_value = $checked;
			break;

		case 'spam':
			$field_name = 'lvot_spam';
			$field_value = $checked;
			break;

		default:
			// invalid vote action
			return;
	}

	$DB->begin();

	$SQL = new SQL();
	$SQL->SELECT( 'lvot_link_ID' );
	$SQL->FROM( 'T_links__vote' );
	$SQL->WHERE( 'lvot_link_ID = '.$DB->quote( $link_ID ) );
	$SQL->WHERE_and( 'lvot_user_ID = '.$DB->quote( $user_ID ) );
	$vote = $DB->get_row( $SQL->get() );

	// Save a voting results in DB
	if( empty( $vote ) )
	{ // User replace into to avoid duplicate key conflict in case when user clicks two times fast one after the other
		$result = $DB->query( 'REPLACE INTO T_links__vote ( lvot_link_ID, lvot_user_ID, '.$field_name.' )
						VALUES ( '.$DB->quote( $link_ID ).', '.$DB->quote( $user_ID ).', '.$DB->quote( $field_value ).' )' );
	}
	else
	{ // Update existing record, because user already has a vote for this file
		$result = $DB->query( 'UPDATE T_links__vote
					SET '.$field_name.' = '.$DB->quote( $field_value ).'
					WHERE lvot_link_ID = '.$DB->quote( $link_ID ).'
						AND lvot_user_ID = '.$DB->quote( $user_ID ) );
	}

	if( $result )
	{
		$DB->commit();
	}
	else
	{
		$DB->rollback();
	}
}


function sort_links_by_filename( $a, $b )
{
	$a_File = $a->get_File();
	$b_File = $b->get_File();

	$a_type = $a_File->dir_or_file();
	$b_type = $b_File->dir_or_file();

	if( $a_type === $b_type )
	{
		$r = strnatcmp( $a_File->_name, $b_File->_name );
	}
	elseif( $a_type == 'directory' )
	{
		$r = -1;
	}
	else
	{
		$r = 1;
	}

	return $r;
}
?>