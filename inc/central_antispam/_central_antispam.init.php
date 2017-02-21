<?php
/**
 * This is the init file for the central antispam module
 *
 * @copyright (c)2003-2016 by Francois PLANQUE - {@link http://fplanque.net/}
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

/**
 * Minimum PHP version required for central antispam module to function properly
 */
$required_php_version[ 'central_antispam' ] = '5.2';

/**
 * Minimum MYSQL version required for central antispam module to function properly
 */
$required_mysql_version[ 'central_antispam' ] = '5.0.3';

/**
 * Aliases for table names:
 *
 * (You should not need to change them.
 *  If you want to have multiple b2evo installations in a single database you should
 *  change {@link $tableprefix} in _basic_config.php)
 */
$db_config['aliases'] = array_merge( $db_config['aliases'], array(
		'T_centralantispam__keyword' => $tableprefix.'centralantispam__keyword',
		'T_centralantispam__source'  => $tableprefix.'centralantispam__source',
		'T_centralantispam__report'  => $tableprefix.'centralantispam__report',
	) );


/**
 * Controller mappings.
 *
 * For each controller name, we associate a controller file to be found in /inc/ .
 * The advantage of this indirection is that it is easy to reorganize the controllers into
 * subdirectories by modules. It is also easy to deactivate some controllers if you don't
 * want to provide this functionality on a given installation.
 *
 * Note: while the controller mappings might more or less follow the menu structure, we do not merge
 * the two tables since we could, at any time, decide to make a skin with a different menu structure.
 * The controllers however would most likely remain the same.
 *
 * @global array
 */
$ctrl_mappings = array_merge( $ctrl_mappings, array(
		'central_antispam' => 'central_antispam/central_antispam.ctrl.php',
	) );


/**
 * Get the CaKeywordCache
 *
 * @param string The text that gets used for the "None" option in the objects options list
 * @return CaKeywordCache
 */
function & get_CaKeywordCache( $none_name = NULL )
{
	global $CaKeywordCache;

	if( ! isset( $CaKeywordCache ) )
	{	// Cache doesn't exist yet:
		$CaKeywordCache = new DataObjectCache( 'CaKeyword', false, 'T_centralantispam__keyword', 'cakw_', 'cakw_ID', 'cakw_keyword', 'cakw_keyword', $none_name ? $none_name : T_('Unknown') );
	}

	return $CaKeywordCache;
}


/**
 * Get the CaSourceCache
 *
 * @param string The text that gets used for the "None" option in the objects options list
 * @return CaSourceCache
 */
function & get_CaSourceCache( $none_name = NULL )
{
	global $CaSourceCache;

	if( ! isset( $CaSourceCache ) )
	{	// Cache doesn't exist yet:
		$CaSourceCache = new DataObjectCache( 'CaSource', false, 'T_centralantispam__source', 'casrc_', 'casrc_ID', 'casrc_baseurl', 'casrc_baseurl', $none_name ? $none_name : T_('Unknown') );
	}

	return $CaSourceCache;
}


/**
 * central_antispam_Module definition
 */
class central_antispam_Module extends Module
{
	function init()
	{
		$this->check_required_php_version( 'central_antispam' );
	}


	/**
	 * Builds the 2nd half of the menu. This is the one with the configuration features
	 *
	 * At some point this might be displayed differently than the 1st half.
	 */
	function build_menu_3()
	{
		global $AdminUI, $admin_url, $current_User;

		if( ! is_logged_in() || ! $current_User->check_perm( 'centralantispam', 'view' ) )
		{	// Don't display menu if current user has no acces to central antispam:
			return;
		}

		// Display Central Antispam menu:
		$AdminUI->add_menu_entries( NULL, array(
			'central_antispam' => array(
				'text' => T_('Central Antispam'),
				'href' => $admin_url.'?ctrl=central_antispam',
				'entries' => array(
					'keywords' => array(
						'text' => T_('Keywords'),
						'href' => $admin_url.'?ctrl=central_antispam&amp;tab=keywords',
					),
					'reporters' => array(
						'text' => T_('Reporters'),
						'href' => $admin_url.'?ctrl=central_antispam&amp;tab=reporters',
					),
				),
			) ) );
	}


	/**
	 * Get default module permissions
	 *
	 * #param integer Group ID
	 * @return array
	 */
	function get_default_group_permissions( $grp_ID )
	{
		switch( $grp_ID )
		{
			case 1: // Administrators group ID equals 1
				$perm_centralantispam = 'allowed';
				break;
			default: // Other groups
				$perm_centralantispam = 'none';
				break;
		}

		// We can return as many default permissions as we want:
		// e.g. array ( permission_name => permission_value, ... , ... )
		return $permissions = array(
				'perm_centralantispam' => $perm_centralantispam
			);
	}


	/**
	 * Get available group permissions
	 *
	 * @return array
	 */
	function get_available_group_permissions()
	{
		// 'label' is used in the group form as label for radio buttons group
		// 'user_func' is used to check user permission. This function should be defined in module initializer.
		// 'group_func' is used to check group permission. This function should be defined in module initializer.
		// 'perm_block' group form block where this permissions will be displayed. Now available, the following blocks: additional, system
		// 'options' is permission options
		$permissions = array(
			'perm_centralantispam' => array(
				'label' => T_('Allow central antispam management'),
				'user_func'  => 'check_centralantispam_user_perm',
				'group_func' => 'check_centralantispam_group_perm',
				'perm_block' => 'additional',
				'perm_type' => 'checkbox',
				'note' => '',
				),
		);
		// We can return as many permissions as we want.
		// In other words, one module can return many pluggable permissions.
		return $permissions;
	}


