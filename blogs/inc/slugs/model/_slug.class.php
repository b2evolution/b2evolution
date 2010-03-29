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
 * @version $
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

	function Slug( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_slug', 'slug_', 'slug_ID' );

		$this->delete_restrictions = array(
				array( 'table'=>'T_items__item', 'fk'=>'post_urltitle', 'msg'=>T_('%d related post') ),
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
		// enable just numbers and letters and '-' and '_'
		if( preg_match( '#^([0-9A-Za-z]|-)+$#', $slug_title ) )
		{
			if( $this->dbexists( 'slug_title', $slug_title ) )
			{
				$Messages->add( sprintf( T_('%s slug title is already exists!'), $slug_title ) ); 
			}
			$this->set( 'title', $slug_title );
		}
		else
		{
			$Messages->add( T_('Title with spaces and spceial characters are not allowed!'), 'error' );
		}

		// type
		$this->set_string_from_param( 'type', true );

		// object ID:
		$object_id = param( 'slug_object_ID', 'string' );
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


	function check_relations( $what, $ignore = array(), $addlink = false )
	{
		global $DB, $Messages;

		foreach( $this->$what as $restriction )
		{
			if( !in_array( $restriction['fk'], $ignore ) )
			{
				if( $addlink )
				{ // get linked objects and add a link
					$link = '';
					if( $addlink )
					{ // get link from derived class
						$link = $this->get_restriction_link( $restriction );
					}
					// without restriction => don't display the message
					if( $link != '' )
					{
						$Messages->add( $link, 'restrict' );
					}
				}
				else
				{ // count and show how many object is connected
					$count = $DB->get_var(
					'SELECT COUNT(*)
					   FROM '.$restriction['table'].'
					  WHERE '.$restriction['fk'].' = '.$DB->quote( $this->title ),
					0, 0, 'restriction/cascade check' );
					if( $count )
					{
						$Messages->add( sprintf( $restriction['msg'], $count ), 'restrict' );
					}
				}
			}
		}
	}
}

?>