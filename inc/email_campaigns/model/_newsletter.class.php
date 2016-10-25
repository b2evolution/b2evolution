<?php
/**
 * This file implements the newsletter class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}.
*
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );


/**
 * Newsletter Class
 *
 * @package evocore
 */
class Newsletter extends DataObject
{
	var $name;

	var $label;

	var $active = 1;

	/**
	 * Constructor
	 *
	 * @param object table Database row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_email__newsletter', 'enlt_', 'enlt_ID' );

		if( $db_row !== NULL )
		{
			$this->ID = $db_row->enlt_ID;
			$this->name = $db_row->enlt_name;
			$this->label = $db_row->enlt_label;
			$this->active = $db_row->enlt_active;
		}
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		// Active:
		param( 'enlt_active', 'integer', 0 );
		$this->set_from_Request( 'active' );

		if( param( 'enlt_name', 'string', NULL ) !== NULL )
		{	// Name:
			param_string_not_empty( 'enlt_name', T_('Please enter a newsletter name.') );
			$this->set_from_Request( 'name' );
		}

		// Label:
		param( 'enlt_label', 'string', NULL );
		$this->set_from_Request( 'label', 'enlt_label', true );

		return ! param_errors_detected();
	}
}

?>