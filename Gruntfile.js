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
					'rsc/build/testless.css': 'rsc/less/test.less',

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

					// Bootstrap back-office styles:
					'rsc/build/bootstrap-backoffice-b2evo_base.bundle.css': [
							// Basic styles for all bootstrap skins
							'rsc/less/bootstrap-basic_styles.less',
							'rsc/less/bootstrap-basic.less',
							'rsc/less/bootstrap-item_base.less',		// fp> I added this because blockquote was not properly styled in the backoffice
							'rsc/less/bootstrap-evoskins.less'			// Common styles for all bootstrap skins
						],

					// Bootstrap skins
					'skins/bootstrap_blog_skin/style.css':      'skins/bootstrap_blog_skin/style.less',
					'skins/bootstrap_main_skin/style.css':      'skins/bootstrap_main_skin/style.less',
					'skins/bootstrap_forums_skin/style.css':    'skins/bootstrap_forums_skin/style.less',
					'skins/bootstrap_gallery_legacy/style.css': 'skins/bootstrap_gallery_legacy/style.less',
					'skins/bootstrap_gallery_skin/style.css':   'skins/bootstrap_gallery_skin/style.less',
					'skins/bootstrap_manual_skin/style.css':    'skins/bootstrap_manual_skin/style.less',
					'skins/bootstrap_photoblog_skin/style.css': 'skins/bootstrap_photoblog_skin/style.less',
					'skins_adm/bootstrap/rsc/css/style.css':    'skins_adm/bootstrap/rsc/css/style.less',

					// Helper pages
					'rsc/build/b2evo_helper_screens.css':    'rsc/less/b2evo_helper_screens.less',

					// Colorbox
					'rsc/css/colorbox/colorbox-regular.css':   'rsc/css/colorbox/colorbox-regular.less',
					'rsc/css/colorbox/colorbox-bootstrap.css': 'rsc/css/colorbox/colorbox-bootstrap.less',
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
			skin_evopress: {
				nonull: true, // Display missing files
				src: ['skins/evopress/style.css', 'skins/evopress/item.css'],
				dest: 'skins/evopress/evopress.bundle.css',
			},
			/*
			 * JS:
			 */
			// Login screen:
			sha1_md5: {
				src: ['rsc/js/src/sha1.js', 'rsc/js/src/md5.js', 'rsc/js/src/twin-bcrypt.js'],
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
			bootstrap_backoffice_b2evo_base: {
				nonull: true, // Display missing files
				src: 'rsc/build/bootstrap-backoffice-b2evo_base.bundle.css',
				dest: 'rsc/build/bootstrap-backoffice-b2evo_base.bmin.css',
			},
			bootstrap_skins: {
				files: {
					// Bootstrap skins
					'skins/bootstrap_blog_skin/style.min.css':      'skins/bootstrap_blog_skin/style.css',
					'skins/bootstrap_main_skin/style.min.css':      'skins/bootstrap_main_skin/style.css',
					'skins/bootstrap_forums_skin/style.min.css':    'skins/bootstrap_forums_skin/style.css',
					'skins/bootstrap_gallery_legacy/style.min.css': 'skins/bootstrap_gallery_legacy/style.css',
					'skins/bootstrap_gallery_skin/style.min.css':   'skins/bootstrap_gallery_skin/style.css',
					'skins/bootstrap_manual_skin/style.min.css':    'skins/bootstrap_manual_skin/style.css',
					'skins/bootstrap_photoblog_skin/style.min.css': 'skins/bootstrap_photoblog_skin/style.css',
					'skins_adm/bootstrap/rsc/css/style.min.css':    'skins_adm/bootstrap/rsc/css/style.css',
				}
			},
			skin_evopress: {
				src: 'skins/evopress/evopress.bundle.css',
				dest: 'skins/evopress/evopress.bmin.css',
			},
			colorbox: {
				files: {
					'rsc/build/colorbox-regular.min.css':   'rsc/css/colorbox/colorbox-regular.css',
					'rsc/build/colorbox-bootstrap.min.css': 'rsc/css/colorbox/colorbox-bootstrap.css',
				}
			},
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
			// Colorbox + Voting + Touchswipe
			colorbox: {
				options: {
					banner: '/* This includes 4 files: jquery.colorbox.js, voting.js, jquery.touchswipe.js, colorbox.init.js */\n'
				},
				nonull: true, // Display missing files
				src: ['rsc/js/colorbox/jquery.colorbox.js', 'rsc/js/voting.js', 'rsc/js/jquery/jquery.touchswipe.js', 'rsc/js/colorbox/colorbox.init.js'],
				dest: 'rsc/js/build/colorbox.bmin.js'
			},
			// Bubbletip
			bubbletip: {
				options: {
					banner: '/* This includes 4 files: bubbletip.js, plugins.js, userfields.js, colorpicker.js */\n'
				},
				nonull: true, // Display missing files
				// fp>yura: why isn't jquery.bubbletip.js bundled into this?
				// if plugins.js is used only for editing we should probably move it to a textedit.bundle
				src: ['rsc/js/bubbletip.js', 'rsc/js/plugins.js', 'rsc/js/userfields.js', 'rsc/js/colorpicker.js'],
				dest: 'rsc/js/build/bubbletip.bmin.js'
			},
			// Popover (Analog of bubbletip on bootstrap skins)
			popover: {
				options: {
					banner: '/* This includes 5 files: bootstrap/usernames.js, bootstrap/plugins.js, bootstrap/userfields.js, bootstrap/colorpicker.js, bootstrap/formfields.js */\n'
				},
				nonull: true, // Display missing files
				src: ['rsc/js/bootstrap/usernames.js', 'rsc/js/bootstrap/plugins.js', 'rsc/js/bootstrap/userfields.js', 'rsc/js/bootstrap/colorpicker.js', 'rsc/js/bootstrap/formfields.js'],
				dest: 'rsc/js/build/popover.bmin.js'
			},
			// Textcomplete plugin to suggest user names in textareas with '@username'
			textcomplete: {
				options: {
					banner: '/* This includes 2 files: jquery.textcomplete.js, textcomplete.init.js */\n'
				},
				nonull: true, // Display missing files
				src: ['rsc/js/jquery/jquery.textcomplete.js', 'rsc/js/textcomplete.init.js'],
				dest: 'rsc/js/build/textcomplete.bmin.js'
			},
			// JS files that are used on front-office standard skins:
			evo_frontoffice: {
				options: {
					banner: '/* This includes 9 files: src/evo_modal_window.js, src/evo_images.js, src/evo_user_crop.js, src/evo_user_report.js, src/evo_user_contact_groups.js, src/evo_rest_api.js, src/evo_item_flag.js, src/evo_links.js, ajax.js */\n'
				},
				nonull: true, // Display missing files
				src: ['rsc/js/src/evo_modal_window.js',
							'rsc/js/src/evo_images.js',
							'rsc/js/src/evo_user_crop.js',
							'rsc/js/src/evo_user_report.js',
							'rsc/js/src/evo_user_contact_groups.js',
							'rsc/js/src/evo_rest_api.js',
							'rsc/js/src/evo_item_flag.js',
							'rsc/js/src/evo_links.js',
							'rsc/js/ajax.js'],
				dest: 'rsc/js/build/evo_frontoffice.bmin.js'
			},
			// JS files that are used on front-office bootstrap skins:
			evo_frontoffice_bootstrap: {
				options: {
					banner: '/* This includes 9 files: src/bootstrap-evo_modal_window.js, src/evo_images.js, src/evo_user_crop.js, src/evo_user_report.js, src/evo_user_contact_groups.js, src/evo_rest_api.js, src/evo_item_flag.js, src/evo_links.js, ajax.js */\n'
				},
				nonull: true, // Display missing files
				src: ['rsc/js/src/bootstrap-evo_modal_window.js',
							'rsc/js/src/evo_images.js',
							'rsc/js/src/evo_user_crop.js',
							'rsc/js/src/evo_user_report.js',
							'rsc/js/src/evo_user_contact_groups.js',
							'rsc/js/src/evo_rest_api.js',
							'rsc/js/src/evo_item_flag.js',
							'rsc/js/src/evo_links.js',
							'rsc/js/ajax.js'],
				dest: 'rsc/js/build/bootstrap-evo_frontoffice.bmin.js'
			},
			// JS files that are used on back-office standard skins:
			evo_backoffice: {
				options: {
					banner: '/* This includes 15 files: functions.js, ajax.js, communication.js, form_extensions.js, backoffice.js, extracats.js, dynamic_select.js, '+
						'src/evo_modal_window.js, src/evo_images.js, src/evo_user_crop.js, src/evo_user_report.js, src/evo_user_deldata.js, '+
						'src/evo_user_org.js, src/evo_rest_api.js, src/evo_links.js */\n'
				},
				nonull: true, // Display missing files
				src: ['rsc/js/functions.js',
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
							'rsc/js/src/evo_rest_api.js',
							'rsc/js/src/evo_links.js'],
				dest: 'rsc/js/build/evo_backoffice.bmin.js'
			},
			// JS files that are used on back-office bootstrap skins:
			evo_backoffice_bootstrap: {
				options: {
					banner: '/* This includes 15 files: functions.js, ajax.js, communication.js, form_extensions.js, backoffice.js, extracats.js, dynamic_select.js, '+
						'src/bootstrap-evo_modal_window.js, src/evo_images.js, src/evo_user_crop.js, src/evo_user_report.js, src/evo_user_deldata.js, '+
						'src/evo_user_org.js, src/evo_rest_api.js, src/evo_links.js */\n'
				},
				nonull: true, // Display missing files
				src: ['rsc/js/functions.js',
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
							'rsc/js/src/evo_rest_api.js',
							'rsc/js/src/evo_links.js'],
				dest: 'rsc/js/build/bootstrap-evo_backoffice.bmin.js'
			},
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

};