<?php 
/*
 * b2evolution - http://b2evolution.net/
 *
 * Copyright (c) 2003-2004 by Francois PLANQUE - http://fplanque.net/
 * Released under GNU GPL License - http://b2evolution.net/about/license.html
 *
 * This file built upon code from original b2 - http://cafelog.com/
 */
require_once (dirname(__FILE__).'/_header.php'); // this will actually load blog params for req blog

/*
 * Display urrent user's profile:
 */	

$title = T_('My Profile');
require(dirname(__FILE__).'/_menutop.php');
require(dirname(__FILE__).'/_menutop_end.php');

?>
		
<div class="bPosts">
	<div class="bPost">
		<h2><?php echo T_('Edit your profile') ?></h2>
		<?php
			require( get_path('skins').'/_profile.php');
		?>
	</div>
</div>

<!-- ================================== START OF SIDEBAR ================================== -->

<div class="bSideBar">
	<div class="bSideItem">
		<h2><?php echo T_('Bookmarklet') ?></h2>
		
		<?php
		if($is_NS4 || $is_gecko) 
		{
			?>
			<p><?php echo T_('Add this link to your Favorites/Bookmarks:') ?><br />
			<a href="javascript:Q=document.selection?document.selection.createRange().text:document.getSelection();void(window.open('<?php echo $admin_url ?>/b2bookmarklet.php?text='+escape(Q)+'&amp;popupurl='+escape(location.href)+'&amp;popuptitle='+escape(document.title),'b2evobookmarklet','scrollbars=yes,resizable=yes,width=750,height=550,left=25,top=15,status=yes'));"><?php echo T_('b2evo bookmarklet') ?></a></p>
			<?php
		}
		elseif ($is_winIE)
		{
			?>
			<p><?php echo T_('Add this link to your Favorites/Bookmarks:') ?><br />
			<a href="javascript:Q='';if(top.frames.length==0)Q=document.selection.createRange().text;void(btw=window.open('<?php echo $admin_url ?>/b2bookmarklet.php?text='+escape(Q)+'&amp;popupurl='+escape(location.href)+'&amp;popuptitle='+escape(document.title),'b2evobookmarklet','scrollbars=yes,resizable=yes,width=750,height=550,left=25,top=15,status=yes'));btw.focus();"><?php echo T_('b2evo bookmarklet') ?></a>
			</p>
			<?php
		}
		elseif($is_opera)
		{
			?>
			<p><?php echo T_('Add this link to your Favorites/Bookmarks:') ?><br />
			<a href="javascript:void(window.open('<?php echo $admin_url ?>/b2bookmarklet.php?popupurl='+escape(location.href)+'&amp;popuptitle='+escape(document.title),'b2evobookmarklet','scrollbars=yes,resizable=yes,width=750,height=550,left=25,top=15,status=yes'));"><?php echo T_('b2evo bookmarklet') ?></a></p>
			<?php
		}
		elseif($is_macIE)
		{
			?>
			<p><?php echo T_('Add this link to your Favorites/Bookmarks:') ?><br />
			<a href="javascript:Q='';if(top.frames.length==0);void(btw=window.open('<?php echo $admin_url ?>/b2bookmarklet.php?text='+escape(document.getSelection())+'&amp;popupurl='+escape(location.href)+'&amp;popuptitle='+escape(document.title),'b2evobookmarklet','scrollbars=yes,resizable=yes,width=750,height=550,left=25,top=15,status=yes'));btw.focus();"><?php echo T_('b2evo bookmarklet') ?></a></p>
			<?php
		}


		// Sidebar:
		if ($is_gecko)
		{
			?>
			<script language="JavaScript">
				function addsidebar()
				{
					if ((typeof window.sidebar == "object") && (typeof window.sidebar.addPanel == "function"))
						window.sidebar.addPanel("<?php echo T_('Post to b2evolution') ?>","<?php echo $admin_url ?>/b2sidebar.php","");
					else
						alert("<?php echo T_('No Sidebar found! You must use Mozilla 0.9.4 or later!') ?>");
				}
			</script>
			<br />
			<h2><?php echo T_('SideBar') ?></h2>
			<p><?php printf( T_('Add the <a %s>b2evo sidebar</a> !'), 'href="#" onClick="addsidebar()"' ); ?></p>
			<?php
		}
		elseif($is_winIE || $is_macIE)
		{
			?>
			<br />
			<h2><?php echo T_('SideBar') ?></h2>
			<p><?php echo T_('Add this link to your favorites:') ?><br />
			<a href="javascript:Q='';if(top.frames.length==0)Q=document.selection.createRange().text;void(_search=open('<?php echo $admin_url ?>/b2sidebar.php?popuptitle='+escape(document.title)+'&amp;popupurl='+escape(location.href)+'&amp;text='+escape(Q),'_search'))"><?php echo T_('b2evo sidebar') ?></a></p>
			<?php 
		}
		?>
		</div>
	</div>
	<div style="clear:both;"></div>
<?php

require( dirname(__FILE__).'/_footer.php' ); 

?>