	/**
	 * Check permission for the group
	 */
	function check_centralantispam_group_perm( $permlevel, $permvalue, $permtarget )
	{
		// Only 'allowed' value means group has permission
		return $permvalue == 'allowed';
	}


	/**
	 * Upgrade this module's tables in b2evo database
	 */
	function upgrade_b2evo_tables()
	{
		global $DB, $tableprefix, $old_db_version;

		// Check if DB tables of this module were installed before:
		$existing_tables = $DB->get_col( 'SHOW TABLES LIKE "'.$tableprefix.'centralantispam__%"' );

		if( ! in_array( $tableprefix.'centralantispam__keyword', $existing_tables ) )
		{	// Create a table only if it doesn't exist yet:
			task_begin( 'Creating table for central antispam keywords...' );
			db_create_table( 'T_centralantispam__keyword', '
				cakw_ID              INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
				cakw_keyword         VARCHAR(2000) NULL,
				cakw_status          ENUM("new", "published", "revoked") NOT NULL DEFAULT "new",
				cakw_statuschange_ts TIMESTAMP NULL,
				cakw_lastreport_ts   TIMESTAMP NULL,
				PRIMARY KEY (cakw_ID),
				INDEX cakw_keyword (cakw_keyword(255)),
				INDEX cakw_statuschange_ts (cakw_statuschange_ts),
				INDEX cakw_lastreport_ts (cakw_lastreport_ts)' );
			task_end();
		}

		if( ! in_array( $tableprefix.'centralantispam__source', $existing_tables ) )
		{	// Create a table only if it doesn't exist yet:
			task_begin( 'Creating table for central antispam sources...' );
			db_create_table( 'T_centralantispam__source', '
				casrc_ID      INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
				casrc_baseurl VARCHAR(2000) NULL,
				casrc_status  ENUM ("trusted", "promising", "unknown", "suspect", "blocked") NOT NULL DEFAULT "unknown",
				PRIMARY KEY (casrc_ID)' );
			task_end();
		}

		if( ! in_array( $tableprefix.'centralantispam__report', $existing_tables ) )
		{	// Create a table only if it doesn't exist yet:
			task_begin( 'Creating table for central antispam reports...' );
			db_create_table( 'T_centralantispam__report', '
				carpt_cakw_ID  INT(10) UNSIGNED NOT NULL,
				carpt_casrc_ID INT(10) UNSIGNED NOT NULL,
				carpt_ts       TIMESTAMP NULL,
				PRIMARY KEY carpt_PK (carpt_cakw_ID, carpt_casrc_ID)' );
			task_end();
		}

		if( $old_db_version < 12066 )
		{	// part of 6.8.0-alpha
			task_begin( 'Upgrade central antispam keywords table...' );
			$DB->query( 'ALTER TABLE T_centralantispam__keyword
				MODIFY cakw_status ENUM("new", "published", "revoked", "ignored") NOT NULL DEFAULT "new"' );
			task_end();
		}
	}


	/**
	 * Handle collections module htsrv actions
	 */
	function handle_htsrv_action()
	{
		global $current_User, $DB, $Session, $localtimenow, $debug, $debug_jslog;

		if( ! is_logged_in() )
		{	// User must be logged in:
			bad_request_die( T_( 'You are not logged in.' ) );
		}

		load_funcs( 'central_antispam/model/_central_antispam.funcs.php' );

		// Do not append Debuglog to response!
		$debug = false;

		// Do not append Debug JSlog to response!
		$debug_jslog = false;

		switch( param_action() )
		{
			case 'cakeyword_status_edit':
				// Update status of central antispam keyword from list screen by clicking on the status column:

				// Check that this action request is not a CSRF hacked request:
				$Session->assert_received_crumb( 'cakeyword' );

				// Check permission:
				$current_User->check_perm( 'centralantispam', 'edit', true );

				$new_status = param( 'new_status', 'string' );
				$cakw_ID = param( 'cakw_ID', 'integer', true );
				$statuschange_ts = date( 'Y-m-d H:i:s', $localtimenow );

				$DB->query( 'UPDATE T_centralantispam__keyword
						SET cakw_status = '.( empty( $new_status ) ? 'NULL' : $DB->quote( $new_status ) ).',
								cakw_statuschange_ts = '.$DB->quote( $statuschange_ts ).'
					WHERE cakw_ID ='.$DB->quote( $cakw_ID ) );
				echo '<a href="#" rel="'.$new_status.'" style="color:#FFF" color="'.ca_get_keyword_status_color( $new_status ).'" date="'.format_to_output( mysql2localedatetime_spans( $statuschange_ts ), 'htmlspecialchars' ).'">'.ca_get_keyword_status_title( $new_status ).'</a>';
				break;

			case 'casource_status_edit':
				// Update status of central antispam source from list screen by clicking on the status column:

				// Check that this action request is not a CSRF hacked request:
				$Session->assert_received_crumb( 'casource' );

				// Check permission:
				$current_User->check_perm( 'centralantispam', 'edit', true );

				$new_status = param( 'new_status', 'string' );
				$casrc_ID = param( 'casrc_ID', 'integer', true );

				$DB->query( 'UPDATE T_centralantispam__source
						SET casrc_status = '.( empty( $new_status ) ? 'NULL' : $DB->quote( $new_status ) ).'
					WHERE casrc_ID ='.$DB->quote( $casrc_ID ) );
				echo '<a href="#" rel="'.$new_status.'" style="color:#FFF" color="'.ca_get_source_status_color( $new_status ).'">'.ca_get_source_status_title( $new_status ).'</a>';
				break;
		}

		// EXit here because next code will try to call "header_redirect()":
		exit(0);
	}
}

$central_antispam_Module = new central_antispam_Module();

?>