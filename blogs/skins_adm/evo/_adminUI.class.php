<?php
/**
 * This file implements the Admin UI class for the evo skin.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
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
 * We'll use the default AdminUI templates etc.
 *
 * @package admin-skin
 * @subpackage evo
 */
class AdminUI extends AdminUI_general
{

	/**
	 * This function should init the templates - like adding Javascript through the {@link add_headline()} method.
	 */
	function init_templates()
	{
		// This is included before controller specifc require_css() calls:
		require_css( 'skins_adm/evo/rsc/css/style.css', true );
		require_js( '#jquery#', 'blog' );
		require_js( 'jquery/jquery.raty.min.js', 'blog' );
	}


	/**
	 * Get the top of the HTML <body>.
	 *
	 * @uses get_page_head()
	 * @return string
	 */
	function get_body_top()
	{
		global $Messages;

		$r = '';

		$r .= $this->get_page_head();

		// Display MAIN menu:
		$r .= $this->get_html_menu().'

		<div id="panelbody" class="panelbody">
		';

		$r .= '

		<div id="payload">
		';

		$r .= $this->get_bloglist_buttons();

		// Display info & error messages
		$r .= $Messages->display( NULL, NULL, false, 'action_messages' );

		return $r;
	}


	/**
	 * Close open div(s).
	 *
	 * @return string
	 */
	function get_body_bottom()
	{
		global $rsc_url;

		$r = '';

		$r .= "\n\t</div>";

		$r .= "</div>\n";	// Close right col.

		$r .= '<img src="'.$rsc_url.'/img/blank.gif" width="1" height="1" alt="" />';

		return $r;
	}


	/**
	 * GLOBAL HEADER - APP TITLE, LOGOUT, ETC.
	 *
	 * @return string
	 */
	function get_page_head()
    {
		$r = '
		<div id="header">
			<h1>'.$this->get_title_for_titlearea().'</h1>
		</div>
		';

		return $r;
	}

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
				// main level
				global $app_shortname, $app_version;

				$r = parent::get_template( $name, $depth );
				$r['after'] = "</ul>\n<p class=\"center\">$app_shortname v <strong>$app_version</strong></p>\n</div>";
				return $r;
				break;


			case 'CollectionList':
				// Template for a list of Collections (Blogs)
				return array(
						'before' => '<div id="TitleArea">',
						'after' => '</div>',
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
	 * Get colors for page elements that can't be controlled by CSS (charts)
	 */
	function get_color( $what )
	{
		switch( $what )
		{
			case 'payload_background':
				return 'fbfbfb';
				break;
		}
		debug_die( 'unknown color' );
	}


}

/*
 * $Log$
 * Revision 1.40  2011/09/24 06:00:03  efy-yurybakh
 * star rating plugin (backoffice)
 *
 * Revision 1.39  2011/09/04 22:13:25  fplanque
 * copyright 2011
 *
 * Revision 1.38  2010/11/25 15:16:35  efy-asimo
 * refactor $Messages
 *
 * Revision 1.37  2010/02/08 17:56:48  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.36  2009/10/12 23:56:27  blueyed
 * Cleanup of messages handling, forgot to commit this.
 *
 * Revision 1.35  2009/10/12 22:11:28  blueyed
 * Fix blank.gif some: use conditional comments, where marked as being required for IE. Add ALT tags and close tags.
 *
 * Revision 1.34  2009/08/31 17:21:32  fplanque
 * minor
 *
 * Revision 1.33  2009/05/18 02:59:16  fplanque
 * Skins can now have an item.css file to specify content formats. Used in TinyMCE.
 * Note there are temporarily too many CSS files.
 * Two ways of solving is: smart resource bundles and/or merge files that have only marginal benefit in being separate
 *
 * Revision 1.32  2009/04/21 19:19:50  blueyed
 * doc/normalization
 *
 * Revision 1.31  2009/03/08 23:57:58  fplanque
 * 2009
 *
 * Revision 1.30  2009/03/07 21:35:03  blueyed
 * Fix indent, nuke globals.
 *
 * Revision 1.29  2009/01/01 02:19:26  blueyed
 * fix RECOMMIT
 *
 * Revision 1.28  2009/01/01 02:18:25  blueyed
 * RECOMMIT (1.26): Drop CSS title 'Blue', which is not required/used for skins_adm/evo/rsc/css/style.css
 *
 * Revision 1.27  2008/12/30 23:00:41  fplanque
 * Major waste of time rolling back broken black magic! :(
 * 1) It was breaking the backoffice as soon as $admin_url was not a direct child of $baseurl.
 * 2) relying on dynamic argument decoding for backward comaptibility is totally unmaintainable and unreliable
 * 3) function names with () in log break searches big time
 * 4) complexity with no purpose (at least as it was)
 *
 * Revision 1.24  2008/01/22 14:31:06  fplanque
 * minor
 *
 */
?>
