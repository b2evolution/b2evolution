<?php
/**
 * This file implements the coll_avatar_Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2009 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2008 by Daniel HAHLER - {@link http://daniel.hahler.de/}.
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
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );

/**
 * coll_avatar_Widget Class.
 *
 * This displays the blog owner's avatar.
 *
 * @package evocore
 */
class coll_avatar_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function coll_avatar_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'coll_avatar' );
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('Avatar');
	}


	/**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display the avatar of the blog owner.');
	}


	/**
	 * Get definitions for editable params
	 *
	 * @see Plugin::GetDefaultSettings()
	 * @param array local params
	 *  - 'size': Size definition, see {@link $thumbnail_sizes}. E.g. 'fit-160x160'.
	 */
	function get_param_definitions( $params )
	{
		global $thumbnail_sizes;

		$options = array_combine( array_keys($thumbnail_sizes), array_keys($thumbnail_sizes) );
		$r = array_merge( array(
			'size' => array(
					'type' => 'select',
					'label' => T_('Image size'),
					'options' => $options,
					'note' => T_('List of available image sizes is defined in $thumbnail_sizes.'),
					'defaultvalue' => 'fit-160x160',
				),
			), parent::get_param_definitions( $params ) );

		return $r;
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $cat_modifier;
		global $Blog;

		$this->init_display( $params );

		$owner_User = $Blog->get_owner_User();
		$img_tag = $owner_User->get_avatar_imgtag($this->disp_params['size']);

		if( ! $img_tag )
		{
			return;
		}

		// START DISPLAY:
		echo $this->disp_params['block_start'];

		// Display title if requested
		$this->disp_title();

		echo $img_tag;

		echo $this->disp_params['block_end'];

		return true;
	}

}


/*
 * $Log$
 * Revision 1.2  2009/09/20 00:51:43  fplanque
 * OMG!!
 *
 * Revision 1.1  2009/09/20 00:33:59  blueyed
 * Add widget to display avatar of collection/blog owner. Install it for all new blogs by default.
 *
 */
?>
