<?php
/**
 * This file implements the xyz Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
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
		return T_('List of all available XML feeds.');
	}


  /**
   * Get definitions for editable params
   *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		global $use_strict;
		$r = array_merge( array(
				'title' => array(
					'label' => T_( 'Title' ),
					'size' => 40,
					'note' => T_( 'This is the title to display, $icon$ will be replaced by the feed icon' ),
					'defaultvalue' => '$icon$ '.T_('XML Feeds'),
				),
				'disp_info_link' => array(
					'label' => T_( 'Help link' ),
					'type' => 'checkbox',
					'note' => T_( 'Check this to display "What is RSS?" link' ),
					'defaultvalue' => 1,
				),
				'info_link' => array(
					'label' => T_( 'New Window' ),
					'type' => 'checkbox',
					'note' => T_( 'Check this to add target="_blank" to the "What is RSS?" link' ),
					'defaultvalue' => !$use_strict,
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
		global $rsc_url;
		global $Blog;

		$this->init_display( $params );

		// Available XML feeds:
		echo $this->disp_params['block_start'];

		$title = str_replace( '$icon$', '<img src="'.$rsc_url.'icons/feed-icon-16x16.gif" width="16" height="16" class="top" alt="" /> ', $this->disp_params['title']);
			// fp> TODO: support for different icon sizes and backgrounds (at least black and white; mid grey would be cool also)
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


		// Display "info" link, if activated.
		if( $this->disp_params['disp_info_link'] )
		{
			/**
			 * @var AbstractSettings
			 */
			global $global_Cache;

			$feedhlp = $global_Cache->get( 'feedhlp' );
			if( empty( $feedhlp ) )
			{	// Use basic default: (fp> needs serious update)
				$feedhlp = array( array( 'http://webreference.fr/2006/08/30/rss_atom_xml', 'What is RSS?' ) );
			}

			if( $this->disp_params[ 'info_link' ] )
			{
				$link_params = array( 'target' => '_blank' );
			}
			else
			{
				$link_params = array( 'target' => '' );
			}
			display_list( $feedhlp, $this->disp_params['notes_start'], $this->disp_params['notes_end'], ' ', '', '', NULL, 1, $link_params );
		}

		echo $this->disp_params['block_end'];

		return true;
	}
}


/*
 * $Log$
 * Revision 1.12  2009/03/04 00:53:42  fplanque
 * minor
 *
 * Revision 1.11  2009/02/27 19:51:35  blueyed
 * XML feeds widget: add a 'disp_info_link' config option, defaulting to false. Since the help is very outdated, it makes no sense to eventually load the global_Cache just for that. Also makes the widget cleaner. Might default to true, when it provides more valuable info.
 *
 * Revision 1.10  2008/09/15 03:12:22  fplanque
 * help link update
 *
 * Revision 1.9  2008/05/06 23:35:47  fplanque
 * The correct way to add linebreaks to widgets is to add them to $disp_params when the container is called, right after the array_merge with defaults.
 *
 * Revision 1.7  2008/01/21 09:35:37  fplanque
 * (c) 2008
 *
 * Revision 1.6  2007/12/23 16:16:18  fplanque
 * Wording improvements
 *
 * Revision 1.5  2007/12/23 14:14:25  fplanque
 * Enhanced widget name display
 *
 * Revision 1.4  2007/12/22 19:55:00  yabs
 * cleanup from adding core params
 *
 * Revision 1.3  2007/11/28 17:50:24  fplanque
 * normalization
 *
 * Revision 1.2  2007/11/27 10:02:04  yabs
 * added params
 *
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