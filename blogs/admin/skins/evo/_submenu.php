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