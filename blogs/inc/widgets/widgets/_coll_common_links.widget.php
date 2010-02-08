<?php
/**
 * This file implements the coll_common_links_Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
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

/**
 * ComponentWidget: Common navigation links.
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @todo dh> why are "STRONG" tags hardcoded here? can this get dropped/removed? should the style
 *           get adjusted to use font-weight:bold then?
 * fp> yes but make sure to put the font-weight in a place where it applies to all (existing) skins by default; e-g blog_base.css
 *
 * @package evocore
 */
class coll_common_links_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function coll_common_links_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'coll_common_links' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Common Navigation Links');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output($this->disp_params['title']);
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display these links: Recently, Archives, Categories, Latest Comments');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		$r = array_merge( array(
				'title' => array(
					'label' => T_('Block title'),
					'note' => T_( 'Title to display in your skin.' ),
					'size' => 40,
					'defaultvalue' => '',
				),
				'show_recently' => array(
					'type' => 'checkbox',
					'label' => T_('Show "Recently"'),
					'note' => T_('Go to the most recent posts / the blog\'s home.'),
					'defaultvalue' => 1,
				),
				'show_search' => array(
					'type' => 'checkbox',
					'label' => T_('Show "Search"'),
					'note' => T_('Go to the search page.'),
					'defaultvalue' => 0,
				),
				'show_postidx' => array(
					'type' => 'checkbox',
					'label' => T_('Show "Post index"'),
					'note' => T_('Go to the post index.'),
					'defaultvalue' => 0,
				),
				'show_archives' => array(
					'type' => 'checkbox',
					'label' => T_('Show "Archives"'),
					'note' => T_('Go to the monthly/weekly/daily archive list.'),
					'defaultvalue' => 1,
				),
				'show_categories' => array(
					'type' => 'checkbox',
					'label' => T_('Show "Categories"'),
					'note' => T_('Go to the category tree.'),
					'defaultvalue' => 1,
				),
				'show_mediaidx' => array(
					'type' => 'checkbox',
					'label' => T_('Show "Photo index"'),
					'note' => T_('Go to the photo index / contact sheet.'),
					'defaultvalue' => 0,
				),
				'show_latestcomments' => array(
					'type' => 'checkbox',
					'label' => T_('Show "Latest comments"'),
					'note' => T_('Go to the latest comments.'),
					'defaultvalue' => 1,
				),
				'show_owneruserinfo' => array(
					'type' => 'checkbox',
					'label' => T_('Show "Owner details"'),
					'note' => T_('Go to user info about the blog owner.'),
					'defaultvalue' => 0,
				),
				'show_ownercontact' => array(
					'type' => 'checkbox',
					'label' => T_('Show "Contact"'),
					'note' => T_('Go to message form to contact the blog owner.'),
					'defaultvalue' => 0,
				),
				'show_sitemap' => array(
					'type' => 'checkbox',
					'label' => T_('Show "Site map"'),
					'note' => T_('Go to site map (HTML version).'),
					'defaultvalue' => 0,
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

		// Collection common links:
		echo $this->disp_params['block_start'];

		// Display title if requested
		$this->disp_title();

		echo $this->disp_params['list_start'];

		if( $this->disp_params['show_recently'] )
		{
			echo $this->disp_params['item_start'];
			echo '<strong><a href="'.$Blog->get('url').'">'.T_('Recently').'</a></strong>';
			echo $this->disp_params['item_end'];
		}

		if( $this->disp_params['show_search'] )
		{
			echo $this->disp_params['item_start'];
			echo '<strong><a href="'.$Blog->get('searchurl').'">'.T_('Search').'</a></strong>';
			echo $this->disp_params['item_end'];
		}

		if( $this->disp_params['show_postidx'] )
		{
			echo $this->disp_params['item_start'];
			echo '<strong><a href="'.$Blog->get('postidxurl').'">'.T_('Post index').'</a></strong>';
			echo $this->disp_params['item_end'];
		}

		if( $this->disp_params['show_archives'] )
		{
			// fp> TODO: don't display this if archives plugin not installed... or depluginize archives (I'm not sure)
			echo $this->disp_params['item_start'];
			echo '<strong><a href="'.$Blog->get('arcdirurl').'">'.T_('Archives').'</a></strong>';
			echo $this->disp_params['item_end'];
		}

		if( $this->disp_params['show_categories'] )
		{
			echo $this->disp_params['item_start'];
			echo '<strong><a href="'.$Blog->get('catdirurl').'">'.T_('Categories').'</a></strong>';
			echo $this->disp_params['item_end'];
		}

		if( $this->disp_params['show_mediaidx'] )
		{
			echo $this->disp_params['item_start'];
			echo '<strong><a href="'.$Blog->get('mediaidxurl').'">'.T_('Photo index').'</a></strong>';
			echo $this->disp_params['item_end'];
		}

		if( $this->disp_params['show_latestcomments'] )
		{
			echo $this->disp_params['item_start'];
			echo '<strong><a href="'.$Blog->get('lastcommentsurl').'">'.T_('Latest comments').'</a></strong>';
			echo $this->disp_params['item_end'];
		}

		if( $this->disp_params['show_owneruserinfo'] )
		{
			echo $this->disp_params['item_start'];
			echo '<strong><a href="'.$Blog->get('userurl').'">'.T_('Owner details').'</a></strong>';
			echo $this->disp_params['item_end'];
		}

		if( $this->disp_params['show_ownercontact'] && $url = $Blog->get_contact_url( true ) )
		{	// owner allows contact:
			echo $this->disp_params['item_start'];
			echo '<strong><a href="'.$url.'">'.T_('Contact').'</a></strong>';
			echo $this->disp_params['item_end'];
		}

		if( $this->disp_params['show_sitemap'] )
		{
			echo $this->disp_params['item_start'];
			echo '<strong><a href="'.$Blog->get('sitemapurl').'">'.T_('Site map').'</a></strong>';
			echo $this->disp_params['item_end'];
		}

		echo $this->disp_params['list_end'];
		echo $this->disp_params['block_end'];

		return true;
	}
}


