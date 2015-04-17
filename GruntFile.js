module.exports = function(grunt) {

    grunt.initConfig({
        jasmine: {
            test: {
                src: 'Resources/public/js/*.js',
                options: {
                    specs: 'Tests/Resources/*Test.js',
                    helpers: 'Tests/Resources/*Helper.js',
                    vendor:  [
                        'http://code.jquery.com/jquery-1.11.2.min.js',
                        'Resources/public/js/jstree.js'
                    ]

                }
            }

        }
    });

    grunt.loadNpmTasks('grunt-contrib-jasmine');
    grunt.registerTask('default', ['jasmine']);
};