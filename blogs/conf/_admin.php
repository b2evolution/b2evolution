<?php
/*
 * This is b2evolution's admin config file
 * Version of this file: 0.8.5
 *
 * This sets how the back-office works
 *
 * Reminder: everything that starts with #, /* or // is a comment
 */

# Cross posting:
# set this to 0 if you want users to post to a single category only
# set this to 1 if you want to be able to cross-post among multiple categories
# set this to 2 if you want to be able to cross-post among multiple blogs/categories
$allow_cross_posting = 1;

# Do you want to display buttons to help insettion of HTML tags
$use_quicktags = 1;     //  1 to enable, 0 to disable

# Do you want to be able to link each post to an URL ?
$use_post_url = 1;			// 1 to enable, 0 to disable

# When banning referrers/comment URLs, do you want to automatically remove any referrers and comments containing the banned domain?
# (you will be asked to confirm the ban if you enable this)
$deluxe_ban = 1;	// 1 to enable, 0 to disable

# Do not edit the following unless you known what you're doing...

# Backoffice two colum display:
$admin_2col_start = '<div class="bPosts">';
$admin_2col_nextcol = '</div><div class="bSideBar">';
$admin_2col_end = '</div><div style="clear:both;"></div>';
# If you run into troubles with the backoffice two colum display, you can use the alternative below:
# $admin_2col_start = '<table cellspacing="0"><tr><td class="bPosts">';
# $admin_2col_nextcol = '</td><td class="bSideBar">';
# $admin_2col_end = '</td></tr></table>';

?>
