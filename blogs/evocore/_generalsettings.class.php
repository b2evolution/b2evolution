<?php
/**
 * This file implements the GeneralSettings class, which handles Name/Value pairs.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
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
 * @version $Id$
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

/**
 * Includes
 */
require_once dirname(__FILE__).'/_abstractsettings.class.php';

/**
 * Class to handle the global settings.
 *
 * @package evocore
 */
class GeneralSettings extends AbstractSettings
{
	var $_defaults = array( 'upload_enabled' => '1',
													'upload_allowedext' => 'jpg gif png',
													'upload_allowedmime' => 'image/jpeg image/gif image/png',
													'upload_maxkb' => '100',

													'fm_enabled' => '1',
													'fm_enable_roots_blog' => '1',
													'fm_enable_roots_group' => '1',
													'fm_enable_roots_user' => '1',
													'fm_enable_create_dir' => '1',
													'fm_enable_create_file' => '1',

													'regexp_filename' => '^[a-zA-Z0-9\-_.]+$'
												);
	/**
	 * Constructor
	 *
	 * loads settings, checks db_version
	 */
	function GeneralSettings()
	{
		global $new_db_version;

		parent::AbstractSettings( 'T_settings', array( 'set_name' ), 'set_value' );


		// check DB version:
		if( $this->get( 'db_version' ) != $new_db_version )
		{ // Database is not up to date:
			$error_message = 'Database schema is not up to date!'
												.'<br />'
												.'You have schema version &laquo;<em>'.(integer)$this->get( 'db_version' ).'</em>&raquo;, '
												.'but we would need &laquo;<em>'.(integer)$new_db_version.'</em>&raquo;.';
			require dirname(__FILE__).'/_conf_error.inc.php'; // error & exit
		}
	}

}

/*
 * $Log$
 * Revision 1.11  2005/03/15 19:19:47  fplanque
 * minor, moved/centralized some includes
 *
 * Revision 1.9  2005/02/28 09:06:33  blueyed
 * removed constants for DB config (allows to override it from _config_TEST.php), introduced EVO_CONFIG_LOADED
 *
 * Revision 1.8  2005/01/15 20:13:38  blueyed
 * regexp_fileman moved to $Settings
 *
 * Revision 1.7  2005/01/14 17:38:13  blueyed
 * defaults added
 *
 * Revision 1.6  2005/01/10 02:14:02  blueyed
 * new settings
 *
 * Revision 1.5  2005/01/06 05:20:14  blueyed
 * refactored (constructor), getDefaults()
 *
 * Revision 1.4  2004/12/30 22:54:38  blueyed
 * errormessage beautified
 *
 * Revision 1.3  2004/11/08 02:23:44  blueyed
 * allow caching by column keys (e.g. user ID)
 *
 * Revision 1.2  2004/10/16 01:31:22  blueyed
 * documentation changes
 *
 * Revision 1.1  2004/10/13 22:46:32  fplanque
 * renamed [b2]evocore/*
 *
 * Revision 1.11  2004/10/12 10:27:18  fplanque
 * Edited code documentation.
 *
 */
?>