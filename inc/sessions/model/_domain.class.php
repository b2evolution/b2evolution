<?php
/**
 * This file implements the Domain class.
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
 * Slug Class
 *
 * @package evocore
 */
class Domain extends DataObject
{
	var $name;

	var $status;

	var $type;

	var $comment;

	/**
	 * Constructor
	 *
	 * @param object table Database row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_basedomains', 'dom_', 'dom_ID' );

		if( $db_row != NULL )
		{
			$this->ID = $db_row->dom_ID;
			$this->name = $db_row->dom_name;
			$this->status = $db_row->dom_status;
			$this->type = $db_row->dom_type;
			$this->comment = $db_row->dom_comment;
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

		$dom_comment = param( 'dom_comment', 'string', true );
		$this->set( 'comment', $dom_comment, true );

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