<?php
/**
 * This file implements the xyz Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2014 by Francois Planque - {@link http://fplanque.com/}
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
 * @version $Id: _coll_tagline.widget.php 6411 2014-04-07 15:17:33Z yura $
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
class coll_tagline_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function coll_tagline_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'coll_tagline' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Blog tagline');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		global $Blog;

		return $Blog->dget( 'tagline', 'htmlbody' );
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		global $Blog;
		return sprintf( T_('&laquo;%s&raquo; from the blog\'s <a %s>general settings</a>.'),
				'<strong>'.$Blog->dget('tagline').'</strong>', 'href="?ctrl=coll_settings&tab=general&blog='.$Blog->ID.'"' );
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

		// Collection tagline:
		echo $this->disp_params['block_start'];
		echo $this->disp_params['block_body_start'];
		// TODO: there appears to be no possibility to wrap the tagline in e.g. "<h2>%s</h2>"
		//       Should there be a widget param for this?  fp> probably yes
		$Blog->disp( 'tagline', 'htmlbody' );
		echo $this->disp_params['block_body_end'];
		echo $this->disp_params['block_end'];

		return true;
	}
}

?>