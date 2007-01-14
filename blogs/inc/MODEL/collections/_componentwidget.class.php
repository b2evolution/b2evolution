<?php
/**
 * This file implements the Widget class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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

global $core_componentwidget_defs;
$core_componentwidget_defs = array(
		'coll_title'        => NT_('Blog Title'),
		'coll_tagline'      => NT_('Blog Tagline'),
		'coll_longdesc'     => NT_('Long Description of this Blog'),
		'coll_common_links' => NT_('Common Navigation Links'),
		'coll_search_form'  => NT_('Content Search Form'),
		'coll_xml_feeds'    => NT_('XML Feeds (RSS / Atom)'),
		'user_tools'        => NT_('User Tools'),
		'admin_help'        => NT_('Admin Help'),
	);

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class ComponentWidget extends DataObject
{
	var $coll_ID;
	/**
	 * Container name
	 */
	var $sco_name;
	var $order;
	var $type;
	var $code;
	var $params;

	/**
	 * Lazy instantiated
	 * (false if this Widget is not handled by a Plugin)
	 * @var Plugin
	 */
	var $Plugin = NULL;


	/**
	 * Constructor
	 */
	function ComponentWidget( $db_row = NULL, $type = 'core', $code = NULL, $params = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_widget', 'wi_', 'wi_ID' );

		if( is_null($db_row) )
		{	// We are creating an object here:
			$this->set( 'type', $type );
			$this->set( 'code', $code );
			// $this->set( 'params', $params );
		}
		else
		{	// Wa are loading an object:
			$this->ID       = $db_row->wi_ID;
			$this->coll_ID  = $db_row->wi_coll_ID;
			$this->sco_name = $db_row->wi_sco_name;
			$this->type     = $db_row->wi_type;
			$this->code     = $db_row->wi_code;
			$this->params   = $db_row->wi_params;
		}
	}


	/**
	 * Get ref to Plugin handling this Widget
	 *
	 * @return Plugin
	 */
	function & get_Plugin()
	{
		global $Plugins;

		if( is_null( $this->Plugin ) )
		{
			if( $this->type != 'plugin' )
			{
				$this->Plugin = false;
			}
			else
			{
				$this->Plugin = & $Plugins->get_by_code( $this->code );
			}
		}

		return $this->Plugin;
	}


	/**
	 * Get name of widget
	 */
	function get_name()
	{
		global $core_componentwidget_defs;

		switch( $this->type )
		{
			case 'core':
				if( ! empty( $core_componentwidget_defs[ $this->code ] ) )
				{
					return T_($core_componentwidget_defs[ $this->code ]);
				}
				break;

			case 'plugin':
				// Make sure Plugin is loaded:
				if( $this->get_Plugin() )
				{
					return $this->Plugin->name;
				}
				return T_('Inactive / Uninstalled plugin');
				break;
		}

		return T_('Unknown');
	}


	/**
	 * Display the widget!
	 *
	 * @todo fp> handle custom params for each widget
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		global $Blog;
		global $Plugins;
		global $rsc_url;

		// Customize params to the current widget:
		$params = str_replace( '$wi_class$', 'widget_'.$this->type.'_'.$this->code, $params );

		switch( $this->type )
		{
			case 'core':
				switch( $this->code )
				{
					case 'coll_title':
						// Collection title:
						echo $params['block_start'];
						echo $params['block_title_start'];
						echo '<a href="'.$Blog->get( 'url', 'raw' ).'">';
						$Blog->disp( 'name', 'htmlbody' );
						echo '</a>';
						echo $params['block_title_end'];
						echo $params['block_end'];
						return true;

		      case 'coll_tagline':
						// Collection tagline:
						echo $params['block_start'];
						$Blog->disp( 'tagline', 'htmlbody' );
						echo $params['block_end'];
						return true;

		      case 'coll_longdesc':
						// Collection long description:
						echo $params['block_start'];
						echo '<p>';
						$Blog->disp( 'longdesc', 'htmlbody' );
						echo '</p>';
						echo $params['block_end'];
						return true;

		      case 'coll_common_links':
						// Collection common links:
						echo $params['block_start'];
						echo $params['list_start'];

						// $Blog->disp( 'staticurl', 'raw' ) echo T_('Recently') echo T_('(cached)')
						// $Blog->disp( 'dynurl', 'raw' ) echo T_('Recently') echo T_('(no cache)')

						echo $params['item_start'];
						echo '<strong><a href="'.$Blog->get('dynurl').'">'.T_('Recently').'</a></strong>';
						echo $params['item_end'];

						// fp> TODO: don't display this if archives plugin not installed... or depluginize archives (I'm not sure)
						echo $params['item_start'];
						echo '<strong><a href="'.$Blog->get('arcdirurl').'">'.T_('Archives').'</a></strong>';
						echo $params['item_end'];

						echo $params['item_start'];
						echo '<strong><a href="'.$Blog->get('lastcommentsurl').'">'.T_('Last comments').'</a></strong>';
						echo $params['item_end'];

						echo $params['list_end'];
						echo $params['block_end'];
						return true;

		      case 'coll_search_form':
						// Collection search form:
						echo $params['block_start'];

						echo $params['block_title_start'];
						echo T_('Search');
						echo $params['block_title_end'];

						form_formstart( $Blog->dget( 'blogurl', 'raw' ), 'search', 'SearchForm' );
						echo '<p>';
						$s = get_param( 's' );
						echo '<input type="text" name="s" size="30" value="'.htmlspecialchars($s).'" class="SearchField" /><br />';
						$sentence = get_param( 'sentence' );
						echo '<input type="radio" name="sentence" value="AND" id="sentAND" '.( $sentence=='AND' ? 'checked="checked" ' : '' ).'/><label for="sentAND">'.T_('All Words').'</label><br />';
						echo '<input type="radio" name="sentence" value="OR" id="sentOR" '.( $sentence=='OR' ? 'checked="checked" ' : '' ).'/><label for="sentOR">'.T_('Some Word').'</label><br />';
						echo '<input type="radio" name="sentence" value="sentence" id="sentence" '.( $sentence=='sentence' ? 'checked="checked" ' : '' ).'/><label for="sentence">'.T_('Entire phrase').'</label>';
						echo '</p>';
						echo '<input type="submit" name="submit" class="submit" value="'.T_('Search').'" />';
						echo '</form>';

						echo $params['block_end'];
						return true;

					case 'coll_xml_feeds':
						// Available XML feeds:
						echo $params['block_start'];

 						echo $params['block_title_start'];
						echo '<img src="'.$rsc_url.'icons/feed-icon-16x16.gif" width="16" height="16" class="top" alt="" /> '.T_('XML Feeds');
						echo $params['block_title_end'];

						echo $params['list_start'];

						$SkinCache = & get_Cache( 'SkinCache' );
						$SkinCache->load_by_type( 'feed' );

						// TODO: this is like touching private parts :>
						foreach( $SkinCache->cache as $Skin )
						{
							if( $Skin->type != 'feed' )
							{	// This skin cannot be used here...
								continue;
							}

							echo $params['item_start'];
							echo $Skin->name.': ';
							echo '<a href="'.$Blog->get_item_feed_url( $Skin->folder ).'">'.T_('Posts').'</a>, ';
							echo '<a href="'.$Blog->get_comment_feed_url( $Skin->folder ).'">'.T_('Comments').'</a>';
							echo $params['item_end'];
						}

						echo $params['list_end'];

						echo $params['block_end'];
						return true;

					case 'user_tools':
						// User tools:
						echo $params['block_start'];

						echo $params['block_title_start'];
						echo T_('User tools');
						echo $params['block_title_end'];

						echo $params['list_start'];
						user_login_link( $params['item_start'], $params['item_end'] );
						user_register_link( $params['item_start'], $params['item_end'] );
						user_admin_link( $params['item_start'], $params['item_end'] );
						user_profile_link( $params['item_start'], $params['item_end'] );
						user_subs_link( $params['item_start'], $params['item_end'] );
						user_logout_link( $params['item_start'], $params['item_end'] );
						echo $params['list_end'];

						echo $params['block_end'];
						return true;


					case 'admin_help':

				}
				break;

			case 'plugin':
				// Call plugin (will return false if Plugin is not enabled):
				if( $Plugins->call_by_code( $this->code, $params ) )
				{
					return true;
				}
				break;
		}

		echo '<!-- Unkown '.$this->type.' widget: '.$this->code.' -->';
	}


	/**
	 * Insert object into DB based on previously recorded changes.
	 *
	 * @return boolean true on success
	 */
	function dbinsert()
	{
		global $DB;

		if( $this->ID != 0 ) die( 'Existing object cannot be inserted!' );

		$DB->begin();

		$order_max = $DB->get_var(
			'SELECT MAX(wi_order)
				 FROM T_widget
				WHERE wi_coll_ID = '.$this->coll_ID.'
					AND wi_sco_name = '.$DB->quote($this->sco_name), 0, 0, 'Get current max order' );

		$this->set( 'order', $order_max+1 );

		$res = parent::dbinsert();

		$DB->commit();

		return $res;
	}
}


