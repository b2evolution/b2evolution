<?php
	/*
	 * This is the template that displays the message email form
	 *
	 * This file is not meant to be called directly.
	 * It is meant to be called by an include in the _main.php template.
	 * To display a feedback, you should call a stub AND pass the right parameters
	 * For example: /blogs/index.php?p=1&more=1&c=1&tb=1&pb=1
	 * Note: don't cod ethis URL by hand, use the template functions to generate it!
	 */
	if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


	/*
	 * We now call the default message email handler...
	 * However you can replace this file with the full handler (in /blogs) and customize it!
	 */
	require get_path('skins').'_msgform.php';
?>