<?php
/**
 * This file implements the GeneralSettings class, which handles Name/Value pairs. {{{
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * {@internal
 * Daniel HAHLER grants François PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 *
 * @version $Id$ }}}
 *
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );

/**
 * Includes
 */
require_once( dirname(__FILE__).'/_class_abstractsettings.php' );

/**
 * Class to handle the global settings
 */
class GeneralSettings extends AbstractSettings
{
	/**
	 * Constructor
	 *
	 * loads settings, checks db_version
	 */
	function GeneralSettings()
	{ // constructor
		global $new_db_version;

		$this->dbtablename = 'T_settings';
		$this->colkeynames = array( 'set_name' );
		$this->colvaluename = 'set_value';

		parent::AbstractSettings();

		if( $this->get( 'db_version' ) != $new_db_version )
		{	// Database is not up to date:
			$error_message = 'Database schema is not up to date. You have schema version '.$this->get( 'db_version' ).', but we would need '.$new_db_version.'.';
			require dirname(__FILE__).'/_conf_error.page.php';	// error & exit
		}
	}

}
?>