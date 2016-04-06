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
            '**/!(*.js|*.less)',                // Everything except .js and .less files
            'base/less/*.less',                 // LESS libraries used in runtime styles
            'widgets/**/styles/*.less',         // All the widgets' runtime .less files
            '!widgets/**/styles/*.css',         // Don't copy any .css files compiled from runtime .less files
            '!{build,build/**}',                // Ignore build/ and contents
            '!{tests,tests/**}',                // Ignore tests/ and contents
            '!{tmp,tmp/**}',                    // Ignore tmp/ and contents
            '!phpunit.xml',                     // Not the unit tests configuration file.
            '!so-widgets-bundle.php',           // Not the base plugin file. It is copied by the 'version' task.
            '!readme.txt',                      // Not the readme.txt file. It is copied by the 'version' task.
            '!readme.md',                       // Ignore the readme.md file. It is for the github repo.
            '!.editorconfig',                   // Ignore .editorconfig file. Only for development.
        ]
    }
};
