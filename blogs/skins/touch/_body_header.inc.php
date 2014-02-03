<?php
/**
 * This is the BODY header include template.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-structure}
 *
 * This is meant to be included in a page template.
 *
 * @package evoskins
 * @subpackage evopress
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $dummy_fields;

?>

<!-- New noscript check, we need js on now folks -->
<noscript>
<div id="noscript-wrap">
	<div id="noscript">
		<h2><?php echo T_( 'Notice' ); ?></h2>
		<p><?php echo T_( 'JavaScript for Mobile Safari is currently turned off.' ); ?></p>
		<p><?php echo T_( 'Turn it on in <em> Settings &rsaquo; Safari </em><br /> to view this website.' ); ?></p>
	</div>
</div>
</noscript>

<div style="display: none;" id="wptouch-menu" class="dropper">
	<div id="wptouch-menu-inner">
		<div id="menu-head">
			<div id="tabnav">
				<a class="selected" href="#head-pages"><?php echo T_('Pages'); ?></a>
				<a href="#head-blogs"><?php echo T_('Blogs'); ?></a>
				<?php if( is_logged_in() ) { ?>
				<a href="#head-account"><?php echo T_('My Profile'); ?></a>
				<?php } else { ?>
				<a href="#head-account" id="loginopen"><?php echo T_('Login'); ?></a>
				<?php } ?>
			</div>

			<?php
				// ------------------------- "Menu" CONTAINER EMBEDDED HERE --------------------------
				// Display container and contents:
				skin_container( NT_('Menu'), array(
						// The following params will be used as defaults for widgets included in this container:
						'block_start' => '<ul id="head-pages">',
						'block_end' => '</ul>',
						'block_display_title' => false,
						'list_start' => '',
						'list_end' => '',
						'item_start' => '<li>',
						'item_end' => '</li>',
					) );
				// ----------------------------- END OF "Menu" CONTAINER -----------------------------
			?>
	
			<?php 
				// Display container and contents:
				skin_container( NT_('Page Top'), array(
						// The following params will be used as defaults for widgets included in this container:
						'block_start' => '<ul id="head-blogs">',
						'block_end' => '</ul>',
						'block_display_title' => false,
						'list_start' => '',
						'list_end' => '',
						'item_start' => '<li>',
						'item_end' => '</li>',
					) );
			?>

			<ul id="head-account">
			<?php
				if( is_logged_in() )
				{	// Build menu for logged in user
					if( $current_User->check_perm( 'admin', 'normal' ) )
					{	// User has a permission to access admin
						global $admin_url;
				?>
					<li><a href="<?php echo $admin_url; ?>"><?php echo T_('Admin'); ?></a></li>
				<?php } ?>
				<li><a href="<?php echo url_add_param( $Blog->get('url'), 'disp=user' ); ?>"><?php echo T_('My Profile'); ?></a></li>
				<?php
					if( $current_User->check_perm( 'perm_messaging', 'reply' ) )
					{	// User has access for messages module
				?>
				<li><a href="<?php echo url_add_param( $Blog->get('url'), 'disp=threads' ); ?>"><?php echo T_('My messages'); ?></a></li>
				<?php } ?>
				<li><a href="<?php echo get_user_logout_url(); ?>"><?php echo T_('Logout'); ?></a></li>
			<?php
				}
				else
				{	// Display info to login user
			?>
				<li class="text">
					<?php echo T_('Enter your username and password<br>in the boxes above.'); ?><br><br>
					<?php
						$source = param( 'source', 'string', 'inskin login form' );
						echo get_user_register_link( '<strong>', '</strong>', T_('No account yet? Register here').' &raquo;', '#', true /*disp_when_logged_in*/, '', $source );
					?>
				</li>
			<?php } ?>
			</ul>
		</div>
	</div>
</div>

<div id="headerbar">
	<div id="headerbar-title">
		<a href="<?php echo $Blog->get( 'url', 'raw' ); ?>"><img id="logo-icon" src="<?php echo $Skin->get_url(); ?>img/icon-pool/Default.png" alt="<?php echo $Blog->dget( 'name', 'text' ); ?>"></a>
		<a href="<?php echo $Blog->get( 'url', 'raw' ); ?>"><?php echo $Blog->dget( 'name', 'htmlbody' ); ?></a>
	</div>
	<div id="headerbar-menu">
		<a href="javascript:return false;"><?php echo T_( 'Menu' ); ?></a>
	</div>
</div>

<!--#start The Login Overlay -->
<div id="wptouch-login">
	<div id="wptouch-login-inner">
		<form name="loginform" id="loginform" action="<?php echo get_samedomain_htsrv_url() ?>login.php" method="post">
			<label><input type="text" name="<?php echo $dummy_fields['login'] ?>" id="log" placeholder="<?php echo T_( 'Login' ); ?>" value="" /></label>
			<label><input type="password" name="<?php echo $dummy_fields['pwd'] ?>" placeholder="<?php echo T_( 'Password' ); ?>" id="pwd" value="" /></label>
			<input type="submit" id="logsub" name="submit" value="<?php echo T_( 'Login' ); ?>" />
			<input type="hidden" name="redirect_to" value="<?php echo $_SERVER['REQUEST_URI']; ?>"/>
			<input type="hidden" value="<?php echo get_crumb( 'loginform' ); ?>" name="crumb_loginform" />
			<input type="hidden" value="login" name="login_action[login]" />
			<input type="hidden" value="<?php echo (int)use_in_skin_login(); ?>" name="inskin" />
			<a href="javascript:return false;"><img class="head-close" src="<?php echo $Skin->get_url(); ?>img/head-close.png" alt="close" /></a>
		</form>
	</div>
</div>

<!-- #start The Search Overlay -->
<div id="wptouch-search"> 
	<div id="wptouch-search-inner">
		<form method="get" id="searchform" action="<?php echo $Blog->gen_blogurl(); ?>">
			<input type="hidden" name="disp" value="search" />
			<input type="text" placeholder="<?php echo T_( 'Search...' ); ?>" name="s" id="search-input" /> 
			<input name="submit" type="submit" tabindex="1" id="search-submit" placeholder="<?php echo T_( 'Search...' ); ?>"  />
			<a href="javascript:return false;"><img class="head-close" src="<?php echo $Skin->get_url(); ?>img/head-close.png" alt="close" /></a>
		</form>
	</div>
</div>

<div id="drop-fade">
	<a id="searchopen" class="top" href="javascript:return%20false;">Search</a>
</div>

<div class="content">