/*
 * $Log$
 * Revision 1.16  2010/02/08 17:54:47  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.15  2010/02/06 23:10:50  sam2kb
 * minor
 *
 * Revision 1.14  2009/12/22 23:13:39  fplanque
 * Skins v4, step 1:
 * Added new disp modes
 * Hooks for plugin disp modes
 * Enhanced menu widgets (BIG TIME! :)
 *
 * Revision 1.13  2009/09/14 13:54:13  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.12  2009/09/12 11:03:13  efy-arrin
 * Included the ClassName in the loadclass() with proper UpperCase
 *
 * Revision 1.11  2009/09/10 13:44:57  tblue246
 * Translation fixes/update
 *
 * Revision 1.10  2009/05/18 03:59:39  fplanque
 * minor/doc
 *
 * Revision 1.9  2009/04/06 23:19:07  blueyed
 * coll_common_links_Widget: TODO about hardcoded strong tags, doc, indent.
 *
 * Revision 1.8  2009/03/13 02:32:07  fplanque
 * Cleaned up widgets.
 * Removed stupid widget_name param.
 *
 * Revision 1.7  2009/03/08 23:57:46  fplanque
 * 2009
 *
 * Revision 1.6  2008/05/06 23:35:47  fplanque
 * The correct way to add linebreaks to widgets is to add them to $disp_params when the container is called, right after the array_merge with defaults.
 *
 * Revision 1.4  2008/01/21 09:35:37  fplanque
 * (c) 2008
 *
 * Revision 1.3  2007/12/23 14:14:25  fplanque
 * Enhanced widget name display
 *
 * Revision 1.2  2007/12/22 19:55:00  yabs
 * cleanup from adding core params
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