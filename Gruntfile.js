// In order to use Grunt:
// - first install NodeJS on your system (http://nodejs.org), which includes the npm tool.
// - then install the Grunt CLI on your system: (sudo) npm install -g grunt-cli
// - then switch to the b2evolution folder (where this file resides) and install (locally in this dir) 
//   everything that is needed: just type "npm install" - this will use the package.json file to know what to install.
//   each of the required packages with "npm install xxx" for each of the Dependencies listed in package.json
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
			}
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

	});

	// Load the plugin that provides the "uglify" task:
	grunt.loadNpmTasks('grunt-contrib-uglify');
	// Load the plugin that provides the "less" task:
	grunt.loadNpmTasks('grunt-contrib-less');
	// Load the plugin that provides the "watch" task:
	grunt.loadNpmTasks('grunt-contrib-watch');

	// Default task(s):
	grunt.registerTask('default', ['uglify','less']);

};
