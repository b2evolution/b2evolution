<?php
/**
 * This file implements the Bookmarket plugin.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 * {@internal
 * b2evolution is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * b2evolution is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with b2evolution; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 * }}
 *
 * @package plugins
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: François PLANQUE - {@link http://fplanque.net/}
 * @author cafelog (team) - http://cafelog.com/
 *
 * @version $Id$
 */
if( !defined('DB_USER') ) die( 'Please, do not access this page directly.' );


/**
 * Sidebar plugin
 *
 * Adds a tool allowing blogging from the sidebar
 */
class bookmarklet_plugin extends Plugin
{
	var $name = 'Bookmarklet';
	var $code = 'cafeSidB';
	var $priority = 94;
	var $version = 'CVS $Revision$';
	var $author = 'Cafelog team';
	var $help_url = 'http://b2evolution.net/';
	var $is_tool = true;


	/**
	 * Constructor
	 *
	 * {@internal bookmarklet_plugin::bookmarklet_plugin(-)}}
	 */
	function bookmarklet_plugin()
	{
		$this->short_desc = T_('Allow bookmarklet blogging.');
		$this->long_desc = T_('Adds a tool allowing blogging through a bookmarklet.');
	}


 	/**
	 * We are displaying the tool menu
	 *
	 * {@internal bookmarklet_plugin::ToolMenu(-)}}
	 *
	 * @todo get rid of global $is_gecko, $is_winIE, $is_macIE
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function ToolMenu( $params )
	{
		global $is_NS4, $is_gecko, $is_winIE, $is_opera, $is_macIE, $admin_url;

		if($is_NS4 || $is_gecko)
		{
			?>
			<p><?php echo T_('Add this link to your Favorites/Bookmarks:') ?><br />
			<a href="javascript:Q=document.selection?document.selection.createRange().text:document.getSelection();void(window.open('<?php echo $admin_url ?>b2bookmarklet.php?text='+escape(Q)+'&amp;popupurl='+escape(location.href)+'&amp;popuptitle='+escape(document.title),'b2evobookmarklet','scrollbars=yes,resizable=yes,width=750,height=550,left=25,top=15,status=yes'));"><?php echo T_('b2evo bookmarklet') ?></a></p>
			<?php
			return true;
		}
		elseif ($is_winIE)
		{
			?>
			<p><?php echo T_('Add this link to your Favorites/Bookmarks:') ?><br />
			<a href="javascript:Q='';if(top.frames.length==0)Q=document.selection.createRange().text;void(btw=window.open('<?php echo $admin_url ?>b2bookmarklet.php?text='+escape(Q)+'&amp;popupurl='+escape(location.href)+'&amp;popuptitle='+escape(document.title),'b2evobookmarklet','scrollbars=yes,resizable=yes,width=750,height=550,left=25,top=15,status=yes'));btw.focus();"><?php echo T_('b2evo bookmarklet') ?></a>
			</p>
			<?php
			return true;
		}
		elseif($is_opera)
		{
			?>
			<p><?php echo T_('Add this link to your Favorites/Bookmarks:') ?><br />
			<a href="javascript:void(window.open('<?php echo $admin_url ?>b2bookmarklet.php?popupurl='+escape(location.href)+'&amp;popuptitle='+escape(document.title),'b2evobookmarklet','scrollbars=yes,resizable=yes,width=750,height=550,left=25,top=15,status=yes'));"><?php echo T_('b2evo bookmarklet') ?></a></p>
			<?php
			return true;
		}
		elseif($is_macIE)
		{
			?>
			<p><?php echo T_('Add this link to your Favorites/Bookmarks:') ?><br />
			<a href="javascript:Q='';if(top.frames.length==0);void(btw=window.open('<?php echo $admin_url ?>b2bookmarklet.php?text='+escape(document.getSelection())+'&amp;popupurl='+escape(location.href)+'&amp;popuptitle='+escape(document.title),'b2evobookmarklet','scrollbars=yes,resizable=yes,width=750,height=550,left=25,top=15,status=yes'));btw.focus();"><?php echo T_('b2evo bookmarklet') ?></a></p>
			<?php
			return true;
		}

		return false;
	}
}
?>