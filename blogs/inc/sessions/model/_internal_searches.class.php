<?php
/**
 * This file implements the Internal search item class.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * Internal search item Class
 *
 * @package evocore
 */
class InternalSearches extends DataObject
{
	var $keywords = '';
	var $name = '';
	/**
	 * @var int
	 */
	var $coll_ID = '';
	var $session_ID = '';

	/**
	 * Constructor
	 *
	 * @param object Database row
	 */
	function InternalSearches( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_logs__internal_searches', 'isrch_', 'isrch_ID' );

 		if( $db_row )
		{
			$this->ID            = $db_row->isrch_ID;
			$this->coll_ID       = $db_row->isrch_coll_ID;
			$this->session_ID    = $db_row->isrch_session_ID;
			$this->keywords      = $db_row->isrch_keywords;
			
		}
		else
		{	// Create a new internal search item:
		}
	}


	/**
	 * Generate help title text for action
	 *
	 * @param string action code: edit, delete, etc.
	 * @return string translated help string
	 */
	function get_action_title( $action )
	{
		switch( $action )
		{
			case 'edit': return T_('Edit this internal search...');
			case 'copy': return T_('Duplicate this internal search...');
			case 'delete': return T_('Delete this internal search!');
			default:
				return '';
		}
	}


	/**
	 * Check permission on a persona
	 *
	 * @todo fp> break up central User::check_perm() so that add-on modules do not need to add code into User class.
	 *
	 * @return boolean true if granted
	 */
	function check_perm( $action= 'view', $assert = true )
	{
		/**
		* @var User
		*/
		global $current_User;

		return $current_User->check_perm( 'stats', $action, $assert );
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		// Coll ID
		$this->set_string_from_param( 'coll_ID', true );

		// Session ID
		$this->set_string_from_param( 'session_ID', true );

		// Keywords :
		$this->set_string_from_param( 'keywords' );
		
		return ! param_errors_detected();
	}


	function get_keywords()
	{
		return $this->keywords;
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
			case 'coll_ID':
				return $this->set_param( $parname, 'number', $parvalue, true );
				
			case 'session_ID':
				return $this->set_param( $parname, 'number', $parvalue, true );

			case 'keywords':
			default:
				return $this->set_param( $parname, 'string', $parvalue, $make_null );
		}
	}


}


/*
 * $Log$
 * Revision 1.1  2011/09/07 12:00:20  lxndral
 * internal searches update
 *
 * Revision 1.0  2011/09/05 20:07:19  alexader
 *
 */
?>