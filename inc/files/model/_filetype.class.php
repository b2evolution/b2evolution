<?php
/**
 * This file implements the file type class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
 *
 * @package evocore
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
		$this->old_extensions = $this->extensions;
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
		$this->old_mimetype = $this->mimetype;
		// asimo> TODO: Consider to add some further validation for the ftyp_mimetype param value
		// If it will be correctly validated, the corresponding db field collation may be changed to 'ascii_bin'
		param_string_not_empty( 'ftyp_mimetype', T_('Please enter a mime type.') );
		$this->set_from_Request( 'mimetype' );

		// Icon for the mime type
		param( 'ftyp_icon', 'string', '' );
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
	 *
	 * @param array Params
	 * @return string
	 */
	function get_icon( $params = array() )
	{
		$params = array_merge( array(
				'alt'   => '#',
				'title' => '#',
			), $params );

		global $rsc_url;

		$icon = $this->icon;
		if( empty( $icon ) )
		{ // use default icon
			$icon = 'file_unknown';
		}

		return get_icon( $icon, 'imgtag', array(
				'alt'   => $params['alt'] == '#' ? $this->dget( 'name', 'htmlattr' ) : $params['alt'],
				'title' => $params['title'] == '#' ? $this->dget( 'name', 'htmlattr' ) : $params['title']
			) );
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
		if( !is_logged_in( false ) )
		{
			return $this->allowed == 'any';
		}
		if( $allow_locked == NULL )
		{
			$allow_locked = $current_User->check_perm( 'files', 'all' );
		}
		return ( $this->allowed != 'admin' ) ? true : $allow_locked;
	}


	/**
	 * Insert object into DB based on previously recorded changes.
	 *
	 * @return boolean true on success, false on failure to insert
	 */
	function dbinsert()
	{
		global $DB;

		$dbchanges = $this->dbchanges;

		$DB->begin();

		if( ( $r = parent::dbinsert() ) !== false )
		{
			// Update types of the Files
			$this->update_file_types();

			$DB->commit();
		}
		else
		{
			$DB->rollback();
		}

		return $r;
	}


	/**
	 * Update the DB based on previously recorded changes
	 *
	 * @return boolean true on success, false on failure to update, NULL if no update necessary
	 */
	function dbupdate()
	{
		global $DB;

		$dbchanges = $this->dbchanges;

		$DB->begin();

		if( ( $r = parent::dbupdate() ) !== false )
		{
			// Update types of the Files
			$this->update_file_types();

			$DB->commit();
		}
		else
		{
			$DB->rollback();
		}

		return $r;
	}


	/**
	 * Delete object from DB.
	 *
	 * @return boolean true on success, false on failure to update
	 */
	function dbdelete()
	{
		global $DB;

		// Save old extensions and old mimetype to update file types after deleting
		$this->old_extensions = $this->extensions;
		$this->extensions = '';
		$this->old_mimetype = $this->mimetype;
		$this->mimetype = '';

		$DB->begin();

		if( ( $r = parent::dbdelete() ) !== false )
		{
			// Update types of the Files
			$this->update_file_types();

			$DB->commit();
		}
		else
		{
			$DB->rollback();
		}

		return $r;
	}


	/**
	 * Update field 'file_type' in the table T_files
	 */
	function update_file_types()
	{
		if( $this->extensions == $this->old_extensions && $this->mimetype == $this->old_mimetype )
		{ // The extensions and mimetype were not changed
			return;
		}

		global $DB;

		$old_extensions = explode( ' ', $this->old_extensions );

		if( preg_match( '#^audio/#', $this->old_mimetype ) && $this->mimetype != $this->old_mimetype )
		{ // Previous mimetype was 'audio' but it is changed to other, We should update file type
			$deleted_extensions = $old_extensions;
		}

		if( preg_match( '#^audio/#', $this->mimetype ) )
		{ // Current file type is Audio format

			$new_extensions = $this->get_extensions();
			if( empty( $new_extensions ) && empty( $old_extensions ) )
			{ // No extensions
				return;
			}

			$deleted_extensions = array_diff( $old_extensions, $new_extensions );
			$added_extensions = array_diff( $new_extensions, $old_extensions );
		}

		if( ! empty( $deleted_extensions ) )
		{ // Delete file type from old extensions
			$DB->query( 'UPDATE T_files
					SET file_type = NULL
				WHERE file_path LIKE "%.'.implode( '" OR file_path LIKE "%.', $deleted_extensions ).'"' );
		}

		if( ! empty( $added_extensions ) )
		{ // Set audio file type for new extensions
			$DB->query( 'UPDATE T_files
					SET file_type = "audio"
				WHERE file_path LIKE "%.'.implode( '" OR file_path LIKE "%.', $added_extensions ).'"' );
		}
	}
}

?>