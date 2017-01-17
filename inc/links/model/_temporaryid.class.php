<?php
/**
 * This file implements the Temporary ID class, which is used to link attachments to new creating objects.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link https://github.com/b2evolution/b2evolution}.
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evocore
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * Item Link
 *
 * @package evocore
 */
class TemporaryID extends DataObject
{
	var $type;
	var $coll_ID;

	/**
	 * Constructor
	 *
	 * @param table Database row
	 */
	function __construct( $db_row = NULL )
	{
		// Call parent constructor:
		parent::__construct( 'T_temporary_ID', 'tmp_', 'tmp_ID' );

		if( $db_row != NULL )
		{
			$this->ID = $db_row->tmp_ID;
			$this->type = $db_row->tmp_type;
			$this->coll_ID = $db_row->tmp_coll_ID;
		}
	}
}

?>