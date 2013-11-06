<?php
/**
 * This file implements the Mobile Skin Switcher Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
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
class mobile_skin_switcher_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function mobile_skin_switcher_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'mobile_skin_switcher' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_( 'Mobile Skin Switcher' );
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 *
	 * @return string The block title, the first 60 characters of the block
	 *                content or an empty string.
	 */
	function get_short_desc()
	{
		if( empty( $this->disp_params['title'] ) )
		{
			return strmaxlen( $this->disp_params['content'], 60, NULL, /* use htmlspecialchars() */ 'formvalue' );
		}

		return format_to_output( $this->disp_params['title'] );
	}


  /**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Mobile Skin Switcher.');
	}


  /**
   * Get definitions for editable params
   *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		// Demo data:
		$r = array_merge( array(
				'title' => array(
					'label' => T_('Title'),
					'size' => 60,
					'defaultvalue' => T_('Mobile skin'),
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
		$this->init_display( $params );

		// Collection common links:
		echo $this->disp_params['block_start'];

		echo '<div id="switch">'.$this->disp_params['title'].':
			<div>
				<a href="'.$_SERVER['REQUEST_URI'].'#switch" id="switch-link">
					<span class="on active">'.T_('ON').'</span>
					<span class="off">'.T_('OFF').'</span>
				</a>
			</div>
		</div>';

		echo $this->disp_params['block_end'];

		return true;
	}
}


/*
 * $Log$
 * Revision 1.2  2013/11/06 08:05:09  efy-asimo
 * Update to version 5.0.1-alpha-5
 *
 */
?>