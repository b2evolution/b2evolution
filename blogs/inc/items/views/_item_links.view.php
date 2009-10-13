<?php
/**
 * This file displays the links attached to an Item (called within the attachment_frame)
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $Blog;

/**
 * Needed by functions
 * @var Item
 */
global $edited_Item;

global $AdminUI;

// Override $debug in order to keep the display of the iframe neat
global $debug;
$debug = 0;

// Name of the iframe we want some actions to come back to:
param( 'iframe_name', 'string', '', true );

$SQL = & new SQL();

$SQL->SELECT( 'link_ID, link_ltype_ID, link_position, file_ID, file_title, file_root_type, file_root_ID, file_path, file_alt, file_desc' );
$SQL->FROM( 'T_links LEFT JOIN T_files ON link_file_ID = file_ID' );
$SQL->WHERE( 'link_itm_ID = '.$edited_Item->ID );
$SQL->ORDER_BY( 'link_position+0, link_order, link_ID' );

$Results = & new Results( $SQL->get(), 'link_' );

$Results->title = T_('Attachments');

/*
 * Sub Type column
 */
function display_subtype( & $row )
{
	if( empty($row->file_ID) )
	{
		return '';
	}

	global $current_File;
	// Instantiate a File object for this line:
	$current_File = new File( $row->file_root_type, $row->file_root_ID, $row->file_path ); // COPY!
	// Flow meta data into File object:
	$current_File->load_meta( false, $row );

	return $current_File->get_preview_thumb( 'fulltype' );
}
$Results->cols[] = array(
						'th' => T_('Icon/Type'),
						'td_class' => 'shrinkwrap',
						'td' => '%display_subtype( {row} )%',
					);


/*
 * LINK column
 */
