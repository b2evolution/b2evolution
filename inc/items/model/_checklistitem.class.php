<?php
/**
 * This file implements the ChecklistItem class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2020 by Francois Planque - {@link http://fplanque.com/}.
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
class ChecklistItem extends DataObject
{
    /**
	 * The item (parent) of this Comment (lazy-filled).
	 * @see ChecklistItem::get_Item()
	 * @see ChecklistItem::set_Item()
	 * @access protected
	 * @var Item
	 */
	var $Item;
	/**
	 * The ID of the comment's Item.
	 * @var integer
	 */
    var $item_ID;
    
    var $checked;
	var $label;

	/**
	 * Constructor
	 *
	 * @param object table Database row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_items__checklist_lines', 'check_', 'check_ID' );

		if( $db_row != NULL )
		{
			$this->ID = $db_row->check_ID;
			$this->item_ID = $db_row->check_item_ID;
			$this->checked = $db_row->check_checked;
			$this->label = $db_row->check_label;
		}
	}


	/**
	 * Get the Item this comment relates to
	 *
	 * @return Item
	 */
	function & get_Item()
	{
		if( ! isset( $this->Item ) )
		{
			$ItemCache = & get_ItemCache();
			$this->Item = & $ItemCache->get_by_ID( $this->item_ID, false, false );
		}

		return $this->Item;
	}


	/**
	 * Set Item this comment relates to
	 * @param Item
	 */
	function set_Item( & $Item )
	{
		$this->Item = & $Item;
		parent::set_param( 'item_ID', 'number', $Item->ID );
	}
}

?>
