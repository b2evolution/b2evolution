<?php
/**
 * This file implements the xyz Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}
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

load_class( 'widgets/model/_widget.class.php' );

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class coll_xml_feeds_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function coll_xml_feeds_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'coll_xml_feeds' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('XML Feeds (RSS / Atom)');
	}


  /**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display list of all available XML feeds.');
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $rsc_url;
		global $Blog;

		$this->init_display( $params );

		// Available XML feeds:
		echo $this->disp_params['block_start'];

		$title = '<img src="'.$rsc_url.'icons/feed-icon-16x16.gif" width="16" height="16" class="top" alt="" /> '.T_('XML Feeds');
		$this->disp_title( $title );

		echo $this->disp_params['list_start'];

		$SkinCache = & get_Cache( 'SkinCache' );
		$SkinCache->load_by_type( 'feed' );

		// TODO: this is like touching private parts :>
		foreach( $SkinCache->cache as $Skin )
		{
			if( $Skin->type != 'feed' )
			{	// This skin cannot be used here...
				continue;
			}

			echo $this->disp_params['item_start'];
			echo $Skin->name.': ';
			echo '<a href="'.$Blog->get_item_feed_url( $Skin->folder ).'">'.T_('Posts').'</a>, ';
			echo '<a href="'.$Blog->get_comment_feed_url( $Skin->folder ).'">'.T_('Comments').'</a>';
			echo $this->disp_params['item_end'];
		}

		echo $this->disp_params['list_end'];

		echo $this->disp_params['notes_start'];
		echo '<a href="http://webreference.fr/2006/08/30/rss_atom_xml" target="_blank" title="External - English">What is RSS?</a>';
		echo $this->disp_params['notes_end'];

		echo $this->disp_params['block_end'];

		return true;
	}
}


/*
 * $Log$
 * Revision 1.1  2007/06/25 11:02:23  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.2  2007/06/20 21:42:13  fplanque
 * implemented working widget/plugin params
 *
 * Revision 1.1  2007/06/18 21:25:47  fplanque
 * one class per core widget
 *
 */
?>