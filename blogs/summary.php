<?php
/**
 * This is a demo template displaying a summary of the last posts in each blog
 *
 * If you're new to b2evolution templates or skins, you should not start with this file
 * It will be easier to start examining blog_a.php or noskin_a.php for instance...
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2006 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * @package evoskins
 * @subpackage noskin
 */

/**
 * Check this: we are requiring _main.inc.php INSTEAD of _blog_main.inc.php because we are not
 * trying to initialize any particular blog
 */
require_once dirname(__FILE__).'/conf/_config.php';

require_once $inc_path.'_main.inc.php';

header( 'Content-type: text/html; charset='.$io_charset );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>"><!-- InstanceBegin template="/Templates/Standard.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
<!-- InstanceBeginEditable name="doctitle" -->
<title><?php echo T_('Summary Demo'); ?></title>
<!-- InstanceEndEditable -->
<!-- InstanceBeginEditable name="head" -->
 <!-- InstanceEndEditable -->
<link rel="stylesheet" href="rsc/css/fp02.css" type="text/css" />
</head>
<body>
<div class="pageHeader">
<div class="pageHeaderContent">

<!-- InstanceBeginEditable name="NavBar2" -->
<?php // --------------------------- BLOG LIST INCLUDED HERE -----------------------------
	$display_blog_list = 1; // forced

	# this is what will start and end your blog links
	$blog_list_start = '<div class="NavBar">';
	$blog_list_end = '</div>';
	# this is what will separate your blog links
	$blog_item_start = '';
	$blog_item_end = '';
	# This is the class of for the selected blog link:
	$blog_selected_link_class = 'NavButton2';
	# This is the class of for the other blog links:
	$blog_other_link_class = 'NavButton2';
	# This is additionnal markup before and after the selected blog name
	$blog_selected_name_before = '<span class="small">';
	$blog_selected_name_after = '</span>';
	# This is additionnal markup before and after the other blog names
	$blog_other_name_before = '<span class="small">';
	$blog_other_name_after = '</span>';
	// Include the bloglist
	require( $skins_path.'_bloglist.php');
	// ---------------------------------- END OF BLOG LIST --------------------------------- ?>
<!-- InstanceEndEditable -->

<div class="NavBar">
<div class="pageTitle">
<h1 id="pageTitle"><!-- InstanceBeginEditable name="PageTitle" --><?php echo T_('Summary Demo') ?><!-- InstanceEndEditable --></h1>
</div>
</div>

<div class="pageHeaderEnd"></div>

</div>
</div>


<div class="pageSubTitle"><!-- InstanceBeginEditable name="SubTitle" --><?php echo T_('This demo template displays a summary of last posts in all blogs') ?><!-- InstanceEndEditable --></div>


<div class="main"><!-- InstanceBeginEditable name="Main" -->

<!-- =================================== START OF MAIN AREA =================================== -->


<?php // --------------------------- BLOG LIST -----------------------------

	load_class( 'MODEL/items/_itemlist2.class.php' );

	$BlogCache = & get_Cache( 'BlogCache' );

	for( $blog = blog_list_start();
				$blog != false;
				 $blog = blog_list_next() )
	{ # by uncommenting the following lines you can hide some blogs
		// if( $blog == 1 ) continue; // Hide blog 1...
		?>
		<h3><a href="<?php blog_list_iteminfo('blogurl', 'raw' ) ?>" title="<?php blog_list_iteminfo( 'shortdesc', 'htmlattr'); ?>"><?php blog_list_iteminfo( 'name', 'htmlbody'); ?></a></h3>
		<ul>
		<?php	// Get the 3 last posts for each blog:
			$Blog_B = & $BlogCache->get_by_ID( $blog );

			$BlogBList = & new ItemList2( $Blog_B, NULL, 'now', 3 );

			$BlogBList->set_filters( array(
					'order' => 'DESC',
					'unit' => 'posts',
				) );

			// Run the query:
			$BlogBList->query();

			while( $Item = & $BlogBList->get_item() )
			{
				?>
				<li lang="<?php $Item->lang() ?>">
					<?php $Item->issue_date() ?>:
					<?php $Item->permanent_link( '#title#' ) ?>
					<span class="small">[<?php $Item->lang() ?>]</span>
				</li>
				<?php
			}
			?>
			<li><a href="<?php blog_list_iteminfo('blogurl', 'raw' ) ?>"><?php echo T_('More posts...') ?></a></li>
		</ul>
		<?php
	}
	// ---------------------------------- END OF BLOG LIST --------------------------------- ?>
<!-- InstanceEndEditable --></div>
<table cellspacing="3" class="wide">
  <tr>
  <td class="cartouche">Original page design by <a href="http://fplanque.net/">Fran&ccedil;ois PLANQUE</a> </td>

	<td class="cartouche" align="right"> <a href="http://b2evolution.net/" title="b2evolution home"><img src="rsc/img/b2evolution_button.png" alt="b2evolution" width="80" height="15" class="middle" /></a></td>
  </tr>
</table>
<p class="baseline"><!-- InstanceBeginEditable name="Baseline" -->

<!-- InstanceEndEditable --></p>
</body>
<!-- InstanceEnd --></html>