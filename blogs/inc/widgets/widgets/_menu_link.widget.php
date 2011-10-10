<?php
/**
 * This file implements the menu_link_Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );

global $menu_link_widget_link_types;
$menu_link_widget_link_types = array(
		'home' => T_('Blog home'),
		'arcdir' => T_('Archive directory'),
		'catdir' => T_('Category directory'),
		'postidx' => T_('Post index'),
		'mediaidx' => T_('Photo index'),
		'sitemap' => T_('Site Map'),
		'latestcomments' => T_('Latest comments'),
		'owneruserinfo' => T_('Blog owner details'),
		'ownercontact' => T_('Blog owner contact form'),
		'search' => T_('Search page'),
		'login' => T_('Log in form'),
		'register' => T_('Registration form'),
		'profile' => T_('Profile form'),
		'avatar' => T_('Profile picture editing'),
		'users' => T_('User directory'),
		'item' => T_('Any item (post, page, etc...)'),
		'url' => T_('Any URL'),
	);

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @todo dh> this needs to implement BlockCaching cache_keys properly:
 *            - "login": depends on $currentUser being set or not
 *            ...
 *
 * @package evocore
 */
class menu_link_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function menu_link_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'menu_link' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Menu link');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		global $menu_link_widget_link_types;

		$this->load_param_array();


		if( !empty($this->param_array['link_text']) )
		{	// We have a custom link text:
			return $this->param_array['link_text'];
		}

		if( !empty($this->param_array['link_type']) )
		{	// TRANS: %s is the link type, e. g. "Blog home" or "Log in form"
			return sprintf( T_( '%s link' ), $menu_link_widget_link_types[$this->param_array['link_type']] );
		}

		return $this->get_name();
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display a configurable menu entry/link');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		global $menu_link_widget_link_types;

		$r = array_merge( array(
				'link_type' => array(
					'label' => T_( 'Link Type' ),
					'note' => T_('What do you want to link to?'),
					'type' => 'select',
					'options' => $menu_link_widget_link_types,
					'defaultvalue' => 'home',
				),
				'link_text' => array(
					'label' => T_('Link text'),
					'note' => T_( 'Text to use for the link (leave empty for default).' ),
					'type' => 'text',
					'size' => 20,
					'defaultvalue' => '',
				),
				// fp> TODO: ideally we would have a link icon to go click on the destination...
				'item_ID' => array(
					'label' => T_('Item ID'),
					'note' => T_( 'ID of post, page, etc. for "Item" type links.' ),
					'type' => 'text',
					'size' => 5,
					'defaultvalue' => '',
				),
				'link_href' => array(
					'label' => T_('URL'),
					'note' => T_( 'Destination URL for "URL" type links.' ),
					'type' => 'text',
					'size' => 30,
					'defaultvalue' => '',
				),
			), parent::get_param_definitions( $params )	);

		return $r;
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		/**
		* @var Blog
		*/
		global $Blog;

		$this->init_display( $params );

		switch(	$this->disp_params['link_type'] )
		{
			case 'search':
				$url = $Blog->get('searchurl');
				$text = T_('Search');
				break;

			case 'arcdir':
				$url = $Blog->get('arcdirurl');
				$text = T_('Archives');
				break;

			case 'catdir':
				$url = $Blog->get('catdirurl');
				$text = T_('Categories');
				break;

			case 'postidx':
				$url = $Blog->get('postidxurl');
				$text = T_('Post index');
				break;

			case 'mediaidx':
				$url = $Blog->get('mediaidxurl');
				$text = T_('Photo index');
				break;

			case 'sitemap':
				$url = $Blog->get('sitemapurl');
				$text = T_('Site map');
				break;

			case 'latestcomments':
				$url = $Blog->get('lastcommentsurl');
				$text = T_('Latest comments');
				break;

			case 'owneruserinfo':
				$url = $Blog->get('userurl');
				$text = T_('Owner details');
				break;

			case 'ownercontact':
				if( ! $url = $Blog->get_contact_url( true ) )
				{ // user does not allow contact form:
					return;
				}
				$text = T_('Contact');
				break;

			case 'login':
				if( is_logged_in() ) return false;
				$url = get_login_url( 'menu link' );
				if( isset($this->BlockCache) )
				{	// Do NOT cache because some of these links are using a redirect_to param, which makes it page dependent.
					// so this will be cached by the PageCache; there is no added benefit to cache it in the BlockCache
					// (which could have been shared between several pages):
					$this->BlockCache->abort_collect();
				}

				$text = T_('Log in');
				break;

			case 'register':
				if( ! $url = get_user_register_url( NULL, 'menu link' ) )
				{
					return false;
				}
				if( isset($this->BlockCache) )
				{	// Do NOT cache because some of these links are using a redirect_to param, which makes it page dependent.
					// Note: also beware of the source param.
					// so this will be cached by the PageCache; there is no added benefit to cache it in the BlockCache
					// (which could have been shared between several pages):
					$this->BlockCache->abort_collect();
				}

				$text = T_('Register');
				break;

			case 'profile':
				if( ! is_logged_in() ) return false;
				$url = get_user_profile_url();
				$text = T_('Profile');
				break;

			case 'avatar':
				if( ! is_logged_in() ) return false;
				$url = get_user_avatar_url();
				$text = T_('Profile picture');
				break;

			case 'users':
				global $Settings;
				if( ! is_logged_in() && ! $Settings->get( 'allow_anonymous_user_list' ) )
				{	// Don't allow anonymous users to see users list
					return false;
				}
				$url = $Blog->get( 'usersurl' );
				$text = T_('User directory');
				break;

			case 'item':
				$ItemCache = & get_ItemCache();
				/**
				* @var Item
				*/
				$item_ID = (integer)($this->disp_params['item_ID']);
				$Item = & $ItemCache->get_by_ID( $item_ID, false, false );
				if( empty($Item) )
				{	// Item not found
					return false;
				}
				$url = $Item->get_permanent_url();
				$text = $Item->title;
				break;

			case 'url':
				$url = $this->disp_params['link_href'];
				$text = '[URL]';	// should normally be overriden below...
				break;

			case 'home':
			default:
				$url = $Blog->get('url');
				$text = T_('Home');
		}

		// Override default link text?
		if( !empty($this->param_array['link_text']) )
		{	// We have a custom link text:
			$text = $this->param_array['link_text'];
		}

		echo $this->disp_params['block_start'];
		echo $this->disp_params['list_start'];

		echo $this->disp_params['item_start'];
		echo '<a href="'.$url.'">'.$text.'</a>';
		echo $this->disp_params['item_end'];

		echo $this->disp_params['list_end'];
		echo $this->disp_params['block_end'];

		return true;
	}


	/**
	 * Maybe be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @return array of keys this widget depends on
	 */
	function get_cache_keys()
	{
		global $Blog, $current_User;

		$keys = array(
				'wi_ID'   => $this->ID,					// Have the widget settings changed ?
				'set_coll_ID' => $Blog->ID			// Have the settings of the blog changed ? (ex: new owner, new skin)
			);

		switch( $this->disp_params['link_type'] )
		{
			case 'login':  		/* This one will probably abort caching by itself anyways */
			case 'register':	/* This one will probably abort caching by itself anyways */
			case 'profile':		// This can be cached
			case 'avatar':
				// This link also depends on whether or not someone is logged in:
				$keys['loggedin'] = (is_logged_in() ? 1 : 0);

		}

		return $keys;
	}
}


