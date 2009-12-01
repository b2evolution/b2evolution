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

		$options = array();
		// PHP 4 replacement for array_combine():
		foreach( array_keys( $thumbnail_sizes ) as $thumb_size )
		{
			$options[$thumb_size] = $thumb_size;
		}

		$r = array_merge( array(
			'thumb_size' => array(
					'type' => 'select',
					'label' => T_('Image size'),
					'options' => $options,
					'note' => sprintf( /* TRANS: %s is a config variable name */ T_('List of available image sizes is defined in %s.'), '$thumbnail_sizes' ),
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

		$owner_User = & $Blog->get_owner_User();

		if( ! $owner_User->has_avatar() )
		{
			return false;
		}

		// START DISPLAY:
		echo $this->disp_params['block_start'];

		// Display title if requested
		$this->disp_title();

		echo $owner_User->get_link( array(
				'link_to'		   => 'userpage',  // TODO: make configurable $this->disp_params['link_to']
				'link_text'    => 'avatar',
				'thumb_size'	 => $this->disp_params['thumb_size'],
			) );

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
		global $Blog;

		$owner_User = & $Blog->get_owner_User();

		return array(
				'wi_ID'   => $this->ID,					// Have the widget settings changed ?
				'set_coll_ID' => $Blog->ID,			// Have the settings of the blog changed ? (ex: new owner, new skin)
				'user_ID' => $owner_User->ID, 	// Has the owner User changed? (name, avatar, etc..)
			);
	}
}


/*
 * $Log$
 * Revision 1.7  2009/12/01 04:19:25  fplanque
 * even more invalidation dimensions
 *
 * Revision 1.6  2009/12/01 03:45:37  fplanque
 * multi dimensional invalidation
 *
 * Revision 1.5  2009/10/03 21:00:50  tblue246
 * Bugfixes
 *
 * Revision 1.4  2009/09/30 19:09:39  blueyed
 * trans fix
 *
 * Revision 1.3  2009/09/20 01:35:52  fplanque
 * Factorized User::get_link()
 *
 * Revision 1.2  2009/09/20 00:51:43  fplanque
 * OMG!!
 *
 * Revision 1.1  2009/09/20 00:33:59  blueyed
 * Add widget to display avatar of collection/blog owner. Install it for all new blogs by default.
 *
 */
?>
