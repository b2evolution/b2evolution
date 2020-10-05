// ---------------------------------------------------------------
// To get started with Grunt: see http://b2evolution.net/man/grunt
// ---------------------------------------------------------------
module.exports = function(grunt) {

	// Project configuration:
	grunt.initConfig({

		// Read project settings into the pkg property:
		// (Will allow to refer to the values of the properties below)
		pkg: grunt.file.readJSON('package.json'),

		// Configuration for the less->css compiling tasks:
		less: {
			development: {
			// Note! The output css files are not compressed on this task
				options: {
					compress: false,
					//yuicompress: true,
					//optimization: 2
				},
				files: {
					// target.css file: source.less file
					// 'rsc/build/testless.css': 'rsc/less/test.less',

					// Basic styles:
					'rsc/css/basic_styles.css': 'rsc/less/basic_styles.less',
					'rsc/css/basic.css':        'rsc/less/basic.less',
					'rsc/css/blog_base.css':    'rsc/less/blog_base.less',
					'rsc/css/item_base.css':    'rsc/less/item_base.less',

					// Fp> the following probaly needs to be merged with the font and back office bundles below
					'rsc/css/bootstrap-blog_base.css': 'rsc/less/bootstrap-blog_base.less', // Used on several back-office pages


					// Bootstrap front-office styles:
					'rsc/build/bootstrap-b2evo_base.bundle.css': [
							// Basic styles for all bootstrap skins
							'rsc/less/bootstrap-basic_styles.less',
							'rsc/less/bootstrap-basic.less',
							'rsc/less/bootstrap-blog_base.less',
							'rsc/less/bootstrap-item_base.less',
							'rsc/less/bootstrap-evoskins.less'			// Common styles for all bootstrap skins
						],

					// Superbundle Font-Awesome + Bootstrap + Front-office styles:
					'rsc/build/bootstrap-b2evo_base-superbundle.bundle.css': [
							'rsc/ext/font-awesome/css/font-awesome.css',
							'rsc/ext/bootstrap/css/bootstrap.css',
							'rsc/build/bootstrap-b2evo_base.bundle.css',
						],

					// Bootstrap back-office styles:
					'rsc/build/bootstrap-backoffice-b2evo_base.bundle.css': [
							// Basic styles for all bootstrap skins
							'rsc/less/bootstrap-basic_styles.less',
							'rsc/less/bootstrap-basic.less',
							'rsc/less/bootstrap-item_base.less',		// fp> I added this because blockquote was not properly styled in the backoffice
							'rsc/less/bootstrap-evoskins.less'			// Common styles for all bootstrap skins
						],

					// Back-office bootstrap skin styles:
					'skins_adm/bootstrap/rsc/css/style.bundle.css': [
							'skins_adm/bootstrap/rsc/css/style.less',
							'rsc/less/inc/jquery.easy-pie-chart.inc.less',
							'rsc/less/inc/jquery.jqplot.inc.less',
						],

					// Bootstrap skins
					'skins/green_bootstrap_theme/style.css':        'skins/green_bootstrap_theme/style.less',
					'skins/green_bootstrap_theme/std/style.css':    'skins/green_bootstrap_theme/std/style.less',
					'skins/green_bootstrap_theme/photo/style.css':  'skins/green_bootstrap_theme/photo/style.less',
					'skins/green_bootstrap_theme/forum/style.css':  'skins/green_bootstrap_theme/forum/style.less',
					'skins/green_bootstrap_theme/manual/style.css': 'skins/green_bootstrap_theme/manual/style.less',
					'skins/bootstrap_blog_skin/style.css':          'skins/bootstrap_blog_skin/style.less',
					'skins/bootstrap_blocks_blog_skin/style.css':   'skins/bootstrap_blocks_blog_skin/style.less',
					'skins/bootstrap_main_skin/style.css':          'skins/bootstrap_main_skin/style.less',
					'skins/bootstrap_forums_skin/style.css':        'skins/bootstrap_forums_skin/style.less',
					'skins/bootstrap_gallery_legacy/style.css':     'skins/bootstrap_gallery_legacy/style.less',
					'skins/bootstrap_gallery_skin/style.css':       'skins/bootstrap_gallery_skin/style.less',
					'skins/bootstrap_manual_skin/style.css':        'skins/bootstrap_manual_skin/style.less',
					'skins/bootstrap_photoblog_skin/style.css':     'skins/bootstrap_photoblog_skin/style.less',
					'skins/jared_skin/style.css':                   'skins/jared_skin/style.less',
					'skins/tabs_bootstrap_home_skin/style.css':     'skins/tabs_bootstrap_home_skin/style.less',
					'skins/default_site_skin/style.css':            'skins/default_site_skin/style.less',
					'skins/bootstrap_site_dropdown_skin/style.css': 'skins/bootstrap_site_dropdown_skin/style.less',
					'skins/bootstrap_site_navbar_skin/style.css':   'skins/bootstrap_site_navbar_skin/style.less',
					'skins/bootstrap_site_tabs_skin/style.css':     'skins/bootstrap_site_tabs_skin/style.less',
					'skins_adm/bootstrap/rsc/css/style.css':        'skins_adm/bootstrap/rsc/css/style.less',

					// Helper pages
					'rsc/build/b2evo_helper_screens.css':    'rsc/less/b2evo_helper_screens.less',

					// Colorbox
					'rsc/customized/jquery/colorbox/css/colorbox-regular.css':   'rsc/customized/jquery/colorbox/css/colorbox-regular.less',
					'rsc/customized/jquery/colorbox/css/colorbox-bootstrap.css': 'rsc/customized/jquery/colorbox/css/colorbox-bootstrap.less',

					// evo helpdesk widget
					'rsc/css/evo_helpdesk_widget.css': 'rsc/less/evo_helpdesk_widget.less',

					// info dots plugin
					'plugins/infodots_plugin/infodots.css': 'plugins/infodots_plugin/infodots.less',

					// Video plugin
					'plugins/videoplug_plugin/css/videoplug.css': 'plugins/videoplug_plugin/css/videoplug.less',
				}
			},

			// fp> I removed the 'compress' task because when we want to compress, we should use 'cssmin' which is more efficient and also used '*.bmin.css' filenames
		},

		// Configuration for the scss->css compiling tasks:
		// sass: {
			// development: {
				// options: {
					// style: 'expanded',
				// },
				// files: {
					// target.css file: source.scss file
				// }
			// }
		// },

		// Configuration for Autoprefixing tasks:
		autoprefixer: {
			options: {
				// by default autoprefixer will remove old, no longer needed, prefixes:
				browsers: ['last 5 versions']
			},
			dist: {
				src: ['rsc/build/*.css','rsc/css/*.css','rsc/css/colorbox/*.css','skins/**/*.css','skins_adm/**/*.css', // INCLUDE patterns
						'!**/*.bundle.css','!**/*.bmin.css','!**/*.min.css'] // EXCLUDE patterns
			}
		},

		// Configuration for the concatenate tasks:
		concat: {
			options: {
				// The following will appear on top of the created files:
				// banner: '/*! <%= pkg.name %> v<%= pkg.version %> */\n',
			},
			/*
			 * CSS
			 */
			b2evo_base: {
				options: {
					banner: '<%= concat.options.banner %>/* This includes: basic_styles.css, basic.css, blog_base.css, item_base.css */\n'
				},
				nonull: true, // Display missing files
				src: ['rsc/css/basic_styles.css', 'rsc/css/basic.css', 'rsc/css/blog_base.css', 'rsc/css/item_base.css'],
				dest: 'rsc/build/b2evo_base.bundle.css',
			},
			/*
			 * JS:
			 */
			// Login screen:
			sha1_md5: {
				src: ['rsc/ext/sha1.js', 'rsc/ext/md5.js', 'rsc/ext/twin-bcrypt.js'],
				dest: 'rsc/js/build/sha1_md5.bundle.js',
			},
		},

		// CSS minification:
		cssmin: {
			options: {
			},
			b2evo_base: {
				nonull: true, // Display missing files
				src: 'rsc/build/b2evo_base.bundle.css',
				dest: 'rsc/build/b2evo_base.bmin.css',
			},
			bootstrap_b2evo_base: {
				nonull: true, // Display missing files
				src: 'rsc/build/bootstrap-b2evo_base.bundle.css',
				dest: 'rsc/build/bootstrap-b2evo_base.bmin.css',
			},
			bootstrap_b2evo_base_superbundle: {
				nonull: true, // Display missing files
				src: 'rsc/build/bootstrap-b2evo_base-superbundle.bundle.css',
				dest: 'rsc/build/bootstrap-b2evo_base-superbundle.bmin.css',
			},
			bootstrap_backoffice_b2evo_base: {
				nonull: true, // Display missing files
				src: 'rsc/build/bootstrap-backoffice-b2evo_base.bundle.css',
				dest: 'rsc/build/bootstrap-backoffice-b2evo_base.bmin.css',
			},
			backoffice_bootstrap_skin_style: {
				nonull: true, // Display missing files
				src: 'skins_adm/bootstrap/rsc/css/style.bundle.css',
				dest: 'skins_adm/bootstrap/rsc/css/style.bmin.css',
			},
			bootstrap_skins: {
				files: {
					// Bootstrap skins
					'skins/green_bootstrap_theme/style.min.css':        'skins/green_bootstrap_theme/style.css',
					'skins/green_bootstrap_theme/std/style.min.css':    'skins/green_bootstrap_theme/std/style.css',
					'skins/green_bootstrap_theme/photo/style.min.css':  'skins/green_bootstrap_theme/photo/style.css',
					'skins/green_bootstrap_theme/forum/style.min.css':  'skins/green_bootstrap_theme/forum/style.css',
					'skins/green_bootstrap_theme/manual/style.min.css': 'skins/green_bootstrap_theme/manual/style.css',
					'skins/bootstrap_blog_skin/style.min.css':          'skins/bootstrap_blog_skin/style.css',
					'skins/bootstrap_blocks_blog_skin/style.min.css':   'skins/bootstrap_blocks_blog_skin/style.css',
					'skins/bootstrap_main_skin/style.min.css':          'skins/bootstrap_main_skin/style.css',
					'skins/bootstrap_forums_skin/style.min.css':        'skins/bootstrap_forums_skin/style.css',
					'skins/bootstrap_gallery_legacy/style.min.css':     'skins/bootstrap_gallery_legacy/style.css',
					'skins/bootstrap_gallery_skin/style.min.css':       'skins/bootstrap_gallery_skin/style.css',
					'skins/bootstrap_manual_skin/style.min.css':        'skins/bootstrap_manual_skin/style.css',
					'skins/bootstrap_photoblog_skin/style.min.css':     'skins/bootstrap_photoblog_skin/style.css',
					'skins/jared_skin/style.min.css':                   'skins/jared_skin/style.css',
					'skins/tabs_bootstrap_home_skin/style.min.css':     'skins/tabs_bootstrap_home_skin/style.css',
					'skins/default_site_skin/style.min.css':            'skins/default_site_skin/style.css',
					'skins/bootstrap_site_dropdown_skin/style.min.css': 'skins/bootstrap_site_dropdown_skin/style.css',
					'skins/bootstrap_site_navbar_skin/style.min.css':   'skins/bootstrap_site_navbar_skin/style.css',
					'skins/bootstrap_site_tabs_skin/style.min.css':     'skins/bootstrap_site_tabs_skin/style.css',
					'skins_adm/bootstrap/rsc/css/style.min.css':        'skins_adm/bootstrap/rsc/css/style.css',
				}
			},
			colorbox: {
				files: {
					'rsc/build/colorbox-regular.min.css':   'rsc/customized/jquery/colorbox/css/colorbox-regular.css',
					'rsc/build/colorbox-bootstrap.min.css': 'rsc/customized/jquery/colorbox/css/colorbox-bootstrap.css',
				}
			},
			ddexitpop: {
				src: [ 'rsc/css/ddexitpop/ddexitpop.css', 'rsc/css/ddexitpop/animate.min.css' ],
				dest: 'rsc/build/ddexitpop.bmin.css',
			},
			evo_helpdesk_widget: {
				src: 'rsc/css/evo_helpdesk_widget.css',
				dest: 'rsc/css/evo_helpdesk_widget.min.css',
			},
			helper_pages: {
				src: 'rsc/build/b2evo_helper_screens.css',
				dest: 'rsc/build/b2evo_helper_screens.min.css',
			},
			jqplot: {
				src: [ 'rsc/ext/jquery/jqplot/css/jquery.jqplot.css', 'rsc/ext/jquery/jqplot/css/jquery.jqplot.b2evo.css' ],
				dest: 'rsc/build/b2evo_jqplot.bmin.css',
			},
			videoplug: {
				src: 'plugins/videoplug_plugin/css/videoplug.css',
				dest: 'plugins/videoplug_plugin/css/videoplug.min.css',
			}
		},

		// Configuration for the uglify minifying tasks:
		uglify: {
			// Login screen:
			sha1_md5: {
				nonull: true, // Display missing files
				src: ['rsc/js/build/sha1_md5.bundle.js'],
				dest: 'rsc/js/build/sha1_md5.bmin.js'
			},
			// Another Target:
			/*  Early tests:
			functionsjs: {
				nonull: true, // Display missing files
				src: 'rsc/js/functions.js',
				dest: 'rsc/js/build/functions.min.js'
			},
			*/
			// TinyMCE
			tinymce: {
				files: {
					'rsc/ext/tiny_mce/plugins/image/plugin.min.js': 'rsc/ext/tiny_mce/plugins/image/plugin.js',
					'rsc/ext/tiny_mce/plugins/link/plugin.min.js': 'rsc/ext/tiny_mce/plugins/link/plugin.js',
					'rsc/ext/tiny_mce/plugins/b2evo_attachments/plugin.min.js': 'rsc/ext/tiny_mce/plugins/b2evo_attachments/plugin.js',
					'rsc/ext/tiny_mce/plugins/b2evo_shorttags/plugin.min.js': 'rsc/ext/tiny_mce/plugins/b2evo_shorttags/plugin.js',
					'rsc/ext/tiny_mce/plugins/evo_view/plugin.min.js': 'rsc/ext/tiny_mce/plugins/evo_view/plugin.js',
					'plugins/tinymce_plugin/js/evo_view_shortcodes.bmin.js': ['plugins/tinymce_plugin/js/shortcodes.js', 'plugins/tinymce_plugin/js/evo_view.js'],
				}
			},

			// Colorbox + Voting + Touchswipe
			colorbox: {
				options: {
					banner: '/* This includes 4 files: jquery.colorbox.js, voting.js, jquery.touchswipe.js, colorbox.init.js */\n'
				},
				nonull: true, // Display missing files
				src: ['rsc/customized/jquery/colorbox/js/jquery.colorbox.js', 'rsc/js/voting.js', 'rsc/ext/jquery/touchswipe/jquery.touchswipe.js', 'rsc/js/colorbox.init.js'],
				dest: 'rsc/js/build/colorbox.bmin.js'
			},
			// Bubbletip
			bubbletip: {
				options: {
					banner: '/* This includes 3 files: bubbletip.js, popover.js, userfields.js */\n'
				},
				nonull: true, // Display missing files
				// fp>yura: why isn't jquery.bubbletip.js bundled into this?
				// if popover.js is used only for editing we should probably move it to a textedit.bundle
				src: ['rsc/js/bubbletip.js', 'rsc/js/popover.js', 'rsc/js/userfields.js'],
				dest: 'rsc/js/build/bubbletip.bmin.js'
			},
			// Popover (Analog of bubbletip on bootstrap skins)
			popover: {
				options: {
					banner: '/* This includes 4 files: bootstrap/usernames.js, bootstrap/popover.js, bootstrap/userfields.js, bootstrap/formfields.js */\n'
				},
				nonull: true, // Display missing files
				src: ['rsc/js/bootstrap/usernames.js', 'rsc/js/bootstrap/popover.js', 'rsc/js/bootstrap/userfields.js', 'rsc/js/bootstrap/formfields.js'],
				dest: 'rsc/js/build/popover.bmin.js'
			},
			// Textcomplete plugin to suggest user names in textareas with '@username'
			textcomplete: {
				options: {
					banner: '/* This includes 2 files: jquery.textcomplete.js, textcomplete.init.js */\n'
				},
				nonull: true, // Display missing files
				src: ['rsc/ext/jquery/textcomplete/jquery.textcomplete.js', 'rsc/ext/jquery/textcomplete/textcomplete.init.js'],
				dest: 'rsc/js/build/textcomplete.bmin.js'
			},
			// JS files that are used marketing popup container:
			ddexitpop: {
				options: { banner: '/* This includes ddexitpop files to initialize marketing popup container */\n' },
				nonull: true, // Display missing files
				src: ['rsc/js/src/ddexitpop.js', 'rsc/js/src/evo_init_ddexitpop.js'],
				dest: 'rsc/js/build/ddexitpop.bmin.js'
			},
			// JS files that may be used on ANY page of front-office and back-office
			evo_generic: {
				options: {
					banner: '/* This file includes ALL generic files that may be used on any page of front-office and back-office */\n'
				},
				nonull: true, // Display missing files
				src: [
					'rsc/js/src/evo_generic_functions.js',
					'rsc/js/src/evo_init_generic_jquery_ready_functions.js',
					'rsc/js/src/evo_init_password_indicator.js',
					'rsc/js/src/evo_init_password_edit.js',
					'rsc/js/src/evo_init_login_validator.js',
					'rsc/js/src/evo_init_skin_bootstrap_forums.js',
					'rsc/js/src/evo_init_autocomplete_login.js',
					'rsc/js/src/evo_init_widget_poll.js',
					'rsc/js/src/evo_init_widget_item_checklist_lines.js',
					'rsc/js/src/evo_init_plugin_auto_anchors.js',
					'rsc/js/src/evo_init_plugin_custom_tags.js',
					'rsc/js/src/evo_init_plugin_table_contents.js',
					'rsc/js/src/evo_init_plugin_shortlinks.js',
					'rsc/js/src/evo_init_plugin_inlines.js',
					'rsc/js/src/evo_init_plugin_markdown.js',
					'rsc/js/src/evo_init_plugin_polls.js',
					'rsc/js/src/evo_init_plugin_shortcodes.js',
					'rsc/js/src/evo_init_plugin_widescroll.js',
					'rsc/js/src/evo_init_plugin_videoplug.js',
					'rsc/js/src/evo_init_editable_column.js',
					'rsc/js/src/evo_init_regional.js',
					'rsc/js/src/evo_init_bootstrap_tooltips.js',
					'rsc/js/src/evo_comment_funcs.js',
					'rsc/js/src/evo_user_funcs.js',
					'rsc/js/build/colorbox.bmin.js',
				],
				dest: 'rsc/js/build/evo_generic.bmin.js'
			},
			// JS files that are used on front-office standard skins:
			evo_frontoffice: {
				options: {
					banner: '/* This includes 11 files: build/evo_generic.bmin.js, src/evo_modal_window.js, src/evo_images.js, src/evo_user_crop.js, src/evo_user_report.js, src/evo_user_contact_groups.js, src/evo_rest_api.js, src/evo_item_flag.js, src/evo_links.js, src/evo_forms.js, ajax.js */\n'
				},
				nonull: true, // Display missing files
				src: ['rsc/js/build/evo_generic.bmin.js',
							'rsc/js/src/evo_modal_window.js',
							'rsc/js/src/evo_images.js',
							'rsc/js/src/evo_user_crop.js',
							'rsc/js/src/evo_user_report.js',
							'rsc/js/src/evo_user_contact_groups.js',
							'rsc/js/src/evo_rest_api.js',
							'rsc/js/src/evo_item_flag.js',
							'rsc/js/src/evo_links.js',
							'rsc/js/src/evo_forms.js',
							'rsc/js/ajax.js'],
				dest: 'rsc/js/build/evo_frontoffice.bmin.js'
			},
			// JS files that are used on front-office bootstrap skins:
			evo_frontoffice_bootstrap: {
				options: {
					banner: '/* This includes 11 files: build/evo_generic.bmin.js, src/bootstrap-evo_modal_window.js, src/evo_images.js, src/evo_user_crop.js, src/evo_user_report.js, src/evo_user_contact_groups.js, src/evo_rest_api.js, src/evo_item_flag.js, src/evo_links.js, src/evo_forms.js, ajax.js */\n'
				},
				nonull: true, // Display missing files
				src: ['rsc/js/build/evo_generic.bmin.js',
							'rsc/js/src/bootstrap-evo_modal_window.js',
							'rsc/js/src/evo_images.js',
							'rsc/js/src/evo_user_crop.js',
							'rsc/js/src/evo_user_report.js',
							'rsc/js/src/evo_user_contact_groups.js',
							'rsc/js/src/evo_rest_api.js',
							'rsc/js/src/evo_item_flag.js',
							'rsc/js/src/evo_links.js',
							'rsc/js/src/evo_forms.js',
							'rsc/js/ajax.js'],
				dest: 'rsc/js/build/bootstrap-evo_frontoffice.bmin.js'
			},
			// JS files(bundled with jQuery and Bootstrap) that are used on front-office bootstrap skins:
			evo_frontoffice_bootstrap_superbundle: {
				options: {
					banner: '/* Includes files for bootstrap front-office skins */\n'
				},
				nonull: true, // Display missing files
				src: [
					'rsc/ext/jquery/jquery.min.js',
					'rsc/ext/jquery/jquery-migrate.min.js',
					'rsc/ext/jquery/ui/js/jquery.ui.b2evo.min.js',
					'rsc/ext/bootstrap/js/bootstrap.min.js',
					'rsc/js/build/bootstrap-evo_frontoffice.bmin.js',
				],
				dest: 'rsc/js/build/bootstrap-evo_frontoffice-superbundle.bmin.js'
			},
			// JS files that are used on back-office standard skins:
			evo_backoffice: {
				options: {
					banner: '/* This includes 23 files: build/evo_generic.bmin.js, functions.js, ajax.js, communication.js, form_extensions.js, extracats.js, dynamic_select.js, backoffice.js, blog_widgets.js,'+
						'src/evo_modal_window.js, src/evo_images.js, src/evo_user_crop.js, src/evo_user_report.js, src/evo_user_deldata.js, '+
						'src/evo_user_org.js, src/evo_automation.js, src/evo_user_tags.js, src/evo_user_status.js, src/evo_user_groups.js, src/evo_rest_api.js, src/evo_links.js, src/evo_forms.js, src/evo_input_counter.js */\n'
				},
				nonull: true, // Display missing files
				src: ['rsc/js/build/evo_generic.bmin.js',
							'rsc/js/functions.js',
							'rsc/js/ajax.js',
							'rsc/js/communication.js',
							'rsc/js/form_extensions.js',
							'rsc/js/extracats.js',
							'rsc/js/dynamic_select.js',
							'rsc/js/backoffice.js',
							'rsc/js/blog_widgets.js',
							'rsc/js/src/evo_modal_window.js',
							'rsc/js/src/evo_images.js',
							'rsc/js/src/evo_user_crop.js',
							'rsc/js/src/evo_user_report.js',
							'rsc/js/src/evo_user_deldata.js',
							'rsc/js/src/evo_user_org.js',
							'rsc/js/src/evo_automation.js',
							'rsc/js/src/evo_user_tags.js',
							'rsc/js/src/evo_user_status.js',
							'rsc/js/src/evo_user_groups.js',
							'rsc/js/src/evo_user_filters.js',
							'rsc/js/src/evo_rest_api.js',
							'rsc/js/src/evo_files.js',
							'rsc/js/src/evo_links.js',
							'rsc/js/src/evo_forms.js',
							'rsc/js/src/evo_input_counter.js'],
				dest: 'rsc/js/build/evo_backoffice.bmin.js'
			},
			// JS files that are used on back-office bootstrap skins:
			evo_backoffice_bootstrap: {
				options: {
					banner: '/* This includes 23 files: build/evo_generic.bmin.js, functions.js, ajax.js, communication.js, form_extensions.js, extracats.js, dynamic_select.js, backoffice.js, '+
						'blog_widgets.js, src/bootstrap-evo_modal_window.js, src/evo_images.js, src/evo_user_crop.js, src/evo_user_report.js, src/evo_user_deldata.js, '+
						'src/evo_user_org.js, src/evo_automation.js, src/evo_user_tags.js, src/evo_user_status.js, src/evo_user_groups.js, src/evo_rest_api.js, src/evo_links.js, src/evo_forms.js, src/evo_input_counter.js */\n'
				},
				nonull: true, // Display missing files
				src: ['rsc/js/build/evo_generic.bmin.js',
							'rsc/js/functions.js',
							'rsc/js/ajax.js',
							'rsc/js/communication.js',
							'rsc/js/form_extensions.js',
							'rsc/js/extracats.js',
							'rsc/js/dynamic_select.js',
							'rsc/js/backoffice.js',
							'rsc/js/blog_widgets.js',
							'rsc/js/src/bootstrap-evo_modal_window.js',
							'rsc/js/src/evo_images.js',
							'rsc/js/src/evo_user_crop.js',
							'rsc/js/src/evo_user_report.js',
							'rsc/js/src/evo_user_deldata.js',
							'rsc/js/src/evo_user_org.js',
							'rsc/js/src/evo_automation.js',
							'rsc/js/src/evo_user_tags.js',
							'rsc/js/src/evo_user_status.js',
							'rsc/js/src/evo_user_groups.js',
							'rsc/js/src/evo_user_filters.js',
							'rsc/js/src/evo_rest_api.js',
							'rsc/js/src/evo_files.js',
							'rsc/js/src/evo_links.js',
							'rsc/js/src/evo_forms.js',
							'rsc/js/src/evo_input_counter.js'],
				dest: 'rsc/js/build/bootstrap-evo_backoffice.bmin.js'
			},
			evo_helpdesk_widget: {
				src: 'rsc/js/evo_helpdesk_widget.js',
				dest: 'rsc/js/evo_helpdesk_widget.min.js',
			},
			evo_fileuploader: {
				options: {
					banner: '/* This file includes ALL files that are used for quick file uploader */\n'
				},
				nonull: true, // Display missing files
				src: [
					'rsc/customized/fileuploader/js/fine-uploader.js',
					'rsc/js/src/evo_init_dragdrop_button.js',
					'rsc/js/src/evo_init_attachment_fieldset.js',
				],
				dest: 'rsc/js/build/evo_fileuploader.bmin.js'
			},
			evo_fileuploader_sortable: {
				options: {
					banner: '/* This file includes ALL files that are used for quick file uploader with sortable feature for attachments */\n'
				},
				nonull: true, // Display missing files
				src: [
					'rsc/js/build/evo_fileuploader.bmin.js',
					'rsc/ext/jquery/sortable/jquery.sortable.min.js',
					'rsc/js/src/evo_init_link_sortable.js',
				],
				dest: 'rsc/js/build/evo_fileuploader_sortable.bmin.js'
			},
			evo_jqplot: {
				options: {
					banner: '/* This file includes ALL files that are used for drawing charts using jqplot */\n'
				},
				nonull: true, // Display missing files
				src: [
					'rsc/ext/jquery/jqplot/js/jquery.jqplot.min.js',
					'rsc/ext/jquery/jqplot/js/jqplot.barRenderer.min.js',
					'rsc/ext/jquery/jqplot/js/jqplot.canvasAxisTickRenderer.min.js',
					'rsc/ext/jquery/jqplot/js/jqplot.canvasTextRenderer.min.js',
					'rsc/ext/jquery/jqplot/js/jqplot.canvasOverlay.min.js',
					'rsc/ext/jquery/jqplot/js/jqplot.categoryAxisRenderer.min.js',
					'rsc/ext/jquery/jqplot/js/jqplot.donutRenderer.min.js',
					'rsc/ext/jquery/jqplot/js/jqplot.enhancedLegendRenderer.min.js',
					'rsc/ext/jquery/jqplot/js/jqplot.highlighter.min.js',	
				],
				dest: 'rsc/js/build/evo_jqplot.bmin.js'
			}
		},

		// Markdown to HTML
		markdown: {
			readme: {
				options: {
					template: 'readme.template.html'
				},
				files: [{
					expand: true,
					src: 'readme.md',
					dest: '',
					ext: '.html'
				}]
			},
			conf_error: {
				options: {
					template: 'skins_adm/conf_error.main.template.php',
				},
				files: [{
					expand: true,
					src: 'skins_adm/conf_error.main.md',
					dest: '',
					ext: '.main.php'
				}]
			}
		},

		// Configuration for the watch tasks:
		watch: {
			/* Early tests:
			functionsjs: {
				files: ['rsc/js/functions.js'],
				tasks: ['uglify:functionsjs'],
			},
			ajaxcomjs: {
				files: ['rsc/js/ajax.js', 'rsc/js/communication.js'],
				tasks: ['uglify:ajaxcomjs'],
			},
			*/
			less: {
				// Which files to watch (all .less files recursively)
				files: ['**/*.less'],
				tasks: ['less'],
				options: {
					nospawn: true,
				}
			},
			sass: {
				// Which files to watch (all .scss files recursively)
				files: ['**/*.scss'],
				tasks: ['sass'],
				options: {
					nospawn: true,
				}
			},
			concat_autoprefixer_cssmin: {
				// Which files to watch (all .css files recursively)
				files: ['**/*.css'],
				tasks: ['autoprefixer','concat','cssmin'],
				options: {
					nospawn: true,
				}
			},
			markdown: {
				files: ['readme.md','readme.template.html','skins_adm/conf_error.main.md','skins_adm/conf_error.main.template.php'],
				tasks: ['markdown']
			}
		},

	});

	// Load the plugin that provides the tasks ( "uglify", "less", "sass", etc. ):
	grunt.loadNpmTasks('grunt-contrib-less');
	grunt.loadNpmTasks('grunt-contrib-sass');
	grunt.loadNpmTasks('grunt-autoprefixer');
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-contrib-cssmin');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.loadNpmTasks('grunt-contrib-watch');
	grunt.loadNpmTasks('grunt-markdown');

	// Default task(s):
	grunt.registerTask('default', ['less','autoprefixer','concat','cssmin','uglify','markdown']);
	grunt.registerTask('styles', ['less','autoprefixer','concat','cssmin']);

};
