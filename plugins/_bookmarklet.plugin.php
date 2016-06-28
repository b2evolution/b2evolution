<?php
/**
 * This file implements the Bookmarket plugin.
 *
 * This file is part of the b2evolution project - {@link http://b2evolution.net/}
 *
 * @license GNU GPL v2 - {@link http://b2evolution.net/about/gnu-gpl-license}
 *
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package plugins
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
	var $version = '6.7.0';
	var $author = 'Cafelog team';
	var $number_of_installs = 1;


	/**
	 * Init
	 */
	function PluginInit( & $params )
	{
		$this->short_desc = T_('Allow bookmarklet blogging.');
		$this->long_desc = T_('Adds a tool allowing blogging through a bookmarklet.');
	}


	/**
	 * We are displaying the tool menu.
	 *
	 * @todo Do not create links/javascript code based on browser detection! But: test for functionality!
	 *
	 * @return boolean did we display a tool menu block?
	 */
	function AdminToolPayload()
	{
		global $Hit, $admin_url;

		if( $Hit->is_NS4() || $Hit->is_gecko() || $Hit->is_firefox() )
		{
			?>
			<p><?php echo T_('Add this link to your Favorites/Bookmarks:') ?><br />
			<a href="javascript:Q=document.selection?document.selection.createRange().text:document.getSelection();void(window.open('<?php echo $admin_url ?>?ctrl=items&amp;action=new&amp;mode=bookmarklet&amp;content='+escape(Q)+'&amp;post_url='+escape(location.href)+'&amp;post_title='+escape(document.title),'b2evobookmarklet','scrollbars=yes,resizable=yes,width=750,height=550,left=25,top=15,status=yes'));"><?php echo T_('b2evo bookmarklet') ?></a></p>
			<?php
			return true;
		}
		elseif( $Hit->is_winIE() )
		{
			?>
			<p><?php echo T_('Add this link to your Favorites/Bookmarks:') ?><br />
			<a href="javascript:Q='';if(top.frames.length==0)Q=document.selection.createRange().text;void(btw=window.open('<?php echo $admin_url ?>?ctrl=items&amp;action=new&amp;mode=bookmarklet&amp;content='+escape(Q)+'&amp;post_url='+escape(location.href)+'&amp;post_title='+escape(document.title),'b2evobookmarklet','scrollbars=yes,resizable=yes,width=750,height=550,left=25,top=15,status=yes'));btw.focus();"><?php echo T_('b2evo bookmarklet') ?></a>
			</p>
			<?php
			return true;
		}
		elseif( $Hit->is_opera() )
		{
			?>
			<p><?php echo T_('Add this link to your Favorites/Bookmarks:') ?><br />
			<a href="javascript:void(window.open('<?php echo $admin_url ?>?ctrl=items&amp;action=new&amp;mode=bookmarklet&amp;post_url='+escape(location.href)+'&amp;post_title='+escape(document.title),'b2evobookmarklet','scrollbars=yes,resizable=yes,width=750,height=550,left=25,top=15,status=yes'));"><?php echo T_('b2evo bookmarklet') ?></a></p>
			<?php
			return true;
		}
		elseif( $Hit->is_macIE() )
		{
			?>
			<p><?php echo T_('Add this link to your Favorites/Bookmarks:') ?><br />
			<a href="javascript:Q='';if(top.frames.length==0);void(btw=window.open('<?php echo $admin_url ?>?ctrl=items&amp;action=new&amp;mode=bookmarklet&amp;content='+escape(document.getSelection())+'&amp;post_url='+escape(location.href)+'&amp;post_title='+escape(document.title),'b2evobookmarklet','scrollbars=yes,resizable=yes,width=750,height=550,left=25,top=15,status=yes'));btw.focus();"><?php echo T_('b2evo bookmarklet') ?></a></p>
			<?php
			return true;
		}
		else
		{  // This works in Safari, at least
			?>
			<p><?php echo T_('Add this link to your Favorites/Bookmarks:') ?><br />
			<a href="javascript:Q=window.getSelection();void(window.open('<?php echo $admin_url ?>?ctrl=items&amp;action=new&amp;mode=bookmarklet&amp;content='+escape(Q)+'&amp;post_url='+escape(window.location.href)+'&amp;post_title='+escape(document.title),'b2evobookmarklet','scrollbars=yes,resizable=yes,width=750,height=550,status=yes'));"><?php echo T_('b2evo bookmarklet') ?></a></p>
			<?php
			return true;
		}

		return false;
	}
}

?>