<?php
/**
 * This file implements the UI controller for additional tools.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package admin
 * @author This file built upon code from original b2 - http://cafelog.com/
 */

/**
 * Includes:
 */
require_once( dirname(__FILE__).'/_header.php' ); // this will actually load blog params for req blog
$AdminUI->setPath( 'tools' );
$admin_pagetitle = T_('Tools');
require( dirname(__FILE__).'/_menutop.php' );

// Loop through plugins:
$Plugins->restart();

while( $loop_Plugin = & $Plugins->get_next() )
{
	if( $loop_Plugin->is_tool )
	{	// This plugin is a tool, we must display something:
		echo '<div class="panelblock">';
		echo '<h2>';
		$loop_Plugin->name();
		echo '</h2>';
		$loop_Plugin->ToolMenu( array() );
		echo '</div>';
	}
}


?>

<div class="panelblock">
	<h2><?php echo T_('Movable Type Import') ?></h2>
	<ol>
		<li><?php echo T_('Use MT\'s export functionnality to create a .TXT file containing your posts;') ?></li>
		<li><?php echo T_('Place that file into the /admin folder on your server;') ?></li>
		<li><?php printf( T_('Follow the insctructions in the <a %s>MT migration utility</a>.'), ' href="import-mt.php"' ) ?></li>
	</ol>
</div>

<?php
require( dirname(__FILE__). '/_footer.php' );
?>