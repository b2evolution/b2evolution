<?php
/**
 * This file implements the UI for item links in the filemanager.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
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
 * @var LinkOwner
 */
global $LinkOwner;

global $mode;

if( $mode != 'upload' )
{	// If not opearting in a popup opened from post edit screen:

	$Form = new Form( NULL, 'fm_links', 'post', 'fieldset' );


	$Form->begin_form( 'fform' );

	$Form->hidden_ctrl();

	$SQL = $LinkOwner->get_SQL();

	$Results = new Results( $SQL->get(), 'link_' );

	$view_link_title = $LinkOwner->translate( 'View this owner...' );
	$Results->title = sprintf( T_('Files linked to &laquo;%s&raquo;'),
					'<a href="'.$LinkOwner->get_view_url().'" title="'.$view_link_title.'">'.$LinkOwner->get( 'title' ).'</a>' );

	if( $LinkOwner->check_perm( 'edit', false ) )
	{
		$Results->global_icon( $LinkOwner->translate( 'Edit this owner...' ), 'edit', $LinkOwner->get_edit_url(), T_('Edit') );
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
		global $current_File, $current_User;
		global $LinkOwner;

		$r = T_( 'You don\'t have permission to access this file root' );
		if( $current_User->check_perm( 'files', 'view', false, $current_File->get_FileRoot() ) )
		{
			// File relative path & name:
			$r = $current_File->get_linkedit_link( $LinkOwner->type, $LinkOwner->get_ID() );
		}
		return $r;
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
	$Results->cols[] = array(
							'th' => T_('Actions'),
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							'td' => '%link_actions( #link_ID#, {CUR_IDX}, {TOTAL_ROWS} )%',
						);

	// POSITION COLUMN:
	$Results->cols[] = array(
						'th' => T_('Position'),
						'td_class' => 'shrinkwrap',
						'td' => '%display_link_position( {row} )%',
					);

	$Results->display();

	$Form->end_form( );
}

if( $LinkOwner->check_perm( 'edit' ) )
{	// Check that we have permission to edit item:
	echo '<div>', $LinkOwner->translate( 'Click on link %s icons below to link additional files to $ownerTitle$.',
							get_icon( 'link', 'imgtag', array('class'=>'top') ) ), '</div>';
}

?>