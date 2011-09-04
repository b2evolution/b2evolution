<?php
/**
 * This file implements the file type class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
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
 * {@internal Open Source relicensing agreement:
 * PROGIDISTRI S.A.S. grants Francois PLANQUE the right to license
 * PROGIDISTRI S.A.S.'s contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * Filetype Class
 *
 * @package evocore
 */
class Filetype extends DataObject
{
	var $extensions = '';
	var $name = '';
	var $mimetype = '';
	var $icon = '';
	var $viewtype = '';
	var $allowed ='';

	/**
	 * Constructor
	 *
	 * @param table Database row
	 */
	function Filetype( $db_row = NULL )
	{

		// Call parent constructor:
		parent::DataObject( 'T_filetypes', 'ftyp_', 'ftyp_ID' );

		$this->delete_restrictions = array();
		$this->delete_cascades = array();

 		if( $db_row != NULL )
		{
			$this->ID         = $db_row->ftyp_ID;
			$this->extensions = $db_row->ftyp_extensions;
			$this->name       = $db_row->ftyp_name;
			$this->mimetype   = $db_row->ftyp_mimetype;
			$this->icon       = $db_row->ftyp_icon;
			$this->viewtype   = $db_row->ftyp_viewtype;
			$this->allowed    = $db_row->ftyp_allowed;
		}
		else
		{	// Create a new filetype:
			$this->set( 'viewtype', 'browser' );
			$this->set( 'allowed', 'registered' );
		}
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		global $force_upload_forbiddenext;

		// Extensions
		if( param_string_not_empty( 'ftyp_extensions', T_('Please enter file extensions separated by space.') ) )
		{ // Check if estensions has a valid format
			$GLOBALS['ftyp_extensions'] = strtolower( trim( $GLOBALS['ftyp_extensions'] ) );
			$reg_exp = '/^[a-z0-9]+( [a-z0-9]+)*$/';
			if( !preg_match( $reg_exp, $GLOBALS['ftyp_extensions'], $res ) )
			{ // Extensiosn has an invalid format
				param_error( 'ftyp_extensions', T_( 'Invalid file extensions format.' ) );
			}
		}
		$this->set_from_Request( 'extensions' );

		// Name
		param_string_not_empty( 'ftyp_name', T_('Please enter a name.') );
		$this->set_from_Request( 'name' );

		// Mime type
		param_string_not_empty( 'ftyp_mimetype', T_('Please enter a mime type.') );
		$this->set_from_Request( 'mimetype' );

		// Icon for the mime type
		if( param( 'ftyp_icon', 'string', '' ) )
		{
			param_check_filename( 'ftyp_icon', T_('Please enter a file name.') );
		}
		$this->set_from_Request( 'icon' );

		// View type
		param( 'ftyp_viewtype', 'string' );
		$this->set_from_Request( 'viewtype' );

		// Allowed to upload theses extensions
		param( 'ftyp_allowed', 'string', 'registered' );
		if( $GLOBALS['ftyp_allowed'] != 'admin' )
		{
			// Check if the extension is in the array of the not allowed extensions (_advanced.php)
			$not_allowed = false;
			$extensions = explode ( ' ', $GLOBALS['ftyp_extensions'] );
			foreach($extensions as $extension)
			{
				if( in_array( $extension, $force_upload_forbiddenext ) )
				{
					$not_allowed = true;
					continue;
				}
			}
			if( $not_allowed )
			{ // this extension is not allowed
				$GLOBALS['ftyp_allowed'] = 'admin';
			}
		}
		$this->set_from_Request( 'allowed' );

		return ! param_errors_detected();
	}


	/**
	 * Set param value
	 *
	 * By default, all values will be considered strings
	 *
	 * @param string parameter name
	 * @param mixed parameter value
	 * @param boolean true to set to NULL if empty value
	 * @return boolean true, if a value has been set; false if it has not changed
	 */
	function set( $parname, $parvalue, $make_null = false )
	{
		switch( $parname )
		{
			case 'extensions':
			case 'name':
			case 'mimetype':
			case 'icon':
			case 'viewtype':
			case 'allowed':
			default:
				return $this->set_param( $parname, 'string', $parvalue, $make_null );
		}
	}

	/**
	 * Return the img html code of the icon
	 * @return string
	 */
	function get_icon()
	{
		global $rsc_url;

		$icon = $this->icon;
		if( empty($icon) )
		{ // use default icon
			$icon = 'default.png';
		}

		return '<img src="'.$rsc_url.'icons/fileicons/'.$icon.'" alt="" title="'.$this->dget('name', 'htmlattr').'" class="middle" />';
	}


	/**
	 * Get list of extensions for this filetype.
	 * The first is being considered the default / most appropriate one.
	 * @return array
	 */
	function get_extensions()
	{
		return explode(' ', $this->extensions);
	}


	/**
	 * Get if filetype is allowed for the currentUser
	 * 
	 * @param boolean locked files are allowed for the current user.
	 * @return boolean true if currentUser is allowed to upload/rename files with this filetype, false otherwise
	 */
	function is_allowed( $allow_locked = NULL )
	{
		global $current_User;
		if( !is_logged_in() )
		{
			return $this->allowed == 'any';
		}
		if( $allow_locked == NULL )
		{
			$allow_locked = $current_User->check_perm( 'files', 'all' );
		}
		return ( $this->allowed != 'admin' ) ? true : $allow_locked;
	}
}

/*
 * $Log$
 * Revision 1.10  2011/09/04 22:13:15  fplanque
 * copyright 2011
 *
 * Revision 1.9  2011/03/10 14:54:18  efy-asimo
 * Allow file types modification & add m4v file type
 *
 * Revision 1.8  2010/02/08 17:52:18  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.7  2009/10/13 23:06:01  blueyed
 * minor
 *
 * Revision 1.6  2009/10/02 20:34:32  blueyed
 * Improve handling of wrong file extensions for image.
 *  - load_image: if the wrong mimetype gets passed, return error, instead of letting imagecreatefrom* fail
 *  - upload: detect wrong extensions, rename accordingly and add a warning
 *
 * Revision 1.5  2009/09/14 13:04:53  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.4  2009/08/30 17:27:03  fplanque
 * better NULL param handling all over the app
 *
 * Revision 1.3  2009/03/08 23:57:43  fplanque
 * 2009
 *
 * Revision 1.2  2008/01/21 09:35:29  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 10:59:57  fplanque
 * MODULES (refactored MVC)
 *
 */
?>
