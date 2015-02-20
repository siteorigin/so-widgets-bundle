jQuery(function($) {
    (function ($, _) {

        var
            Testimonial,
            TestimonialCollection,
            TestimonialsView,
            TestimonialView;

        Testimonial = self.Testimonial = Backbone.Model.extend({
            name: null,
            location: null,
            image: null,
            text: null,
            url: null,
            target: null
        });

        TestimonialCollection = self.TestimonialCollection = Backbone.Collection.extend({
            model: Testimonial,
            initialize: function () {

            }
        });

        TestimonialsView = self.TestimonialView = Backbone.View.extend({
            testimonials: null,
            initialize: function (args) {
                this.testimonials = args.testimonials;
                this.render();
            },
            render: function () {
                var _this = this;
                this.testimonials.each(
                    function (testimonial) {
                        var tstV = new TestimonialView({ model: testimonial });
                        _this.$el.find('.sow-testimonial-items').append(tstV.render().el);
                    }
                );
                var $testimonialsContainer = $('.sow-testimonials-container');
                var itemsPerPage = sowTestimonialWidget.testimonialsPerPage;
                var itemWidth = $testimonialsContainer.width()/itemsPerPage;
                var options = {
                    selector: '.sow-testimonial-items > .sow-testimonial-item',
                    animation: sowTestimonialWidget.transitionStyle,
                    animationLoop: false,
                    directionNav: false,
                    slideshow: false
                };
                if(sowTestimonialWidget.transitionStyle == 'slide') {
                    _.extend(options, {
                        itemWidth: itemWidth,
                        minItems: itemsPerPage,
                        maxItems: itemsPerPage,
                        move: itemsPerPage
                    });
                } else if (sowTestimonialWidget.transitionStyle == 'thumbnails') {
                    this.testimonials.each(
                        function (testimonial) {
                            var $thmbsCnt =_this.$el.siblings('.sow-testimonials-thumbnails-nav').find('.sow-testimonials-thumbnail-items');
                            var imgSrc = testimonial.get('image');
                            if(imgSrc) {
                                $thmbsCnt.append('<li><img src="' + imgSrc + '"/></li>');
                            } else {
                                $thmbsCnt.append('<li><div class="no-image" style="width: 150px; height:150px; background: #404040"/></li>');
                            }
                            console.log($thmbsCnt.html());
                        }
                    );
                    $('.sow-testimonials-thumbnails-nav').flexslider({
                        animation: "slide",
                        controlNav: false,
                        animationLoop: false,
                        slideshow: false,
                        itemWidth: 210,
                        itemMargin: 5,
                        directionNav: false,
                        asNavFor: '.sow-testimonials-container'
                    });
                    _.extend(options, {
                        animation: 'slide',
                        controlNav: false,
                        sync: '.sow-testimonials-thumbnails-nav'
                    });
                }
                $testimonialsContainer.flexslider(options);
                return this;
            }
        });

        TestimonialView = self.TestimonialView = Backbone.View.extend({
            template: _.template(sowTestimonialWidget.testimonialTemplate),
            render: function () {
                this.setElement(this.template(this.model.attributes));
                return this;
            }
        });

        var testimonials = new TestimonialCollection(sowTestimonialWidget.testimonials);
        new TestimonialsView({
            el: $(this.document).find('.sow-testimonials-container'),
            testimonials: testimonials
        });
    })($, _)

} );