(function(f){if(typeof exports==="object"&&typeof module!=="undefined"){module.exports=f()}else if(typeof define==="function"&&define.amd){define([],f)}else{var g;if(typeof window!=="undefined"){g=window}else if(typeof global!=="undefined"){g=global}else if(typeof self!=="undefined"){g=self}else{g=this}g.Trianglify = f()}})(function(){var define,module,exports;return (function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({"./lib/trianglify.js":[function(require,module,exports){
    /*
     * Trianglify.js
     * by @qrohlf
     *
     * Licensed under the GPLv3
     */

    var delaunay = require('delaunay-fast');
    var seedrandom = require('seedrandom');
    var chroma = require('chroma-js'); //PROBLEM: chroma.js is nearly 32k in size
    var colorbrewer = require('./colorbrewer'); //We could use the chroma.js colorbrewer, but it's got some ugly stuff so we use our own subset.

    var Pattern = require('./pattern');

    var defaults = {
        width: 600,                       // Width of pattern
        height: 400,                      // Height of pattern
        cell_size: 75,                    // Size of the cells used to generate a randomized grid
        variance: 0.75,                   // how much to randomize the grid
        seed: null,                       // Seed for the RNG
        x_colors: 'random',               // X color stops
        y_colors: 'match_x',              // Y color stops
        palette: colorbrewer,             // Palette to use for 'random' color option
        color_space: 'lab',               // Color space used for gradient construction & interpolation
        color_function: null,             // Color function f(x, y) that returns a color specification that is consumable by chroma-js
        stroke_width: 1.51                // Width of stroke. Defaults to 1.51 to fix an issue with canvas antialiasing.
    };

    /*********************************************************
     *
     * Main function that is exported to the global namespace
     *
     **********************************************************/

    function Trianglify(opts) {
        // apply defaults
        opts = _merge_opts(defaults, opts);

        // setup seedable RNG
        rand = seedrandom(opts.seed);

        // randomize colors if requested
        if (opts.x_colors === 'random') opts.x_colors = _random_from_palette();
        if (opts.y_colors === 'random') opts.y_colors = _random_from_palette();
        if (opts.y_colors === 'match_x') opts.y_colors = opts.x_colors;

        // some sanity-checking
        if (!(opts.width > 0 && opts.height > 0)) {
            throw new Error("Width and height must be numbers greater than 0");
        }

        if (opts.cell_size < 2) {
            throw new Error("Cell size must be greater than 2.");
        }

        // Setup the color gradient function
        var gradient;

        if (opts.color_function) {
            gradient = function(x, y) {
                return chroma(opts.color_function(x, y));
            };
        } else {
            var x_color = chroma.scale(opts.x_colors).mode(opts.color_space);
            var y_color = chroma.scale(opts.y_colors).mode(opts.color_space);
            gradient = function(x, y) {
                return chroma.interpolate(x_color(x), y_color(y), 0.5, opts.color_space);
            };
        }

        // Figure out key dimensions

        // it's a pain to prefix width and height with opts all the time, so let's
        // give them proper variables to refer to
        var width = opts.width;
        var height = opts.height;

        // How many cells we're going to have on each axis (pad by 2 cells on each edge)
        var cells_x = Math.floor((width + 4 * opts.cell_size) / opts.cell_size);
        var cells_y = Math.floor((height + 4 * opts.cell_size) / opts.cell_size);

        // figure out the bleed widths to center the grid
        var bleed_x = ((cells_x * opts.cell_size) - width)/2;
        var bleed_y = ((cells_y * opts.cell_size) - height)/2;

        // how much can out points wiggle (+/-) given the cell padding?
        var variance = opts.cell_size * opts.variance / 2;

        // Set up normalizers
        var norm_x = function(x) {
            return _map(x, [-bleed_x, width+bleed_x], [0, 1]);
        };

        var norm_y = function(y) {
            return _map(y, [-bleed_y, height+bleed_y], [0, 1]);
        };

        // generate a point mesh
        var points = _generate_points(width, height);

        // delaunay.triangulate gives us indices into the original coordinate array
        var geom_indices = delaunay.triangulate(points);

        // iterate over the indices in groups of three to flatten them into polygons, with color lookup
        var triangles = [];
        var lookup_point = function(i) { return points[i];};
        for (var i=0; i < geom_indices.length; i += 3) {
            var vertices = [geom_indices[i], geom_indices[i+1], geom_indices[i+2]].map(lookup_point);
            var centroid = _centroid(vertices);
            var color = gradient(norm_x(centroid.x), norm_y(centroid.y)).hex();
            triangles.push([color, vertices]);
        }
        return Pattern(triangles, opts);


        /*********************************************************
         *
         * Private functions
         *
         **********************************************************/

        function _map(num, in_range, out_range ) {
            return ( num - in_range[0] ) * ( out_range[1] - out_range[0] ) / ( in_range[1] - in_range[0] ) + out_range[0];
        }

        // generate points on a randomized grid
        function _generate_points(width, height) {

            var points = [];
            for (var i = - bleed_x; i < width + bleed_x; i += opts.cell_size) {
                for (var j = - bleed_y; j < height + bleed_y; j += opts.cell_size) {
                    var x = i + opts.cell_size/2 + _map(rand(), [0, 1], [-variance, variance]);
                    var y = j + opts.cell_size/2 + _map(rand(), [0, 1], [-variance, variance]);
                    points.push([x, y].map(Math.floor));
                }
            }

            return points;
        }

        //triangles only!
        function _centroid(d) {
            return {
                x: (d[0][0] + d[1][0] + d[2][0])/3,
                y: (d[0][1] + d[1][1] + d[2][1])/3
            };
        }

        // select a random palette from colorbrewer
        function _random_from_palette() {
            if (opts.palette instanceof Array) {
                return opts.palette[Math.floor(rand()*opts.palette.length)];
            }

            var keys = Object.keys(opts.palette);
            return opts.palette[keys[Math.floor(rand()*keys.length)]];
        }

        // shallow extend (sort of) for option defaults
        function _merge_opts(defaults, options) {
            var out = {};

            // shallow-copy defaults so we don't mutate the input objects (bad)
            for (var key in defaults) {
                out[key] = defaults[key];
            }

            for (key in options) {
                if (defaults.hasOwnProperty(key)) {
                    out[key] = options[key]; // override defaults with options
                } else {
                    throw new Error(key+" is not a configuration option for Trianglify. Check your spelling?");
                }
            }
            return out;
        }

    } //end of Trianglify function closure

// exports
    Trianglify.colorbrewer = colorbrewer;
    Trianglify.defaults = defaults;
    module.exports = Trianglify;
},{"./colorbrewer":"/Users/gpriday/Downloads/trianglify-master 2/lib/colorbrewer.js","./pattern":"/Users/gpriday/Downloads/trianglify-master 2/lib/pattern.js","chroma-js":"/Users/gpriday/Downloads/trianglify-master 2/node_modules/chroma-js/chroma.js","delaunay-fast":"/Users/gpriday/Downloads/trianglify-master 2/node_modules/delaunay-fast/delaunay.js","seedrandom":"/Users/gpriday/Downloads/trianglify-master 2/node_modules/seedrandom/index.js"}],"/Users/gpriday/Downloads/trianglify-master 2/lib/colorbrewer.js":[function(require,module,exports){
    /*
     * colorbrewer.js
     *
     * Colorbrewer colors, by Cindy Brewer
     */

    module.exports = {
        YlGn: ["#ffffe5","#f7fcb9","#d9f0a3","#addd8e","#78c679","#41ab5d","#238443","#006837","#004529"],
        YlGnBu: ["#ffffd9","#edf8b1","#c7e9b4","#7fcdbb","#41b6c4","#1d91c0","#225ea8","#253494","#081d58"],
        GnBu: ["#f7fcf0","#e0f3db","#ccebc5","#a8ddb5","#7bccc4","#4eb3d3","#2b8cbe","#0868ac","#084081"],
        BuGn: ["#f7fcfd","#e5f5f9","#ccece6","#99d8c9","#66c2a4","#41ae76","#238b45","#006d2c","#00441b"],
        PuBuGn: ["#fff7fb","#ece2f0","#d0d1e6","#a6bddb","#67a9cf","#3690c0","#02818a","#016c59","#014636"],
        PuBu: ["#fff7fb","#ece7f2","#d0d1e6","#a6bddb","#74a9cf","#3690c0","#0570b0","#045a8d","#023858"],
        BuPu: ["#f7fcfd","#e0ecf4","#bfd3e6","#9ebcda","#8c96c6","#8c6bb1","#88419d","#810f7c","#4d004b"],
        RdPu: ["#fff7f3","#fde0dd","#fcc5c0","#fa9fb5","#f768a1","#dd3497","#ae017e","#7a0177","#49006a"],
        PuRd: ["#f7f4f9","#e7e1ef","#d4b9da","#c994c7","#df65b0","#e7298a","#ce1256","#980043","#67001f"],
        OrRd: ["#fff7ec","#fee8c8","#fdd49e","#fdbb84","#fc8d59","#ef6548","#d7301f","#b30000","#7f0000"],
        YlOrRd: ["#ffffcc","#ffeda0","#fed976","#feb24c","#fd8d3c","#fc4e2a","#e31a1c","#bd0026","#800026"],
        YlOrBr: ["#ffffe5","#fff7bc","#fee391","#fec44f","#fe9929","#ec7014","#cc4c02","#993404","#662506"],
        Purples: ["#fcfbfd","#efedf5","#dadaeb","#bcbddc","#9e9ac8","#807dba","#6a51a3","#54278f","#3f007d"],
        Blues: ["#f7fbff","#deebf7","#c6dbef","#9ecae1","#6baed6","#4292c6","#2171b5","#08519c","#08306b"],
        Greens: ["#f7fcf5","#e5f5e0","#c7e9c0","#a1d99b","#74c476","#41ab5d","#238b45","#006d2c","#00441b"],
        Oranges: ["#fff5eb","#fee6ce","#fdd0a2","#fdae6b","#fd8d3c","#f16913","#d94801","#a63603","#7f2704"],
        Reds: ["#fff5f0","#fee0d2","#fcbba1","#fc9272","#fb6a4a","#ef3b2c","#cb181d","#a50f15","#67000d"],
        Greys: ["#ffffff","#f0f0f0","#d9d9d9","#bdbdbd","#969696","#737373","#525252","#252525","#000000"],
        PuOr: ["#7f3b08","#b35806","#e08214","#fdb863","#fee0b6","#f7f7f7","#d8daeb","#b2abd2","#8073ac","#542788","#2d004b"],
        BrBG: ["#543005","#8c510a","#bf812d","#dfc27d","#f6e8c3","#f5f5f5","#c7eae5","#80cdc1","#35978f","#01665e","#003c30"],
        PRGn: ["#40004b","#762a83","#9970ab","#c2a5cf","#e7d4e8","#f7f7f7","#d9f0d3","#a6dba0","#5aae61","#1b7837","#00441b"],
        PiYG: ["#8e0152","#c51b7d","#de77ae","#f1b6da","#fde0ef","#f7f7f7","#e6f5d0","#b8e186","#7fbc41","#4d9221","#276419"],
        RdBu: ["#67001f","#b2182b","#d6604d","#f4a582","#fddbc7","#f7f7f7","#d1e5f0","#92c5de","#4393c3","#2166ac","#053061"],
        RdGy: ["#67001f","#b2182b","#d6604d","#f4a582","#fddbc7","#ffffff","#e0e0e0","#bababa","#878787","#4d4d4d","#1a1a1a"],
        RdYlBu: ["#a50026","#d73027","#f46d43","#fdae61","#fee090","#ffffbf","#e0f3f8","#abd9e9","#74add1","#4575b4","#313695"],
        Spectral: ["#9e0142","#d53e4f","#f46d43","#fdae61","#fee08b","#ffffbf","#e6f598","#abdda4","#66c2a5","#3288bd","#5e4fa2"],
        RdYlGn: ["#a50026","#d73027","#f46d43","#fdae61","#fee08b","#ffffbf","#d9ef8b","#a6d96a","#66bd63","#1a9850","#006837"]
    };
},{}],"/Users/gpriday/Downloads/trianglify-master 2/lib/pattern.js":[function(require,module,exports){
    (function (process){
        /*
         * Pattern.js
         * Contains rendering implementations for trianglify-generated geometry
         */

// conditionally load jsdom if we don't have a browser environment available.
        var doc = (typeof document !== "undefined") ? document : require('jsdom').jsdom('<html></html>');

        function Pattern(polys, opts) {

            // SVG rendering method
            function render_svg() {
                var svg = doc.createElementNS("http://www.w3.org/2000/svg", 'svg');
                svg.setAttribute('width', opts.width);
                svg.setAttribute('height', opts.height);

                polys.forEach(function(poly) {
                    var path = doc.createElementNS("http://www.w3.org/2000/svg", 'path');
                    path.setAttribute("d", "M" + poly[1].join("L") + "Z");
                    path.setAttribute("fill", poly[0]);
                    path.setAttribute("stroke", poly[0]);
                    path.setAttribute("stroke-width", opts.stroke_width);
                    svg.appendChild(path);
                });

                return svg;
            }

            // Canvas rendering method
            function render_canvas(canvas) {
                // check for canvas support
                if (typeof process !== "undefined") {
                    try {
                        require('canvas');
                    } catch (e) {
                        throw Error('The optional node-canvas dependency is needed for Trianglify to render using canvas in node.');
                    }
                }

                if (!canvas) {
                    canvas = doc.createElement('canvas');
                }

                canvas.setAttribute('width', opts.width);
                canvas.setAttribute('height', opts.height);
                ctx = canvas.getContext("2d");
                ctx.canvas.width = opts.width;
                ctx.canvas.height = opts.height;

                polys.forEach(function(poly) {
                    ctx.fillStyle = ctx.strokeStyle = poly[0];
                    ctx.lineWidth = opts.stroke_width;
                    ctx.beginPath();
                    ctx.moveTo.apply(ctx, poly[1][0]);
                    ctx.lineTo.apply(ctx, poly[1][1]);
                    ctx.lineTo.apply(ctx, poly[1][2]);
                    ctx.fill();
                    ctx.stroke();
                });

                return canvas;
            }

            // PNG rendering method
            // currently returns a data url as a string since toBlob support really isn't there yet...
            function render_png() {
                return render_canvas().toDataURL("image/png");
            }

            // Return an object with all the relevant functions/properties attached to it
            return {
                polys: polys,
                opts: opts,
                svg: render_svg,
                canvas: render_canvas,
                png: render_png
            };
        }

        module.exports = Pattern;
    }).call(this,require('_process'))
},{"_process":"/Users/gpriday/Downloads/trianglify-master 2/node_modules/browserify/node_modules/process/browser.js","canvas":"/Users/gpriday/Downloads/trianglify-master 2/node_modules/browserify/node_modules/browser-resolve/empty.js","jsdom":"/Users/gpriday/Downloads/trianglify-master 2/node_modules/browserify/node_modules/browser-resolve/empty.js"}],"/Users/gpriday/Downloads/trianglify-master 2/node_modules/browserify/node_modules/browser-resolve/empty.js":[function(require,module,exports){

},{}],"/Users/gpriday/Downloads/trianglify-master 2/node_modules/browserify/node_modules/process/browser.js":[function(require,module,exports){
// shim for using process in browser

    var process = module.exports = {};
    var queue = [];
    var draining = false;

    function drainQueue() {
        if (draining) {
            return;
        }
        draining = true;
        var currentQueue;
        var len = queue.length;
        while(len) {
            currentQueue = queue;
            queue = [];
            var i = -1;
            while (++i < len) {
                currentQueue[i]();
            }
            len = queue.length;
        }
        draining = false;
    }
    process.nextTick = function (fun) {
        queue.push(fun);
        if (!draining) {
            setTimeout(drainQueue, 0);
        }
    };

    process.title = 'browser';
    process.browser = true;
    process.env = {};
    process.argv = [];
    process.version = ''; // empty string to avoid regexp issues
    process.versions = {};

    function noop() {}

    process.on = noop;
    process.addListener = noop;
    process.once = noop;
    process.off = noop;
    process.removeListener = noop;
    process.removeAllListeners = noop;
    process.emit = noop;

    process.binding = function (name) {
        throw new Error('process.binding is not supported');
    };

// TODO(shtylman)
    process.cwd = function () { return '/' };
    process.chdir = function (dir) {
        throw new Error('process.chdir is not supported');
    };
    process.umask = function() { return 0; };

},{}],"/Users/gpriday/Downloads/trianglify-master 2/node_modules/chroma-js/chroma.js":[function(require,module,exports){
// Generated by CoffeeScript 1.6.2
    /** echo  * @license echo  * while read i do echo  *  done echo
     */


    (function() {
        var Color, K, PITHIRD, TWOPI, X, Y, Z, bezier, brewer, chroma, clip_rgb, colors, cos, css2rgb, hex2rgb, hsi2rgb, hsl2rgb, hsv2rgb, lab2lch, lab2rgb, lab_xyz, lch2lab, lch2rgb, limit, luminance, luminance_x, rgb2hex, rgb2hsi, rgb2hsl, rgb2hsv, rgb2lab, rgb2lch, rgb_xyz, root, type, unpack, xyz_lab, xyz_rgb, _ref;

        chroma = function(x, y, z, m) {
            return new Color(x, y, z, m);
        };

        if ((typeof module !== "undefined" && module !== null) && (module.exports != null)) {
            module.exports = chroma;
        }

        if (typeof define === 'function' && define.amd) {
            define([], function() {
                return chroma;
            });
        } else {
            root = typeof exports !== "undefined" && exports !== null ? exports : this;
            root.chroma = chroma;
        }

        chroma.color = function(x, y, z, m) {
            return new Color(x, y, z, m);
        };

        chroma.hsl = function(h, s, l, a) {
            return new Color(h, s, l, a, 'hsl');
        };

        chroma.hsv = function(h, s, v, a) {
            return new Color(h, s, v, a, 'hsv');
        };

        chroma.rgb = function(r, g, b, a) {
            return new Color(r, g, b, a, 'rgb');
        };

        chroma.hex = function(x) {
            return new Color(x);
        };

        chroma.css = function(x) {
            return new Color(x);
        };

        chroma.lab = function(l, a, b) {
            return new Color(l, a, b, 'lab');
        };

        chroma.lch = function(l, c, h) {
            return new Color(l, c, h, 'lch');
        };

        chroma.hsi = function(h, s, i) {
            return new Color(h, s, i, 'hsi');
        };

        chroma.gl = function(r, g, b, a) {
            return new Color(r * 255, g * 255, b * 255, a, 'gl');
        };

        chroma.interpolate = function(a, b, f, m) {
            if ((a == null) || (b == null)) {
                return '#000';
            }
            if (type(a) === 'string') {
                a = new Color(a);
            }
            if (type(b) === 'string') {
                b = new Color(b);
            }
            return a.interpolate(f, b, m);
        };

        chroma.mix = chroma.interpolate;

        chroma.contrast = function(a, b) {
            var l1, l2;

            if (type(a) === 'string') {
                a = new Color(a);
            }
            if (type(b) === 'string') {
                b = new Color(b);
            }
            l1 = a.luminance();
            l2 = b.luminance();
            if (l1 > l2) {
                return (l1 + 0.05) / (l2 + 0.05);
            } else {
                return (l2 + 0.05) / (l1 + 0.05);
            }
        };

        chroma.luminance = function(color) {
            return chroma(color).luminance();
        };

        chroma._Color = Color;

        /**
         chroma.js

         Copyright (c) 2011-2013, Gregor Aisch
         All rights reserved.

         Redistribution and use in source and binary forms, with or without
         modification, are permitted provided that the following conditions are met:

         * Redistributions of source code must retain the above copyright notice, this
         list of conditions and the following disclaimer.

         * Redistributions in binary form must reproduce the above copyright notice,
         this list of conditions and the following disclaimer in the documentation
         and/or other materials provided with the distribution.

         * The name Gregor Aisch may not be used to endorse or promote products
         derived from this software without specific prior written permission.

         THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
         AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
         IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
         DISCLAIMED. IN NO EVENT SHALL GREGOR AISCH OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
         INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
         BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
         DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
         OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
         NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
         EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

         @source: https://github.com/gka/chroma.js
         */


        Color = (function() {
            function Color() {
                var a, arg, args, m, me, me_rgb, x, y, z, _i, _len, _ref, _ref1, _ref2, _ref3, _ref4;

                me = this;
                args = [];
                for (_i = 0, _len = arguments.length; _i < _len; _i++) {
                    arg = arguments[_i];
                    if (arg != null) {
                        args.push(arg);
                    }
                }
                if (args.length === 0) {
                    _ref = [255, 0, 255, 1, 'rgb'], x = _ref[0], y = _ref[1], z = _ref[2], a = _ref[3], m = _ref[4];
                } else if (type(args[0]) === "array") {
                    if (args[0].length === 3) {
                        _ref1 = args[0], x = _ref1[0], y = _ref1[1], z = _ref1[2];
                        a = 1;
                    } else if (args[0].length === 4) {
                        _ref2 = args[0], x = _ref2[0], y = _ref2[1], z = _ref2[2], a = _ref2[3];
                    } else {
                        throw 'unknown input argument';
                    }
                    m = (_ref3 = args[1]) != null ? _ref3 : 'rgb';
                } else if (type(args[0]) === "string") {
                    x = args[0];
                    m = 'hex';
                } else if (type(args[0]) === "object") {
                    _ref4 = args[0]._rgb, x = _ref4[0], y = _ref4[1], z = _ref4[2], a = _ref4[3];
                    m = 'rgb';
                } else if (args.length >= 3) {
                    x = args[0];
                    y = args[1];
                    z = args[2];
                }
                if (args.length === 3) {
                    m = 'rgb';
                    a = 1;
                } else if (args.length === 4) {
                    if (type(args[3]) === "string") {
                        m = args[3];
                        a = 1;
                    } else if (type(args[3]) === "number") {
                        m = 'rgb';
                        a = args[3];
                    }
                } else if (args.length === 5) {
                    a = args[3];
                    m = args[4];
                }
                if (a == null) {
                    a = 1;
                }
                if (m === 'rgb') {
                    me._rgb = [x, y, z, a];
                } else if (m === 'gl') {
                    me._rgb = [x * 255, y * 255, z * 255, a];
                } else if (m === 'hsl') {
                    me._rgb = hsl2rgb(x, y, z);
                    me._rgb[3] = a;
                } else if (m === 'hsv') {
                    me._rgb = hsv2rgb(x, y, z);
                    me._rgb[3] = a;
                } else if (m === 'hex') {
                    me._rgb = hex2rgb(x);
                } else if (m === 'lab') {
                    me._rgb = lab2rgb(x, y, z);
                    me._rgb[3] = a;
                } else if (m === 'lch') {
                    me._rgb = lch2rgb(x, y, z);
                    me._rgb[3] = a;
                } else if (m === 'hsi') {
                    me._rgb = hsi2rgb(x, y, z);
                    me._rgb[3] = a;
                }
                me_rgb = clip_rgb(me._rgb);
            }

            Color.prototype.rgb = function() {
                return this._rgb.slice(0, 3);
            };

            Color.prototype.rgba = function() {
                return this._rgb;
            };

            Color.prototype.hex = function() {
                return rgb2hex(this._rgb);
            };

            Color.prototype.toString = function() {
                return this.name();
            };

            Color.prototype.hsl = function() {
                return rgb2hsl(this._rgb);
            };

            Color.prototype.hsv = function() {
                return rgb2hsv(this._rgb);
            };

            Color.prototype.lab = function() {
                return rgb2lab(this._rgb);
            };

            Color.prototype.lch = function() {
                return rgb2lch(this._rgb);
            };

            Color.prototype.hsi = function() {
                return rgb2hsi(this._rgb);
            };

            Color.prototype.gl = function() {
                return [this._rgb[0] / 255, this._rgb[1] / 255, this._rgb[2] / 255, this._rgb[3]];
            };

            Color.prototype.luminance = function(lum, mode) {
                var cur_lum, eps, max_iter, test;

                if (mode == null) {
                    mode = 'rgb';
                }
                if (!arguments.length) {
                    return luminance(this._rgb);
                }
                if (lum === 0) {
                    this._rgb = [0, 0, 0, this._rgb[3]];
                }
                if (lum === 1) {
                    this._rgb = [255, 255, 255, this._rgb[3]];
                }
                cur_lum = luminance(this._rgb);
                eps = 1e-7;
                max_iter = 20;
                test = function(l, h) {
                    var lm, m;

                    m = l.interpolate(0.5, h, mode);
                    lm = m.luminance();
                    if (Math.abs(lum - lm) < eps || !max_iter--) {
                        return m;
                    }
                    if (lm > lum) {
                        return test(l, m);
                    }
                    return test(m, h);
                };
                this._rgb = (cur_lum > lum ? test(new Color('black'), this) : test(this, new Color('white'))).rgba();
                return this;
            };

            Color.prototype.name = function() {
                var h, k;

                h = this.hex();
                for (k in chroma.colors) {
                    if (h === chroma.colors[k]) {
                        return k;
                    }
                }
                return h;
            };

            Color.prototype.alpha = function(alpha) {
                if (arguments.length) {
                    this._rgb[3] = alpha;
                    return this;
                }
                return this._rgb[3];
            };

            Color.prototype.css = function(mode) {
                var hsl, me, rgb, rnd;

                if (mode == null) {
                    mode = 'rgb';
                }
                me = this;
                rgb = me._rgb;
                if (mode.length === 3 && rgb[3] < 1) {
                    mode += 'a';
                }
                if (mode === 'rgb') {
                    return mode + '(' + rgb.slice(0, 3).map(Math.round).join(',') + ')';
                } else if (mode === 'rgba') {
                    return mode + '(' + rgb.slice(0, 3).map(Math.round).join(',') + ',' + rgb[3] + ')';
                } else if (mode === 'hsl' || mode === 'hsla') {
                    hsl = me.hsl();
                    rnd = function(a) {
                        return Math.round(a * 100) / 100;
                    };
                    hsl[0] = rnd(hsl[0]);
                    hsl[1] = rnd(hsl[1] * 100) + '%';
                    hsl[2] = rnd(hsl[2] * 100) + '%';
                    if (mode.length === 4) {
                        hsl[3] = rgb[3];
                    }
                    return mode + '(' + hsl.join(',') + ')';
                }
            };

            Color.prototype.interpolate = function(f, col, m) {
                /*
                 interpolates between colors
                 f = 0 --> me
                 f = 1 --> col
                 */

                var dh, hue, hue0, hue1, lbv, lbv0, lbv1, me, res, sat, sat0, sat1, xyz0, xyz1;

                me = this;
                if (m == null) {
                    m = 'rgb';
                }
                if (type(col) === "string") {
                    col = new Color(col);
                }
                if (m === 'hsl' || m === 'hsv' || m === 'lch' || m === 'hsi') {
                    if (m === 'hsl') {
                        xyz0 = me.hsl();
                        xyz1 = col.hsl();
                    } else if (m === 'hsv') {
                        xyz0 = me.hsv();
                        xyz1 = col.hsv();
                    } else if (m === 'hsi') {
                        xyz0 = me.hsi();
                        xyz1 = col.hsi();
                    } else if (m === 'lch') {
                        xyz0 = me.lch();
                        xyz1 = col.lch();
                    }
                    if (m.substr(0, 1) === 'h') {
                        hue0 = xyz0[0], sat0 = xyz0[1], lbv0 = xyz0[2];
                        hue1 = xyz1[0], sat1 = xyz1[1], lbv1 = xyz1[2];
                    } else {
                        lbv0 = xyz0[0], sat0 = xyz0[1], hue0 = xyz0[2];
                        lbv1 = xyz1[0], sat1 = xyz1[1], hue1 = xyz1[2];
                    }
                    if (!isNaN(hue0) && !isNaN(hue1)) {
                        if (hue1 > hue0 && hue1 - hue0 > 180) {
                            dh = hue1 - (hue0 + 360);
                        } else if (hue1 < hue0 && hue0 - hue1 > 180) {
                            dh = hue1 + 360 - hue0;
                        } else {
                            dh = hue1 - hue0;
                        }
                        hue = hue0 + f * dh;
                    } else if (!isNaN(hue0)) {
                        hue = hue0;
                        if ((lbv1 === 1 || lbv1 === 0) && m !== 'hsv') {
                            sat = sat0;
                        }
                    } else if (!isNaN(hue1)) {
                        hue = hue1;
                        if ((lbv0 === 1 || lbv0 === 0) && m !== 'hsv') {
                            sat = sat1;
                        }
                    } else {
                        hue = Number.NaN;
                    }
                    if (sat == null) {
                        sat = sat0 + f * (sat1 - sat0);
                    }
                    lbv = lbv0 + f * (lbv1 - lbv0);
                    if (m.substr(0, 1) === 'h') {
                        res = new Color(hue, sat, lbv, m);
                    } else {
                        res = new Color(lbv, sat, hue, m);
                    }
                } else if (m === 'rgb') {
                    xyz0 = me._rgb;
                    xyz1 = col._rgb;
                    res = new Color(xyz0[0] + f * (xyz1[0] - xyz0[0]), xyz0[1] + f * (xyz1[1] - xyz0[1]), xyz0[2] + f * (xyz1[2] - xyz0[2]), m);
                } else if (m === 'lab') {
                    xyz0 = me.lab();
                    xyz1 = col.lab();
                    res = new Color(xyz0[0] + f * (xyz1[0] - xyz0[0]), xyz0[1] + f * (xyz1[1] - xyz0[1]), xyz0[2] + f * (xyz1[2] - xyz0[2]), m);
                } else {
                    throw "color mode " + m + " is not supported";
                }
                res.alpha(me.alpha() + f * (col.alpha() - me.alpha()));
                return res;
            };

            Color.prototype.premultiply = function() {
                var a, rgb;

                rgb = this.rgb();
                a = this.alpha();
                return chroma(rgb[0] * a, rgb[1] * a, rgb[2] * a, a);
            };

            Color.prototype.darken = function(amount) {
                var lch, me;

                if (amount == null) {
                    amount = 20;
                }
                me = this;
                lch = me.lch();
                lch[0] -= amount;
                return chroma.lch(lch).alpha(me.alpha());
            };

            Color.prototype.darker = function(amount) {
                return this.darken(amount);
            };

            Color.prototype.brighten = function(amount) {
                if (amount == null) {
                    amount = 20;
                }
                return this.darken(-amount);
            };

            Color.prototype.brighter = function(amount) {
                return this.brighten(amount);
            };

            Color.prototype.saturate = function(amount) {
                var lch, me;

                if (amount == null) {
                    amount = 20;
                }
                me = this;
                lch = me.lch();
                lch[1] += amount;
                return chroma.lch(lch).alpha(me.alpha());
            };

            Color.prototype.desaturate = function(amount) {
                if (amount == null) {
                    amount = 20;
                }
                return this.saturate(-amount);
            };

            return Color;

        })();

        clip_rgb = function(rgb) {
            var i;

            for (i in rgb) {
                if (i < 3) {
                    if (rgb[i] < 0) {
                        rgb[i] = 0;
                    }
                    if (rgb[i] > 255) {
                        rgb[i] = 255;
                    }
                } else if (i === 3) {
                    if (rgb[i] < 0) {
                        rgb[i] = 0;
                    }
                    if (rgb[i] > 1) {
                        rgb[i] = 1;
                    }
                }
            }
            return rgb;
        };

        css2rgb = function(css) {
            var hsl, i, m, rgb, _i, _j, _k, _l;

            css = css.toLowerCase();
            if ((chroma.colors != null) && chroma.colors[css]) {
                return hex2rgb(chroma.colors[css]);
            }
            if (m = css.match(/rgb\(\s*(\-?\d+),\s*(\-?\d+)\s*,\s*(\-?\d+)\s*\)/)) {
                rgb = m.slice(1, 4);
                for (i = _i = 0; _i <= 2; i = ++_i) {
                    rgb[i] = +rgb[i];
                }
                rgb[3] = 1;
            } else if (m = css.match(/rgba\(\s*(\-?\d+),\s*(\-?\d+)\s*,\s*(\-?\d+)\s*,\s*([01]|[01]?\.\d+)\)/)) {
                rgb = m.slice(1, 5);
                for (i = _j = 0; _j <= 3; i = ++_j) {
                    rgb[i] = +rgb[i];
                }
            } else if (m = css.match(/rgb\(\s*(\-?\d+(?:\.\d+)?)%,\s*(\-?\d+(?:\.\d+)?)%\s*,\s*(\-?\d+(?:\.\d+)?)%\s*\)/)) {
                rgb = m.slice(1, 4);
                for (i = _k = 0; _k <= 2; i = ++_k) {
                    rgb[i] = Math.round(rgb[i] * 2.55);
                }
                rgb[3] = 1;
            } else if (m = css.match(/rgba\(\s*(\-?\d+(?:\.\d+)?)%,\s*(\-?\d+(?:\.\d+)?)%\s*,\s*(\-?\d+(?:\.\d+)?)%\s*,\s*([01]|[01]?\.\d+)\)/)) {
                rgb = m.slice(1, 5);
                for (i = _l = 0; _l <= 2; i = ++_l) {
                    rgb[i] = Math.round(rgb[i] * 2.55);
                }
                rgb[3] = +rgb[3];
            } else if (m = css.match(/hsl\(\s*(\-?\d+(?:\.\d+)?),\s*(\-?\d+(?:\.\d+)?)%\s*,\s*(\-?\d+(?:\.\d+)?)%\s*\)/)) {
                hsl = m.slice(1, 4);
                hsl[1] *= 0.01;
                hsl[2] *= 0.01;
                rgb = hsl2rgb(hsl);
                rgb[3] = 1;
            } else if (m = css.match(/hsla\(\s*(\-?\d+(?:\.\d+)?),\s*(\-?\d+(?:\.\d+)?)%\s*,\s*(\-?\d+(?:\.\d+)?)%\s*,\s*([01]|[01]?\.\d+)\)/)) {
                hsl = m.slice(1, 4);
                hsl[1] *= 0.01;
                hsl[2] *= 0.01;
                rgb = hsl2rgb(hsl);
                rgb[3] = +m[4];
            }
            return rgb;
        };

        hex2rgb = function(hex) {
            var a, b, g, r, rgb, u;

            if (hex.match(/^#?([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/)) {
                if (hex.length === 4 || hex.length === 7) {
                    hex = hex.substr(1);
                }
                if (hex.length === 3) {
                    hex = hex.split("");
                    hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
                }
                u = parseInt(hex, 16);
                r = u >> 16;
                g = u >> 8 & 0xFF;
                b = u & 0xFF;
                return [r, g, b, 1];
            }
            if (hex.match(/^#?([A-Fa-f0-9]{8})$/)) {
                if (hex.length === 9) {
                    hex = hex.substr(1);
                }
                u = parseInt(hex, 16);
                r = u >> 24 & 0xFF;
                g = u >> 16 & 0xFF;
                b = u >> 8 & 0xFF;
                a = u & 0xFF;
                return [r, g, b, a];
            }
            if (rgb = css2rgb(hex)) {
                return rgb;
            }
            throw "unknown color: " + hex;
        };

        hsi2rgb = function(h, s, i) {
            /*
             borrowed from here:
             http://hummer.stanford.edu/museinfo/doc/examples/humdrum/keyscape2/hsi2rgb.cpp
             */

            var b, g, r, _ref;

            _ref = unpack(arguments), h = _ref[0], s = _ref[1], i = _ref[2];
            h /= 360;
            if (h < 1 / 3) {
                b = (1 - s) / 3;
                r = (1 + s * cos(TWOPI * h) / cos(PITHIRD - TWOPI * h)) / 3;
                g = 1 - (b + r);
            } else if (h < 2 / 3) {
                h -= 1 / 3;
                r = (1 - s) / 3;
                g = (1 + s * cos(TWOPI * h) / cos(PITHIRD - TWOPI * h)) / 3;
                b = 1 - (r + g);
            } else {
                h -= 2 / 3;
                g = (1 - s) / 3;
                b = (1 + s * cos(TWOPI * h) / cos(PITHIRD - TWOPI * h)) / 3;
                r = 1 - (g + b);
            }
            r = limit(i * r * 3);
            g = limit(i * g * 3);
            b = limit(i * b * 3);
            return [r * 255, g * 255, b * 255];
        };

        hsl2rgb = function() {
            var b, c, g, h, i, l, r, s, t1, t2, t3, _i, _ref, _ref1;

            _ref = unpack(arguments), h = _ref[0], s = _ref[1], l = _ref[2];
            if (s === 0) {
                r = g = b = l * 255;
            } else {
                t3 = [0, 0, 0];
                c = [0, 0, 0];
                t2 = l < 0.5 ? l * (1 + s) : l + s - l * s;
                t1 = 2 * l - t2;
                h /= 360;
                t3[0] = h + 1 / 3;
                t3[1] = h;
                t3[2] = h - 1 / 3;
                for (i = _i = 0; _i <= 2; i = ++_i) {
                    if (t3[i] < 0) {
                        t3[i] += 1;
                    }
                    if (t3[i] > 1) {
                        t3[i] -= 1;
                    }
                    if (6 * t3[i] < 1) {
                        c[i] = t1 + (t2 - t1) * 6 * t3[i];
                    } else if (2 * t3[i] < 1) {
                        c[i] = t2;
                    } else if (3 * t3[i] < 2) {
                        c[i] = t1 + (t2 - t1) * ((2 / 3) - t3[i]) * 6;
                    } else {
                        c[i] = t1;
                    }
                }
                _ref1 = [Math.round(c[0] * 255), Math.round(c[1] * 255), Math.round(c[2] * 255)], r = _ref1[0], g = _ref1[1], b = _ref1[2];
            }
            return [r, g, b];
        };

        hsv2rgb = function() {
            var b, f, g, h, i, p, q, r, s, t, v, _ref, _ref1, _ref2, _ref3, _ref4, _ref5, _ref6;

            _ref = unpack(arguments), h = _ref[0], s = _ref[1], v = _ref[2];
            v *= 255;
            if (s === 0) {
                r = g = b = v;
            } else {
                if (h === 360) {
                    h = 0;
                }
                if (h > 360) {
                    h -= 360;
                }
                if (h < 0) {
                    h += 360;
                }
                h /= 60;
                i = Math.floor(h);
                f = h - i;
                p = v * (1 - s);
                q = v * (1 - s * f);
                t = v * (1 - s * (1 - f));
                switch (i) {
                    case 0:
                        _ref1 = [v, t, p], r = _ref1[0], g = _ref1[1], b = _ref1[2];
                        break;
                    case 1:
                        _ref2 = [q, v, p], r = _ref2[0], g = _ref2[1], b = _ref2[2];
                        break;
                    case 2:
                        _ref3 = [p, v, t], r = _ref3[0], g = _ref3[1], b = _ref3[2];
                        break;
                    case 3:
                        _ref4 = [p, q, v], r = _ref4[0], g = _ref4[1], b = _ref4[2];
                        break;
                    case 4:
                        _ref5 = [t, p, v], r = _ref5[0], g = _ref5[1], b = _ref5[2];
                        break;
                    case 5:
                        _ref6 = [v, p, q], r = _ref6[0], g = _ref6[1], b = _ref6[2];
                }
            }
            r = Math.round(r);
            g = Math.round(g);
            b = Math.round(b);
            return [r, g, b];
        };

        K = 18;

        X = 0.950470;

        Y = 1;

        Z = 1.088830;

        lab2lch = function() {
            var a, b, c, h, l, _ref;

            _ref = unpack(arguments), l = _ref[0], a = _ref[1], b = _ref[2];
            c = Math.sqrt(a * a + b * b);
            h = Math.atan2(b, a) / Math.PI * 180;
            return [l, c, h];
        };

        lab2rgb = function(l, a, b) {
            /*
             adapted to match d3 implementation
             */

            var g, r, x, y, z, _ref, _ref1;

            if (l !== void 0 && l.length === 3) {
                _ref = l, l = _ref[0], a = _ref[1], b = _ref[2];
            }
            if (l !== void 0 && l.length === 3) {
                _ref1 = l, l = _ref1[0], a = _ref1[1], b = _ref1[2];
            }
            y = (l + 16) / 116;
            x = y + a / 500;
            z = y - b / 200;
            x = lab_xyz(x) * X;
            y = lab_xyz(y) * Y;
            z = lab_xyz(z) * Z;
            r = xyz_rgb(3.2404542 * x - 1.5371385 * y - 0.4985314 * z);
            g = xyz_rgb(-0.9692660 * x + 1.8760108 * y + 0.0415560 * z);
            b = xyz_rgb(0.0556434 * x - 0.2040259 * y + 1.0572252 * z);
            return [limit(r, 0, 255), limit(g, 0, 255), limit(b, 0, 255), 1];
        };

        lab_xyz = function(x) {
            if (x > 0.206893034) {
                return x * x * x;
            } else {
                return (x - 4 / 29) / 7.787037;
            }
        };

        xyz_rgb = function(r) {
            return Math.round(255 * (r <= 0.00304 ? 12.92 * r : 1.055 * Math.pow(r, 1 / 2.4) - 0.055));
        };

        lch2lab = function() {
            /*
             Convert from a qualitative parameter h and a quantitative parameter l to a 24-bit pixel. These formulas were invented by David Dalrymple to obtain maximum contrast without going out of gamut if the parameters are in the range 0-1.
             A saturation multiplier was added by Gregor Aisch
             */

            var c, h, l, _ref;

            _ref = unpack(arguments), l = _ref[0], c = _ref[1], h = _ref[2];
            h = h * Math.PI / 180;
            return [l, Math.cos(h) * c, Math.sin(h) * c];
        };

        lch2rgb = function(l, c, h) {
            var L, a, b, g, r, _ref, _ref1;

            _ref = lch2lab(l, c, h), L = _ref[0], a = _ref[1], b = _ref[2];
            _ref1 = lab2rgb(L, a, b), r = _ref1[0], g = _ref1[1], b = _ref1[2];
            return [limit(r, 0, 255), limit(g, 0, 255), limit(b, 0, 255)];
        };

        luminance = function(r, g, b) {
            var _ref;

            _ref = unpack(arguments), r = _ref[0], g = _ref[1], b = _ref[2];
            r = luminance_x(r);
            g = luminance_x(g);
            b = luminance_x(b);
            return 0.2126 * r + 0.7152 * g + 0.0722 * b;
        };

        luminance_x = function(x) {
            x /= 255;
            if (x <= 0.03928) {
                return x / 12.92;
            } else {
                return Math.pow((x + 0.055) / 1.055, 2.4);
            }
        };

        rgb2hex = function() {
            var b, g, r, str, u, _ref;

            _ref = unpack(arguments), r = _ref[0], g = _ref[1], b = _ref[2];
            u = r << 16 | g << 8 | b;
            str = "000000" + u.toString(16);
            return "#" + str.substr(str.length - 6);
        };

        rgb2hsi = function() {
            /*
             borrowed from here:
             http://hummer.stanford.edu/museinfo/doc/examples/humdrum/keyscape2/rgb2hsi.cpp
             */

            var TWOPI, b, g, h, i, min, r, s, _ref;

            _ref = unpack(arguments), r = _ref[0], g = _ref[1], b = _ref[2];
            TWOPI = Math.PI * 2;
            r /= 255;
            g /= 255;
            b /= 255;
            min = Math.min(r, g, b);
            i = (r + g + b) / 3;
            s = 1 - min / i;
            if (s === 0) {
                h = 0;
            } else {
                h = ((r - g) + (r - b)) / 2;
                h /= Math.sqrt((r - g) * (r - g) + (r - b) * (g - b));
                h = Math.acos(h);
                if (b > g) {
                    h = TWOPI - h;
                }
                h /= TWOPI;
            }
            return [h * 360, s, i];
        };

        rgb2hsl = function(r, g, b) {
            var h, l, max, min, s, _ref;

            if (r !== void 0 && r.length >= 3) {
                _ref = r, r = _ref[0], g = _ref[1], b = _ref[2];
            }
            r /= 255;
            g /= 255;
            b /= 255;
            min = Math.min(r, g, b);
            max = Math.max(r, g, b);
            l = (max + min) / 2;
            if (max === min) {
                s = 0;
                h = Number.NaN;
            } else {
                s = l < 0.5 ? (max - min) / (max + min) : (max - min) / (2 - max - min);
            }
            if (r === max) {
                h = (g - b) / (max - min);
            } else if (g === max) {
                h = 2 + (b - r) / (max - min);
            } else if (b === max) {
                h = 4 + (r - g) / (max - min);
            }
            h *= 60;
            if (h < 0) {
                h += 360;
            }
            return [h, s, l];
        };

        rgb2hsv = function() {
            var b, delta, g, h, max, min, r, s, v, _ref;

            _ref = unpack(arguments), r = _ref[0], g = _ref[1], b = _ref[2];
            min = Math.min(r, g, b);
            max = Math.max(r, g, b);
            delta = max - min;
            v = max / 255.0;
            if (max === 0) {
                h = Number.NaN;
                s = 0;
            } else {
                s = delta / max;
                if (r === max) {
                    h = (g - b) / delta;
                }
                if (g === max) {
                    h = 2 + (b - r) / delta;
                }
                if (b === max) {
                    h = 4 + (r - g) / delta;
                }
                h *= 60;
                if (h < 0) {
                    h += 360;
                }
            }
            return [h, s, v];
        };

        rgb2lab = function() {
            var b, g, r, x, y, z, _ref;

            _ref = unpack(arguments), r = _ref[0], g = _ref[1], b = _ref[2];
            r = rgb_xyz(r);
            g = rgb_xyz(g);
            b = rgb_xyz(b);
            x = xyz_lab((0.4124564 * r + 0.3575761 * g + 0.1804375 * b) / X);
            y = xyz_lab((0.2126729 * r + 0.7151522 * g + 0.0721750 * b) / Y);
            z = xyz_lab((0.0193339 * r + 0.1191920 * g + 0.9503041 * b) / Z);
            return [116 * y - 16, 500 * (x - y), 200 * (y - z)];
        };

        rgb_xyz = function(r) {
            if ((r /= 255) <= 0.04045) {
                return r / 12.92;
            } else {
                return Math.pow((r + 0.055) / 1.055, 2.4);
            }
        };

        xyz_lab = function(x) {
            if (x > 0.008856) {
                return Math.pow(x, 1 / 3);
            } else {
                return 7.787037 * x + 4 / 29;
            }
        };

        rgb2lch = function() {
            var a, b, g, l, r, _ref, _ref1;

            _ref = unpack(arguments), r = _ref[0], g = _ref[1], b = _ref[2];
            _ref1 = rgb2lab(r, g, b), l = _ref1[0], a = _ref1[1], b = _ref1[2];
            return lab2lch(l, a, b);
        };

        /*
         chroma.js

         Copyright (c) 2011-2013, Gregor Aisch
         All rights reserved.

         Redistribution and use in source and binary forms, with or without
         modification, are permitted provided that the following conditions are met:

         * Redistributions of source code must retain the above copyright notice, this
         list of conditions and the following disclaimer.

         * Redistributions in binary form must reproduce the above copyright notice,
         this list of conditions and the following disclaimer in the documentation
         and/or other materials provided with the distribution.

         * The name Gregor Aisch may not be used to endorse or promote products
         derived from this software without specific prior written permission.

         THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
         AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
         IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
         DISCLAIMED. IN NO EVENT SHALL GREGOR AISCH OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
         INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
         BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
         DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
         OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
         NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
         EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

         @source: https://github.com/gka/chroma.js
         */


        chroma.scale = function(colors, positions) {
            var classifyValue, f, getClass, getColor, resetCache, setColors, setDomain, tmap, _colorCache, _colors, _correctLightness, _domain, _fixed, _max, _min, _mode, _nacol, _numClasses, _out, _pos, _spread;

            _mode = 'rgb';
            _nacol = chroma('#ccc');
            _spread = 0;
            _fixed = false;
            _domain = [0, 1];
            _colors = [];
            _out = false;
            _pos = [];
            _min = 0;
            _max = 1;
            _correctLightness = false;
            _numClasses = 0;
            _colorCache = {};
            setColors = function(colors, positions) {
                var c, col, _i, _j, _ref, _ref1, _ref2;

                if (colors == null) {
                    colors = ['#ddd', '#222'];
                }
                if ((colors != null) && type(colors) === 'string' && (((_ref = chroma.brewer) != null ? _ref[colors] : void 0) != null)) {
                    colors = chroma.brewer[colors];
                }
                if (type(colors) === 'array') {
                    colors = colors.slice(0);
                    for (c = _i = 0, _ref1 = colors.length - 1; 0 <= _ref1 ? _i <= _ref1 : _i >= _ref1; c = 0 <= _ref1 ? ++_i : --_i) {
                        col = colors[c];
                        if (type(col) === "string") {
                            colors[c] = chroma(col);
                        }
                    }
                    if (positions != null) {
                        _pos = positions;
                    } else {
                        _pos = [];
                        for (c = _j = 0, _ref2 = colors.length - 1; 0 <= _ref2 ? _j <= _ref2 : _j >= _ref2; c = 0 <= _ref2 ? ++_j : --_j) {
                            _pos.push(c / (colors.length - 1));
                        }
                    }
                }
                resetCache();
                return _colors = colors;
            };
            setDomain = function(domain) {
                if (domain == null) {
                    domain = [];
                }
                /*
                 # use this if you want to display a limited number of data classes
                 # possible methods are "equalinterval", "quantiles", "custom"
                 */

                _domain = domain;
                _min = domain[0];
                _max = domain[domain.length - 1];
                resetCache();
                if (domain.length === 2) {
                    return _numClasses = 0;
                } else {
                    return _numClasses = domain.length - 1;
                }
            };
            getClass = function(value) {
                var i, n;

                if (_domain != null) {
                    n = _domain.length - 1;
                    i = 0;
                    while (i < n && value >= _domain[i]) {
                        i++;
                    }
                    return i - 1;
                }
                return 0;
            };
            tmap = function(t) {
                return t;
            };
            classifyValue = function(value) {
                var i, maxc, minc, n, val;

                val = value;
                if (_domain.length > 2) {
                    n = _domain.length - 1;
                    i = getClass(value);
                    minc = _domain[0] + (_domain[1] - _domain[0]) * (0 + _spread * 0.5);
                    maxc = _domain[n - 1] + (_domain[n] - _domain[n - 1]) * (1 - _spread * 0.5);
                    val = _min + ((_domain[i] + (_domain[i + 1] - _domain[i]) * 0.5 - minc) / (maxc - minc)) * (_max - _min);
                }
                return val;
            };
            getColor = function(val, bypassMap) {
                var c, col, f0, i, k, p, t, _i, _ref;

                if (bypassMap == null) {
                    bypassMap = false;
                }
                if (isNaN(val)) {
                    return _nacol;
                }
                if (!bypassMap) {
                    if (_domain.length > 2) {
                        c = getClass(val);
                        t = c / (_numClasses - 1);
                    } else {
                        t = f0 = _min !== _max ? (val - _min) / (_max - _min) : 0;
                        t = f0 = (val - _min) / (_max - _min);
                        t = Math.min(1, Math.max(0, t));
                    }
                } else {
                    t = val;
                }
                if (!bypassMap) {
                    t = tmap(t);
                }
                k = Math.floor(t * 10000);
                if (_colorCache[k]) {
                    col = _colorCache[k];
                } else {
                    if (type(_colors) === 'array') {
                        for (i = _i = 0, _ref = _pos.length - 1; 0 <= _ref ? _i <= _ref : _i >= _ref; i = 0 <= _ref ? ++_i : --_i) {
                            p = _pos[i];
                            if (t <= p) {
                                col = _colors[i];
                                break;
                            }
                            if (t >= p && i === _pos.length - 1) {
                                col = _colors[i];
                                break;
                            }
                            if (t > p && t < _pos[i + 1]) {
                                t = (t - p) / (_pos[i + 1] - p);
                                col = chroma.interpolate(_colors[i], _colors[i + 1], t, _mode);
                                break;
                            }
                        }
                    } else if (type(_colors) === 'function') {
                        col = _colors(t);
                    }
                    _colorCache[k] = col;
                }
                return col;
            };
            resetCache = function() {
                return _colorCache = {};
            };
            setColors(colors, positions);
            f = function(v) {
                var c;

                c = getColor(v);
                if (_out && c[_out]) {
                    return c[_out]();
                } else {
                    return c;
                }
            };
            f.domain = function(domain, classes, mode, key) {
                var d;

                if (mode == null) {
                    mode = 'e';
                }
                if (!arguments.length) {
                    return _domain;
                }
                if (classes != null) {
                    d = chroma.analyze(domain, key);
                    if (classes === 0) {
                        domain = [d.min, d.max];
                    } else {
                        domain = chroma.limits(d, mode, classes);
                    }
                }
                setDomain(domain);
                return f;
            };
            f.mode = function(_m) {
                if (!arguments.length) {
                    return _mode;
                }
                _mode = _m;
                resetCache();
                return f;
            };
            f.range = function(colors, _pos) {
                setColors(colors, _pos);
                return f;
            };
            f.out = function(_o) {
                _out = _o;
                return f;
            };
            f.spread = function(val) {
                if (!arguments.length) {
                    return _spread;
                }
                _spread = val;
                return f;
            };
            f.correctLightness = function(v) {
                if (!arguments.length) {
                    return _correctLightness;
                }
                _correctLightness = v;
                resetCache();
                if (_correctLightness) {
                    tmap = function(t) {
                        var L0, L1, L_actual, L_diff, L_ideal, max_iter, pol, t0, t1;

                        L0 = getColor(0, true).lab()[0];
                        L1 = getColor(1, true).lab()[0];
                        pol = L0 > L1;
                        L_actual = getColor(t, true).lab()[0];
                        L_ideal = L0 + (L1 - L0) * t;
                        L_diff = L_actual - L_ideal;
                        t0 = 0;
                        t1 = 1;
                        max_iter = 20;
                        while (Math.abs(L_diff) > 1e-2 && max_iter-- > 0) {
                            (function() {
                                if (pol) {
                                    L_diff *= -1;
                                }
                                if (L_diff < 0) {
                                    t0 = t;
                                    t += (t1 - t) * 0.5;
                                } else {
                                    t1 = t;
                                    t += (t0 - t) * 0.5;
                                }
                                L_actual = getColor(t, true).lab()[0];
                                return L_diff = L_actual - L_ideal;
                            })();
                        }
                        return t;
                    };
                } else {
                    tmap = function(t) {
                        return t;
                    };
                }
                return f;
            };
            f.colors = function(out) {
                var i, samples, _i, _j, _len, _ref;

                if (out == null) {
                    out = 'hex';
                }
                colors = [];
                samples = [];
                if (_domain.length > 2) {
                    for (i = _i = 1, _ref = _domain.length; 1 <= _ref ? _i < _ref : _i > _ref; i = 1 <= _ref ? ++_i : --_i) {
                        samples.push((_domain[i - 1] + _domain[i]) * 0.5);
                    }
                } else {
                    samples = _domain;
                }
                for (_j = 0, _len = samples.length; _j < _len; _j++) {
                    i = samples[_j];
                    colors.push(f(i)[out]());
                }
                return colors;
            };
            return f;
        };

        if ((_ref = chroma.scales) == null) {
            chroma.scales = {};
        }

        chroma.scales.cool = function() {
            return chroma.scale([chroma.hsl(180, 1, .9), chroma.hsl(250, .7, .4)]);
        };

        chroma.scales.hot = function() {
            return chroma.scale(['#000', '#f00', '#ff0', '#fff'], [0, .25, .75, 1]).mode('rgb');
        };

        /*
         chroma.js

         Copyright (c) 2011-2013, Gregor Aisch
         All rights reserved.

         Redistribution and use in source and binary forms, with or without
         modification, are permitted provided that the following conditions are met:

         * Redistributions of source code must retain the above copyright notice, this
         list of conditions and the following disclaimer.

         * Redistributions in binary form must reproduce the above copyright notice,
         this list of conditions and the following disclaimer in the documentation
         and/or other materials provided with the distribution.

         * The name Gregor Aisch may not be used to endorse or promote products
         derived from this software without specific prior written permission.

         THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
         AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
         IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
         DISCLAIMED. IN NO EVENT SHALL GREGOR AISCH OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
         INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
         BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
         DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
         OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
         NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
         EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

         @source: https://github.com/gka/chroma.js
         */


        chroma.analyze = function(data, key, filter) {
            var add, k, r, val, visit, _i, _len;

            r = {
                min: Number.MAX_VALUE,
                max: Number.MAX_VALUE * -1,
                sum: 0,
                values: [],
                count: 0
            };
            if (filter == null) {
                filter = function() {
                    return true;
                };
            }
            add = function(val) {
                if ((val != null) && !isNaN(val)) {
                    r.values.push(val);
                    r.sum += val;
                    if (val < r.min) {
                        r.min = val;
                    }
                    if (val > r.max) {
                        r.max = val;
                    }
                    r.count += 1;
                }
            };
            visit = function(val, k) {
                if (filter(val, k)) {
                    if ((key != null) && type(key) === 'function') {
                        return add(key(val));
                    } else if ((key != null) && type(key) === 'string' || type(key) === 'number') {
                        return add(val[key]);
                    } else {
                        return add(val);
                    }
                }
            };
            if (type(data) === 'array') {
                for (_i = 0, _len = data.length; _i < _len; _i++) {
                    val = data[_i];
                    visit(val);
                }
            } else {
                for (k in data) {
                    val = data[k];
                    visit(val, k);
                }
            }
            r.domain = [r.min, r.max];
            r.limits = function(mode, num) {
                return chroma.limits(r, mode, num);
            };
            return r;
        };

        chroma.limits = function(data, mode, num) {
            var assignments, best, centroids, cluster, clusterSizes, dist, i, j, kClusters, limits, max, max_log, min, min_log, mindist, n, nb_iters, newCentroids, p, pb, pr, repeat, sum, tmpKMeansBreaks, value, values, _i, _j, _k, _l, _m, _n, _o, _p, _q, _r, _ref1, _ref10, _ref11, _ref12, _ref13, _ref14, _ref15, _ref2, _ref3, _ref4, _ref5, _ref6, _ref7, _ref8, _ref9, _s, _t, _u, _v, _w;

            if (mode == null) {
                mode = 'equal';
            }
            if (num == null) {
                num = 7;
            }
            if (type(data) === 'array') {
                data = chroma.analyze(data);
            }
            min = data.min;
            max = data.max;
            sum = data.sum;
            values = data.values.sort(function(a, b) {
                return a - b;
            });
            limits = [];
            if (mode.substr(0, 1) === 'c') {
                limits.push(min);
                limits.push(max);
            }
            if (mode.substr(0, 1) === 'e') {
                limits.push(min);
                for (i = _i = 1, _ref1 = num - 1; 1 <= _ref1 ? _i <= _ref1 : _i >= _ref1; i = 1 <= _ref1 ? ++_i : --_i) {
                    limits.push(min + (i / num) * (max - min));
                }
                limits.push(max);
            } else if (mode.substr(0, 1) === 'l') {
                if (min <= 0) {
                    throw 'Logarithmic scales are only possible for values > 0';
                }
                min_log = Math.LOG10E * Math.log(min);
                max_log = Math.LOG10E * Math.log(max);
                limits.push(min);
                for (i = _j = 1, _ref2 = num - 1; 1 <= _ref2 ? _j <= _ref2 : _j >= _ref2; i = 1 <= _ref2 ? ++_j : --_j) {
                    limits.push(Math.pow(10, min_log + (i / num) * (max_log - min_log)));
                }
                limits.push(max);
            } else if (mode.substr(0, 1) === 'q') {
                limits.push(min);
                for (i = _k = 1, _ref3 = num - 1; 1 <= _ref3 ? _k <= _ref3 : _k >= _ref3; i = 1 <= _ref3 ? ++_k : --_k) {
                    p = values.length * i / num;
                    pb = Math.floor(p);
                    if (pb === p) {
                        limits.push(values[pb]);
                    } else {
                        pr = p - pb;
                        limits.push(values[pb] * pr + values[pb + 1] * (1 - pr));
                    }
                }
                limits.push(max);
            } else if (mode.substr(0, 1) === 'k') {
                /*
                 implementation based on
                 http://code.google.com/p/figue/source/browse/trunk/figue.js#336
                 simplified for 1-d input values
                 */

                n = values.length;
                assignments = new Array(n);
                clusterSizes = new Array(num);
                repeat = true;
                nb_iters = 0;
                centroids = null;
                centroids = [];
                centroids.push(min);
                for (i = _l = 1, _ref4 = num - 1; 1 <= _ref4 ? _l <= _ref4 : _l >= _ref4; i = 1 <= _ref4 ? ++_l : --_l) {
                    centroids.push(min + (i / num) * (max - min));
                }
                centroids.push(max);
                while (repeat) {
                    for (j = _m = 0, _ref5 = num - 1; 0 <= _ref5 ? _m <= _ref5 : _m >= _ref5; j = 0 <= _ref5 ? ++_m : --_m) {
                        clusterSizes[j] = 0;
                    }
                    for (i = _n = 0, _ref6 = n - 1; 0 <= _ref6 ? _n <= _ref6 : _n >= _ref6; i = 0 <= _ref6 ? ++_n : --_n) {
                        value = values[i];
                        mindist = Number.MAX_VALUE;
                        for (j = _o = 0, _ref7 = num - 1; 0 <= _ref7 ? _o <= _ref7 : _o >= _ref7; j = 0 <= _ref7 ? ++_o : --_o) {
                            dist = Math.abs(centroids[j] - value);
                            if (dist < mindist) {
                                mindist = dist;
                                best = j;
                            }
                        }
                        clusterSizes[best]++;
                        assignments[i] = best;
                    }
                    newCentroids = new Array(num);
                    for (j = _p = 0, _ref8 = num - 1; 0 <= _ref8 ? _p <= _ref8 : _p >= _ref8; j = 0 <= _ref8 ? ++_p : --_p) {
                        newCentroids[j] = null;
                    }
                    for (i = _q = 0, _ref9 = n - 1; 0 <= _ref9 ? _q <= _ref9 : _q >= _ref9; i = 0 <= _ref9 ? ++_q : --_q) {
                        cluster = assignments[i];
                        if (newCentroids[cluster] === null) {
                            newCentroids[cluster] = values[i];
                        } else {
                            newCentroids[cluster] += values[i];
                        }
                    }
                    for (j = _r = 0, _ref10 = num - 1; 0 <= _ref10 ? _r <= _ref10 : _r >= _ref10; j = 0 <= _ref10 ? ++_r : --_r) {
                        newCentroids[j] *= 1 / clusterSizes[j];
                    }
                    repeat = false;
                    for (j = _s = 0, _ref11 = num - 1; 0 <= _ref11 ? _s <= _ref11 : _s >= _ref11; j = 0 <= _ref11 ? ++_s : --_s) {
                        if (newCentroids[j] !== centroids[i]) {
                            repeat = true;
                            break;
                        }
                    }
                    centroids = newCentroids;
                    nb_iters++;
                    if (nb_iters > 200) {
                        repeat = false;
                    }
                }
                kClusters = {};
                for (j = _t = 0, _ref12 = num - 1; 0 <= _ref12 ? _t <= _ref12 : _t >= _ref12; j = 0 <= _ref12 ? ++_t : --_t) {
                    kClusters[j] = [];
                }
                for (i = _u = 0, _ref13 = n - 1; 0 <= _ref13 ? _u <= _ref13 : _u >= _ref13; i = 0 <= _ref13 ? ++_u : --_u) {
                    cluster = assignments[i];
                    kClusters[cluster].push(values[i]);
                }
                tmpKMeansBreaks = [];
                for (j = _v = 0, _ref14 = num - 1; 0 <= _ref14 ? _v <= _ref14 : _v >= _ref14; j = 0 <= _ref14 ? ++_v : --_v) {
                    tmpKMeansBreaks.push(kClusters[j][0]);
                    tmpKMeansBreaks.push(kClusters[j][kClusters[j].length - 1]);
                }
                tmpKMeansBreaks = tmpKMeansBreaks.sort(function(a, b) {
                    return a - b;
                });
                limits.push(tmpKMeansBreaks[0]);
                for (i = _w = 1, _ref15 = tmpKMeansBreaks.length - 1; _w <= _ref15; i = _w += 2) {
                    if (!isNaN(tmpKMeansBreaks[i])) {
                        limits.push(tmpKMeansBreaks[i]);
                    }
                }
            }
            return limits;
        };

        /**
         ColorBrewer colors for chroma.js

         Copyright (c) 2002 Cynthia Brewer, Mark Harrower, and The
         Pennsylvania State University.

         Licensed under the Apache License, Version 2.0 (the "License");
         you may not use this file except in compliance with the License.
         You may obtain a copy of the License at
         http://www.apache.org/licenses/LICENSE-2.0

         Unless required by applicable law or agreed to in writing, software distributed
         under the License is distributed on an "AS IS" BASIS, WITHOUT WARRANTIES OR
         CONDITIONS OF ANY KIND, either express or implied. See the License for the
         specific language governing permissions and limitations under the License.

         @preserve
         */


        chroma.brewer = brewer = {
            OrRd: ['#fff7ec', '#fee8c8', '#fdd49e', '#fdbb84', '#fc8d59', '#ef6548', '#d7301f', '#b30000', '#7f0000'],
            PuBu: ['#fff7fb', '#ece7f2', '#d0d1e6', '#a6bddb', '#74a9cf', '#3690c0', '#0570b0', '#045a8d', '#023858'],
            BuPu: ['#f7fcfd', '#e0ecf4', '#bfd3e6', '#9ebcda', '#8c96c6', '#8c6bb1', '#88419d', '#810f7c', '#4d004b'],
            Oranges: ['#fff5eb', '#fee6ce', '#fdd0a2', '#fdae6b', '#fd8d3c', '#f16913', '#d94801', '#a63603', '#7f2704'],
            BuGn: ['#f7fcfd', '#e5f5f9', '#ccece6', '#99d8c9', '#66c2a4', '#41ae76', '#238b45', '#006d2c', '#00441b'],
            YlOrBr: ['#ffffe5', '#fff7bc', '#fee391', '#fec44f', '#fe9929', '#ec7014', '#cc4c02', '#993404', '#662506'],
            YlGn: ['#ffffe5', '#f7fcb9', '#d9f0a3', '#addd8e', '#78c679', '#41ab5d', '#238443', '#006837', '#004529'],
            Reds: ['#fff5f0', '#fee0d2', '#fcbba1', '#fc9272', '#fb6a4a', '#ef3b2c', '#cb181d', '#a50f15', '#67000d'],
            RdPu: ['#fff7f3', '#fde0dd', '#fcc5c0', '#fa9fb5', '#f768a1', '#dd3497', '#ae017e', '#7a0177', '#49006a'],
            Greens: ['#f7fcf5', '#e5f5e0', '#c7e9c0', '#a1d99b', '#74c476', '#41ab5d', '#238b45', '#006d2c', '#00441b'],
            YlGnBu: ['#ffffd9', '#edf8b1', '#c7e9b4', '#7fcdbb', '#41b6c4', '#1d91c0', '#225ea8', '#253494', '#081d58'],
            Purples: ['#fcfbfd', '#efedf5', '#dadaeb', '#bcbddc', '#9e9ac8', '#807dba', '#6a51a3', '#54278f', '#3f007d'],
            GnBu: ['#f7fcf0', '#e0f3db', '#ccebc5', '#a8ddb5', '#7bccc4', '#4eb3d3', '#2b8cbe', '#0868ac', '#084081'],
            Greys: ['#ffffff', '#f0f0f0', '#d9d9d9', '#bdbdbd', '#969696', '#737373', '#525252', '#252525', '#000000'],
            YlOrRd: ['#ffffcc', '#ffeda0', '#fed976', '#feb24c', '#fd8d3c', '#fc4e2a', '#e31a1c', '#bd0026', '#800026'],
            PuRd: ['#f7f4f9', '#e7e1ef', '#d4b9da', '#c994c7', '#df65b0', '#e7298a', '#ce1256', '#980043', '#67001f'],
            Blues: ['#f7fbff', '#deebf7', '#c6dbef', '#9ecae1', '#6baed6', '#4292c6', '#2171b5', '#08519c', '#08306b'],
            PuBuGn: ['#fff7fb', '#ece2f0', '#d0d1e6', '#a6bddb', '#67a9cf', '#3690c0', '#02818a', '#016c59', '#014636'],
            Spectral: ['#9e0142', '#d53e4f', '#f46d43', '#fdae61', '#fee08b', '#ffffbf', '#e6f598', '#abdda4', '#66c2a5', '#3288bd', '#5e4fa2'],
            RdYlGn: ['#a50026', '#d73027', '#f46d43', '#fdae61', '#fee08b', '#ffffbf', '#d9ef8b', '#a6d96a', '#66bd63', '#1a9850', '#006837'],
            RdBu: ['#67001f', '#b2182b', '#d6604d', '#f4a582', '#fddbc7', '#f7f7f7', '#d1e5f0', '#92c5de', '#4393c3', '#2166ac', '#053061'],
            PiYG: ['#8e0152', '#c51b7d', '#de77ae', '#f1b6da', '#fde0ef', '#f7f7f7', '#e6f5d0', '#b8e186', '#7fbc41', '#4d9221', '#276419'],
            PRGn: ['#40004b', '#762a83', '#9970ab', '#c2a5cf', '#e7d4e8', '#f7f7f7', '#d9f0d3', '#a6dba0', '#5aae61', '#1b7837', '#00441b'],
            RdYlBu: ['#a50026', '#d73027', '#f46d43', '#fdae61', '#fee090', '#ffffbf', '#e0f3f8', '#abd9e9', '#74add1', '#4575b4', '#313695'],
            BrBG: ['#543005', '#8c510a', '#bf812d', '#dfc27d', '#f6e8c3', '#f5f5f5', '#c7eae5', '#80cdc1', '#35978f', '#01665e', '#003c30'],
            RdGy: ['#67001f', '#b2182b', '#d6604d', '#f4a582', '#fddbc7', '#ffffff', '#e0e0e0', '#bababa', '#878787', '#4d4d4d', '#1a1a1a'],
            PuOr: ['#7f3b08', '#b35806', '#e08214', '#fdb863', '#fee0b6', '#f7f7f7', '#d8daeb', '#b2abd2', '#8073ac', '#542788', '#2d004b'],
            Set2: ['#66c2a5', '#fc8d62', '#8da0cb', '#e78ac3', '#a6d854', '#ffd92f', '#e5c494', '#b3b3b3'],
            Accent: ['#7fc97f', '#beaed4', '#fdc086', '#ffff99', '#386cb0', '#f0027f', '#bf5b17', '#666666'],
            Set1: ['#e41a1c', '#377eb8', '#4daf4a', '#984ea3', '#ff7f00', '#ffff33', '#a65628', '#f781bf', '#999999'],
            Set3: ['#8dd3c7', '#ffffb3', '#bebada', '#fb8072', '#80b1d3', '#fdb462', '#b3de69', '#fccde5', '#d9d9d9', '#bc80bd', '#ccebc5', '#ffed6f'],
            Dark2: ['#1b9e77', '#d95f02', '#7570b3', '#e7298a', '#66a61e', '#e6ab02', '#a6761d', '#666666'],
            Paired: ['#a6cee3', '#1f78b4', '#b2df8a', '#33a02c', '#fb9a99', '#e31a1c', '#fdbf6f', '#ff7f00', '#cab2d6', '#6a3d9a', '#ffff99', '#b15928'],
            Pastel2: ['#b3e2cd', '#fdcdac', '#cbd5e8', '#f4cae4', '#e6f5c9', '#fff2ae', '#f1e2cc', '#cccccc'],
            Pastel1: ['#fbb4ae', '#b3cde3', '#ccebc5', '#decbe4', '#fed9a6', '#ffffcc', '#e5d8bd', '#fddaec', '#f2f2f2']
        };

        /**
         X11 color names

         http://www.w3.org/TR/css3-color/#svg-color
         */


        chroma.colors = colors = {
            indigo: "#4b0082",
            gold: "#ffd700",
            hotpink: "#ff69b4",
            firebrick: "#b22222",
            indianred: "#cd5c5c",
            yellow: "#ffff00",
            mistyrose: "#ffe4e1",
            darkolivegreen: "#556b2f",
            olive: "#808000",
            darkseagreen: "#8fbc8f",
            pink: "#ffc0cb",
            tomato: "#ff6347",
            lightcoral: "#f08080",
            orangered: "#ff4500",
            navajowhite: "#ffdead",
            lime: "#00ff00",
            palegreen: "#98fb98",
            darkslategrey: "#2f4f4f",
            greenyellow: "#adff2f",
            burlywood: "#deb887",
            seashell: "#fff5ee",
            mediumspringgreen: "#00fa9a",
            fuchsia: "#ff00ff",
            papayawhip: "#ffefd5",
            blanchedalmond: "#ffebcd",
            chartreuse: "#7fff00",
            dimgray: "#696969",
            black: "#000000",
            peachpuff: "#ffdab9",
            springgreen: "#00ff7f",
            aquamarine: "#7fffd4",
            white: "#ffffff",
            orange: "#ffa500",
            lightsalmon: "#ffa07a",
            darkslategray: "#2f4f4f",
            brown: "#a52a2a",
            ivory: "#fffff0",
            dodgerblue: "#1e90ff",
            peru: "#cd853f",
            lawngreen: "#7cfc00",
            chocolate: "#d2691e",
            crimson: "#dc143c",
            forestgreen: "#228b22",
            darkgrey: "#a9a9a9",
            lightseagreen: "#20b2aa",
            cyan: "#00ffff",
            mintcream: "#f5fffa",
            silver: "#c0c0c0",
            antiquewhite: "#faebd7",
            mediumorchid: "#ba55d3",
            skyblue: "#87ceeb",
            gray: "#808080",
            darkturquoise: "#00ced1",
            goldenrod: "#daa520",
            darkgreen: "#006400",
            floralwhite: "#fffaf0",
            darkviolet: "#9400d3",
            darkgray: "#a9a9a9",
            moccasin: "#ffe4b5",
            saddlebrown: "#8b4513",
            grey: "#808080",
            darkslateblue: "#483d8b",
            lightskyblue: "#87cefa",
            lightpink: "#ffb6c1",
            mediumvioletred: "#c71585",
            slategrey: "#708090",
            red: "#ff0000",
            deeppink: "#ff1493",
            limegreen: "#32cd32",
            darkmagenta: "#8b008b",
            palegoldenrod: "#eee8aa",
            plum: "#dda0dd",
            turquoise: "#40e0d0",
            lightgrey: "#d3d3d3",
            lightgoldenrodyellow: "#fafad2",
            darkgoldenrod: "#b8860b",
            lavender: "#e6e6fa",
            maroon: "#800000",
            yellowgreen: "#9acd32",
            sandybrown: "#f4a460",
            thistle: "#d8bfd8",
            violet: "#ee82ee",
            navy: "#000080",
            magenta: "#ff00ff",
            dimgrey: "#696969",
            tan: "#d2b48c",
            rosybrown: "#bc8f8f",
            olivedrab: "#6b8e23",
            blue: "#0000ff",
            lightblue: "#add8e6",
            ghostwhite: "#f8f8ff",
            honeydew: "#f0fff0",
            cornflowerblue: "#6495ed",
            slateblue: "#6a5acd",
            linen: "#faf0e6",
            darkblue: "#00008b",
            powderblue: "#b0e0e6",
            seagreen: "#2e8b57",
            darkkhaki: "#bdb76b",
            snow: "#fffafa",
            sienna: "#a0522d",
            mediumblue: "#0000cd",
            royalblue: "#4169e1",
            lightcyan: "#e0ffff",
            green: "#008000",
            mediumpurple: "#9370db",
            midnightblue: "#191970",
            cornsilk: "#fff8dc",
            paleturquoise: "#afeeee",
            bisque: "#ffe4c4",
            slategray: "#708090",
            darkcyan: "#008b8b",
            khaki: "#f0e68c",
            wheat: "#f5deb3",
            teal: "#008080",
            darkorchid: "#9932cc",
            deepskyblue: "#00bfff",
            salmon: "#fa8072",
            darkred: "#8b0000",
            steelblue: "#4682b4",
            palevioletred: "#db7093",
            lightslategray: "#778899",
            aliceblue: "#f0f8ff",
            lightslategrey: "#778899",
            lightgreen: "#90ee90",
            orchid: "#da70d6",
            gainsboro: "#dcdcdc",
            mediumseagreen: "#3cb371",
            lightgray: "#d3d3d3",
            mediumturquoise: "#48d1cc",
            lemonchiffon: "#fffacd",
            cadetblue: "#5f9ea0",
            lightyellow: "#ffffe0",
            lavenderblush: "#fff0f5",
            coral: "#ff7f50",
            purple: "#800080",
            aqua: "#00ffff",
            whitesmoke: "#f5f5f5",
            mediumslateblue: "#7b68ee",
            darkorange: "#ff8c00",
            mediumaquamarine: "#66cdaa",
            darksalmon: "#e9967a",
            beige: "#f5f5dc",
            blueviolet: "#8a2be2",
            azure: "#f0ffff",
            lightsteelblue: "#b0c4de",
            oldlace: "#fdf5e6"
        };

        /*
         chroma.js

         Copyright (c) 2011-2013, Gregor Aisch
         All rights reserved.

         Redistribution and use in source and binary forms, with or without
         modification, are permitted provided that the following conditions are met:

         * Redistributions of source code must retain the above copyright notice, this
         list of conditions and the following disclaimer.

         * Redistributions in binary form must reproduce the above copyright notice,
         this list of conditions and the following disclaimer in the documentation
         and/or other materials provided with the distribution.

         * The name Gregor Aisch may not be used to endorse or promote products
         derived from this software without specific prior written permission.

         THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
         AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
         IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
         DISCLAIMED. IN NO EVENT SHALL GREGOR AISCH OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
         INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
         BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
         DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY
         OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
         NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE,
         EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

         @source: https://github.com/gka/chroma.js
         */


        type = (function() {
            /*
             for browser-safe type checking+
             ported from jQuery's $.type
             */

            var classToType, name, _i, _len, _ref1;

            classToType = {};
            _ref1 = "Boolean Number String Function Array Date RegExp Undefined Null".split(" ");
            for (_i = 0, _len = _ref1.length; _i < _len; _i++) {
                name = _ref1[_i];
                classToType["[object " + name + "]"] = name.toLowerCase();
            }
            return function(obj) {
                var strType;

                strType = Object.prototype.toString.call(obj);
                return classToType[strType] || "object";
            };
        })();

        limit = function(x, min, max) {
            if (min == null) {
                min = 0;
            }
            if (max == null) {
                max = 1;
            }
            if (x < min) {
                x = min;
            }
            if (x > max) {
                x = max;
            }
            return x;
        };

        unpack = function(args) {
            if (args.length >= 3) {
                return args;
            } else {
                return args[0];
            }
        };

        TWOPI = Math.PI * 2;

        PITHIRD = Math.PI / 3;

        cos = Math.cos;

        /*
         interpolates between a set of colors uzing a bezier spline
         */


        bezier = function(colors) {
            var I, I0, I1, c, lab0, lab1, lab2, lab3, _ref1, _ref2, _ref3;

            colors = (function() {
                var _i, _len, _results;

                _results = [];
                for (_i = 0, _len = colors.length; _i < _len; _i++) {
                    c = colors[_i];
                    _results.push(chroma(c));
                }
                return _results;
            })();
            if (colors.length === 2) {
                _ref1 = (function() {
                    var _i, _len, _results;

                    _results = [];
                    for (_i = 0, _len = colors.length; _i < _len; _i++) {
                        c = colors[_i];
                        _results.push(c.lab());
                    }
                    return _results;
                })(), lab0 = _ref1[0], lab1 = _ref1[1];
                I = function(t) {
                    var i, lab;

                    lab = (function() {
                        var _i, _results;

                        _results = [];
                        for (i = _i = 0; _i <= 2; i = ++_i) {
                            _results.push(lab0[i] + t * (lab1[i] - lab0[i]));
                        }
                        return _results;
                    })();
                    return chroma.lab.apply(chroma, lab);
                };
            } else if (colors.length === 3) {
                _ref2 = (function() {
                    var _i, _len, _results;

                    _results = [];
                    for (_i = 0, _len = colors.length; _i < _len; _i++) {
                        c = colors[_i];
                        _results.push(c.lab());
                    }
                    return _results;
                })(), lab0 = _ref2[0], lab1 = _ref2[1], lab2 = _ref2[2];
                I = function(t) {
                    var i, lab;

                    lab = (function() {
                        var _i, _results;

                        _results = [];
                        for (i = _i = 0; _i <= 2; i = ++_i) {
                            _results.push((1 - t) * (1 - t) * lab0[i] + 2 * (1 - t) * t * lab1[i] + t * t * lab2[i]);
                        }
                        return _results;
                    })();
                    return chroma.lab.apply(chroma, lab);
                };
            } else if (colors.length === 4) {
                _ref3 = (function() {
                    var _i, _len, _results;

                    _results = [];
                    for (_i = 0, _len = colors.length; _i < _len; _i++) {
                        c = colors[_i];
                        _results.push(c.lab());
                    }
                    return _results;
                })(), lab0 = _ref3[0], lab1 = _ref3[1], lab2 = _ref3[2], lab3 = _ref3[3];
                I = function(t) {
                    var i, lab;

                    lab = (function() {
                        var _i, _results;

                        _results = [];
                        for (i = _i = 0; _i <= 2; i = ++_i) {
                            _results.push((1 - t) * (1 - t) * (1 - t) * lab0[i] + 3 * (1 - t) * (1 - t) * t * lab1[i] + 3 * (1 - t) * t * t * lab2[i] + t * t * t * lab3[i]);
                        }
                        return _results;
                    })();
                    return chroma.lab.apply(chroma, lab);
                };
            } else if (colors.length === 5) {
                I0 = bezier(colors.slice(0, 3));
                I1 = bezier(colors.slice(2, 5));
                I = function(t) {
                    if (t < 0.5) {
                        return I0(t * 2);
                    } else {
                        return I1((t - 0.5) * 2);
                    }
                };
            }
            return I;
        };

        chroma.interpolate.bezier = bezier;

    }).call(this);

},{}],"/Users/gpriday/Downloads/trianglify-master 2/node_modules/delaunay-fast/delaunay.js":[function(require,module,exports){
    var Delaunay;

    (function() {
        "use strict";

        var EPSILON = 1.0 / 1048576.0;

        function supertriangle(vertices) {
            var xmin = Number.POSITIVE_INFINITY,
                ymin = Number.POSITIVE_INFINITY,
                xmax = Number.NEGATIVE_INFINITY,
                ymax = Number.NEGATIVE_INFINITY,
                i, dx, dy, dmax, xmid, ymid;

            for(i = vertices.length; i--; ) {
                if(vertices[i][0] < xmin) xmin = vertices[i][0];
                if(vertices[i][0] > xmax) xmax = vertices[i][0];
                if(vertices[i][1] < ymin) ymin = vertices[i][1];
                if(vertices[i][1] > ymax) ymax = vertices[i][1];
            }

            dx = xmax - xmin;
            dy = ymax - ymin;
            dmax = Math.max(dx, dy);
            xmid = xmin + dx * 0.5;
            ymid = ymin + dy * 0.5;

            return [
                [xmid - 20 * dmax, ymid -      dmax],
                [xmid            , ymid + 20 * dmax],
                [xmid + 20 * dmax, ymid -      dmax]
            ];
        }

        function circumcircle(vertices, i, j, k) {
            var x1 = vertices[i][0],
                y1 = vertices[i][1],
                x2 = vertices[j][0],
                y2 = vertices[j][1],
                x3 = vertices[k][0],
                y3 = vertices[k][1],
                fabsy1y2 = Math.abs(y1 - y2),
                fabsy2y3 = Math.abs(y2 - y3),
                xc, yc, m1, m2, mx1, mx2, my1, my2, dx, dy;

            /* Check for coincident points */
            if(fabsy1y2 < EPSILON && fabsy2y3 < EPSILON)
                throw new Error("Eek! Coincident points!");

            if(fabsy1y2 < EPSILON) {
                m2  = -((x3 - x2) / (y3 - y2));
                mx2 = (x2 + x3) / 2.0;
                my2 = (y2 + y3) / 2.0;
                xc  = (x2 + x1) / 2.0;
                yc  = m2 * (xc - mx2) + my2;
            }

            else if(fabsy2y3 < EPSILON) {
                m1  = -((x2 - x1) / (y2 - y1));
                mx1 = (x1 + x2) / 2.0;
                my1 = (y1 + y2) / 2.0;
                xc  = (x3 + x2) / 2.0;
                yc  = m1 * (xc - mx1) + my1;
            }

            else {
                m1  = -((x2 - x1) / (y2 - y1));
                m2  = -((x3 - x2) / (y3 - y2));
                mx1 = (x1 + x2) / 2.0;
                mx2 = (x2 + x3) / 2.0;
                my1 = (y1 + y2) / 2.0;
                my2 = (y2 + y3) / 2.0;
                xc  = (m1 * mx1 - m2 * mx2 + my2 - my1) / (m1 - m2);
                yc  = (fabsy1y2 > fabsy2y3) ?
                m1 * (xc - mx1) + my1 :
                m2 * (xc - mx2) + my2;
            }

            dx = x2 - xc;
            dy = y2 - yc;
            return {i: i, j: j, k: k, x: xc, y: yc, r: dx * dx + dy * dy};
        }

        function dedup(edges) {
            var i, j, a, b, m, n;

            for(j = edges.length; j; ) {
                b = edges[--j];
                a = edges[--j];

                for(i = j; i; ) {
                    n = edges[--i];
                    m = edges[--i];

                    if((a === m && b === n) || (a === n && b === m)) {
                        edges.splice(j, 2);
                        edges.splice(i, 2);
                        break;
                    }
                }
            }
        }

        Delaunay = {
            triangulate: function(vertices, key) {
                var n = vertices.length,
                    i, j, indices, st, open, closed, edges, dx, dy, a, b, c;

                /* Bail if there aren't enough vertices to form any triangles. */
                if(n < 3)
                    return [];

                /* Slice out the actual vertices from the passed objects. (Duplicate the
                 * array even if we don't, though, since we need to make a supertriangle
                 * later on!) */
                vertices = vertices.slice(0);

                if(key)
                    for(i = n; i--; )
                        vertices[i] = vertices[i][key];

                /* Make an array of indices into the vertex array, sorted by the
                 * vertices' x-position. */
                indices = new Array(n);

                for(i = n; i--; )
                    indices[i] = i;

                indices.sort(function(i, j) {
                    return vertices[j][0] - vertices[i][0];
                });

                /* Next, find the vertices of the supertriangle (which contains all other
                 * triangles), and append them onto the end of a (copy of) the vertex
                 * array. */
                st = supertriangle(vertices);
                vertices.push(st[0], st[1], st[2]);

                /* Initialize the open list (containing the supertriangle and nothing
                 * else) and the closed list (which is empty since we havn't processed
                 * any triangles yet). */
                open   = [circumcircle(vertices, n + 0, n + 1, n + 2)];
                closed = [];
                edges  = [];

                /* Incrementally add each vertex to the mesh. */
                for(i = indices.length; i--; edges.length = 0) {
                    c = indices[i];

                    /* For each open triangle, check to see if the current point is
                     * inside it's circumcircle. If it is, remove the triangle and add
                     * it's edges to an edge list. */
                    for(j = open.length; j--; ) {
                        /* If this point is to the right of this triangle's circumcircle,
                         * then this triangle should never get checked again. Remove it
                         * from the open list, add it to the closed list, and skip. */
                        dx = vertices[c][0] - open[j].x;
                        if(dx > 0.0 && dx * dx > open[j].r) {
                            closed.push(open[j]);
                            open.splice(j, 1);
                            continue;
                        }

                        /* If we're outside the circumcircle, skip this triangle. */
                        dy = vertices[c][1] - open[j].y;
                        if(dx * dx + dy * dy - open[j].r > EPSILON)
                            continue;

                        /* Remove the triangle and add it's edges to the edge list. */
                        edges.push(
                            open[j].i, open[j].j,
                            open[j].j, open[j].k,
                            open[j].k, open[j].i
                        );
                        open.splice(j, 1);
                    }

                    /* Remove any doubled edges. */
                    dedup(edges);

                    /* Add a new triangle for each edge. */
                    for(j = edges.length; j; ) {
                        b = edges[--j];
                        a = edges[--j];
                        open.push(circumcircle(vertices, a, b, c));
                    }
                }

                /* Copy any remaining open triangles to the closed list, and then
                 * remove any triangles that share a vertex with the supertriangle,
                 * building a list of triplets that represent triangles. */
                for(i = open.length; i--; )
                    closed.push(open[i]);
                open.length = 0;

                for(i = closed.length; i--; )
                    if(closed[i].i < n && closed[i].j < n && closed[i].k < n)
                        open.push(closed[i].i, closed[i].j, closed[i].k);

                /* Yay, we're done! */
                return open;
            },
            contains: function(tri, p) {
                /* Bounding box test first, for quick rejections. */
                if((p[0] < tri[0][0] && p[0] < tri[1][0] && p[0] < tri[2][0]) ||
                    (p[0] > tri[0][0] && p[0] > tri[1][0] && p[0] > tri[2][0]) ||
                    (p[1] < tri[0][1] && p[1] < tri[1][1] && p[1] < tri[2][1]) ||
                    (p[1] > tri[0][1] && p[1] > tri[1][1] && p[1] > tri[2][1]))
                    return null;

                var a = tri[1][0] - tri[0][0],
                    b = tri[2][0] - tri[0][0],
                    c = tri[1][1] - tri[0][1],
                    d = tri[2][1] - tri[0][1],
                    i = a * d - b * c;

                /* Degenerate tri. */
                if(i === 0.0)
                    return null;

                var u = (d * (p[0] - tri[0][0]) - b * (p[1] - tri[0][1])) / i,
                    v = (a * (p[1] - tri[0][1]) - c * (p[0] - tri[0][0])) / i;

                /* If we're outside the tri, fail. */
                if(u < 0.0 || v < 0.0 || (u + v) > 1.0)
                    return null;

                return [u, v];
            }
        };

        if(typeof module !== "undefined")
            module.exports = Delaunay;
    })();

},{}],"/Users/gpriday/Downloads/trianglify-master 2/node_modules/seedrandom/index.js":[function(require,module,exports){
// A library of seedable RNGs implemented in Javascript.
//
// Usage:
//
// var seedrandom = require('seedrandom');
// var random = seedrandom(1); // or any seed.
// var x = random();       // 0 <= x < 1.  Every bit is random.
// var x = random.quick(); // 0 <= x < 1.  32 bits of randomness.

// alea, a 53-bit multiply-with-carry generator by Johannes Baagøe.
// Period: ~2^116
// Reported to pass all BigCrush tests.
    var alea = require('./lib/alea');

// xor128, a pure xor-shift generator by George Marsaglia.
// Period: 2^128-1.
// Reported to fail: MatrixRank and LinearComp.
    var xor128 = require('./lib/xor128');

// xorwow, George Marsaglia's 160-bit xor-shift combined plus weyl.
// Period: 2^192-2^32
// Reported to fail: CollisionOver, SimpPoker, and LinearComp.
    var xorwow = require('./lib/xorwow');

// xorshift7, by François Panneton and Pierre L'ecuyer, takes
// a different approach: it adds robustness by allowing more shifts
// than Marsaglia's original three.  It is a 7-shift generator
// with 256 bits, that passes BigCrush with no systmatic failures.
// Period 2^256-1.
// No systematic BigCrush failures reported.
    var xorshift7 = require('./lib/xorshift7');

// xor4096, by Richard Brent, is a 4096-bit xor-shift with a
// very long period that also adds a Weyl generator. It also passes
// BigCrush with no systematic failures.  Its long period may
// be useful if you have many generators and need to avoid
// collisions.
// Period: 2^4128-2^32.
// No systematic BigCrush failures reported.
    var xor4096 = require('./lib/xor4096');

// Tyche-i, by Samuel Neves and Filipe Araujo, is a bit-shifting random
// number generator derived from ChaCha, a modern stream cipher.
// https://eden.dei.uc.pt/~sneves/pubs/2011-snfa2.pdf
// Period: ~2^127
// No systematic BigCrush failures reported.
    var tychei = require('./lib/tychei');

// The original ARC4-based prng included in this library.
// Period: ~2^1600
    var sr = require('./seedrandom');

    sr.alea = alea;
    sr.xor128 = xor128;
    sr.xorwow = xorwow;
    sr.xorshift7 = xorshift7;
    sr.xor4096 = xor4096;
    sr.tychei = tychei;

    module.exports = sr;

},{"./lib/alea":"/Users/gpriday/Downloads/trianglify-master 2/node_modules/seedrandom/lib/alea.js","./lib/tychei":"/Users/gpriday/Downloads/trianglify-master 2/node_modules/seedrandom/lib/tychei.js","./lib/xor128":"/Users/gpriday/Downloads/trianglify-master 2/node_modules/seedrandom/lib/xor128.js","./lib/xor4096":"/Users/gpriday/Downloads/trianglify-master 2/node_modules/seedrandom/lib/xor4096.js","./lib/xorshift7":"/Users/gpriday/Downloads/trianglify-master 2/node_modules/seedrandom/lib/xorshift7.js","./lib/xorwow":"/Users/gpriday/Downloads/trianglify-master 2/node_modules/seedrandom/lib/xorwow.js","./seedrandom":"/Users/gpriday/Downloads/trianglify-master 2/node_modules/seedrandom/seedrandom.js"}],"/Users/gpriday/Downloads/trianglify-master 2/node_modules/seedrandom/lib/alea.js":[function(require,module,exports){
// A port of an algorithm by Johannes Baagøe <baagoe@baagoe.com>, 2010
// http://baagoe.com/en/RandomMusings/javascript/
// https://github.com/nquinlan/better-random-numbers-for-javascript-mirror
// Original work is under MIT license -

// Copyright (C) 2010 by Johannes Baagøe <baagoe@baagoe.org>
//
// Permission is hereby granted, free of charge, to any person obtaining a copy
// of this software and associated documentation files (the "Software"), to deal
// in the Software without restriction, including without limitation the rights
// to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
// copies of the Software, and to permit persons to whom the Software is
// furnished to do so, subject to the following conditions:
//
// The above copyright notice and this permission notice shall be included in
// all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
// IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
// FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
// AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
// OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
// THE SOFTWARE.



    (function(global, module, define) {

        function Alea(seed) {
            var me = this, mash = Mash();

            me.next = function() {
                var t = 2091639 * me.s0 + me.c * 2.3283064365386963e-10; // 2^-32
                me.s0 = me.s1;
                me.s1 = me.s2;
                return me.s2 = t - (me.c = t | 0);
            };

            // Apply the seeding algorithm from Baagoe.
            me.c = 1;
            me.s0 = mash(' ');
            me.s1 = mash(' ');
            me.s2 = mash(' ');
            me.s0 -= mash(seed);
            if (me.s0 < 0) { me.s0 += 1; }
            me.s1 -= mash(seed);
            if (me.s1 < 0) { me.s1 += 1; }
            me.s2 -= mash(seed);
            if (me.s2 < 0) { me.s2 += 1; }
            mash = null;
        }

        function copy(f, t) {
            t.c = f.c;
            t.s0 = f.s0;
            t.s1 = f.s1;
            t.s2 = f.s2;
            return t;
        }

        function impl(seed, opts) {
            var xg = new Alea(seed),
                state = opts && opts.state,
                prng = xg.next;
            prng.int32 = function() { return (xg.next() * 0x100000000) | 0; }
            prng.double = function() {
                return prng() + (prng() * 0x200000 | 0) * 1.1102230246251565e-16; // 2^-53
            };
            prng.quick = prng;
            if (state) {
                if (typeof(state) == 'object') copy(state, xg);
                prng.state = function() { return copy(xg, {}); }
            }
            return prng;
        }

        function Mash() {
            var n = 0xefc8249d;

            var mash = function(data) {
                data = data.toString();
                for (var i = 0; i < data.length; i++) {
                    n += data.charCodeAt(i);
                    var h = 0.02519603282416938 * n;
                    n = h >>> 0;
                    h -= n;
                    h *= n;
                    n = h >>> 0;
                    h -= n;
                    n += h * 0x100000000; // 2^32
                }
                return (n >>> 0) * 2.3283064365386963e-10; // 2^-32
            };

            return mash;
        }


        if (module && module.exports) {
            module.exports = impl;
        } else if (define && define.amd) {
            define(function() { return impl; });
        } else {
            this.alea = impl;
        }

    })(
        this,
        (typeof module) == 'object' && module,    // present in node.js
        (typeof define) == 'function' && define   // present with an AMD loader
    );



},{}],"/Users/gpriday/Downloads/trianglify-master 2/node_modules/seedrandom/lib/tychei.js":[function(require,module,exports){
// A Javascript implementaion of the "Tyche-i" prng algorithm by
// Samuel Neves and Filipe Araujo.
// See https://eden.dei.uc.pt/~sneves/pubs/2011-snfa2.pdf

    (function(global, module, define) {

        function XorGen(seed) {
            var me = this, strseed = '';

            // Set up generator function.
            me.next = function() {
                var b = me.b, c = me.c, d = me.d, a = me.a;
                b = (b << 25) ^ (b >>> 7) ^ c;
                c = (c - d) | 0;
                d = (d << 24) ^ (d >>> 8) ^ a;
                a = (a - b) | 0;
                me.b = b = (b << 20) ^ (b >>> 12) ^ c;
                me.c = c = (c - d) | 0;
                me.d = (d << 16) ^ (c >>> 16) ^ a;
                return me.a = (a - b) | 0;
            };

            /* The following is non-inverted tyche, which has better internal
             * bit diffusion, but which is about 25% slower than tyche-i in JS.
             me.next = function() {
             var a = me.a, b = me.b, c = me.c, d = me.d;
             a = (me.a + me.b | 0) >>> 0;
             d = me.d ^ a; d = d << 16 ^ d >>> 16;
             c = me.c + d | 0;
             b = me.b ^ c; b = b << 12 ^ d >>> 20;
             me.a = a = a + b | 0;
             d = d ^ a; me.d = d = d << 8 ^ d >>> 24;
             me.c = c = c + d | 0;
             b = b ^ c;
             return me.b = (b << 7 ^ b >>> 25);
             }
             */

            me.a = 0;
            me.b = 0;
            me.c = 2654435769 | 0;
            me.d = 1367130551;

            if (seed === Math.floor(seed)) {
                // Integer seed.
                me.a = (seed / 0x100000000) | 0;
                me.b = seed | 0;
            } else {
                // String seed.
                strseed += seed;
            }

            // Mix in string seed, then discard an initial batch of 64 values.
            for (var k = 0; k < strseed.length + 20; k++) {
                me.b ^= strseed.charCodeAt(k) | 0;
                me.next();
            }
        }

        function copy(f, t) {
            t.a = f.a;
            t.b = f.b;
            t.c = f.c;
            t.d = f.d;
            return t;
        };

        function impl(seed, opts) {
            var xg = new XorGen(seed),
                state = opts && opts.state,
                prng = function() { return (xg.next() >>> 0) / 0x100000000; };
            prng.double = function() {
                do {
                    var top = xg.next() >>> 11,
                        bot = (xg.next() >>> 0) / 0x100000000,
                        result = (top + bot) / (1 << 21);
                } while (result === 0);
                return result;
            };
            prng.int32 = xg.next;
            prng.quick = prng;
            if (state) {
                if (typeof(state) == 'object') copy(state, xg);
                prng.state = function() { return copy(xg, {}); }
            }
            return prng;
        }

        if (module && module.exports) {
            module.exports = impl;
        } else if (define && define.amd) {
            define(function() { return impl; });
        } else {
            this.tychei = impl;
        }

    })(
        this,
        (typeof module) == 'object' && module,    // present in node.js
        (typeof define) == 'function' && define   // present with an AMD loader
    );



},{}],"/Users/gpriday/Downloads/trianglify-master 2/node_modules/seedrandom/lib/xor128.js":[function(require,module,exports){
// A Javascript implementaion of the "xor128" prng algorithm by
// George Marsaglia.  See http://www.jstatsoft.org/v08/i14/paper

    (function(global, module, define) {

        function XorGen(seed) {
            var me = this, strseed = '';

            me.x = 0;
            me.y = 0;
            me.z = 0;
            me.w = 0;

            // Set up generator function.
            me.next = function() {
                var t = me.x ^ (me.x << 11);
                me.x = me.y;
                me.y = me.z;
                me.z = me.w;
                return me.w ^= (me.w >>> 19) ^ t ^ (t >>> 8);
            };

            if (seed === (seed | 0)) {
                // Integer seed.
                me.x = seed;
            } else {
                // String seed.
                strseed += seed;
            }

            // Mix in string seed, then discard an initial batch of 64 values.
            for (var k = 0; k < strseed.length + 64; k++) {
                me.x ^= strseed.charCodeAt(k) | 0;
                me.next();
            }
        }

        function copy(f, t) {
            t.x = f.x;
            t.y = f.y;
            t.z = f.z;
            t.w = f.w;
            return t;
        }

        function impl(seed, opts) {
            var xg = new XorGen(seed),
                state = opts && opts.state,
                prng = function() { return (xg.next() >>> 0) / 0x100000000; };
            prng.double = function() {
                do {
                    var top = xg.next() >>> 11,
                        bot = (xg.next() >>> 0) / 0x100000000,
                        result = (top + bot) / (1 << 21);
                } while (result === 0);
                return result;
            };
            prng.int32 = xg.next;
            prng.quick = prng;
            if (state) {
                if (typeof(state) == 'object') copy(state, xg);
                prng.state = function() { return copy(xg, {}); }
            }
            return prng;
        }

        if (module && module.exports) {
            module.exports = impl;
        } else if (define && define.amd) {
            define(function() { return impl; });
        } else {
            this.xor128 = impl;
        }

    })(
        this,
        (typeof module) == 'object' && module,    // present in node.js
        (typeof define) == 'function' && define   // present with an AMD loader
    );



},{}],"/Users/gpriday/Downloads/trianglify-master 2/node_modules/seedrandom/lib/xor4096.js":[function(require,module,exports){
// A Javascript implementaion of Richard Brent's Xorgens xor4096 algorithm.
//
// This fast non-cryptographic random number generator is designed for
// use in Monte-Carlo algorithms. It combines a long-period xorshift
// generator with a Weyl generator, and it passes all common batteries
// of stasticial tests for randomness while consuming only a few nanoseconds
// for each prng generated.  For background on the generator, see Brent's
// paper: "Some long-period random number generators using shifts and xors."
// http://arxiv.org/pdf/1104.3115.pdf
//
// Usage:
//
// var xor4096 = require('xor4096');
// random = xor4096(1);                        // Seed with int32 or string.
// assert.equal(random(), 0.1520436450538547); // (0, 1) range, 53 bits.
// assert.equal(random.int32(), 1806534897);   // signed int32, 32 bits.
//
// For nonzero numeric keys, this impelementation provides a sequence
// identical to that by Brent's xorgens 3 implementaion in C.  This
// implementation also provides for initalizing the generator with
// string seeds, or for saving and restoring the state of the generator.
//
// On Chrome, this prng benchmarks about 2.1 times slower than
// Javascript's built-in Math.random().

    (function(global, module, define) {

        function XorGen(seed) {
            var me = this;

            // Set up generator function.
            me.next = function() {
                var w = me.w,
                    X = me.X, i = me.i, t, v;
                // Update Weyl generator.
                me.w = w = (w + 0x61c88647) | 0;
                // Update xor generator.
                v = X[(i + 34) & 127];
                t = X[i = ((i + 1) & 127)];
                v ^= v << 13;
                t ^= t << 17;
                v ^= v >>> 15;
                t ^= t >>> 12;
                // Update Xor generator array state.
                v = X[i] = v ^ t;
                me.i = i;
                // Result is the combination.
                return (v + (w ^ (w >>> 16))) | 0;
            };

            function init(me, seed) {
                var t, v, i, j, w, X = [], limit = 128;
                if (seed === (seed | 0)) {
                    // Numeric seeds initialize v, which is used to generates X.
                    v = seed;
                    seed = null;
                } else {
                    // String seeds are mixed into v and X one character at a time.
                    seed = seed + '\0';
                    v = 0;
                    limit = Math.max(limit, seed.length);
                }
                // Initialize circular array and weyl value.
                for (i = 0, j = -32; j < limit; ++j) {
                    // Put the unicode characters into the array, and shuffle them.
                    if (seed) v ^= seed.charCodeAt((j + 32) % seed.length);
                    // After 32 shuffles, take v as the starting w value.
                    if (j === 0) w = v;
                    v ^= v << 10;
                    v ^= v >>> 15;
                    v ^= v << 4;
                    v ^= v >>> 13;
                    if (j >= 0) {
                        w = (w + 0x61c88647) | 0;     // Weyl.
                        t = (X[j & 127] ^= (v + w));  // Combine xor and weyl to init array.
                        i = (0 == t) ? i + 1 : 0;     // Count zeroes.
                    }
                }
                // We have detected all zeroes; make the key nonzero.
                if (i >= 128) {
                    X[(seed && seed.length || 0) & 127] = -1;
                }
                // Run the generator 512 times to further mix the state before using it.
                // Factoring this as a function slows the main generator, so it is just
                // unrolled here.  The weyl generator is not advanced while warming up.
                i = 127;
                for (j = 4 * 128; j > 0; --j) {
                    v = X[(i + 34) & 127];
                    t = X[i = ((i + 1) & 127)];
                    v ^= v << 13;
                    t ^= t << 17;
                    v ^= v >>> 15;
                    t ^= t >>> 12;
                    X[i] = v ^ t;
                }
                // Storing state as object members is faster than using closure variables.
                me.w = w;
                me.X = X;
                me.i = i;
            }

            init(me, seed);
        }

        function copy(f, t) {
            t.i = f.i;
            t.w = f.w;
            t.X = f.X.slice();
            return t;
        };

        function impl(seed, opts) {
            if (seed == null) seed = +(new Date);
            var xg = new XorGen(seed),
                state = opts && opts.state,
                prng = function() { return (xg.next() >>> 0) / 0x100000000; };
            prng.double = function() {
                do {
                    var top = xg.next() >>> 11,
                        bot = (xg.next() >>> 0) / 0x100000000,
                        result = (top + bot) / (1 << 21);
                } while (result === 0);
                return result;
            };
            prng.int32 = xg.next;
            prng.quick = prng;
            if (state) {
                if (state.X) copy(state, xg);
                prng.state = function() { return copy(xg, {}); }
            }
            return prng;
        }

        if (module && module.exports) {
            module.exports = impl;
        } else if (define && define.amd) {
            define(function() { return impl; });
        } else {
            this.xor4096 = impl;
        }

    })(
        this,                                     // window object or global
        (typeof module) == 'object' && module,    // present in node.js
        (typeof define) == 'function' && define   // present with an AMD loader
    );

},{}],"/Users/gpriday/Downloads/trianglify-master 2/node_modules/seedrandom/lib/xorshift7.js":[function(require,module,exports){
// A Javascript implementaion of the "xorshift7" algorithm by
// François Panneton and Pierre L'ecuyer:
// "On the Xorgshift Random Number Generators"
// http://saluc.engr.uconn.edu/refs/crypto/rng/panneton05onthexorshift.pdf

    (function(global, module, define) {

        function XorGen(seed) {
            var me = this;

            // Set up generator function.
            me.next = function() {
                // Update xor generator.
                var X = me.x, i = me.i, t, v, w;
                t = X[i]; t ^= (t >>> 7); v = t ^ (t << 24);
                t = X[(i + 1) & 7]; v ^= t ^ (t >>> 10);
                t = X[(i + 3) & 7]; v ^= t ^ (t >>> 3);
                t = X[(i + 4) & 7]; v ^= t ^ (t << 7);
                t = X[(i + 7) & 7]; t = t ^ (t << 13); v ^= t ^ (t << 9);
                X[i] = v;
                me.i = (i + 1) & 7;
                return v;
            };

            function init(me, seed) {
                var j, w, X = [];

                if (seed === (seed | 0)) {
                    // Seed state array using a 32-bit integer.
                    w = X[0] = seed;
                } else {
                    // Seed state using a string.
                    seed = '' + seed;
                    for (j = 0; j < seed.length; ++j) {
                        X[j & 7] = (X[j & 7] << 15) ^
                            (seed.charCodeAt(j) + X[(j + 1) & 7] << 13);
                    }
                }
                // Enforce an array length of 8, not all zeroes.
                while (X.length < 8) X.push(0);
                for (j = 0; j < 8 && X[j] === 0; ++j);
                if (j == 8) w = X[7] = -1; else w = X[j];

                me.x = X;
                me.i = 0;

                // Discard an initial 256 values.
                for (j = 256; j > 0; --j) {
                    me.next();
                }
            }

            init(me, seed);
        }

        function copy(f, t) {
            t.x = f.x.slice();
            t.i = f.i;
            return t;
        }

        function impl(seed, opts) {
            if (seed == null) seed = +(new Date);
            var xg = new XorGen(seed),
                state = opts && opts.state,
                prng = function() { return (xg.next() >>> 0) / 0x100000000; };
            prng.double = function() {
                do {
                    var top = xg.next() >>> 11,
                        bot = (xg.next() >>> 0) / 0x100000000,
                        result = (top + bot) / (1 << 21);
                } while (result === 0);
                return result;
            };
            prng.int32 = xg.next;
            prng.quick = prng;
            if (state) {
                if (state.x) copy(state, xg);
                prng.state = function() { return copy(xg, {}); }
            }
            return prng;
        }

        if (module && module.exports) {
            module.exports = impl;
        } else if (define && define.amd) {
            define(function() { return impl; });
        } else {
            this.xorshift7 = impl;
        }

    })(
        this,
        (typeof module) == 'object' && module,    // present in node.js
        (typeof define) == 'function' && define   // present with an AMD loader
    );


},{}],"/Users/gpriday/Downloads/trianglify-master 2/node_modules/seedrandom/lib/xorwow.js":[function(require,module,exports){
// A Javascript implementaion of the "xorwow" prng algorithm by
// George Marsaglia.  See http://www.jstatsoft.org/v08/i14/paper

    (function(global, module, define) {

        function XorGen(seed) {
            var me = this, strseed = '';

            // Set up generator function.
            me.next = function() {
                var t = (me.x ^ (me.x >>> 2));
                me.x = me.y; me.y = me.z; me.z = me.w; me.w = me.v;
                return (me.d = (me.d + 362437 | 0)) +
                    (me.v = (me.v ^ (me.v << 4)) ^ (t ^ (t << 1))) | 0;
            };

            me.x = 0;
            me.y = 0;
            me.z = 0;
            me.w = 0;
            me.v = 0;

            if (seed === (seed | 0)) {
                // Integer seed.
                me.x = seed;
            } else {
                // String seed.
                strseed += seed;
            }

            // Mix in string seed, then discard an initial batch of 64 values.
            for (var k = 0; k < strseed.length + 64; k++) {
                me.x ^= strseed.charCodeAt(k) | 0;
                if (k == strseed.length) {
                    me.d = me.x << 10 ^ me.x >>> 4;
                }
                me.next();
            }
        }

        function copy(f, t) {
            t.x = f.x;
            t.y = f.y;
            t.z = f.z;
            t.w = f.w;
            t.v = f.v;
            t.d = f.d;
            return t;
        }

        function impl(seed, opts) {
            var xg = new XorGen(seed),
                state = opts && opts.state,
                prng = function() { return (xg.next() >>> 0) / 0x100000000; };
            prng.double = function() {
                do {
                    var top = xg.next() >>> 11,
                        bot = (xg.next() >>> 0) / 0x100000000,
                        result = (top + bot) / (1 << 21);
                } while (result === 0);
                return result;
            };
            prng.int32 = xg.next;
            prng.quick = prng;
            if (state) {
                if (typeof(state) == 'object') copy(state, xg);
                prng.state = function() { return copy(xg, {}); }
            }
            return prng;
        }

        if (module && module.exports) {
            module.exports = impl;
        } else if (define && define.amd) {
            define(function() { return impl; });
        } else {
            this.xorwow = impl;
        }

    })(
        this,
        (typeof module) == 'object' && module,    // present in node.js
        (typeof define) == 'function' && define   // present with an AMD loader
    );



},{}],"/Users/gpriday/Downloads/trianglify-master 2/node_modules/seedrandom/seedrandom.js":[function(require,module,exports){
    /*
     Copyright 2014 David Bau.

     Permission is hereby granted, free of charge, to any person obtaining
     a copy of this software and associated documentation files (the
     "Software"), to deal in the Software without restriction, including
     without limitation the rights to use, copy, modify, merge, publish,
     distribute, sublicense, and/or sell copies of the Software, and to
     permit persons to whom the Software is furnished to do so, subject to
     the following conditions:

     The above copyright notice and this permission notice shall be
     included in all copies or substantial portions of the Software.

     THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
     EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
     MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
     IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY
     CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT,
     TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
     SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

     */

    (function (pool, math) {
//
// The following constants are related to IEEE 754 limits.
//
        var global = this,
            width = 256,        // each RC4 output is 0 <= x < 256
            chunks = 6,         // at least six RC4 outputs for each double
            digits = 52,        // there are 52 significant digits in a double
            rngname = 'random', // rngname: name for Math.random and Math.seedrandom
            startdenom = math.pow(width, chunks),
            significance = math.pow(2, digits),
            overflow = significance * 2,
            mask = width - 1,
            nodecrypto;         // node.js crypto module, initialized at the bottom.

//
// seedrandom()
// This is the seedrandom function described above.
//
        function seedrandom(seed, options, callback) {
            var key = [];
            options = (options == true) ? { entropy: true } : (options || {});

            // Flatten the seed string or build one from local entropy if needed.
            var shortseed = mixkey(flatten(
                options.entropy ? [seed, tostring(pool)] :
                    (seed == null) ? autoseed() : seed, 3), key);

            // Use the seed to initialize an ARC4 generator.
            var arc4 = new ARC4(key);

            // This function returns a random double in [0, 1) that contains
            // randomness in every bit of the mantissa of the IEEE 754 value.
            var prng = function() {
                var n = arc4.g(chunks),             // Start with a numerator n < 2 ^ 48
                    d = startdenom,                 //   and denominator d = 2 ^ 48.
                    x = 0;                          //   and no 'extra last byte'.
                while (n < significance) {          // Fill up all significant digits by
                    n = (n + x) * width;              //   shifting numerator and
                    d *= width;                       //   denominator and generating a
                    x = arc4.g(1);                    //   new least-significant-byte.
                }
                while (n >= overflow) {             // To avoid rounding up, before adding
                    n /= 2;                           //   last byte, shift everything
                    d /= 2;                           //   right using integer math until
                    x >>>= 1;                         //   we have exactly the desired bits.
                }
                return (n + x) / d;                 // Form the number within [0, 1).
            };

            prng.int32 = function() { return arc4.g(4) | 0; }
            prng.quick = function() { return arc4.g(4) / 0x100000000; }
            prng.double = prng;

            // Mix the randomness into accumulated entropy.
            mixkey(tostring(arc4.S), pool);

            // Calling convention: what to return as a function of prng, seed, is_math.
            return (options.pass || callback ||
            function(prng, seed, is_math_call, state) {
                if (state) {
                    // Load the arc4 state from the given state if it has an S array.
                    if (state.S) { copy(state, arc4); }
                    // Only provide the .state method if requested via options.state.
                    prng.state = function() { return copy(arc4, {}); }
                }

                // If called as a method of Math (Math.seedrandom()), mutate
                // Math.random because that is how seedrandom.js has worked since v1.0.
                if (is_math_call) { math[rngname] = prng; return seed; }

                // Otherwise, it is a newer calling convention, so return the
                // prng directly.
                else return prng;
            })(
                prng,
                shortseed,
                'global' in options ? options.global : (this == math),
                options.state);
        }
        math['seed' + rngname] = seedrandom;

//
// ARC4
//
// An ARC4 implementation.  The constructor takes a key in the form of
// an array of at most (width) integers that should be 0 <= x < (width).
//
// The g(count) method returns a pseudorandom integer that concatenates
// the next (count) outputs from ARC4.  Its return value is a number x
// that is in the range 0 <= x < (width ^ count).
//
        function ARC4(key) {
            var t, keylen = key.length,
                me = this, i = 0, j = me.i = me.j = 0, s = me.S = [];

            // The empty key [] is treated as [0].
            if (!keylen) { key = [keylen++]; }

            // Set up S using the standard key scheduling algorithm.
            while (i < width) {
                s[i] = i++;
            }
            for (i = 0; i < width; i++) {
                s[i] = s[j = mask & (j + key[i % keylen] + (t = s[i]))];
                s[j] = t;
            }

            // The "g" method returns the next (count) outputs as one number.
            (me.g = function(count) {
                // Using instance members instead of closure state nearly doubles speed.
                var t, r = 0,
                    i = me.i, j = me.j, s = me.S;
                while (count--) {
                    t = s[i = mask & (i + 1)];
                    r = r * width + s[mask & ((s[i] = s[j = mask & (j + t)]) + (s[j] = t))];
                }
                me.i = i; me.j = j;
                return r;
                // For robust unpredictability, the function call below automatically
                // discards an initial batch of values.  This is called RC4-drop[256].
                // See http://google.com/search?q=rsa+fluhrer+response&btnI
            })(width);
        }

//
// copy()
// Copies internal state of ARC4 to or from a plain object.
//
        function copy(f, t) {
            t.i = f.i;
            t.j = f.j;
            t.S = f.S.slice();
            return t;
        };

//
// flatten()
// Converts an object tree to nested arrays of strings.
//
        function flatten(obj, depth) {
            var result = [], typ = (typeof obj), prop;
            if (depth && typ == 'object') {
                for (prop in obj) {
                    try { result.push(flatten(obj[prop], depth - 1)); } catch (e) {}
                }
            }
            return (result.length ? result : typ == 'string' ? obj : obj + '\0');
        }

//
// mixkey()
// Mixes a string seed into a key that is an array of integers, and
// returns a shortened string seed that is equivalent to the result key.
//
        function mixkey(seed, key) {
            var stringseed = seed + '', smear, j = 0;
            while (j < stringseed.length) {
                key[mask & j] =
                    mask & ((smear ^= key[mask & j] * 19) + stringseed.charCodeAt(j++));
            }
            return tostring(key);
        }

//
// autoseed()
// Returns an object for autoseeding, using window.crypto and Node crypto
// module if available.
//
        function autoseed() {
            try {
                if (nodecrypto) { return tostring(nodecrypto.randomBytes(width)); }
                var out = new Uint8Array(width);
                (global.crypto || global.msCrypto).getRandomValues(out);
                return tostring(out);
            } catch (e) {
                var browser = global.navigator,
                    plugins = browser && browser.plugins;
                return [+new Date, global, plugins, global.screen, tostring(pool)];
            }
        }

//
// tostring()
// Converts an array of charcodes to a string
//
        function tostring(a) {
            return String.fromCharCode.apply(0, a);
        }

//
// When seedrandom.js is loaded, we immediately mix a few bits
// from the built-in RNG into the entropy pool.  Because we do
// not want to interfere with deterministic PRNG state later,
// seedrandom will not call math.random on its own again after
// initialization.
//
        mixkey(math.random(), pool);

//
// Nodejs and AMD support: export the implementation as a module using
// either convention.
//
        if ((typeof module) == 'object' && module.exports) {
            module.exports = seedrandom;
            // When in node.js, try using crypto package for autoseeding.
            try {
                nodecrypto = require('crypto');
            } catch (ex) {}
        } else if ((typeof define) == 'function' && define.amd) {
            define(function() { return seedrandom; });
        }

// End anonymous scope, and pass initial values.
    })(
        [],     // pool: entropy pool starts empty
        Math    // math: package containing random, pow, and seedrandom
    );

},{"crypto":false}]},{},["./lib/trianglify.js"])("./lib/trianglify.js")
});