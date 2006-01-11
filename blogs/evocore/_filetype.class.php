<?php
/**
 * This file implements the file type class.
 *
 * @copyright (c)2004-2005 by PROGIDISTRI - {@link http://progidistri.com/}.
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
 * @package gsbcore
 *
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
require_once dirname(__FILE__).'/_dataobject.class.php';

/**
 * Filetype Class
 *
 * @package gsbcore
 */
class Filetype extends DataObject
{
	var $extensions = '' ;
	var $name = ''			 ;
	var $mimetype = ''	 ;
	var $icon = ''			 ;
	var $viewtype = ''	 ;
	var $allowed =''		 ;	

	/**
	 * Constructor
	 *
	 * {@internal Filetype::Filetype(-)}}
	 *
	 * @param table Database row
	 */
	function Filetype( $db_row = NULL )
	{

		// Call parent constructor:
		parent::DataObject( 'T_filetypes', 'ftyp_', 'ftyp_ID' );

		$this->delete_restrictions = array(
					);

  	$this->delete_cascades = array(
			);

 		if( $db_row != NULL )
		{
			$this->ID      			= 	$db_row->ftyp_ID      		;
			$this->extensions  	= 	$db_row->ftyp_extensions 	;
			$this->name   			= 	$db_row->ftyp_name    		;
			$this->mimetype  	 	= 	$db_row->ftyp_mimetype  	;
			$this->icon    			= 	$db_row->ftyp_icon    		;
			$this->viewtype    	= 	$db_row->ftyp_viewtype   	;
			$this->allowed 			=		$db_row->ftyp_allowed		  ;
		}
		else
		{	// Create a new filetype:
			$this->set( 'viewtype', 'browser' );
		}
	}

	
	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		global $Request, $force_upload_forbiddenext;

		// Extensions
		if( $Request->param_string_not_empty( 'ftyp_extensions', T_('Please enter file extensions separated by space.') ) )
		{ // Check if estensions has a valid format
			$Request->params['ftyp_extensions'] = strtolower( trim( $Request->params['ftyp_extensions'] ) );
			$reg_exp = '/^[a-z0-9]+( [a-z0-9]+)*$/';
			if( !preg_match( $reg_exp, $Request->params['ftyp_extensions'], $res ) ) 
			{ // Extensiosn has an invalid format
				$Request->param_error( 'ftyp_extensions', T_( 'Invalid file extensions format.' ) );
			}
		}
		$this->set_from_Request( 'extensions' );
		
		// Name
		$Request->param_string_not_empty( 'ftyp_name', T_('Please enter a name.') );
		$this->set_from_Request( 'name' );
		
		// Mime type
		$Request->param_string_not_empty( 'ftyp_mimetype', T_('Please enter a mime type.') );
		$this->set_from_Request( 'mimetype' );
		
		// Icon for the mime type
		if( $Request->param( 'ftyp_icon', 'string', '' ) )
		{
			$Request->param_isFilename( 'ftyp_icon', T_('Please enter a file name.') );
		}
		$this->set_from_Request( 'icon' );
				
		// View type
		$Request->param( 'ftyp_viewtype', 'string' );
		$this->set_from_Request( 'viewtype' );

		// Allowed to upload theses extensions
		$Request->param( 'ftyp_allowed', 'integer', '0' );
		if( $Request->params['ftyp_allowed'] )
		{
			// Check if the extension is in the array of the not allowed extensions (_advanced.php)
			$not_allowed = false;
			$extensions = explode ( ' ', $Request->params['ftyp_extensions'] );
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
				$Request->params['ftyp_allowed'] = 0;
			} 
		}
		$Request->params['ftyp_allowed'];
		$this->set_from_Request( 'allowed' );
		
		return ! $Request->validation_errors();
	}


	/**
	 * Set param value
	 *
	 * By default, all values will be considered strings
	 *
	 * @param string parameter name
	 * @param mixed parameter value
	 */
	function set( $parname, $parvalue )
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
				$this->set_param( $parname, 'string', $parvalue );
		}
	}
	
	/**
	 * Return the img html code of the icon 
	 *
	 *
	 */
	function get_icon()
	{
		global $rsc_url;

		return '<img src="'.$rsc_url.'icons/fileicons/'.$this->icon.'" alt="" title="'.$this->dget('name', 'htmlattr').'" />';
	}
	
	
}
?>