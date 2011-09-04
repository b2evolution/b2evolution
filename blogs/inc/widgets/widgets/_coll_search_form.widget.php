<?php
/**
 * This file implements the xyz Widget class.
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

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class coll_search_form_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function coll_search_form_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'coll_search_form' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Search Form');
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
		return T_('Display search form');
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
					'defaultvalue' => T_('Search'),
				),
				'disp_search_options' => array(
					'label' => T_( 'Search options' ),
					'note' => T_( 'Display radio buttons for "All Words", "Some Word" and "Entire Phrase"' ),
					'type' => 'checkbox',
					'defaultvalue' => false,
				),
				'use_search_disp' => array(
					'label' => T_( 'Results on search page' ),
					'note' => T_( 'Use advanced search page to display results (disp=search)' ),
					'type' => 'checkbox',
					'defaultvalue' => true,
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
		global $Blog;

		$this->init_display( $params );

		// Collection search form:
		echo $this->disp_params['block_start'];

		$this->disp_title();

		form_formstart( $Blog->gen_blogurl(), 'search', 'SearchForm' );
		if( $this->disp_params[ 'disp_search_options' ] )
		{
			echo '<div class="extended_search_form">';
		}
		else
		{
			echo '<div class="compact_search_form">';
		}
		$s = get_param( 's' );
		echo '<input type="text" name="s" size="25" value="'.htmlspecialchars($s).'" class="search_field SearchField" />';

		if( $this->disp_params[ 'disp_search_options' ] )
		{
			$sentence = get_param( 'sentence' );
			echo '<div class="search_options">';
			echo '<div class="search_option"><input type="radio" name="sentence" value="AND" id="sentAND" '.( $sentence=='AND' ? 'checked="checked" ' : '' ).'/><label for="sentAND">'.T_('All words').'</label></div>';
			echo '<div class="search_option"><input type="radio" name="sentence" value="OR" id="sentOR" '.( $sentence=='OR' ? 'checked="checked" ' : '' ).'/><label for="sentOR">'.T_('Some word').'</label></div>';
			echo '<div class="search_option"><input type="radio" name="sentence" value="sentence" id="sentence" '.( $sentence=='sentence' ? 'checked="checked" ' : '' ).'/><label for="sentence">'.T_('Entire phrase').'</label></div>';
			echo '</div>';
		}
		if( $this->disp_params[ 'use_search_disp' ] )
		{
			echo '<input type="hidden" name="disp" value="search" />';
		}
		echo '<input type="submit" name="submit" class="search_submit submit" value="'.T_('Search').'" />';
		echo '</div>';
		echo '</form>';

		echo $this->disp_params['block_end'];

		return true;
	}
}


/*
 * $Log$
 * Revision 1.20  2011/09/04 22:13:21  fplanque
 * copyright 2011
 *
 * Revision 1.19  2010/02/08 17:54:48  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.18  2009/12/22 23:13:39  fplanque
 * Skins v4, step 1:
 * Added new disp modes
 * Hooks for plugin disp modes
 * Enhanced menu widgets (BIG TIME! :)
 *
 * Revision 1.17  2009/12/20 21:05:10  fplanque
 * New default widget styles
 *
 * Revision 1.16  2009/10/21 22:12:44  blueyed
 * whoops. fix is to use DIVs instead.
 *
 * Revision 1.15  2009/10/21 22:09:52  blueyed
 * Remove wrong closing P tag.
 *
 * Revision 1.14  2009/09/14 13:54:13  efy-arrin
 * Included the ClassName in load_class() call with proper UpperCase
 *
 * Revision 1.13  2009/09/12 11:03:13  efy-arrin
 * Included the ClassName in the loadclass() with proper UpperCase
 *
 * Revision 1.12  2009/09/10 13:44:57  tblue246
 * Translation fixes/update
 *
 * Revision 1.11  2009/03/13 02:32:07  fplanque
 * Cleaned up widgets.
 * Removed stupid widget_name param.
 *
 * Revision 1.10  2009/03/08 23:57:46  fplanque
 * 2009
 *
 * Revision 1.9  2009/02/07 10:09:56  yabs
 * Validation
 *
 * Revision 1.8  2008/05/26 19:02:28  fplanque
 * enhanced search widget
 *
 * Revision 1.7  2008/05/11 01:06:40  fplanque
 * OMG I'm pathetic :/
 *
 * Revision 1.6  2008/05/06 23:34:25  fplanque
 * reverted my own screwup on the search_submit class
 *
 * Revision 1.4  2008/04/30 04:18:34  afwas
 * Combined class submit and class search_submit
 *
 * Revision 1.3  2008/04/26 22:20:45  fplanque
 * Improved compatibility with older skins.
 *
 * Revision 1.2  2008/01/21 09:35:37  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 11:02:20  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.3  2007/06/23 22:05:16  fplanque
 * fixes
 *
 * Revision 1.2  2007/06/20 21:42:13  fplanque
 * implemented working widget/plugin params
 *
 * Revision 1.1  2007/06/18 21:25:47  fplanque
 * one class per core widget
 *
 */
?>