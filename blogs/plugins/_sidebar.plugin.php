<?php
/**
 * This file implements the Sidebar plugin.
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
class sidebar_plugin extends Plugin
{
	var $name = 'SideBar';
	var $code = 'cafeSidB';
	var $priority = 95;
	var $version = 'CVS $Revision$';
	var $author = 'Cafelog team';
	var $help_url = 'http://b2evolution.net/';
	var $is_tool = true;


	/**
	 * Constructor
	 *
	 * {@internal sidebar_plugin::sidebar_plugin(-)}}
	 */
	function sidebar_plugin()
	{
		$this->short_desc = T_('Allow sidebar blogging.');
		$this->long_desc = T_('Adds a tool allowing blogging from the sidebar.');
	}


 	/**
	 * We are displaying the tool menu
	 *
	 * {@internal sidebar_plugin::ToolMenu(-)}}
	 *
	 * @todo get rid of global $is_gecko, $is_winIE, $is_macIE
	 *
	 * @param array Associative array of parameters
	 * @return boolean did we display a toolbar?
	 */
	function ToolMenu( $params )
	{
		global $is_gecko, $is_winIE, $is_macIE, $admin_url;

		if($is_gecko)
		{
			?>
			<script type="text/javascript">
				<!--
				function addsidebar()
				{
					if ((typeof window.sidebar == "object") && (typeof window.sidebar.addPanel == "function"))
						window.sidebar.addPanel("<?php echo T_('Post to b2evolution') ?>","<?php echo $admin_url ?>b2sidebar.php","");
					else
						alert('<?php echo str_replace( "'", "\'", T_('No Sidebar found! You must use Mozilla 0.9.4 or later!')) ?>');
				}
				// -->
			</script>
			<p><?php printf( T_('Add the <a %s>b2evo sidebar</a> !'), 'href="#" onclick="addsidebar()"' ); ?></p>
			<?php
 			return true;
		}

		if($is_winIE || $is_macIE)
		{
			?>
			<p><?php echo T_('Add this link to your favorites:') ?><br />
			<a href="javascript:Q='';if(top.frames.length==0)Q=document.selection.createRange().text;void(_search=open('<?php echo $admin_url ?>b2sidebar.php?popuptitle='+escape(document.title)+'&amp;popupurl='+escape(location.href)+'&amp;text='+escape(Q),'_search'))"><?php echo T_('b2evo sidebar') ?></a></p>
			<?php
			return true;
		}

		return false;
	}
}
?>