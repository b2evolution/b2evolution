<?php
/**
 * This file implements the Sidebar plugin.
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
class sidebar_plugin extends Plugin
{
	var $name = 'SideBar';
	var $code = 'cafeSidB';
	var $priority = 95;
	var $version = 'CVS $Revision$';
	var $author = 'Cafelog team';


	/**
	 * Constructor
	 */
	function sidebar_plugin()
	{
		$this->short_desc = T_('Allow sidebar blogging.');
		$this->long_desc = T_('Adds a tool allowing blogging from the sidebar.');
	}


	/**
	 * We are displaying the tool menu block.
	 *
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
 * Revision 1.10  2006/05/30 19:39:55  fplanque
 * plugin cleanup
 *
 * Revision 1.9  2006/04/11 21:22:26  fplanque
 * partial cleanup
 *
 */
?>