<?php
/**
 * This file implements the UI for item links in the filemanager.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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


$Form = & new Form( NULL, 'fm_links', 'post', 'fieldset' );

$Form->global_icon( T_('Quit link mode!'), 'close', regenerate_url( 'fm_mode' ) );

$Form->begin_form( 'fform', sprintf( T_('Files linked to &laquo;%s&raquo; %s :'),
				'<a href="?ctrl=items&amp;blog='.$edited_Item->blog_ID.'&amp;p='.$edited_Item->ID.'" title="'.T_('View this post...').'">'.$edited_Item->dget('title').'</a>',
				$edited_Item->get_edit_link( '', '', get_icon( 'edit' ) ) ) );

$Form->hidden_ctrl();


$Results = & new Results(
					'SELECT link_ID, link_ltype_ID, T_files.*
						 FROM T_links INNER JOIN T_files ON link_file_ID = file_ID
						WHERE link_itm_ID = '.$edited_Item->ID,
					'link_' );

$Results->title = T_('Existing links');

// TYPE COLUMN:
function file_type( & $row )
{
	global $current_File;

	// Instantiate a File object for this line:
	$current_File = new File( $row->file_root_type, $row->file_root_ID, $row->file_path ); // COPY!
	// Flow meta data into File object:
	$current_File->load_meta( false, $row );

	// File type:
	return $current_File->get_view_link( $current_File->get_icon(), T_('Let browser handle this file!')  ).' '.$current_File->get_type();
}
$Results->cols[] = array(
						'th' => T_('Type'),
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

$Results->cols[] = array(
						'th' => T_('Title'),
						'order' => 'file_title',
						'td_class' => 'left',
						'td' => '$file_title$',
					);


function file_actions( $link_ID )
{
	global $current_File, $edited_Item, $current_User;

	$title = T_('Locate this file!');

	$r = $current_File->get_linkedit_link( '&amp;fm_mode=link_item&amp;item_ID='.$edited_Item->ID, get_icon( 'locate', 'imgtag', array( 'title'=>$title ) ), $title );

	if( $current_User->check_perm( 'item', 'edit', false, $edited_Item ) )
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

if( $current_User->check_perm( 'item', 'edit', false, $edited_Item ) )
{	// Check that we have permission to edit item:
	printf( '<p>'.T_('Click on link %s icons below to link additional files to this item.').'</p>', get_icon( 'link', 'imgtag', array('class'=>'top') ) );
}

$Form->end_form( );


/*
 * $Log$
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
 *
 * Revision 1.6  2006/06/01 19:39:13  fplanque
 * cleaned up Results tables
 *
 * Revision 1.5  2006/04/19 20:13:51  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 * Revision 1.4  2006/03/29 23:24:01  blueyed
 * Fixed linking of files.
 *
 * Revision 1.3  2006/03/12 23:09:01  fplanque
 * doc cleanup
 *
 * Revision 1.2  2006/03/12 03:03:33  blueyed
 * Fixed and cleaned up "filemanager".
 *
 * Revision 1.1  2006/02/23 21:12:17  fplanque
 * File reorganization to MVC (Model View Controller) architecture.
 * See index.hml files in folders.
 * (Sorry for all the remaining bugs induced by the reorg... :/)
 *
 * Revision 1.11  2006/02/10 22:05:07  fplanque
 * Normalized itm links
 *
 * Revision 1.10  2005/12/30 20:13:39  fplanque
 * UI changes mostly (need to double check sync)
 *
 * Revision 1.9  2005/12/14 19:36:15  fplanque
 * Enhanced file management
 *
 * Revision 1.8  2005/12/12 19:21:20  fplanque
 * big merge; lots of small mods; hope I didn't make to many mistakes :]
 *
 * Revision 1.7  2005/12/10 03:05:58  blueyed
 * minor
 *
 * Revision 1.4  2005/09/06 17:13:53  fplanque
 * stop processing early if referer spam has been detected
 *
 * Revision 1.3  2005/08/12 17:41:10  fplanque
 * cleanup
 *
 * Revision 1.2  2005/07/29 17:56:16  fplanque
 * Added functionality to locate files when they're attached to a post.
 * permission checking remains to be done.
 *
 * Revision 1.1  2005/07/26 18:50:48  fplanque
 * enhanced attached file handling
 *
 * This file was extracted from _files.php
 */
?>