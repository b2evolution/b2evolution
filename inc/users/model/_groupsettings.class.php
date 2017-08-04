<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'settings/model/_abstractsettings.class.php', 'AbstractSettings' );
load_class( '_core/model/db/_sql.class.php', 'SQL' );


/**
 * GroupSettings class
 *
 * This class represents group permissions
 */
class GroupSettings extends AbstractSettings
{
	/**
	 * Current or default permission values
	 * @var array
	 */
	var $permission_values = array();

	/**
	 * Permission modules
	 * @var array
	 */
	var $permission_modules = array();

	/**
	 * New permissions values
	 * @access private
	 * @var array
	 */
	var $_permissions = array();


	/**
	 * Constructor
	 */
	function __construct()
	{ 	// call parent constructor
		parent::__construct( 'T_groups__groupsettings', array( 'gset_grp_ID', 'gset_name' ), 'gset_value', 1 );
	}


	/**
	 * Load permissions
	 *
	 * @param integer Group ID
	 */
	function load( $grp_ID )
	{
		global $DB, $modules;

		// Get default group permission from each module
		foreach( $modules as $module )
		{
			$Module = & $GLOBALS[$module.'_Module'];
			if( method_exists( $Module, 'get_default_group_permissions' ) )
			{	// Module has pluggable permissions and we can add them to the current setting
				$this->add( $module, $Module->get_default_group_permissions( $grp_ID ), $grp_ID );
			}
		}

		if( $grp_ID != 0 )
		{
			// Select current group permission from database
			$SQL = new SQL();
			$SQL->SELECT( '*' );
			$SQL->FROM( 'T_groups__groupsettings' );
			$SQL->WHERE( 'gset_grp_ID = '.$grp_ID );

			$DB->begin();

			// Set current group permissions
			$existing_perm = array();
			foreach( $DB->get_results( $SQL->get(), OBJECT, 'Load settings from group #'.$grp_ID ) as $row )
			{
				$existing_perm[] = $row->gset_name;
				$this->permission_values[$row->gset_name] = $row->gset_value;
			}

			// Set default group permission if these permissions don't exist
			$update_permissions = false;
			foreach( $this->permission_values as $name => $value )
			{
				if( ! in_array( $name, $existing_perm ) )
				{
					$this->set( $name, $value, $grp_ID );
					$update_permissions = true;
				}
			}

			if( $update_permissions )
			{	// We can update permission as there are some new permnissions
				$this->update( $grp_ID );
			}

			$DB->commit();
		}
	}


	/**
	 * Add default permission to the group.
	 * Each module can define its own default permissions.
	 *
	 * @param string module name
	 * @param array permissions
	 * @param integer Group ID
	 */
	function add( $module, $permissions, $grp_ID )
	{
		if( ! empty( $permissions ) )
		{
			foreach( $permissions as $key => $value )
			{
				$this->permission_values[$key] = $value;
				$this->permission_modules[$key] = $module;
			}
		}
	}


	/**
	 * Get a permission from the DB group settings table
	 *
	 * @param string name of permission
	 * @param integer Group
	 */
	function get( $permission, $grp_ID )
	{
		if( $grp_ID != 0 )
		{	// We can get permission from database, because the current group setting are available in database
			$this->permission_values[$permission] = parent::getx( $grp_ID, $permission );
		}
		return $this->permission_values[$permission];
	}


	/**
	 * Temporarily sets a group permission ({@link dbupdate()} writes it to DB)
	 *
	 * @param string name of permission
	 * @param mixed new value
	 * @param integer Group ID
	 */
	function set( $permission, $value, $grp_ID )
	{
		if( $grp_ID != 0 )
		{	// We can set permission, because the current group is already in database
			$this->permission_values[$permission] = $value;
			return parent::setx( $grp_ID, $permission, $value );
		}

		$this->_permissions[$permission] = $value;
		return true;
	}


	/**
	 * Update all of the group permissions
	 *
	 * @param integer Group ID
	 */
	function update( $grp_ID )
	{
		if( ! empty( $this->_permissions ) )
		{	// Set temporary permissions. It is only for the new creating group
			foreach( $this->_permissions as $name => $value )
			{
				$this->set( $name, $value, $grp_ID );
			}

			$this->_permissions = array();
		}

		// Update permissions
		return $this->dbupdate();
	}


	/**
	 * Delete all of the group permissions
	 *
	 * @param @param integer Group ID
	 */
	function delete( $grp_ID )
	{
		foreach( $this->permission_values as $name => $value )
		{
			parent::delete( $grp_ID, $name );
		}
	}
}

?>
