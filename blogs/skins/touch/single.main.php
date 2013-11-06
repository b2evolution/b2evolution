<?php
/**
 * This is the main/default page template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://manual.b2evolution.net/Skins_2.0}
 *
 * The main page template is used to display the blog when no specific page template is available
 * to handle the request (based on $disp).
 *
 * @package evoskins
 * @subpackage touch
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

if( version_compare( $app_version, '4.0.0-dev' ) < 0 )
{ // Older 2.x skins work on newer 2.x b2evo versions, but newer 2.x skins may not work on older 2.x b2evo versions.
	die( 'This skin is designed for b2evolution 4.0.0 and above. Please <a href="http://b2evolution.net/downloads/index.html">upgrade your b2evolution</a>.' );
}

// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );


// -------------------------- HTML HEADER INCLUDED HERE --------------------------
skin_include( '_html_header.inc.php' );
// Note: You can customize the default HTML header by copying the generic
// /skins/_html_header.inc.php file into the current skin folder.
// -------------------------------- END OF HEADER --------------------------------
?>


<?php
// ------------------------- BODY HEADER INCLUDED HERE --------------------------
skin_include( '_body_header.inc.php' );
// Note: You can customize the default BODY header by copying the generic
// /skins/_body_footer.inc.php file into the current skin folder.
// ------------------------------- END OF HEADER --------------------------------
?>



<div id="content" class="widecolumn">


<?php
	// ------------------------- MESSAGES GENERATED FROM ACTIONS -------------------------
	messages( array(
			'block_start' => '<div class="action_messages">',
			'block_end'   => '</div>',
		) );
	// --------------------------------- END OF MESSAGES ---------------------------------
?>


<?php
// Display message if no post:
display_if_empty();

echo '<div id="styled_content_block">'; // Beginning of posts display
while( $Item = & mainlist_get_item() )
{	// For each blog post, do everything below up to the closing curly brace "}"
	?>

	<?php
		$Item->locale_temp_switch(); // Temporarily switch to post locale (useful for multilingual blogs)
	?>

	<div class="post">
		<?php
			$Item->title( array(
					'link_type'  => 'permalink',
					'link_class' => 'sh2'
				) );
		?>
		
		<div class="single-post-meta-top">
			<?php
				// We want to display the post date:
				$Item->issue_time( array(
						'before'      => /* TRANS: date */ '',
						'time_format' => 'F jS, Y',
					) );
				$Item->issue_time( array(
						'before'      => /* TRANS: time */ T_('at '),
					) );
				$Item->author( array(
						'before'      => ' > ',
					) );
			?>
		<br>
			<?php /*<a href="#com-head">&darr; Skip to comments</a>*/ ?>
	<?php
		// Link to comments, trackbacks, etc.:
		$Item->feedback_link( array(
				'type' => 'feedbacks',
				'link_before' => '',
				'link_after' => '',
				'link_text_zero' => '&darr; '.T_('Skip to comments'),
				'link_text_one' => '&darr; '.T_('Skip to comments'),
				'link_text_more' => '&darr; '.T_('Skip to comments'),
				'link_title' => '',
				'use_popup' => false,
				'show_in_single_mode' => true
			) );
	?>
		</div>
	</div>

	<div id="<?php $Item->anchor_id() ?>" class="post post<?php $Item->status_raw() ?>" lang="<?php $Item->lang() ?>">
		<?php
			if( $Item->status != 'published' )
			{
				$Item->status( array( 'format' => 'styled' ) );
			}
			// ---------------------- POST CONTENT INCLUDED HERE ----------------------
			skin_include( '_item_content.inc.php', array(
					'image_size'	=>	'fit-400x320',
				) );
			// Note: You can customize the default item feedback by copying the generic
			// /skins/_item_feedback.inc.php file into the current skin folder.
			// -------------------------- END OF POST CONTENT -------------------------
		?>

		<?php
			// ------------------------- "Item - Single" CONTAINER EMBEDDED HERE --------------------------
			// WARNING: EXPERIMENTAL -- NOT RECOMMENDED FOR PRODUCTION -- MAY CHANGE DRAMATICALLY BEFORE RELEASE.
			// Display container contents:
			skin_container( NT_('Item Single'), array(
					// The following (optional) params will be used as defaults for widgets included in this container:
					// This will enclose each widget in a block:
					'block_start' => '<div class="$wi_class$">',
					'block_end' => '</div>',
					// This will enclose the title of each widget:
					'block_title_start' => '<h3>',
					'block_title_end' => '</h3>',
					// If a widget displays a list, this will enclose that list:
					'list_start' => '<ul>',
					'list_end' => '</ul>',
					// This will enclose each item in a list:
					'item_start' => '<li>',
					'item_end' => '</li>',
					// This will enclose sub-lists in a list:
					'group_start' => '<ul>',
					'group_end' => '</ul>',
					// This will enclose (foot)notes:
					'notes_start' => '<div class="notes">',
					'notes_end' => '</div>',
				) );
			// ----------------------------- END OF "Item - Single" CONTAINER -----------------------------
		?>

		<?php
			$Item->edit_link( array( // Link to backoffice for editing
					'before'    => '',
					'after'     => '',
				) );
		?>

		<div class="single-post-meta-bottom">
				<?php
					$Item->categories( array(
						'before'          => ' '.T_('Categories').': ',
						'after'           => '.',
						'include_main'    => true,
						'include_other'   => true,
						'include_external'=> true,
						'link_categories' => true,
					) );
				?>

				<?php
					// List all tags attached to this post:
					$Item->tags( array(
							'before' =>         '<br />'.T_('Tags').': ',
							'after' =>          ' ',
							'separator' =>      ', ',
						) );
				?>
		</div>
		
		<? /*<ul id="post-options">
			<li><a id="oprev" href="http://wordpress.re/?p=50"></a></li>
			<li><a id="omail" onclick="return confirm('Mail a link to this post?');" href="mailto:?subject=Wordpress for b2evolution- Another Post with Everything In It&amp;body=Check out this post:%20http://wordpress.re/?p=57"></a></li>
			<li><a id="otweet" href="javascript:(function(){var%20f=false,t=true,a=f,b=f,u='',w=window,d=document,g=w.open(),p,linkArr=d.getElementsByTagName('link');for(var%20i=0;i%3ClinkArr.length&amp;&amp;!a;i++){var%20l=linkArr[i];for(var%20x=0;x%3Cl.attributes.length;x++){if(l.attributes[x].nodeName.toLowerCase()=='rel'){p=l.attributes[x].nodeValue.split('%20');for(y=0;y%3Cp.length;y++){if(p[y]=='short_url'||p[y]=='shorturl'||p[y]=='shortlink'){a=t;}}}if(l.attributes[x].nodeName.toLowerCase()=='rev'&amp;&amp;l.attributes[x].nodeValue=='canonical'){a=t;}if(a){u=l.href;}}}if(a){go(u);}else{var%20h=d.getElementsByTagName('head')[0]||d.documentElement,s=d.createElement('script');s.src='http://api.bit.ly/shorten?callback=bxtShCb&amp;longUrl='+encodeURIComponent(window.location.href)+'&amp;version=2.0.1&amp;login=amoebe&amp;apiKey=R_60a24cf53d0d1913c5708ea73fa69684';s.charSet='utf-8';h.appendChild(s);}bxtShCb=function(data){var%20rs,r;for(r%20in%20data.results){rs=data.results[r];break;}go(rs['shortUrl']);};function%20go(u){return%20g.document.location.href=('http://mobile.twitter.com/home/?status='+encodeURIComponent(document.title+'%20'+u));}})();"></a></li>		<li><a id="facebook" href="javascript:var%20d=document,f='http://www.facebook.com/share',l=d.location,e=encodeURIComponent,p='.php?src=bm&amp;v=4&amp;i=1297484757&amp;u='+e(l.href)+'&amp;t='+e(d.title);1;try{if%20(!/^(.*\.)?facebook\.[^.]*$/.test(l.host))throw(0);share_internal_bookmarklet(p)}catch(z)%20{a=function()%20{if%20(!window.open(f+'r'+p,'sharer','toolbar=0,status=0,resizable=1,width=626,height=436'))l.href=f+p};if%20(/Firefox/.test(navigator.userAgent))setTimeout(a,0);else{a()}}void(0)"></a></li>		<li><a id="obook" href="javascript:return false;"></a></li>
			<li><a id="onext" href="http://wordpress.re/?p=1"></a></li>
		</ul>*/ ?>
		
		<?php
			// ------------------- PREV/NEXT POST LINKS (SINGLE POST MODE) -------------------
			item_prevnext_links( array(
					'block_start' => '<ul id="post-options">',
					'prev_start'  => '<li>',
					'prev_text'   => '',
					'prev_end'    => '</li>',
					'prev_class'  => 'oprev',
					'next_start'  => '<li>',
					'next_text'   => '',
					'next_end'    => '</li>',
					'next_class'  => 'onext',
					'block_end'   => '</ul>',
				) );
			// ------------------------- END OF PREV/NEXT POST LINKS -------------------------
		?>

	</div>


	<?php
		// ------------------ FEEDBACK (COMMENTS/TRACKBACKS) INCLUDED HERE ------------------
		skin_include( '_item_feedback.inc.php' );
		// Note: You can customize the default item feedback by copying the generic
		// /skins/_item_feedback.inc.php file into the current skin folder.
		// ---------------------- END OF FEEDBACK (COMMENTS/TRACKBACKS) ---------------------
	?>

	<?php
	locale_restore_previous();	// Restore previous locale (Blog locale)
}
echo '</div>'; // End of posts display
?>

</div>


<?php
// ------------------------- MOBILE FOOTER INCLUDED HERE --------------------------
skin_include( '_mobile_footer.inc.php' );
// Note: You can customize the default MOBILE FOOTER footer by copying the
// _mobile_footer.inc.php file into the current skin folder.
// ----------------------------- END OF MOBILE FOOTER -----------------------------

// ------------------------- BODY FOOTER INCLUDED HERE --------------------------
skin_include( '_body_footer.inc.php' );
// Note: You can customize the default BODY footer by copying the
// _body_footer.inc.php file into the current skin folder.
// ------------------------------- END OF FOOTER --------------------------------
?>


<?php
// ------------------------- HTML FOOTER INCLUDED HERE --------------------------
skin_include( '_html_footer.inc.php' );
// Note: You can customize the default HTML footer by copying the
// _html_footer.inc.php file into the current skin folder.
// ------------------------------- END OF FOOTER --------------------------------
?>