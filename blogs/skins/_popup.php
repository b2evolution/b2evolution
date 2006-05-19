<?php
/**
 * This is the template that displays the contents of the feedback popup
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the _main.php template.
 * To display the stats, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=stats
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2005 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evoskins
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// TODO: blueyed>> What's that?? A HTML-page-block for every item?! Shouldn't that just use $Item perhaps??
// it's cafelog/b2 legacy. Idf $Item is already available, then fine ;)
// blueyed> I'm not sure, it just does not seem to make sense to have a loop here!
while( $Item = $MainList->get_item() )
{
header('Content-Type: text/html; charset='.$io_charset);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>">
<head>
	<title><?php	$Blog->disp('name', 'htmlhead') ?> - feedback on '<?php $Item->title( '', '', false, 'htmlhead' ) ?>'</title>
	<?php skin_base_tag(); /* Base URL for this DIR. You need this to fix relative links! */ ?> />
	<style type="text/css" media="screen">
		@import url( 'originalb2/layout2b.css' );
	</style>
	<link rel="stylesheet" type="text/css" media="print" href="originalb2/print.css" />
</head>
<body>

<div id="contentcomments">

<?php
	/**
	 * this includes the feedback and a form to add a new comment depending on request
	 */
	$disp_comments = 1;					// Display the comments if requested
	$disp_comment_form = 1;			// Display the comments form if comments requested
	$disp_trackbacks = 1;				// Display the trackbacks if requested
	$disp_trackback_url = 1;		// Display the trackbal URL if trackbacks requested
	$disp_pingbacks = 1;				// Display the pingbacks if requested
	require( dirname(__FILE__).'/_feedback.php' );
?>
</div>

<div><strong><span style="color: #0099CC">::</span> <a href="javascript:window.close()">close this window</a></strong></div>

</body>
</html>
<?php
}


/*
 * $Log$
 * Revision 1.13  2006/05/19 18:15:06  blueyed
 * Merged from v-1-8 branch
 *
 * Revision 1.12.2.1  2006/05/19 15:06:26  fplanque
 * dirty sync
 *
 * Revision 1.12  2006/04/29 01:24:05  blueyed
 * More decent charset support;
 * unresolved issues include:
 *  - front office still forces the blog's locale/charset!
 *  - if there's content in utf8, it cannot get displayed with an I/O charset of latin1
 *
 * Revision 1.11  2006/04/11 21:22:26  fplanque
 * partial cleanup
 *
 */
?>
