<?php
	while( $MainList->get_item() ) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php echo $blogname ?> - feedback on '<?php the_title() ?>'</title>
	<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
	<meta http-equiv="reply-to" content="you@yourdomain.com" />
	<meta http-equiv="imagetoolbar" content="no" />
	<meta content="TRUE" name="MSSmartTagsPreventParsing" />
 <!-- PAY ATTENTION to the following line: if set incorrectly, all your relative navigation links will fail! -->
	<base href="<?php bloginfo('siteurl'); ?>">
	<style type="text/css" media="screen">
		@import url( 'style.css' );
	</style>
	<link rel="stylesheet" type="text/css" media="print" href="print.css" />
</head>
<body>

<div id="contentcomments">


<?php
		// this includes the feedback and a form to add a new comment depending on request
		$disp_comments = 1;					// Display the comments if requested
		$disp_comment_form = 1;			// Display the comments form if comments requested
		$disp_trackbacks = 1;				// Display the trackbacks if requested
		$disp_trackback_url = 1;		// Display the trackbal URL if trackbacks requested
		$disp_pingbacks = 1;				// Display the pingbacks if requested
		include( dirname(__FILE__)."/_feedback.php");
?>
</div>

<div><b><span style="color: #40B4C3">::</span> <a href="javascript:window.close()">close this window</a></b></div>

</body>
</html>
<?php } ?>