function display_link( & $row )
{
	if( !empty($row->file_ID) )
	{
		/**
		 * @var File
		 */
		global $current_File;
		global $edited_Item;

		$r = '';

		// File relative path & name:
		// return $current_File->get_linkedit_link( '&amp;fm_mode=link_item&amp;itm_ID='.$edited_Item->ID );
		if( $current_File->is_dir() )
		{ // Directory
			$r .= $current_File->dget( '_name' );
		}
		else
		{ // File
			if( $view_link = $current_File->get_view_link() )
			{
				$r .= $view_link;
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

	return '?';
}
$Results->cols[] = array(
						'th' => T_('Destination'),
						'td' => '%display_link( {row} )%',
					);


if( $current_User->check_perm( 'files', 'view', false, $Blog->ID ) )
{
	function file_actions( $link_ID, $cur_idx, $total_rows )
	{
		/**
		 * @var File
		 */
		global $current_File;
		global $edited_Item, $current_User;
		global $iframe_name;

		$r = '';

		if( isset($current_File) )
		{
			$title = T_('Locate this file!');
			$url = $current_File->get_linkedit_url( $edited_Item->ID );
			$r = '<a href="'.$url.'" onclick="return pop_up_window( \''
						.url_add_param( $url, 'mode=upload&amp;iframe_name='.$iframe_name.'' ).'\', \'fileman_upload\', 1000 )" target="_parent" title="'.$title.'">'
						.get_icon( 'locate', 'imgtag', array( 'title'=>$title ) ).'</a> ';
		}

		// Delete link.
		if( $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $edited_Item ) )
	  {	// Check that we have permission to edit item:
			$r .= action_icon( T_('Delete this link!'), 'unlink',
			                  regenerate_url( 'p,itm_ID,action', "link_ID=$link_ID&amp;action=unlink" ) );
		}

		// Change order.
		if( $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $edited_Item ) )
		{	// Check that we have permission to edit item:
			if( $cur_idx > 0 )
			{
				echo action_icon( T_('Move upwards'), 'move_up',
					regenerate_url( 'p,itm_ID,action', "link_ID=$link_ID&amp;action=link_move_up" ) );
			}
			else
			{
				echo get_icon( 'nomove' ).' ';
			}

			if( $cur_idx < $total_rows-1 )
			{
				echo action_icon( T_('Move down'), 'move_down',
					regenerate_url( 'p,itm_ID,action', "link_ID=$link_ID&amp;action=link_move_down" ) );
			}
			else
			{
				echo get_icon( 'nomove' ).' ';
			}
		}

		return $r;
	}
	$Results->cols[] = array(
							'th' => T_('Actions'),
							'td_class' => 'shrinkwrap',
							'td' => '%file_actions( #link_ID#, {CUR_IDX}, {TOTAL_ROWS} )%',
						);
}


/*
 * POSITION column
 */
function display_position( & $row )
{
	// TODO: dh> centralize somewhere.. might get parsed out of ENUM info?!
	// Should be ordered like the ENUM.
	$positions = array(
		'teaser' => T_('Teaser'),
		'aftermore' => T_('After "more"'),
		);

	// TODO: dh> only handle images

	$id = 'display_position_'.$row->link_ID;

	// NOTE: dh> using method=get so that we can use regenerate_url (for non-JS).
	$r = '<form action="" method="post"><select id="'.$id.'" name="link_position">'
		.Form::get_select_options_string($positions, $row->link_position, true).'</select>'
		.'<script type="text/javascript">jQuery("#'.$id.'").change( evo_display_position_onchange );</script>';

	$r .= '<noscript>';
	// Add hidden fields for non-JS
	$url = regenerate_url( 'p,itm_ID,action', 'link_ID='.$row->link_ID.'&action=set_item_link_position', '', '&' );
	$params = explode('&', substr($url, strpos($url, '?')+1));

	foreach($params as $param)
	{
		list($k, $v) = explode('=', $param);
		$r .= '<input type="hidden" name="'.htmlspecialchars($k).'" value="'.htmlspecialchars($v).'" />';
	}
	$r .= '<input class="SaveButton" type="submit" value="&raquo;" />';
	$r .= '</noscript>';
	$r .= '</form>';

	return $r;
}
$Results->cols[] = array(
						'th' => T_('Position'),
						'td_class' => 'shrinkwrap',
						'td' => '%display_position( {row} )%',
					);

$Results->display( $AdminUI->get_template( 'compact_results' ) );


/*
 * $Log$
 * Revision 1.11  2009/10/13 00:24:28  blueyed
 * Cleanup attachment position handling. Make it work for non-JS.
 *
 * Revision 1.10  2009/10/11 03:00:11  blueyed
 * Add "position" and "order" properties to attachments.
 * Position can be "teaser" or "aftermore" for now.
 * Order defines the sorting of attachments.
 * Needs testing and refinement. Upgrade might work already, be careful!
 *
 * Revision 1.9  2009/10/10 16:21:05  blueyed
 * typos
 *
 * Revision 1.8  2009/08/29 12:23:56  tblue246
 * - SECURITY:
 * 	- Implemented checking of previously (mostly) ignored blog_media_(browse|upload|change) permissions.
 * 	- files.ctrl.php: Removed redundant calls to User::check_perm().
 * 	- XML-RPC APIs: Added missing permission checks.
 * 	- items.ctrl.php: Check permission to edit item with current status (also checks user levels) for update actions.
 * - XML-RPC client: Re-added check for zlib support (removed by update).
 * - XML-RPC APIs: Corrected method signatures (return type).
 * - Localization:
 * 	- Fixed wrong permission description in blog user/group permissions screen.
 * 	- Removed wrong TRANS comment
 * 	- de-DE: Fixed bad translation strings (double quotes + HTML attribute = mess).
 * - File upload:
 * 	- Suppress warnings generated by move_uploaded_file().
 * 	- File browser: Hide link to upload screen if no upload permission.
 * - Further code optimizations.
 *
 * Revision 1.7  2009/07/16 14:14:59  tblue246
 * Linked file lists: Only display file title if not empty.
 *
 * Revision 1.6  2009/03/08 23:57:44  fplanque
 * 2009
 *
 * Revision 1.5  2009/03/04 01:57:26  fplanque
 * doc
 *
 * Revision 1.4  2009/03/03 20:25:53  blueyed
 * TODO/question
 *
 * Revision 1.3  2008/09/23 05:26:38  fplanque
 * Handle attaching files when multiple posts are edited simultaneously
 *
 * Revision 1.2  2008/04/14 19:50:51  fplanque
 * enhanced attachments handling in post edit mode
 *
 * Revision 1.1  2008/04/13 22:28:01  fplanque
 * enhanced handlign of files attached to items
 *
 * Revision 1.3  2008/04/03 22:03:09  fplanque
 * added "save & edit" and "publish now" buttons to edit screen.
 *
 * Revision 1.2  2008/01/21 09:35:32  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 11:00:33  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.5  2007/04/26 00:11:12  fplanque
 * (c) 2007
 *
 * Revision 1.4  2006/12/14 01:46:29  fplanque
 * refactoring / factorized image preview display
 *
 * Revision 1.3  2006/12/14 00:47:41  fplanque
 * thumbnails & previews everywhere.
 * this is getting good :D
 *
 * Revision 1.2  2006/12/14 00:01:49  fplanque
 * land in correct collection when opening FM from an Item
 *
 * Revision 1.1  2006/12/12 21:19:31  fplanque
 * UI fixes
 *
 * Revision 1.12  2006/12/12 19:39:07  fplanque
 * enhanced file links / permissions
 *
 * Revision 1.11  2006/12/12 18:04:53  fplanque
 * fixed item links
 */
?>
