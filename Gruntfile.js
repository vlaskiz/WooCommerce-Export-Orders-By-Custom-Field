module.exports = function(grunt) {
    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        // Generate .pot file
        makepot: {
            target: {
                options: {
                    mainFile: 'woocommerce-export-orders-bcf.php', // Main project file.
                    type: 'wp-plugin', // Type of project (wp-plugin or wp-theme).
                    domainPath: '/languages', // Where to save the POT file.
                    include: [
                        'inc/.*',
                        '.*php$'
                    ],
                    exclude: [
                       'vendor/.*',
                       'assets/.*',
                       'languages/.*',
                       'node_modules/.*'
                    ], // List of files or directories to ignore.
                    potFilename: 'woo-export-order-bcf-en.pot', // Name of the POT file.
                    updateTimestamp: true,
                    // updatePoFiles: true
                }
            }
        },

    });

    grunt.loadNpmTasks('grunt-wp-i18n');

    /**
     * Register Tasks
     */

    grunt.registerTask( 'translate', [
        'makepot'
    ]);

}