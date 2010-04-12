<?php
/**
 * This file implements the Slug class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 * @author asimo: Evo Factory / Attila Simo
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );


/**
 * Slug Class
 *
 * @package evocore
 */
class Slug extends DataObject
{
	var $title;

	var $type;

	var $itm_ID;

	/*
	 * constructor
	 * 
	 * object table Database row
	 */
	function Slug( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_slug', 'slug_', 'slug_ID' );

		$this->delete_restrictions = array(
				array( 'table'=>'T_items__item', 'fk'=>'post_canonical_slug_ID', 'msg'=>T_('%d related post') ),
				array( 'table'=>'T_items__item', 'fk'=>'post_tiny_slug_ID', 'msg'=>T_('%d related post') ),
			);

		if( $db_row != NULL )
		{
			$this->ID = $db_row->slug_ID;
			$this->title = $db_row->slug_title;
			$this->type = $db_row->slug_type;
			$this->itm_ID = $db_row->slug_itm_ID;
		}
	}


	/**
	 * Get a member param by its name
	 *
	 * @param mixed Name of parameter
	 * @return mixed Value of parameter
	 */
	function get( $parname )
	{
		return parent::get( $parname );
	}


	/**
	 * Set param value
	 *
	 * @param string parameter name
	 * @param mixed parameter value
	 * @param boolean true to set to NULL if empty value
	 * @return boolean true, if a value has been set; false if it has not changed
	 */
	function set( $parname, $parvalue, $make_null = false )
	{
		return $this->set_param( $parname, 'string', $parvalue, $make_null );
	}


	/**
	 * Load data from Request form fields.
	 *
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		global $Messages;
		// title
		$slug_title = param( 'slug_title', 'string', true );
		$slug_title = urltitle_validate( $slug_title, '', 0, true, 'slug_title', 'slug_ID', 'T_slug' );
		if( $this->dbexists( 'slug_title', $slug_title ) )
		{
			$Messages->add( sprintf( T_('%s slug title already exists!'), $slug_title ), 'error' ); 
		}
		$this->set( 'title', $slug_title );

		// type
		$this->set_string_from_param( 'type', true );

		// object ID:
		$object_id = param( 'slug_object_ID', 'string' );
		// All DataObject ID must be a number
		if( ! is_number( $object_id ) )
		{ // not a number
			$Messages->add( T_('Object ID must be a number!'), 'error' );
			return false;
		}

		switch( $this->type )
		{
			case 'item':
				$ItemCache = & get_ItemCache();
				if( $ItemCache->get_by_ID( $object_id, false, false ) )
				{
					$this->set_from_Request( 'itm_ID', 'slug_object_ID', true );
				}
				else
				{
					$Messages->add( T_('Object ID must be a valid Post ID!'), 'error' );
				}
				break;
		}

		return ! param_errors_detected();
	}


	/*
	 * Create a link to the related oject
	 * 
	 * @return string empty if no related item | link to related item
	 */
	function get_link_to_object( $fk = 'post_canonical_slug_ID' )
	{
		global $DB, $admin_url;

		switch( $this->type )
		{ // can be different type of object
			case 'item':
				$object_ID = 'post_ID';			// related table object ID
				$object_name = 'post_title';	// related table object name
				
				// link to object
				$link = '<a href="'.$admin_url.'?ctrl=items&action=edit&p=%d">%s</a>';
				$object_query = 'SELECT post_ID, post_title FROM T_items__item'
								.' WHERE '.$fk.' = '.$this->ID;
				break;

			default:
				// not defined restriction
				debug_die ( 'Unhandled object type:' . htmlspecialchars ( $this->type ) );
		}

		$result_link = '';
		$query_result = $DB->get_results( $object_query );
		foreach( $query_result as $row )
		{ // create links for each related object
			if( ! ( $obj_name_value = $row->$object_name ) )
			{ // the object name is empty
				$obj_name_value = T_('No name');
			}
			$result_link .= '<br/>'.sprintf( $link, $row->$object_ID, $obj_name_value );
		}

		return $result_link;
	}


	/**
	 * Get link to restricted object
	 *
	 * Used when try to delete a slug, which is another object slug
	 * 
	 * @param array restriction
	 * @return string message with links to objects
	 */
	function get_restriction_link( $restriction )
	{
		$restriction_link = $this->get_link_to_object( $restriction['fk'] );
		if( $restriction_link != '' )
		{ // there are restrictions
			return sprintf( $restriction['msg'].$restriction_link, 1 );
		}
		// no restriction
		return '';
	}


	/**
	 * Update the DB based on previously recorded changes
	 *
	 * @return boolean true on success
	 */
	function dbupdate()
	{
		global $DB, $Messages;
		$ItemCache = & get_ItemCache();
		$Item = $ItemCache->get_by_id( $this->itm_ID );

		$DB->begin();
		if( $Item->get( 'canonical_slug_ID' ) == $this->ID )
		{
			$Item->set( 'urltitle', $this->title );
			if( ! $Item->dbupdate( true, false ) )
			{
				$DB->rollback();
				return false;
			}
			$Messages->add( 'WARNING: this change also changed the canoncial slug of the post!'.$this->get_link_to_object(), 'redwarning' );
		}

		parent::dbupdate();
		$DB->commit();
		return true;
	}
}

?>