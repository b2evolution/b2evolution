<?php
/**
 * This file implements the submenu template
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2004 by Francois PLANQUE - {@link http://fplanque.net/}.
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
 * @package admin-skin
 * @subpackage evo
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: François PLANQUE
 *
 * @version $Id$
 */
?>
<div class="pt" >
	<?php
		if( isset($blogListButtons) )
		{	// We have displayed something above, we'll get a display bug on firefox if we don't do this:
			?>
			<ul class="hack">
				<li><!-- Yes, this empty UL is needed! It's a DOUBLE hack for correct CSS display --></li>
			</ul>
			<?php
		}
	?>
	<div class="panelblocktabs">
		<ul class="tabs">
		<?php
			foreach( $submenu as $loop_tab => $loop_details )
			{
				echo (($loop_tab == $tab) ? '<li class="current">' : '<li>');
				echo '<a href="'.$loop_details[1].'">'.$loop_details[0].'</a></li>';
			}
		?>
		</ul>
	</div>
</div>