/*
 * $Log$
 * Revision 1.25  2011/10/10 20:46:39  fplanque
 * registration source tracking
 *
 * Revision 1.24  2011/10/10 19:48:31  fplanque
 * i18n & login display cleaup
 *
 * Revision 1.23  2011/10/07 11:24:53  efy-yurybakh
 * Extend  _menu_link.widget.php
 *
 * Revision 1.22  2011/09/08 23:29:27  fplanque
 * More blockcache/widget fixes around login/register links.
 *
 * Revision 1.21  2011/09/07 18:25:11  fplanque
 * widget & blockcache fixes
 *
 * Revision 1.20  2011/09/04 22:13:21  fplanque
 * copyright 2011
 *
 * Revision 1.19  2010/04/22 22:49:28  blueyed
 * menu widgets: doc/todo/indent
 *
 * Revision 1.18  2010/02/08 17:54:48  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.17  2009/12/22 23:13:39  fplanque
 * Skins v4, step 1:
 * Added new disp modes
 * Hooks for plugin disp modes
 * Enhanced menu widgets (BIG TIME! :)
 *
 * Revision 1.16  2009/09/14 13:54:13  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.15  2009/09/12 11:03:13  efy-arrin
 * Included the ClassName in the loadclass() with proper UpperCase
 *
 * Revision 1.14  2009/07/02 00:50:59  fplanque
 * doc.
 *
 * Revision 1.13  2009/07/01 09:47:51  yabs
 * bug fix
 *
 * Revision 1.12  2009/05/31 20:57:27  tblue246
 * Translation fix/minor
 *
 * Revision 1.11  2009/04/12 21:52:56  tblue246
 * Fixed translation string
 *
 * Revision 1.10  2009/04/12 09:41:16  tblue246
 * Fix translation string
 *
 * Revision 1.9  2009/03/13 02:32:07  fplanque
 * Cleaned up widgets.
 * Removed stupid widget_name param.
 *
 * Revision 1.8  2009/03/08 23:57:46  fplanque
 * 2009
 *
 * Revision 1.7  2008/01/21 09:35:37  fplanque
 * (c) 2008
 *
 * Revision 1.6  2007/12/23 16:16:18  fplanque
 * Wording improvements
 *
 * Revision 1.5  2007/12/23 15:07:07  fplanque
 * Clean widget name
 *
 * Revision 1.3  2007/12/23 14:14:25  fplanque
 * Enhanced widget name display
 *
 * Revision 1.2  2007/12/22 19:55:00  yabs
 * cleanup from adding core params
 *
 * Revision 1.1  2007/09/28 02:17:48  fplanque
 * Menu widgets
 *
 * Revision 1.1  2007/06/25 11:02:06  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.3  2007/06/20 21:42:13  fplanque
 * implemented working widget/plugin params
 *
 * Revision 1.2  2007/06/20 00:48:17  fplanque
 * some real life widget settings
 *
 * Revision 1.1  2007/06/18 21:25:47  fplanque
 * one class per core widget
 *
 */
?>
