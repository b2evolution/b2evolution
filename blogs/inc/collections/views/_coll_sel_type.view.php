<?php
/**
 * This file implements the UI view for the General blog properties.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2010 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package admin
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id$
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

echo '<h2>'.T_('What kind of blog would you like to create?').'</h2>';

echo '<table class="coll_kind">';

if( $blog_kinds = get_collection_kinds() )
{
	foreach( $blog_kinds as $kind => $info )
	{
		echo '<tr>';
			echo '<td class="coll_kind"><h3><a href="?ctrl=collections&amp;action=new-selskin&amp;kind='.$kind.'">'.$info['name'].' &raquo;</a></h3></td>';
			echo '<td>'.$info['desc'].'<td>';
		echo '</tr>';
	}
}
else
{
	echo '<tr>';
		echo '<td class="coll_kind"><h3><a href="?ctrl=collections&amp;action=new-selskin&amp;kind=std">'.T_('Standard').' &raquo;</a></h3></td>';
		echo '<td>'.T_('A standard blog with the most common features.').'<td>';
	echo '</tr>';
}

echo '</table>';
	
	echo '<p>'.T_('Your selection here will pre-configure your blog in order to optimize it for a particular use. Nothing is final though. You can change all the settings at any time and any kind of blog can be transformed into any other at any time.').'</p>';
	

/*
 * $Log$
 * Revision 1.5  2010/06/01 02:44:44  sam2kb
 * New hooks added: GetCollectionKinds and InitCollectionKinds.
 * Use them to define new and override existing presets for new blogs.
 * See http://forums.b2evolution.net/viewtopic.php?t=21015
 *
 * Revision 1.4  2010/02/08 17:52:09  efy-yury
 * copyright 2009 -> 2010
 *
 * Revision 1.3  2009/03/08 23:57:42  fplanque
 * 2009
 *
 * Revision 1.2  2008/01/21 09:35:27  fplanque
 * (c) 2008
 *
 * Revision 1.1  2007/06/25 10:59:37  fplanque
 * MODULES (refactored MVC)
 *
 * Revision 1.3  2007/04/26 00:11:05  fplanque
 * (c) 2007
 *
 * Revision 1.2  2007/01/15 03:54:36  fplanque
 * pepped up new blog creation a little more
 *
 * Revision 1.1  2007/01/15 00:38:06  fplanque
 * pepped up "new blog" creation a little. To be continued.
 *
 *
 */
?>