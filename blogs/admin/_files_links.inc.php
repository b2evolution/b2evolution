<?php
/**
 * This file implements the UI for item links in the filemanager.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: François PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if( false )
{	/**
	 * This is ugly, sorry, but I temporarily need this until NuSphere fixes their CodeInsight :'(
	 */
	include('_header.php');
	include('files.php');
}

$Form = & new Form( 'files.php', '', 'post', 'fieldset' );

$Form->global_icon( T_('Quit link mode!'), 'close',	$Fileman->getCurUrl( array( 'fm_mode' => false ) ) );

$Form->begin_form( 'fform', sprintf( T_('Link files to &laquo;%s&raquo;...'), $edited_Item->dget('title') ) );

$edited_Item->edit_link( '<p>', '</p>', T_('Edit this post') );

$Results = & new Results(
					'SELECT link_ID, link_ltype_ID, T_files.*
						 FROM T_links INNER JOIN T_files ON link_file_ID = file_ID
						WHERE link_item_ID = '.$edited_Item->ID,
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
	return $current_File->url( $current_File->get_icon(), T_('Let browser handle this file!')  ).' '.$current_File->get_type();
}
$Results->cols[] = array(
						'th' => T_('Type'),
						'td_start' => '<td class="firstcol left">',
						'td' => '%file_type( {row} )%',
					);


// PATH COLUMN:
function file_path()
{
	global $current_File, $edited_Item;

	// File relative path & name:
	return $current_File->edit_link( '&amp;fm_mode=link_item&amp;item_ID='.$edited_Item->ID );
}
$Results->cols[] = array(
						'th' => T_('Path'),
						'order' => 'file_path',
						'td_start' => '<td class="left">',
						'td' => '%file_path()%',
					);

$Results->cols[] = array(
						'th' => T_('Title'),
						'order' => 'file_title',
						'td_start' => '<td class="left">',
						'td' => '$file_title$',
					);


function file_actions( $link_ID )
{
	global $current_File, $edited_Item;

	$title = T_('Locate this file!');

	$r = $current_File->edit_link( '&amp;fm_mode=link_item&amp;item_ID='.$edited_Item->ID, get_icon( 'locate', 'imgtag', array( 'title'=>$title ) ), $title );

	return $r.' '.action_icon( T_('Delete this link!'), 'unlink',
                      regenerate_url( 'action', 'link_ID='.$link_ID.'&amp;action=unlink') );
}
$Results->cols[] = array(
						'th' => T_('Actions'),
						'td_start' => '<td class="lastcol center">',
						'td' => '%file_actions( #link_ID# )%',
					);

$Results->display();

printf( '<p>'.T_('Click on a link icon %s below to link an additional file to this item.').'</p>', get_icon( 'link' ) );

$Form->end_form( );


/*
 * $Log$
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