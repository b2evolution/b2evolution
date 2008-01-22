<?php
/**
 * This file implements the Admin UI class.
 * Alternate admin skins should derive from this class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}.
 * Parts of this file are copyright (c)2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin-skin
 * @subpackage evo
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes
 */
require_once dirname(__FILE__).'/../_adminUI_general.class.php';


/**
 * We define a special template for the main menu.
 *
 * @package admin-skin
 * @subpackage evo
 */
class AdminUI extends AdminUI_general
{
	/**
	 * Get a template by name and depth.
	 *
	 * @param string The template name ('main', 'sub').
	 * @return array
	 */
	function get_template( $name, $depth = 0 )
	{
		switch( $name )
		{
			case 'main':
				switch( $depth )
				{
					default: // just one level for now (might provide dropdown later)
						return array(
							'before' => '<ul class="tabs">',
							'after' => '</ul>',
							'beforeEach' => '<li>',
							'afterEach' => '</li>',
							'beforeEachSel' => '<li class="current">',
							'afterEachSel' => '</li>',
						);
				}
				break;

			case 'CollectionList':
				// Template for a list of Collections (Blogs)
				return array(
						'before' => '',
						'after' => '',
						'select_start' => '<div class="collection_select">',
						'select_end' => '</div>',
						'buttons_start' => '',
						'buttons_end' => '',
						'beforeEach' => '',
						'afterEach' => '',
						'beforeEachSel' => '',
						'afterEachSel' => '',
					);

			default:
				// Delegate to parent class:
				return parent::get_template( $name, $depth );
		}
	}


	/**
	 * Display doctype + <head>...</head> section
	 */
	function disp_html_head()
	{
		global $mode, $rsc_url, $adminskins_path;

		require_css ( 'skins_adm/legacy/rsc/css/variation.css', TRUE, 'Variation' );
		require_css ( 'skins_adm/legacy/rsc/css/desert.css', TRUE, 'Desert' );
		require_css ( 'skins_adm/legacy/rsc/css/legacy.css', TRUE, 'Legacy' );

		if( is_file( $adminskins_path.'/legacy/rsc/css/custom.css' ) )
		{
			require_css ( 'skins_adm/legacy/rsc/css/custom.css', TRUE, 'Custom' );
		}

		// Style switcher:
		require_js( 'styleswitcher.js' );

		parent::disp_html_head();
	}


	/**
	 * GLOBAL HEADER - APP TITLE, LOGOUT, ETC.
	 *
	 * @return string
	 */
	function get_page_head()
	{
		global $htsrv_url_sensitive, $baseurl, $admin_url, $rsc_url, $Blog;
		global $app_shortname, $app_version;

		$r = '
		<div id="header">
			<div id="headfunctions">
				'.$app_shortname.' v <strong>'.$app_version.'</strong> &middot;
				'.T_('Color:').'
				<a href="#" onclick="StyleSwitcher.setActiveStyleSheet(\'Variation\'); return false;" title="Variation (Default)">V</a>'
				.'&middot;<a href="#" onclick="StyleSwitcher.setActiveStyleSheet(\'Desert\'); return false;" title="Desert">D</a>'
				.'&middot;<a href="#" onclick="StyleSwitcher.setActiveStyleSheet(\'Legacy\'); return false;" title="Legacy">L</a>'
				.( is_file( dirname(__FILE__).'/rsc/css/custom.css' ) ? '&middot;<a href="#" onclick="StyleSwitcher.setActiveStyleSheet(\'Custom\'); return false;" title="Custom">C</a>' : '' )
				.'
			</div>'

			// Display MAIN menu:
			.$this->get_html_menu().'
		</div>
		';

		return $r;
	}


	/**
	 *
	 *
	 * @return string
	 */
	function get_body_top()
	{
		global $Messages;

		$r = '';

		if( empty($this->mode) )
		{ // We're not running in an special mode (bookmarklet...)
			$r .= $this->get_page_head();
		}

		$r .= '
			<div id="TitleArea">
				<h1>'.$this->get_bloglist_buttons( '<strong>'.$this->get_title_for_titlearea().'</strong> ' ).'</h1>
			</div>

			<div class="panelbody">'
			."\n\n";

		// Display info & error messages
		$r .= $Messages->display( NULL, NULL, false, 'all', NULL, NULL, 'action_messages' );

		return $r;
	}


	/**
	 * Close open div.
	 *
	 * @return string
	 */
	function get_body_bottom()
	{
		return "\n</div>\n";
	}

	/**
	 * Get colors for page elements that can't be controlled by CSS (charts)
	 */
	function get_color( $what )
	{
		switch( $what )
		{
			case 'payload_background':
				return 'efede0';
				break;
		}
		debug_die( 'unknown color' );
	}

}

/*
 * $Log$
 * Revision 1.26  2008/01/22 14:31:06  fplanque
 * minor
 *
 */
?>