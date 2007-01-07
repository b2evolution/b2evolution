<?php
/**
 * This file implements the Skin class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
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
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Skin Class
 *
 * @package evocore
 */
class Skin extends DataObject
{
	var $name;
	var $folder;
	var $type;

	/**
	 * Constructor
	 *
	 * @param table Database row
	 */
	function Skin( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_skin', 'skin_', 'skin_ID' );

		if( is_null($db_row) )
		{	// We are creating an object here:
			$this->type = 'normal';
		}
		else
		{	// Wa are loading an object:
			$this->ID = $db_row->skin_ID;
			$this->name = $db_row->skin_name;
			$this->folder = $db_row->skin_folder;
			$this->type = $db_row->skin_type;
		}
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		// Name
		param_string_not_empty( 'skin_name', T_('Please enter a name.') );
		$this->set_from_Request( 'name' );

		// Skin type
		param( 'skin_type', 'string' );
		$this->set_from_Request( 'type' );

		return ! param_errors_detected();
	}


	/**
	 *	Display a container
	 */
	function container( $name, $params = array() )
	{
		echo '<div>Debug: container: '.$name.'</div>';
	}


	/**
	 * Discover containers included in skin file
	 * @todo
	 */
	function discover_containers()
	{
		global $skins_path, $Messages;

		$rf_main_subpath = $this->folder.'/_main.php';
		$af_main_path = $skins_path.$rf_main_subpath;

		if( ! is_readable($af_main_path) )
		{
			$Messages->add( sprintf( T_('Cannot read skin file &laquo;%s&raquo;!'), $rf_main_subpath ), 'error' );
			return false;
		}

		$file_contents = @file_get_contents( $af_main_path );
		if( ! is_string($file_contents) )
		{
			$Messages->add( sprintf( T_('Cannot read skin file &laquo;%s&raquo;!'), $rf_main_subpath ), 'error' );
			return false;
		}


		// if( ! preg_match_all( '~ \$Skin->container\( .*? (\' (.+?) \' )|(" (.+?) ") ~xmi', $file_contents, $matches ) )
		if( ! preg_match_all( '~ \$Skin->container\( .*? ((\' (.+?) \')|(" (.+?) ")) ~xmi', $file_contents, $matches ) )
		{
			$Messages->add( sprintf( T_('No containers found in skin file &laquo;%s&raquo;!'), $rf_main_subpath ), 'error' );
			return false;
		}

		// Merge matches from the two regexp parts (due to regexp "|" )
		$container_list = array_merge( $matches[3], $matches[5] );

		// Filter out empty elements (due to regexp "|" )
		$container_list = array_filter( $container_list, create_function( '$a', 'return !empty($a);' ) );

		// pre_dump( $container_list );

		// TODO : register into db

		$Messages->add( sprintf( T_('%d containers have been found in the skin file.'), count( $container_list ) ), 'success' );
		return true;
	}


	/**
	 * Display skinshot for skin folder in various places.
	 *
	 * Including for NON installed skins.
	 *
	 * @static
	 */
	function disp_skinshot( $skin_folder, $function = NULL )
	{
		global $skins_path, $skins_url;

		echo '<div class="skinshot">';
		echo '<div class="skinshot_placeholder">';
		if( file_exists( $skins_path.$skin_folder.'/skinshot.jpg' ) )
		{
			echo '<img src="'.$skins_url.$skin_folder.'/skinshot.jpg" width="240" height="180" alt="'.$skin_folder.'" />';
		}
		else
		{
			echo '<div class="skinshot_noshot">'.T_('No skinshot available for').'</div>';
			echo '<div class="skinshot_name">'.$skin_folder.'</div>';
		}
		echo '</div>';
		echo '<div class="legend">';
		if( $function == 'install' )
		{
			echo '<div class="actions">';
			echo '<a href="?ctrl=skins&amp;action=create&amp;skin_folder='.rawurlencode($skin_folder).'" title="'.T_('Install NOW!').'">';
			echo T_('Install NOW!').'</a>';
			echo '</div>';
		}
		echo '<strong>'.$skin_folder.'</strong>';
		echo '</div>';
		echo '</div>';
	}


}


/*
 * $Log$
 * Revision 1.3  2007/01/07 19:40:18  fplanque
 * discover skin containers
 *
 * Revision 1.2  2007/01/07 05:32:11  fplanque
 * added some more DB skin handling (install+uninstall+edit properties ok)
 * still useless though :P
 * next step: discover containers in installed skins
 *
 * Revision 1.1  2006/12/29 01:10:06  fplanque
 * basic skin registering
 *
 */
?>