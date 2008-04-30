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
		return T_('Content Search Form');
	}


  /**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display search form');
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
		echo "\n" . $this->disp_params['block_start'] . "\n";

		$this->disp_title( T_('Search') );

		form_formstart( $Blog->gen_blogurl(), 'search', 'SearchForm' );
		echo '<p>' . "\n";
		$s = get_param( 's' );
		echo '<input type="text" name="s" size="25" value="'.htmlspecialchars($s).'" class="SearchField" /><br />' . "\n";
		$sentence = get_param( 'sentence' );
		echo '<input type="radio" name="sentence" value="AND" id="sentAND" '.( $sentence=='AND' ? 'checked="checked" ' : '' ).'/><label for="sentAND">'.T_('All Words').'</label><br />' . "\n";
		echo '<input type="radio" name="sentence" value="OR" id="sentOR" '.( $sentence=='OR' ? 'checked="checked" ' : '' ).'/><label for="sentOR">'.T_('Some Word').'</label><br />' . "\n";
		echo '<input type="radio" name="sentence" value="sentence" id="sentence" '.( $sentence=='sentence' ? 'checked="checked" ' : '' ).'/><label for="sentence">'.T_('Entire phrase').'</label>' . "\n";
		echo '</p>' . "\n";
		echo '<input type="submit" name="submit" class="submit search_submit" value="'.T_('Search').'" />' . "\n";
		echo '</form>' . "\n";

		echo $this->disp_params['block_end'] . "\n";

		return true;
	}
}


/*
 * $Log$
 * Revision 1.5  2008/04/30 13:05:03  afwas
 * Added linebreaks
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