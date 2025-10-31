module.exports = {
    slug: 'so-widgets-bundle',
    jsMinSuffix: '.min',
    version: {
        src: [
            'so-widgets-bundle.php',
            'readme.txt'
        ]
    },
    less: {
        src: [
            'admin/**/*.less',
            'base/**/*.less',
            'css/**/*.less',
            'widgets/**/*.less',
            'compat/**/*.less',
            '!base/less/*.less',
            '!base/inc/widgets/less/*.less',
            '!widgets/**/styles/*.less'
        ],
        include:[]
    },
    sass: {
        src: [],
        include:[]
    },
    js: {
        src: [
            'admin/**/*.js',
            'base/**/*.js',
            'compat/**/*.js',
            'js/**/*.js',
            'widgets/**/*.js',
            '!{node_modules,node_modules/**}',  // Ignore node_modules/ and contents
            '!{tmp,tmp/**}',                    // Ignore dist/ and contents
            '!**/**/*.min.js'                   // Ignore already minified files.
        ]
    },
    bust : {
        src: []
    },
    copy: {
        src: [
            // Use restrictive positive patterns instead of broad match + exclusions.
            // This fixes Gulp 5 exclusion pattern bug where !build/** is ignored.

            // Root level files (excluding those handled by version task).
            'changelog.txt',
            'wpml-config.xml',

            // Directory-based patterns.
            'admin/**/!(*.js|*.less|*.css)',
            'base/**/!(*.js|*.less|*.css)',
            'compat/**/!(*.js|*.less|*.css)',
            'css/**/!(*.js|*.less|*.css)',
            'icons/**/!(*.js|*.less|*.css)',
            'js/**/!(*.js|*.less|*.css)',
            'widgets/**/!(*.js|*.less|*.css)',
            'icons/**/*css',                                      // Copy CSS for icon packs.
            'js/lib/**/*css',                                     // Copy CSS for JS libs.
            'css/lib/**/*css',                                    // Copy CSS for JS libs.
            'base/less/*.less',                                   // LESS libraries used in runtime styles.
            'base/inc/widgets/less/*.less',                       // Widget LESS libraries.
            'base/inc/installer/css/*css',                        // Include Installer CSS.
            'widgets/**/styles/*.less',                           // All the widgets' runtime .less files.
            '!{build,build/**}',                                  // Ignore build/ and contents
			'!{node_modules,node_modules/**}',                    // Ignore node_modules/ and contents.
			'!{tests,tests/**}',                                  // Ignore tests/ and contents.
            '!{tmp,tmp/**}',                                      // Ignore tmp/ and contents
			'!playwright.config.js',                              // Exclude playwright config file.
            '!so-widgets-bundle.php',                             // Not the base plugin file. It is copied by the 'version' task.
            '!readme.txt',                                        // Not the readme.txt file. It is copied by the 'version' task.
            '!readme.md',                                         // Ignore the readme.md file. It is for the github repo.
            '!.editorconfig',                                     // Ignore .editorconfig file. Only for development.
            '!base/inc/installer/inc/github-plugin-updater.php',  // Exclude Installer's  Updater.
            '!{package.json,package-lock.json}',                  // Exclude node package files.
        ]
    },
    pot: {
        src: [
            '**/*.php',                         // All the PHP files.
            '!tmp/**/*.php',                    // Ignore tmp/ and contents.
            '!dist/**/*.php'                    // Ignore dist/ and contents.
        ],
        textdomain: 'so-widgets-bundle',
        destFile: 'so-widgets-bundle.pot',
        package: 'SiteOrigin Widgets Bundle',
        bugReport: 'http://www.siteorigin.com/thread',
        lastTranslator: 'SiteOrigin <support@siteorigin.com>',
        team: 'SiteOrigin <support@siteorigin.com>'
    },
    googleFonts: {
        dest: 'base/inc/fonts.php',
    },
    fontAwesome: {
        base: 'icons/fontawesome/',
        version: '6.7.2',
        css: {
            dest: 'icons/fontawesome/'
        },
        fonts: {
            dest: 'icons/fontawesome/webfonts/'
        }
    }
};
