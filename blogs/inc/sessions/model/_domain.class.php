<?php
/**
 * This file implements the Domain class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}.
*
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * EVO FACTORY grants Francois PLANQUE the right to license
 * EVO FACTORY contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _domain.class.php 7043 2014-07-02 08:35:45Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );


/**
 * Slug Class
 *
 * @package evocore
 */
class Domain extends DataObject
{
	var $name;

	var $status;

	var $type;

	/**
	 * Constructor
	 *
	 * @param object table Database row
	 */
	function Domain( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_basedomains', 'dom_', 'dom_ID' );

		if( $db_row != NULL )
		{
			$this->ID = $db_row->dom_ID;
			$this->name = $db_row->dom_name;
			$this->status = $db_row->dom_status;
			$this->type = $db_row->dom_type;
		}
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		param_string_not_empty( 'dom_name', T_('Please enter domain name.') );
		$dom_name = get_param( 'dom_name' );
		$this->set( 'name', $dom_name );

		$dom_status = param( 'dom_status', 'string', true );
		$this->set( 'status', $dom_status, true );

		$dom_type = param( 'dom_type', 'string', true );
		$this->set( 'type', $dom_type, true );

		if( ! param_errors_detected() )
		{ // Check domains with the same name and type
			global $Messages, $DB;
			$SQL = new SQL();
			$SQL->SELECT( 'dom_ID' );
			$SQL->FROM( 'T_basedomains' );
			$SQL->WHERE( 'dom_ID != '.$this->ID );
			$SQL->WHERE_and( 'dom_name = '.$DB->quote( $dom_name ) );
			$SQL->WHERE_and( 'dom_type = '.$DB->quote( $dom_type ) );
			if( $DB->get_var( $SQL->get() ) )
			{
				$Messages->add( T_('Domain already exists with the same name and type.') );
			}
		}

		return ! param_errors_detected();
	}
}

?>