/*
 * $Log$
 * Revision 1.11  2007/01/14 03:24:30  fplanque
 * widgets complete proof of concept with multiple skins
 *
 * Revision 1.10  2007/01/14 01:32:11  fplanque
 * more widgets supported! :)
 *
 * Revision 1.9  2007/01/13 22:28:12  fplanque
 * doc
 *
 * Revision 1.8  2007/01/13 18:40:33  fplanque
 * SkinTag/Widget plugins now get displayed inside of the containers.
 * next step: adapt all default skins to use this.
 *
 * Revision 1.7  2007/01/13 14:35:42  blueyed
 * todo: $Plugin should be a ref?!
 *
 * Revision 1.6  2007/01/13 04:10:44  fplanque
 * implemented "add" support for plugin widgets
 *
 * Revision 1.5  2007/01/12 02:40:26  fplanque
 * widget default params proof of concept
 * (param customization to be done)
 *
 * Revision 1.4  2007/01/11 20:44:19  fplanque
 * skin containers proof of concept
 * (no params handling yet though)
 *
 * Revision 1.3  2007/01/11 02:57:25  fplanque
 * implemented removing widgets from containers
 *
 * Revision 1.2  2007/01/08 23:45:48  fplanque
 * A little less rough widget manager...
 * (can handle multiple instances of same widget and remembers order)
 *
 * Revision 1.1  2007/01/08 21:55:42  fplanque
 * very rough widget handling
 *
 */
?>