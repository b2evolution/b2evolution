<?php
/**
 * This file displays the links attached to an Item
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


global $edited_Item;

$SQL = & new SQL();

$SQL->SELECT( 'link_ID, link_ltype_ID, file_ID, file_title, file_root_type, file_root_ID, file_path, file_alt, file_desc' );
$SQL->FROM( 'T_links LEFT JOIN T_files ON link_file_ID = file_ID' );
$order_fields = array( 'file_path' );


$SQL->WHERE( 'link_itm_ID = '.$edited_Item->ID );

$Results = & new Results( $SQL->get(), 'link_' );

$Results->title = T_('Linked to...');

/*
 * TYPE
 */
function display_type( & $row )
{
	if( !empty($row->file_ID) )
	{
		return T_('File');
	}
	// >ONgsb:
	elseif( !empty($row->pos_ID) )
	{
		return T_('Position');
	}
	elseif( !empty($row->cont_ID) )
	{
		return T_('Contact');
	}
	elseif( !empty($row->etab_ID) )
	{
		return T_('Establishment');
	}
	elseif( !empty($row->firm_ID) )
	{
		return T_('Firm');
	}
	elseif( !empty($row->itm_ID) )
	{
		return T_('Item');
	}
	// <ONgsb

	return '?';
}
$Results->cols[] = array(
						'th' => T_('Type'),
						'th_class' => 'shrinkwrap',
						'order' => implode( ', ', $order_fields ),
						'td_class' => 'shrinkwrap',
						'td' => '%display_type( {row} )%',
					);


/*
 * Sub Type column
 */
function display_subtype( & $row )
{
	$r = '';

	if( !empty($row->file_ID) )
	{
		global $current_File;
		// Instantiate a File object for this line:
		$current_File = new File( $row->file_root_type, $row->file_root_ID, $row->file_path ); // COPY!
		// Flow meta data into File object:
		$current_File->load_meta( false, $row );

		// File type:
		$r .= $current_File->get_type().' ';

		if( $current_File->is_dir() )
		{ // Directory
			$r .= $current_File->get_icon();
		}
		else
		{ // File
			if( $view_link = $current_File->get_view_link( $current_File->get_icon(), NULL, NULL ) )
			{
				$r .=  $view_link;
			}
			else
			{ // File extension unrecognized
				$r .=  $current_File->get_icon();
			}
		}
	}

  return $r;
}
$Results->cols[] = array(
						'th' => T_('Sub-Type'),
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

		$r .= '<span class="filemeta"> - '.$current_File->dget('title').'</span>';

		return $r;
	}

	return '?';
}
$Results->cols[] = array(
						'th' => T_('Destination'),
						'td' => '%display_link( {row} )%',
					);

if( $edit_allowed )
{	// Check that we have permission to edit item:

	function file_actions( $link_ID )
	{
		/**
		 * @var File
		 */
		global $current_File;
		global $edited_Item;

		$r = '';

		if( isset($current_File) )
		{
			$title = T_('Locate this file!');
			$r = $current_File->get_linkedit_link( $edited_Item->ID, get_icon( 'locate', 'imgtag', array( 'title'=>$title ) ), $title ).' ';
		}

		return $r.action_icon( T_('Delete this link!'), 'unlink',
		                    regenerate_url( 'p,itm_ID,action', "link_ID=$link_ID&amp;action=unlink" ) );
	}
	$Results->cols[] = array(
							'th' => T_('Actions'),
							'td_class' => 'shrinkwrap',
							'td' => '%file_actions( #link_ID# )%',
						);
}

if( $current_User->check_perm( 'files', 'view' ) )
{
	$Results->global_icon( T_('Link a file...'), 'link',
													'admin.php?ctrl=files&amp;fm_mode=link_item&amp;item_ID='.$edited_Item->ID, T_('Link files'), 3, 4 );
}

$Results->display();

/*
 * $Log$
 * Revision 1.11  2006/12/12 18:04:53  fplanque
 * fixed item links
 *
 * Revision 1.10  2006/12/07 20:03:32  fplanque
 * Woohoo! File editing... means all skin editing.
 *
 * Revision 1.9  2006/11/30 22:34:15  fplanque
 * bleh
 *
 * Revision 1.8  2006/11/24 18:27:26  blueyed
 * Fixed link to b2evo CVS browsing interface in file docblocks
 *
 * Revision 1.7  2006/06/13 21:49:15  blueyed
 * Merged from 1.8 branch
 *
 * Revision 1.4.2.1  2006/06/12 20:00:40  fplanque
 * one too many massive syncs...
 *
 * Revision 1.5  2006/05/30 21:53:06  blueyed
 * Replaced $EvoConfig->DB with $db_config
 *
 * Revision 1.4  2006/04/19 20:13:51  fplanque
 * do not restrict to :// (does not catch subdomains, not even www.)
 *
 */
?>