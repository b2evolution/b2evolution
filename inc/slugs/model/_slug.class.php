<?php
/**
 * This file implements the Slug class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}.
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
class Slug extends DataObject
{
	var $title;

	var $type;

	var $itm_ID;

	/**
	 * Constructor
	 *
	 * @param object table Database row
	 */
	function Slug( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_slug', 'slug_', 'slug_ID' );

		if( $db_row != NULL )
		{
			$this->ID = $db_row->slug_ID;
			$this->title = $db_row->slug_title;
			$this->type = $db_row->slug_type;
			$this->itm_ID = $db_row->slug_itm_ID;
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
				array( 'table'=>'T_items__item', 'fk'=>'post_canonical_slug_ID', 'fk_short'=>'canonical_slug_ID', 'msg'=>T_('%d related post') ),
				array( 'table'=>'T_items__item', 'fk'=>'post_tiny_slug_ID', 'fk_short'=>'tiny_slug_ID', 'msg'=>T_('%d related post') ),
			);
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
			$Messages->add( sprintf( T_('The slug &laquo;%s&raquo; already exists.'), $slug_title ), 'error' );
		}
		$this->set( 'title', $slug_title );

		// type
		$this->set_string_from_param( 'type', true );

		// object ID:
		$object_id = param( 'slug_object_ID', 'string' );
		// All DataObject ID must be a number
		if( ! is_number( $object_id ) && $this->type != 'help' )
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


	/**
	 * Create a link to the related oject.
	 *
	 * @param string Display text - if NULL, will get the object title
	 * @param string type values:
	 * 		- 'admin_view': link to this object admin interface view
	 * 		- 'public_view': link to this object public interface view (on blog)
	 * 		- 'edit': link to this object edit screen
	 * @return string link to related object, or empty if no related object, or url does not exist.
	 */
	function get_link_to_object( $link_text = NULL, $type = 'admin_view' )
	{
		if( $object = $this->get_object() )
		{
			if( ! isset($link_text ) )
			{ // link_text is not set -> get object title for link text
				$link_text = $object->get( 'title' );
			}
			// get respective url
			$link_url = $this->get_url_to_object( $type );
			if( $link_url != '' )
			{ // URL exists
				// add link title
				if( $type == 'public_view' || $type == 'admin_view' )
				{
					$link_title = ' title="'.sprintf( T_('View this %s...'), $this->get( 'type') ).'"';
				}
				elseif( $type == 'edit' )
				{
					$link_title = ' title="'.sprintf( T_('Edit this %s...'), $this->get( 'type') ).'"';
				}
				else
				{
					$link_title = '';
				}
				// return created link
				return '<a href="'.$link_url.'"'.$link_title.'>'.$link_text.'</a>';
			}
		}
		return '';
	}


	/**
	 * Create a link to the related oject (in the admin!).
	 *
	 * @param string type values:
	 * 		- 'admin_view': url to this item admin interface view
	 * 		- 'public_view': url to this item public interface view (on blog)
	 * 		- 'edit': url to this item edit screen
	 * @return string URL to related object, or empty if no related object or URL does not exist.
	 */
	function get_url_to_object( $type = 'admin_view' )
	{
		if( $object = $this->get_object() )
		{ // related object exists
			// asimo> Every slug target class need to have get_url() function
			return $object->get_url( $type );
		}
		return '';
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
		if( $object = $this->get_object() )
		{ // object exists
			// check if this is a restriction for this slug or not!
			if( $object->get( $restriction['fk_short'] ) == $this->ID )
			{
				$restriction_link = $this->get_link_to_object();
			}
		}
		if( isset( $restriction_link ) )
		{ // there are restrictions
			return sprintf( $restriction['msg'].'<br/>'.str_replace('%', '%%', $restriction_link), 1 );
		}
		// no restriction
		return '';
	}


	/**
	 * Get linked object.
	 * @return object
	 */
	function & get_object()
	{
		global $DB, $admin_url;

		switch( $this->type )
		{ // can be different type of object
			case 'item':
				// TODO: dh> should use ItemCache altogether
				// was: $object_query = 'SELECT post_ID, post_title FROM T_items__item WHERE '.$fk.' = '.$this->ID;
				$ItemCache = & get_ItemCache();
				return $ItemCache->get_by_ID( $this->itm_ID, false, false );

			case 'help':
				return false;

			default:
				// not defined restriction
				debug_die('Slug::get_object: Unhandled object type: '.htmlspecialchars($this->type));
		}
	}


	/**
	 * Update the DB based on previously recorded changes.
	 *
	 * @todo dh> this is very Item specific, and should get fixed probably.
	 *
	 * @return boolean true on success
	 */
	function dbupdate()
	{
		global $DB, $Messages;
		$ItemCache = & get_ItemCache();
		$Item = & $ItemCache->get_by_id( $this->itm_ID );

		$DB->begin();
		if( $Item->get( 'canonical_slug_ID' ) == $this->ID )
		{
			$Item->set( 'urltitle', $this->title );
			if( ! $Item->dbupdate( true, false, false ) )
			{
				$DB->rollback();
				return false;
			}
			$Messages->add( sprintf(T_('Warning: this change also changed the canonical slug of the post! (%s)'), $this->get_link_to_object()), 'warning' );
		}

		parent::dbupdate();
		$DB->commit();
		return true;
	}
}

?>