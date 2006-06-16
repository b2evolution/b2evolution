<?php
/**
 * This file implements the Bookmarket plugin.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * @package plugins
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE - {@link http://fplanque.net/}
 * @author cafelog (team) - http://cafelog.com/
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Sidebar plugin
 *
 * Adds a tool allowing blogging from the sidebar
 */
class bookmarklet_plugin extends Plugin
{
	var $name = 'Bookmarklet';
	var $code = 'cafeBkmk';
	var $priority = 94;
	var $version = '1.8';
	var $author = 'Cafelog team';


	/**
	 * Constructor
	 */
	function bookmarklet_plugin()
	{
		$this->short_desc = T_('Allow bookmarklet blogging.');
		$this->long_desc = T_('Adds a tool allowing blogging through a bookmarklet.');
	}


	/**
	 * We are displaying the tool menu.
	 *
	 * @todo Do not create links/javascript code based on browser detection! But: test for functionality!
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a tool menu block?
	 */
	function AdminToolPayload( $params )
	{
		global $Hit, $admin_url;

		if( $Hit->is_NS4 || $Hit->is_gecko )
		{
			?>
			<p><?php echo T_('Add this link to your Favorites/Bookmarks:') ?><br />
			<a href="javascript:Q=document.selection?document.selection.createRange().text:document.getSelection();void(window.open('<?php echo $admin_url ?>?ctrl=edit&amp;mode=bookmarklet&amp;text='+escape(Q)+'&amp;popupurl='+escape(location.href)+'&amp;popuptitle='+escape(document.title),'b2evobookmarklet','scrollbars=yes,resizable=yes,width=750,height=550,left=25,top=15,status=yes'));"><?php echo T_('b2evo bookmarklet') ?></a></p>
			<?php
			return true;
		}
		elseif( $Hit->is_winIE )
		{
			?>
			<p><?php echo T_('Add this link to your Favorites/Bookmarks:') ?><br />
			<a href="javascript:Q='';if(top.frames.length==0)Q=document.selection.createRange().text;void(btw=window.open('<?php echo $admin_url ?>?ctrl=edit&amp;mode=bookmarklet&amp;text='+escape(Q)+'&amp;popupurl='+escape(location.href)+'&amp;popuptitle='+escape(document.title),'b2evobookmarklet','scrollbars=yes,resizable=yes,width=750,height=550,left=25,top=15,status=yes'));btw.focus();"><?php echo T_('b2evo bookmarklet') ?></a>
			</p>
			<?php
			return true;
		}
		elseif( $Hit->is_opera )
		{
			?>
			<p><?php echo T_('Add this link to your Favorites/Bookmarks:') ?><br />
			<a href="javascript:void(window.open('<?php echo $admin_url ?>?ctrl=edit&amp;mode=bookmarklet&amp;popupurl='+escape(location.href)+'&amp;popuptitle='+escape(document.title),'b2evobookmarklet','scrollbars=yes,resizable=yes,width=750,height=550,left=25,top=15,status=yes'));"><?php echo T_('b2evo bookmarklet') ?></a></p>
			<?php
			return true;
		}
		elseif( $Hit->is_macIE )
		{
			?>
			<p><?php echo T_('Add this link to your Favorites/Bookmarks:') ?><br />
			<a href="javascript:Q='';if(top.frames.length==0);void(btw=window.open('<?php echo $admin_url ?>?ctrl=edit&amp;mode=bookmarklet&amp;text='+escape(document.getSelection())+'&amp;popupurl='+escape(location.href)+'&amp;popuptitle='+escape(document.title),'b2evobookmarklet','scrollbars=yes,resizable=yes,width=750,height=550,left=25,top=15,status=yes'));btw.focus();"><?php echo T_('b2evo bookmarklet') ?></a></p>
			<?php
			return true;
		}

		return false;
	}
}


/*
 * $Log$
 * Revision 1.12  2006/06/16 21:30:57  fplanque
 * Started clean numbering of plugin versions (feel free do add dots...)
 *
 * Revision 1.11  2006/05/30 19:39:55  fplanque
 * plugin cleanup
 *
 * Revision 1.10  2006/05/19 15:59:52  blueyed
 * Fixed bookmarklet plugin. Thanks to personman for pointing it out.
 *
 * Revision 1.9  2006/04/11 21:22:26  fplanque
 * partial cleanup
 *
 */
?>