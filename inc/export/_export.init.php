<?php
/**
 * This is the init file for the export module
 *
 * @copyright (c)2003-2018 by Francois PLANQUE - {@link http://fplanque.net/}
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


/**
 * Minimum PHP version required for export module to function properly
 */
$required_php_version[ 'export' ] = '5.0';

/**
 * Minimum MYSQL version required for export module to function properly
 */
$required_mysql_version[ 'export' ] = '4.1';

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
		'exportxml' => 'export/xml.ctrl.php',
	) );


/**
 * export_Module definition
 */
class export_Module extends Module
{
	function init()
	{
		$this->check_required_php_version( 'export' );
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
			//'' => '',
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
		global $AdminUI, $current_User;

		if( is_logged_in() && $current_User->check_perm( 'options', 'edit' ) )
		{	// Display tab for export in Tools menu if current user has a permission:
			$AdminUI->add_menu_entries( array( 'options', 'misc' ), array(
				'export' => array(
					'text' => T_('Export'),
					'href' => '?ctrl=exportxml' ),
				) );
		}
	}
}

$export_Module = new export_Module();

?>