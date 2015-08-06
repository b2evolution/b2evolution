<?php
/**
 * This file implements the ItemTag class.
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
 * ItemTag Class
 *
 * @package evocore
 */
class ItemTag extends DataObject
{
	var $name;

	/**
	 * Constructor
	 *
	 * @param object table Database row
	 */
	function ItemTag( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_items__tag', 'tag_', 'tag_ID' );

		if( $db_row != NULL )
		{
			$this->ID = $db_row->tag_ID;
			$this->name = $db_row->tag_name;
		}
	}


	/**
	 * Get delete cascade settings
	 *
	 * @return array
	 */
	static function get_delete_cascades()
	{
		return array(
				array( 'table' => 'T_items__itemtag', 'fk' => 'itag_tag_ID', 'msg' => /* TRANS: cascade delete */ T_('%d tags from the posts') ),
			);
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
		// Name
		$tag_name = param( 'tag_name', 'string', true );
		$this->set( 'name', $tag_name );
		if( $existing_tag_ID = $this->dbexists( 'tag_name', $tag_name ) )
		{ // Other tag already exists with the same name:
			if( empty( $this->ID ) )
			{ // Suggest to edit existing tag for new creating tag
				param_error( 'tag_name', sprintf( T_('This tag already exists. Do you want to <a %s>edit the existing tag</a>?'),
					'href="?ctrl=itemtags&amp;action=edit&amp;tag_ID='.$existing_tag_ID.'"' ) );
			}
			else
			{ // Suggest to merge for existing tag
				global $DB, $Messages, $display_merge_tags_form;
				$new_tag_posts = intval( $DB->get_var( 'SELECT COUNT( itag_itm_ID ) FROM T_items__itemtag WHERE itag_tag_ID = '.$DB->quote( $existing_tag_ID ) ) );
				$old_tag_posts = intval( $DB->get_var( 'SELECT COUNT( itag_itm_ID ) FROM T_items__itemtag WHERE itag_tag_ID = '.$DB->quote( $this->ID ) ) );

				// Set this to know to display a confirmation message to merge this tag
				$this->merge_tag_ID = $existing_tag_ID;
				$this->merge_message = sprintf( T_('The previously named "%s" tag (applied to %d posts) will be merged with the existing "%s" tag (already applied to %d posts). Are you sure?' ),
					$this->dget( 'name' ),
					$old_tag_posts,
					$tag_name,
					$new_tag_posts,
					'href="?ctrl=itemtags&amp;action=merge&amp;old_tag_ID='.$this->ID.'&amp;tag_ID='.$existing_tag_ID.'&amp;'.url_crumb( 'tag' ).'"',
					'href="?ctrl=itemtags&amp;action=edit&amp;tag_ID='.$this->ID.'"' );

				// Return FALSE to don't save current changes without confirmation
				return false;
			}
		}

		return ! param_errors_detected();
	}
}

?>