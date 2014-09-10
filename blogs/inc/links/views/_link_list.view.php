<?php
/**
 * This file displays the links attached to an Object, which can be an Item, Comment, ... (called within the attachment_frame)
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * @package admin
 *
 * @version $Id: $
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

$Results = new Results( $SQL->get(), 'link_' );

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
							'td' => '%link_actions( #link_ID#, {CUR_IDX}, {TOTAL_ROWS} )%',
						);
}

$Results->cols[] = array(
						'th' => T_('Position'),
						'td_class' => 'shrinkwrap',
						'td' => '%display_link_position( {row} )%',
					);

$Results->display( $AdminUI->get_template( 'compact_results' ) );

?>