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
            '!{tests,tests/**}',                // Ignore tests/ and contents
            '!{tmp,tmp/**}'                     // Ignore dist/ and contents
        ]
    },
    bust : {
        src: []
    },
    copy: {
        src: [
            '**/!(*.js|*.less|*.css)',          // Everything except .js, .css and .less files
            'icons/**/*css',                    // Copy CSS for icon packs.
            'js/lib/**/*css',                   // Copy CSS for JS libs.
            'base/less/*.less',                 // LESS libraries used in runtime styles
            'widgets/**/styles/*.less',         // All the widgets' runtime .less files
            '!{build,build/**}',                // Ignore build/ and contents
            '!{tests,tests/**}',                // Ignore tests/ and contents
            '!{tmp,tmp/**}',                    // Ignore tmp/ and contents
            '!phpunit.xml',                     // Not the unit tests configuration file.
            '!so-widgets-bundle.php',           // Not the base plugin file. It is copied by the 'version' task.
            '!readme.txt',                      // Not the readme.txt file. It is copied by the 'version' task.
            '!readme.md',                       // Ignore the readme.md file. It is for the github repo.
            '!.editorconfig',                   // Ignore .editorconfig file. Only for development.
        ]
    },
    i18n: {
        src: [
            '**/*.php',                         // All the PHP files.
            '!tmp/**/*.php',                    // Ignore tmp/ and contents
            '!dist/**/*.php'                    // Ignore dist/ and contents
        ],
    },
    googleFonts: {
        dest: 'base/inc/fonts.php',
    }
};
