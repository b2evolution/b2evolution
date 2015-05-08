<?php
/**
 * This is the template that displays the chapter block in list
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template (or other templates)
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/gnu-gpl-license}
 * @copyright (c)2003-2015 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 * @subpackage bootstrap_manual
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Default params:
$params = array_merge( array(
		'Chapter'        => NULL,
		'before_title'   => '<h3>',
		'after_title'    => '</h3>',
		'before_content' => '<div>',
		'after_content'  => '</div>',
	), $params );

$Chapter = & $params['Chapter'];

if( !empty( $Chapter ) )
{	// Display chapter
?>
<li class="chapter"><?php

	echo $params['before_title'];

	?><a href="<?php echo $Chapter->get_permanent_url(); ?>" class="link"><?php echo get_icon( 'expand' ).$Chapter->dget( 'name' ); ?></a><?php

	//echo ' <span class="red">'.( $Chapter->get( 'order' )> 0? $Chapter->get( 'order' ) : 'NULL').'</span>'.$params['after_title'];
	echo $params['after_title'];

	if( $Chapter->dget( 'description' ) != '' )
	{	// Display chapter description
		echo $params['before_content']
			.$Chapter->dget( 'description' )
			.$params['after_content'];
	}
?></li>
<?php
}
?>