<?php
/**
 * This file implements the Admin UI class.
 * Alternate admin skins should derive from this class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}.
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


			default:
				// Delegate to parent class:
				return parent::get_template( $name, $depth );
		}
	}


	/**
	 * Get HTML head lines, links (to CSS files especially).
	 *
	 * @return string Calls parent::get_headlines()
	 */
	function get_headlines()
	{
		global $mode, $rsc_url, $adminskins_path;

		$this->headlines[] = '<link href="skins_adm/legacy/rsc/css/variation.css" rel="stylesheet" type="text/css" title="Variation" />';
		$this->headlines[] = '<link href="skins_adm/legacy/rsc/css/desert.css" rel="alternate stylesheet" type="text/css" title="Desert" />';
		$this->headlines[] = '<link href="skins_adm/legacy/rsc/css/legacy.css" rel="alternate stylesheet" type="text/css" title="Legacy" />';

		if( is_file( $adminskins_path.'/legacy/rsc/css/custom.css' ) )
		{
			$this->headlines[] = '<link href="skins_adm/legacy/rsc/css/custom.css" rel="alternate stylesheet" type="text/css" title="Custom" />';
		}

		// Style switcher:
		$this->headlines[] = '<script type="text/javascript" src="'.$rsc_url.'js/styleswitcher.js?v=2"></script>';

		return parent::get_headlines();
	}


	/**
	 * GLOBAL HEADER - APP TITLE, LOGOUT, ETC.
	 *
	 * @return string
	 */
	function get_page_head()
	{
		global $htsrv_url_sensitive, $baseurl, $admin_url, $rsc_url, $Blog;

		$r = '
		<div id="header">
			'.$this->admin_logo.'

			<div id="headfunctions">
				'.T_('Style:').'
				<a href="#" onclick="StyleSwitcher.setActiveStyleSheet(\'Variation\'); return false;" title="Variation (Default)">V</a>'
				.'&middot;<a href="#" onclick="StyleSwitcher.setActiveStyleSheet(\'Desert\'); return false;" title="Desert">D</a>'
				.'&middot;<a href="#" onclick="StyleSwitcher.setActiveStyleSheet(\'Legacy\'); return false;" title="Legacy">L</a>'
				.( is_file( dirname(__FILE__).'/rsc/css/custom.css' ) ? '&middot;<a href="#" onclick="StyleSwitcher.setActiveStyleSheet(\'Custom\'); return false;" title="Custom">C</a>' : '' )
				.'
				&bull; '
				// Note: if we log in with another user, we may not have the perms to come back to the same place any more, thus: redirect to admin home.
				.'<a href="'.$htsrv_url_sensitive.'login.php?action=logout&amp;redirect_to='.rawurlencode(url_rel_to_same_host($admin_url, $htsrv_url_sensitive)).'">'.T_('Logout').'</a>
				&bull;
				<a href="'.( empty($Blog) ? $baseurl : $Blog->get('url') ).'">'.T_('Exit to blogs').'
					<img src="'.$rsc_url.'icons/close.gif" width="14" height="14" border="0" class="top" alt="" title="'
					.T_('Exit to blogs').'" /></a>
			</div>

			<div id="headinfo">'.$this->get_head_info().'</div>'

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
				<h1><strong>'.$this->get_title_for_titlearea().'</strong>
				'.$this->get_bloglist_buttons( '', '' ).'
				</h1>
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
}

?>