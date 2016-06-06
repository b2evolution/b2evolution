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
$required_php_version[ 'central_antispam' ] = '5.0';

/**
 * Minimum MYSQL version required for central antispam module to function properly
 */
$required_mysql_version[ 'central_antispam' ] = '4.1';

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
	 * Translations
	 *
	 * @param mixed $string
	 * @param mixed $req_locale
	 * @return string
	 */
	function T_( $string, $req_locale = '' )
	{
		global $current_locale;

		static $trans = array(
			'' => '',
		);

		if( $current_locale == 'fr-FR' )
		{
			if( isset( $trans[ $string ] ) )
			{
				return $trans[ $string ];
			}
		}

		return T_( $string );
	}


	/**
	 * Builds the 2nd half of the menu. This is the one with the configuration features
	 *
	 * At some point this might be displayed differently than the 1st half.
	 */
	function build_menu_3()
	{
		global $AdminUI, $admin_url;

		// Display Central Antispam menu:
		$AdminUI->add_menu_entries( NULL, array(
			'central_antispam' => array(
				'text' => $this->T_('Central Antispam'),
				'href' => $admin_url.'?ctrl=central_antispam',
				'entries' => array(
					'keywords' => array(
						'text' => $this->T_('Keywords'),
						'href' => $admin_url.'?ctrl=central_antispam&amp;tab=keywords',
					),
					'sources' => array(
						'text' => $this->T_('Reporters'),
						'href' => $admin_url.'?ctrl=central_antispam&amp;tab=sources',
					),
				),
			) ) );
	}
}

$central_antispam_Module = new central_antispam_Module();

?>