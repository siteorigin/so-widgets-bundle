var soWidgetPostSelector = ( function ($, _) {
    var
        Post,
        PostCollection,
        PostCollectionSummaryView,
        PostCollectionView,
        PostSelectView,
        Query,
        QueryForm,
        QueryBuilder;

    // Model for a single post
    Post = self.Post = Backbone.Model.extend( {
        title : null,
        thumbnail : null,
        id : null
    } );

    // A collection of posts
    PostCollection = self.PostCollection = Backbone.Collection.extend( {
        model: Post,
        foundPosts: null,

        updateWithQuery: function(query){
            // Ignore empty queries
            if(query === '') {
                return;
            }

            // Reset the post collection by fetching the results from the server
            var c = this;
            $.post(
                sowPostsSelectorTpl.ajaxurl,
                { action: 'sow_get_posts', query: query, 'ignore_pagination' : true },
                function(data){
                    c.foundPosts = data.found_posts;
                    c.reset(data.posts);
                }
            );
        }
    } );

    // This represents a query that will be passed to the widget
    Query = self.Query = Backbone.Model.extend( {

        // The original query
        query: null,

        // The field we'll save and load from
        syncField: null,

        // get_posts fields
        post_type: null,
        terms: null,
        post_status: null,
        posts_per_page: null,
        post__in: null,
        tax_query: null,
        date_range: null,

        // The order fields for get_posts.
        orderby: null,
        order: null,
        sticky: null,

        defaults: {
            'post_type' : [ 'post' ],
            'orderby' : 'post_date',
            'order' : 'DESC',
            'posts_per_page' : '',
            'post_status' : 'publish',
            'sticky' : ''
        },

        initialize: function(params, options) {
            this.set( this.parseQuery(params.query) );
        },

        // Get the post query model as a WordPress get_posts query
        getQuery: function(){
            var query = [];
            if( typeof this.get('post_type') !== 'undefined' ) query.push('post_type=' + this.get('post_type').join(','));
            if( typeof this.get('post__in') !== 'undefined' && !_.isEmpty( this.get('post__in') ) ) query.push( 'post__in=' + this.get('post__in').join(',') );
            if( typeof this.get('tax_query') !== 'undefined' && !_.isEmpty( this.get('tax_query') ) ) query.push( 'tax_query=' + this.get('tax_query').join(',') );
            if( typeof this.get('date_query') !== 'undefined' && !_.isEmpty( this.get('date_query') ) ) query.push( 'date_query=' + JSON.stringify(this.get('date_query')) );

            if( typeof this.get('orderby') !== 'undefined' ) query.push( 'orderby=' + this.get('orderby') );
            if( typeof this.get('order') !== 'undefined' ) query.push( 'order=' + this.get('order') );
            if( typeof this.get('posts_per_page') !== 'undefined' ) query.push( 'posts_per_page=' + this.get('posts_per_page') );
            if( typeof this.get('sticky') !== 'undefined' ) query.push( 'sticky=' + this.get('sticky') );
            if( typeof this.get('additional') !== 'undefined' ) query.push( 'additional=' + this.get('additional') );

            return query.join('&');
        },

        // Set the current query
        setQuery: function(query){
            this.set( this.parseQuery(query) );
            return this;
        },

        // Load a get_posts query string into this object.
        parseQuery: function( query ){
            var
                re = /([^&=]+)=?([^&]*)/g ,
                decodeRE = /\+/g ,
                decode = function (str) {return decodeURIComponent( str.replace(decodeRE, " ") );} ,
                params = {} ,
                e;

            while ( e = re.exec(query) ) {
                var k = decode( e[1] ), v = decode( e[2] );
                if (k.substring(k.length - 2) === '[]') {
                    k = k.substring(0, k.length - 2);
                    (params[k] || (params[k] = [])).push(v);
                }
                else params[k] = v;
            }

            // This is a simple array that we use to store parts of the query.
            var theQuery = {};

            if( params.hasOwnProperty('post_type') ) theQuery.post_type = params.post_type.split(',');
            if( params.hasOwnProperty('post__in') ) theQuery.post__in = params.post__in.split(',');
            if( params.hasOwnProperty('tax_query') ) theQuery.tax_query = params.tax_query.split(',');
            if( params.hasOwnProperty('date_query') ) theQuery.date_query = JSON.parse(params.date_query);

            if( params.hasOwnProperty('orderby') ) theQuery.orderby = params.orderby;
            if( params.hasOwnProperty('order') ) theQuery.order = params.order;
            if( params.hasOwnProperty('posts_per_page') ) theQuery.posts_per_page = params.posts_per_page;
            if( params.hasOwnProperty('sticky') ) theQuery.sticky = params.sticky;
            if( params.hasOwnProperty('additional') ) theQuery.additional = params.additional;

            theQuery.query = query;
            return theQuery;
        },


        sync: function( method, model ){

            if(method === 'create') {
                var curVal = this.syncField.val();
                var newVal = this.getQuery();
                if(curVal !== newVal) {
                    this.syncField.val(newVal);
                    this.syncField.trigger('change');
                }
            }
            else {
                this.setQuery( this.syncField.val() );
            }

        },

        // This is the field we'll sync the query to when it changes
        setSyncField: function( field ){
            this.syncField = field;
        }
    });

    // The main builder view. This handles all sub views
    QueryBuilder = self.QueryBuilder = Backbone.View.extend( {

        attached: false,
        rendered: false,

        views: {},
        activeView: null,

        events: {
            'click .media-modal-backdrop, .media-modal-close': 'escapeHandler',
            'click .media-toolbar-primary .button': 'buttonHandler'
        },

        // Initialize the builder
        initialize: function() {
            // Listen for changes to the model
            this.listenTo(this.model, "change", this.queryModelChange);

            // Create the current posts summary view
            var postCollection = new PostCollection();
            this.views.postSummary = new PostCollectionSummaryView( {
                posts: postCollection,
                el: this.el
            } );
            this.views.postSummary.builder = this;
            this.views.postSummary.posts.updateWithQuery( this.model.getQuery() );

            // Create the sub views
            this.addSubView( 'form' , new QueryForm({ el: this.el, model: this.model }) );
            this.addSubView( 'postsView' , new PostCollectionView({ el: this.el, posts: postCollection }) );
            this.addSubView( 'postsSelect' , new PostSelectView({ el: this.el, model: this.model }) );

            // When the button is pressed in the form subview, close this
            this.views.form.bind('buttonHandler', this.close, this);
        },

        // Change the model we're using
        changeModel: function(model){
            this.model = model;
            this.render();
        },

        // Render the builder and the currently active sub view
        render: function() {
            // Create the modal
            this.$el.html( sowPostsSelectorTpl.modal );

            // Add the button from the sub view
            this.$el.find('.media-toolbar-primary .button').html( this.views[this.activeView].buttonText );
            this.$el.find('.media-frame-title h1').html( this.views[this.activeView].modalTitle );

            this.rendered = true;

            // Render the supporting views
            if(this.activeView !== 'postsSelect') {
                this.views.postSummary.render();
            }

            // Render the active view
            this.views[this.activeView].render();

            return this;
        },

        // Close and save the builder
        close: function(){
            this.$el.hide();
            this.trigger('close');
            this.model.save();
            return this;
        },

        // Open the builder
        open: function(){
            this.show();
            this.setActiveView('form');
            this.trigger('open');
            this.model.fetch();
        },

        // Save the model
        save: function(){
            this.close();
            this.model.save();
            this.trigger('save');
        },

        // Attach this builder to the body
        attach: function() {
            if ( ! this.rendered ) {
                this.render();
            }

            if( !this.attached ) {
                this.$el.appendTo('body');
                this.attached = true;
            }

            return this;
        },

        // Show the builder
        show: function(){
            this.attach();
            if( !this.$el.is(':visible') ) {
                this.$el.show();
            }
        },

        // Escape this
        escapeHandler: function(event){
            event.preventDefault();
            this.close();
        },

        // Handles the main button click on the frame
        buttonHandler: function(event){
            event.preventDefault();

            // Let the current view handle this button push...
            this.views[this.activeView].buttonHandler().trigger('buttonHandler');
        },

        // Add a subview to this builder
        addSubView: function( name, view ) {
            this.views[name] = view;
            view.builder = this;

            if(this.activeView === null) {
                this.activeView = name;
            }
        },

        // Set the active view
        setActiveView: function(name) {
            this.activeView = name;
            this.render();
            return;
        },

        queryModelChange: function(){
            this.views['postSummary'].posts.updateWithQuery( this.model.getQuery() );
        }

    } );

    // Handles the form for creating the query
    QueryForm = self.QueryForm = Backbone.View.extend( {
        buttonText: 'Save Query',
        modalTitle: 'Build Posts Query',
        form: null,

        initialize: function(params) {
        },

        render: function(){
            var thisView = this;

            // Add the fields
            this.form = $('<div class="query-builder-form>"></div>');

            // The post type field
            this.form.append('<div class="query-builder-form-field">' + sowPostsSelectorTpl.fields.post_type + '</div>');
            if( typeof this.model.get('post_type') !== 'undefined' ) this.form.find('select[name="post_type"]').val( this.model.get('post_type') );

            // The post__in field
            this.form.append('<div class="query-builder-form-field">' + sowPostsSelectorTpl.fields.post__in + '</div>');
            if( typeof this.model.get('post__in') !== 'undefined' ) this.form.find('input[name="post__in"]').val( this.model.get('post__in').join(',') );

            // The taxonomy field
            this.form.append('<div class="query-builder-form-field ui-front">' + sowPostsSelectorTpl.fields.tax_query + '</div>');
            if( typeof this.model.get('tax_query') !== 'undefined' ) this.form.find('input[name="tax_query"]').val( this.model.get('tax_query'));

            // The date range fields
            this.form.append('<div class="query-builder-form-field">' + sowPostsSelectorTpl.fields.date_query + '</div>');
            if( typeof this.model.get('date_query') !== 'undefined' ) {
				var dateQuery = this.model.get('date_query');
				if( dateQuery.hasOwnProperty('after')) this.form.find('input[name="after"]').val(dateQuery.after);
				if( dateQuery.hasOwnProperty('before')) this.form.find('input[name="before"]').val(dateQuery.before);
			}

            // The order field
            this.form.append($('<div class="query-builder-form-field">' + sowPostsSelectorTpl.fields.orderby + '</div>'));
            if( typeof this.model.get('orderby') !== 'undefined' ) this.form.find('select[name="orderby"]').val(this.model.get('orderby'));
            if( typeof this.model.get('order') !== 'undefined' ) this.form.find('input[name="order"]').val(this.model.get('order'));

            // The posts per page field
            this.form.append('<div class="query-builder-form-field">' + sowPostsSelectorTpl.fields.posts_per_page + '</div>');
            if( typeof this.model.get('posts_per_page') !== 'undefined' ) this.form.find('input[name="posts_per_page"]').val( this.model.get('posts_per_page'));

            // The sticky posts field
            this.form.append('<div class="query-builder-form-field">' + sowPostsSelectorTpl.fields.sticky + '</div>');
            if( typeof this.model.get('sticky') !== 'undefined' ) this.form.find('select[name="sticky"]').val( this.model.get('sticky'));

            // The additional query arguments field
            this.form.append('<div class="query-builder-form-field">' + sowPostsSelectorTpl.fields.additional + '</div>');
            if( typeof this.model.get('additional') !== 'undefined' ) this.form.find('input[name="additional"]').val( decodeURIComponent( this.model.get('additional') ) );


            var orderField =  this.form.find('input[name="order"]');
            var orderButton = orderField.closest('.query-builder-form-field').find('.sow-order-button');

            // Reset the ordering button
            var resetOrderButton = function () {
                if (orderField.val() === 'DESC') {
                    orderButton.removeClass('sow-order-button-asc');
                    orderButton.addClass('sow-order-button-desc');
                }
                else {
                    orderButton.addClass('sow-order-button-asc');
                    orderButton.removeClass('sow-order-button-desc');
                }
            };
            resetOrderButton();

            orderButton.click(function(e){
                e.preventDefault();
                if(orderField.val() === 'DESC') {
                    orderField.val('ASC');
                }
                else {
                    orderField.val('DESC');
                }
                resetOrderButton();
                thisView.updateModel();
                return false;
            });

            // Add the form to the builder
            this.$el.find('.query-builder-content').empty().append(this.form);

            // Update the model when anything changes
            this.$el.find('.query-builder-form-field select, .query-builder-form-field input').change(function(){
                thisView.updateModel();
            });

            // When we click the select posts button, change to the posts select view
            this.$el.find('.query-builder-form-field .sow-select-posts').click(function(e){
                e.preventDefault();
                thisView.builder.setActiveView('postsSelect');
            });

            // Set up the autocomplete on the taxonomy query
            this.form.find('input[name="tax_query"]').autocomplete({
                source: function (request, response) {
                    $.getJSON(
                        sowPostsSelectorTpl.ajaxurl,
                        {
                            term: request.term.split(/,\s*/).pop(),
                            action: 'sow_search_terms'
                        },
                        response
                    );
                },
                search: function () {
                    // custom minLength
                    var term = this.value.split(/,\s*/).pop();
                    if (term.length < 1) {
                        return false;
                    }
                },
                focus: function () {
                    // prevent value inserted on focus
                    return false;
                },
                select: function (event, ui) {
                    var terms = this.value.split(/,\s*/);
                    // remove the current input
                    terms.pop();
                    // add the selected item
                    terms.push(ui.item.value);
                    // add placeholder to get the comma-and-space at the end
                    terms.push("");
                    this.value = terms.join(", ");

                    // Update the model after we've addded a new term
                    thisView.updateModel();
                    return false;
                }
            });

            return this;
        },

        // Update the model with what's in the form
        updateModel: function(){
            this.model.set( 'post_type', this.$el.find('*[name="post_type"]').val() );

            // Add the posts in part to the model
            if(this.$el.find('*[name="post__in"]').val().trim() !== '') {
                this.model.set( 'post__in', this.$el.find('*[name="post__in"]').val().split(',').map(function(a){ return Number( a.trim() ); }) );
            }
            else {
                this.model.set( 'post__in', []);
            }

            // Build the taxonomy query
            if(this.$el.find('*[name="tax_query"]').val().trim() !== '') {
                var tax_query = this.$el.find('*[name="tax_query"]').val().split(',').map(function(a){ return a.trim(); });
                this.model.set( 'tax_query', _.compact(tax_query) );
            }
            else {
                this.model.set( 'tax_query', []);
            }
			this.model.set( 'date_query', {after: this.$el.find('*[name="after"]').val(), before: this.$el.find('*[name="before"]').val()});
            this.model.set( 'orderby', this.$el.find('*[name="orderby"]').val() );
            this.model.set( 'order', this.$el.find('*[name="order"]').val() );
            this.model.set( 'posts_per_page', this.$el.find('*[name="posts_per_page"]').val() );
            this.model.set( 'sticky', this.$el.find('*[name="sticky"]').val() );
            this.model.set( 'additional', encodeURIComponent( this.$el.find('*[name="additional"]').val() ) );

            this.model.set( 'query', this.model.getQuery() );

            return this;
        },

        // The button handler is triggered by QueryBuilder
        buttonHandler: function(){
            this.updateModel();
            return this;
        }
    } );

    // Displays a small count of the current number of queries
    PostCollectionSummaryView = self.PostCollectionSummaryView = Backbone.View.extend( {
        template: _.template(sowPostsSelectorTpl.foundPosts),
        posts: null,

        initialize: function(args) {
            // When ever the posts changes, we'll render the summary view
            this.posts = args.posts;
            this.posts.bind('reset', this.render, this);
        },

        render: function(){
            this.$el.find('.media-toolbar-secondary').html(this.template( {foundPosts : this.posts.foundPosts} ));

            var v = this;
            this.$el.find('.media-toolbar-secondary .preview-query-posts').click(function(e){
                e.preventDefault();
                v.builder.setActiveView('postsView');
            });
        }
    } );

    // Displays all the posts in the current collection
    PostCollectionView = self.PostCollectionView = Backbone.View.extend( {
        buttonText: 'Back',
        modalTitle: 'Current Posts',
        template: _.template(sowPostsSelectorTpl.postSummary),

        posts: null,

        initialize: function(args){
            this.posts = args.posts;
        },

        render: function(){
            var $c = this.$el.find('.query-builder-content').empty().append('<div class="sow-current-posts"></div>').find('.sow-current-posts');

            // Render all the posts
            $c = this.$el.find('.query-builder-content');
            var template = this.template;
            this.posts.each(function(post){
                $c.append(template(post.attributes));
            });

            return this;
        },

        // The button handler is triggered by QueryBuilder
        buttonHandler: function(){
            this.builder.setActiveView('form');
            return this;
        }
    } );

    // Select posts
    PostSelectView = self.PostSelectView = Backbone.View.extend( {
        buttonText: 'Finish Selection',
        modalTitle: 'Select Posts',
        sortable: null,

        postCache: {},
        postTemplate: _.template(sowPostsSelectorTpl.postSummary),

        initialize: function(){
            this.postCache = {};
        },

        render: function(){
            var posts = this.model.get('post__in');
            var postType = this.model.get('post_type').join(',');

            this.$el.find('.query-builder-content').empty().html(sowPostsSelectorTpl.selector);

            // Set up the sortable
            this.sortable = this.$el.find('.query-builder-content #sow-post-selector .sow-posts-sortable').sortable({
                placeholder: "ui-state-highlight",
                forcePlaceholderSize: true,
                items : '> .sow-post-selector-summary'
            });

            // Add any posts
            this.addPosts( posts );

            // Set up the autocomplete
            var v = this;
            var $searchInput = this.$el.find('.query-builder-content #sow-post-selector .sow-search-field');

            $searchInput.autocomplete({
                source: function(request, response) {
                    request.type = postType;
                    request.action = 'sow_search_posts';
                    $.get(
                        sowPostsSelectorTpl.ajaxurl,
                        request,
                        response
                    );
                },
                minLength: 0,
                select: function( event, ui ) {
                    event.preventDefault();
                    $(this).val('');

                    // Grab this new post and insert it
                    v.addPosts([ui.item.value]);

                    return false;
                }
            });
            $searchInput.focusin(
                function() {
                    $searchInput.autocomplete("search", $searchInput.val());
                }
            )

            // Handle clicking on the remove buttons
            this.$el.find('.query-builder-content').on('click', '.sow-remove', function(e){
                e.preventDefault();
                var $$ = $(this);
                $$.closest('.sow-post-selector-summary').fadeOut('fast', function(){
                    $(this).remove();
                    v.sortable.sortable('refresh');
                });
            });

            return this;
        },

        addPosts: function(posts) {
            if(typeof posts === 'undefined' || _.isEmpty(posts)) {
                return;
            }

            var getPosts = [];
            for(var i = 0; i < posts.length; i++) {
                if(typeof this.postCache[ posts[i] ] === 'undefined') {
                    getPosts.push(posts[i]);
                }
            }

            // Fetch any posts that we haven't already
            var v = this;
            if(!_.isEmpty(getPosts)) {
                $.post(
                    sowPostsSelectorTpl.ajaxurl,
                    {
                        action: 'sow_get_posts',
                        query : 'post_type=_all&posts_per_page=-1&post__in=' + getPosts.join(',')
                    },
                    function(data){
                        if(typeof data.posts !== 'undefined') {
                            _.each(data.posts, function(post, i){
                                v.postCache[post.id] = {
                                    id : post.id,
                                    title : post.title,
                                    thumbnail : post.thumbnail,
                                    editUrl: post.editUrl
                                };
                            });
                        }

                        v.refreshLoading();
                    }
                );
            }

            // Add placeholder posts
            var postItem;
            for(var i = 0; i < posts.length; i++) {
                if( typeof this.postCache[posts[i]] === 'undefined' ) {
                    // Create a temporary post
                    postItem = $(this.postTemplate( {id:posts[i], title: '', thumbnail: '', editUrl: '#'} )).addClass('sow-post-loading');
                }
                else {
                    postItem = $(this.postTemplate( this.postCache[posts[i]] ));
                }

                postItem.appendTo(this.sortable);
            }
            this.sortable.sortable('refresh');


            return this;
        },

        refreshLoading: function(){
            var v = this;
            this.sortable.find('.sow-post-selector-summary.sow-post-loading').each(function(){
                var $$ = $(this);
                var id = $$.data('id');

                if(typeof v.postCache[id] !== 'undefined') {
                    $$.removeClass('sow-post-loading');
                    var postItem = $(v.postTemplate( v.postCache[ id ] ));

                    $$.html( postItem.html() );
                }

            });
        },

        // The button handler is triggered by QueryBuilder
        buttonHandler: function(){
            // Update the post__in value
            var ids = [];
            this.sortable.find('.sow-post-selector-summary').each(function(){
                ids.push(Number($(this).data('id')));
            });
            this.model.set('post__in', ids);

            if(!_.isEmpty(ids)) {
                this.model.set('post_type', ['_all']);
                this.model.set('orderby', 'post__in');
            }

            this.builder.setActiveView('form');
            return this;
        }
    } );

    jQuery( function($){
        $('body').on('click', '.sow-select-posts', function(e){
            e.preventDefault();
            var $postSelectorButton = $(this);
	
			// The main QueryBuilder instance.
			var builder = new QueryBuilder( { model: new Query( { query: '' } ) } );
			builder.model.setSyncField( $postSelectorButton.siblings('.siteorigin-widget-input') );
			builder.model.sync( 'update' );
			builder.views.postSummary.posts.on( 'reset', function( postsCollection ) {
				$postSelectorButton.find( '.sow-current-count' ).text( postsCollection.foundPosts );
			});
            builder.open();
        });
    } );

} )( jQuery, _ );
