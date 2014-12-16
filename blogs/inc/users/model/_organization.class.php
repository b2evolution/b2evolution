<?php
/**
 * This file implements the Organization class, which manages user organizations.
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
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id: _organization.class.php 7044 2014-07-02 08:55:10Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * User Invitation Code
 */
class Organization extends DataObject
{
	/**
	 * Name
	 * @var string
	 */
	var $name;

	/**
	 * Url
	 * @var string
	 */
	var $url;

	/**
	 * Constructor
	 *
	 * @param object DB row
	 */
	function Organization( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_users__organization', 'org_', 'org_ID' );

		if( $db_row != NULL )
		{ // Loading an object from DB:
			$this->ID   = $db_row->org_ID;
			$this->name = $db_row->org_name;
			$this->url  = $db_row->org_url;
		}
	}


	/**
	 * Get delete restriction settings
	 *
	 * @return array
	 */
	static function get_delete_restrictions()
	{
		return array(
				array( 'table'=>'T_users__user_org', 'fk'=>'uorg_org_ID', 'msg'=>T_('%d users in this organization') ),
			);
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		// Name
		param( 'org_name', 'string' );
		param_check_not_empty( 'org_name', T_('You must provide a name!') );
		$this->set_from_Request( 'name', 'org_name' );

		// Url
		param( 'org_url', 'string' );
		param_check_url( 'org_url', 'commenting' );
		$this->set_from_Request( 'url', 'org_url', true );

		return ! param_errors_detected();
	}


	/**
	 * Get organization name.
	 *
	 * @return string organization name
	 */
	function get_name()
	{
		return $this->name;
	}
}

?>