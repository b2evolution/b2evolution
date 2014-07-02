// In order to use Grunt:
// - first install NodeJS on your system (http://nodejs.org), which includes the npm tool.
// - then install the Grunt CLI on your system: (sudo) npm install -g grunt-cli
// - then switch to the b2evolution folder (where this file resides) and install (locally in this dir) 
//   everything that is needed: just type "npm install" - this will use the package.json file to know what to install.
//   each of the required packages with "npm install xxx" for each of the Dependencies listed in package.json
// - then, in order to be able to invoke 'grunt' on your system, type "sudo npm install -g grunt-cli". This will make
//   grunt available from the Command Line Interface.
// Once this is done, you can:
// - type 'grunt' (in this dir) and run the default tasks
// - type 'grunt xxx' where xxx is a specific task name
// - type 'grunt watch' and grunt will start listening for file edits and run automatically 
// Note for devs: when adding new plugins, use for example "npm install grunt-contrib-less --save-dev" 
// to update the package.json file with the new plugin reference.

module.exports = function(grunt) {

	// Project configuration:
	grunt.initConfig({

		// Read project settings into the pkg property:
		// (Will allow to refer to the values of the properties below)
		pkg: grunt.file.readJSON('package.json'),

		// Configuration for the uglify minifying tasks:
		uglify: {

			// General options for all targets (can be overriden):
			options: { 
				// The following will appear on top of the created files:
				banner: '/*! <%= pkg.name %> v<%= pkg.version %> <%= grunt.template.today("yyyy-mm-dd") %> */\n',
			},
			// First target to be created:
			functionsjs: {
				nonull: true, // Display missing files
				src: 'blogs/rsc/js/functions.js',
				dest: 'blogs/rsc/js/build/functions.min.js'
			},
			// Another Target:
			loginjs: { 
				nonull: true, // Display missing files
				src: ['blogs/rsc/js/sha1_md5.js'],
				dest: 'blogs/rsc/js/build/sha1_md5.min.js'
			},
			// Another Target:
			ajaxcomjs: {
				options: {
					// Extend default banner:
					banner: '<%= uglify.options.banner %>/* This includes 2 files */\n'
				},
				nonull: true, // Display missing files
				src: ['blogs/rsc/js/ajax.js', 'blogs/rsc/js/communication.js'],
				dest: 'blogs/rsc/js/build/ajaxcom.min.js',
			},
			// Colorbox + Voting + Touchswipe
			colorbox: {
				options: {
					banner: '<%= uglify.options.banner %>/* This includes 3 files: jquery.colorbox.js, voting.js, jquery.touchswipe.js */\n'
				},
				nonull: true, // Display missing files
				src: ['blogs/rsc/js/colorbox/jquery.colorbox.js', 'blogs/rsc/js/voting.js', 'blogs/rsc/js/jquery/jquery.touchswipe.js'],
				dest: 'blogs/rsc/js/build/jquery.colorbox.b2evo.min.js'
			},
			// Bubbletip
			bubbletip: {
				options: {
					banner: '<%= uglify.options.banner %>/* This includes 3 files: bubbletip.js, plugins.js, userfields.js */\n'
				},
				nonull: true, // Display missing files
				src: ['blogs/rsc/js/bubbletip.js', 'blogs/rsc/js/plugins.js', 'blogs/rsc/js/userfields.js'],
				dest: 'blogs/rsc/js/build/bubbletip_bundle.min.js'
			},
			// Popover (Analog of bubbletip on bootstrap skins)
			popover: {
				options: {
					banner: '<%= uglify.options.banner %>/* This includes 3 files: bootstrap/usernames.js, bootstrap/plugins.js, bootstrap/userfields.js */\n'
				},
				nonull: true, // Display missing files
				src: ['blogs/rsc/js/bootstrap/usernames.js', 'blogs/rsc/js/bootstrap/plugins.js', 'blogs/rsc/js/bootstrap/userfields.js'],
				dest: 'blogs/rsc/js/build/popover_bundle.min.js'
			},
		},

		// Configuration for the less->css compiling tasks:
		less: {
			development: {
				options: {
					//compress: true,
					//yuicompress: true,
					//optimization: 2
				},
				files: {
					// target.css file: source.less file
					'blogs/rsc/css/test.css': 'blogs/rsc/less/test.less',
				}
			}
		},

		// Configuration for the watch tasks:
		watch: {
			functionsjs: {
				files: ['blogs/rsc/js/functions.js'],
				tasks: ['uglify:functionsjs'],
			},
			loginjs: {
				files: ['blogs/rsc/js/sha1_md5.js'],
				tasks: ['uglify:loginjs'],
			},
			ajaxcomjs: {
				files: ['blogs/rsc/js/ajax.js', 'blogs/rsc/js/communication.js'],
				tasks: ['uglify:ajaxcomjs'],
			},
			less: {
				// Which files to watch (all .less files recursively in the less directory)
				files: ['blogs/rsc/less/**/*.less'],
				tasks: ['less'],
				options: {
					nospawn: true,
				}
			}
		},

		// Configuration for the concatenate tasks:
		concat: {
			jquery_migrate: {
				src: ['blogs/rsc/js/jquery.js', 'blogs/rsc/js/jquery/jquery-migrate.js'],
				dest: 'blogs/rsc/js/build/jquery.b2evo.js',
			},
			jquery_migrate_min: {
				src: ['blogs/rsc/js/jquery.min.js', 'blogs/rsc/js/jquery/jquery-migrate.min.js'],
				dest: 'blogs/rsc/js/build/jquery.b2evo.min.js',
			},
		},

	});

	// Load the plugin that provides the "uglify" task:
	grunt.loadNpmTasks('grunt-contrib-uglify');
	// Load the plugin that provides the "less" task:
	grunt.loadNpmTasks('grunt-contrib-less');
	// Load the plugin that provides the "watch" task:
	grunt.loadNpmTasks('grunt-contrib-watch');
	// Load the plugin that provides the "concatenate" task:
	grunt.loadNpmTasks('grunt-contrib-concat');

	// Default task(s):
	grunt.registerTask('default', ['uglify','less','concat']);

};