<?php
/**
 * This file implements the UI for item links in the filemanager.
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
 * @var Item
 */
global $edited_Item;

global $mode;

if( $mode != 'upload' )
{	// If not opearting in a popup opened from post edit screen:

	$Form = & new Form( NULL, 'fm_links', 'post', 'fieldset' );


	$Form->begin_form( 'fform' );

	$Form->hidden_ctrl();


	$Results = & new Results(
						'SELECT link_ID, link_ltype_ID, T_files.*
							 FROM T_links INNER JOIN T_files ON link_file_ID = file_ID
							WHERE link_itm_ID = '.$edited_Item->ID,
						'link_' );

	$Results->title = sprintf( T_('Files linked to &laquo;%s&raquo;'),
					'<a href="?ctrl=items&amp;blog='.$edited_Item->get_blog_ID().'&amp;p='.$edited_Item->ID.'" title="'
					.T_('View this post...').'">'.$edited_Item->dget('title').'</a>' );

	if( $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $edited_Item ) )
	{ // User has permission to edit this post
		$Results->global_icon( T_('Edit this post...'), 'edit', '?ctrl=items&amp;action=edit&amp;p='.$edited_Item->ID, T_('Edit') );
	}

	// Close link mode and continue in File Manager (remember the Item_ID though):
	$Results->global_icon( T_('Quit link mode!'), 'close', regenerate_url( 'fm_mode' ) );


	// TYPE COLUMN:
	function file_type( & $row )
	{
		global $current_File;

		// Instantiate a File object for this line:
		$current_File = new File( $row->file_root_type, $row->file_root_ID, $row->file_path ); // COPY (FUNC) needed for following columns
		// Flow meta data into File object:
		$current_File->load_meta( false, $row );

		return $current_File->get_preview_thumb( 'fulltype' );
	}
	$Results->cols[] = array(
							'th' => T_('File'),
							'order' => 'link_ID',
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							'td' => '%file_type( {row} )%',
						);


	// PATH COLUMN:
	function file_path()
	{
		/**
		 * @global File
		 */
		global $current_File;
		global $edited_Item;

		// File relative path & name:
		return $current_File->get_linkedit_link( '&amp;fm_mode=link_item&amp;item_ID='.$edited_Item->ID );
	}
	$Results->cols[] = array(
							'th' => T_('Path'),
							'order' => 'file_path',
							'td_class' => 'left',
							'td' => '%file_path()%',
						);


	// TITLE COLUMN:
	$Results->cols[] = array(
							'th' => T_('Title'),
							'order' => 'file_title',
							'td_class' => 'left',
							'td' => '$file_title$',
						);


	// ACTIONS COLUMN:
	function file_actions( $link_ID )
	{
		global $current_File, $edited_Item, $current_User;

		$title = T_('Locate this file!');

		$r = $current_File->get_linkedit_link( '&amp;fm_mode=link_item&amp;item_ID='.$edited_Item->ID,
						get_icon( 'locate', 'imgtag', array( 'title'=>$title ) ), $title );

		if( $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $edited_Item ) )
		{	// Check that we have permission to edit item:
			$r .= action_icon( T_('Delete this link!'), 'unlink',
	                      regenerate_url( 'action', 'link_ID='.$link_ID.'&amp;action=unlink') );
		}

		return $r;
	}
	$Results->cols[] = array(
							'th' => T_('Actions'),
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							'td' => '%file_actions( #link_ID# )%',
						);

	$Results->display();

	$Form->end_form( );
}

if( $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $edited_Item ) )
{	// Check that we have permission to edit item:
	echo '<div>', sprintf( T_('Click on link %s icons below to link additional files to the post %s.'),
							get_icon( 'link', 'imgtag', array('class'=>'top') ),
							'&laquo;<strong>'.$edited_Item->dget( 'title' ).'</strong>&raquo;' ), '</div>';
}



/*
 * $Log$
 * Revision 1.10  2009/07/04 15:58:26  tblue246
 * Translation fixes and update of German translation
 *
 * Revision 1.9  2009/03/08 23:57:43  fplanque
 * 2009
 *
 * Revision 1.8  2009/02/25 22:17:53  blueyed
 * ItemLight: lazily load blog_ID and main_Chapter.
 * There is more, but I do not want to skim the diff again, after
 * "cvs ci" failed due to broken pipe.
 *
 * Revision 1.7  2008/09/23 05:26:38  fplanque
 * Handle attaching files when multiple posts are edited simultaneously
 *
 * Revision 1.6  2008/04/14 19:50:51  fplanque
 * enhanced attachments handling in post edit mode
 *
 * Revision 1.5  2008/04/13 20:40:06  fplanque
 * enhanced handlign of files attached to items
 *
 * Revision 1.4  2008/04/03 22:03:08  fplanque
 * added "save & edit" and "publish now" buttons to edit screen.
 *
 * Revision 1.3  2008/01/21 09:35:29  fplanque
 * (c) 2008
 *
 * Revision 1.2  2007/09/26 21:53:23  fplanque
 * file manager / file linking enhancements
 *
 * Revision 1.1  2007/06/25 11:00:01  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.13  2007/04/26 00:11:10  fplanque
 * (c) 2007
 *
 * Revision 1.12  2006/12/14 01:46:29  fplanque
 * refactoring / factorized image preview display
 *
 * Revision 1.11  2006/12/14 00:33:53  fplanque
 * thumbnails & previews everywhere.
 * this is getting good :D
 *
 * Revision 1.10  2006/12/12 19:39:07  fplanque
 * enhanced file links / permissions
 *
 * Revision 1.9  2006/12/12 18:04:53  fplanque
 * fixed item links
 *
 * Revision 1.8  2006/12/07 20:03:32  fplanque
 * Woohoo! File editing... means all skin editing.
 *
 * Revision 1.7  2006/11/24 18:27:25  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 */
?>
