<?php
/**
 * This is a demo template displaying a summary of the last posts in each blog
 *
 * If you're new to b2evolution templates or skins, you should not start with this file
 * It will be easier to start examining blog_a.php or noskin_a.php for instance...
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2008 by Francois PLANQUE - {@link http://fplanque.net/}
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

load_funcs( 'skins/_skin.funcs.php' );

header( 'Content-type: text/html; charset='.$io_charset );
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php locale_lang() ?>" lang="<?php locale_lang() ?>"><!-- InstanceBegin template="/Templates/Standard.dwt" codeOutsideHTMLIsLocked="false" -->
<head>
<!-- InstanceBeginEditable name="doctitle" -->
<title><?php echo T_('Summary Demo'); ?></title>
<!-- InstanceEndEditable --> 
<link rel="stylesheet" href="rsc/css/fp02.css" type="text/css" />
<!-- InstanceBeginEditable name="head" -->
 <!-- InstanceEndEditable --> 
</head>
<body>
<!-- InstanceBeginEditable name="ToolBar" -->
	<?php
		// ---------------------------- TOOLBAR INCLUDED HERE ----------------------------
		require $skins_path.'_toolbar.inc.php';
		// ------------------------------- END OF TOOLBAR --------------------------------
	?>
<!-- InstanceEndEditable -->

<div class="pageHeader">
<div class="pageHeaderContent">

<!-- InstanceBeginEditable name="NavBar2" -->
<?php
	// --------------------------------- START OF BLOG LIST --------------------------------
	skin_widget( array(
						// CODE for the widget:
						'widget' => 'colls_list_public',
						// Optional display params
						'block_start' => '<div class="NavBar">',
						'block_end' => '</div>',
						'block_display_title' => false,
						'list_start' => '',
						'list_end' => '',
						'item_start' => '',
						'item_end' => '',
						'item_selected_start' => '',
						'item_selected_end' => '',
						'link_selected_class' => 'NavButton2',
						'link_default_class' => 'NavButton2',
				) );
	// ---------------------------------- END OF BLOG LIST ---------------------------------
?>
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

	load_class('items/model/_itemlist.class.php');

	$BlogCache = & get_Cache( 'BlogCache' );

	$blog_array = $BlogCache->load_public( 'ID' );

	foreach( $blog_array as $blog )
	{	// Loop through all public blogs:
		# by uncommenting the following lines you can hide some blogs
		// if( $blog == 2 ) continue; // Hide blog 2...

    /**
		 * @var Blog
		 */
		$l_Blog = & $BlogCache->get_by_ID( $blog );

		?>
		<h3><a href="<?php echo $l_Blog->gen_blogurl(); ?>" title="<?php $l_Blog->disp( 'shortdesc', 'htmlattr' ); ?>"><?php $l_Blog->disp( 'name', 'htmlattr' ); ?></a></h3>
		<ul>
		<?php	// Get the 3 last posts for each blog:

			$BlogBList = & new ItemList2( $l_Blog, NULL, 'now', 3 );

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
					<?php
						$Item->issue_date( array(
								'before'      => ' ',
								'after'       => ' ',
								'date_format' => '#',
							) );

						$Item->title( array(
								'link_type' => 'permalink',
							) );
					?>
					<span class="small">[<?php $Item->lang() ?>]</span>
				</li>
				<?php
			}
			?>
			<li><a href="<?php echo $l_Blog->gen_blogurl(); ?>"><?php echo T_('More posts...') ?></a></li>
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
<!-- InstanceBeginEditable name="Baseline" -->
<?php
	debug_info();
?>
<!-- InstanceEndEditable -->
</body>
<!-- InstanceEnd --></html>