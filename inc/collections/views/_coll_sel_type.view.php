<?php
/**
 * This file implements the UI view for the General blog properties.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2016 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package admin
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


echo '<div class="panel panel-default">';

echo '<div class="panel-heading">'
			.'<h2 class="panel-title">'.T_('What kind of collection would you like to create?').get_manual_link('create-collection-select-type').'</h2>'
		.'</div>';

echo '<div class="panel-body">';

echo '<p>'.T_('Your selection here will pre-configure your collection in order to optimize it for a particular use. Nothing is final though. You can change all the settings at any time and any kind of collection can be transformed into any other at any time.').'</p>';

echo '<table class="coll_kind">';

if( $blog_kinds = get_collection_kinds() )
{
	foreach( $blog_kinds as $kind => $info )
	{
		echo '<tr>';
			echo '<td class="coll_kind"><a href="?ctrl=collections&amp;action=new-selskin&amp;kind='.$kind.'" class="btn '.( !empty($info['class']) ? $info['class'] : 'btn-default' ).'">'.$info['name'].' &raquo;</a></td>';
			echo '<td class="coll_kind__desc"><p>'.$info['desc'].'</p>';
			if( !empty($info['note']) )
			{
				echo '<p class="text-muted">'.$info['note'].'</p>';
			}
			echo '</td>';
		echo '</tr>';
	}
}
else
{
	echo '<tr>';
		echo '<td class="coll_kind"><h3><a href="?ctrl=collections&amp;action=new-selskin&amp;kind=std">'.T_('Standard').' &raquo;</a></h3></td>';
		echo '<td>'.T_('A standard blog with the most common features.').'</td>';
	echo '</tr>';
}

echo '</table>';

echo '</div>';

echo '</div>';

?>