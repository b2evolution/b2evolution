<?php
/**
 * This file implements the Sidebar plugin.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
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
class sidebar_plugin extends Plugin
{
	var $name = 'SideBar';
	var $code = 'cafeSidB';
	var $priority = 95;
	var $version = '1.9-dev';
	var $author = 'Cafelog team';


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Allow sidebar blogging.');
		$this->long_desc = T_('Adds a tool allowing blogging from the sidebar.');
	}


	/**
	 * We are displaying the tool menu block.
	 *
	 * @todo fp>I think this is broken. And I think I'm going to take it down alltogether (should be a complete plugin instead).
	 *       dh> it is a plugin already?!
		* fp> No. The plugin does only half of the job, the other half bloats the core and has not been maintained for 2 years maybe. Conclusion: if someone needs this he's gonna have to write a full plugin for it.
	 *  dh> IMHO the "bloat in the core" should just get moved here, and hooks should be added as needed.
	 *      (If someone makes a "real plugin", he'd need hooks anyway!)
	 * @param array Associative array of parameters
	 * @return boolean did we display a tool menu block?
	 */
	function AdminToolPayload( $params )
	{
		global $Hit, $admin_url;

		if( $Hit->is_gecko )
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

		if( $Hit->is_IE )
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


/*
 * $Log$
 * Revision 1.18  2006/12/03 01:53:25  blueyed
 * doc
 *
 * Revision 1.17  2006/12/03 00:22:17  fplanque
 * doc
 *
 * Revision 1.16  2006/12/01 18:14:48  blueyed
 * doc/todo
 *
 * Revision 1.15  2006/11/30 22:34:16  fplanque
 * bleh
 *
 * Revision 1.14  2006/07/10 20:19:30  blueyed
 * Fixed PluginInit behaviour. It now gets called on both installed and non-installed Plugins, but with the "is_installed" param appropriately set.
 *
 * Revision 1.13  2006/07/07 21:26:49  blueyed
 * Bumped to 1.9-dev
 *
 * Revision 1.12  2006/07/06 19:56:29  fplanque
 * no message
 *
 * Revision 1.11  2006/06/16 21:30:57  fplanque
 * Started clean numbering of plugin versions (feel free do add dots...)
 *
 * Revision 1.10  2006/05/30 19:39:55  fplanque
 * plugin cleanup
 *
 * Revision 1.9  2006/04/11 21:22:26  fplanque
 * partial cleanup
 *
 */
?>