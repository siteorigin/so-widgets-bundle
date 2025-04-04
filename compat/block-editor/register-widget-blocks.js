const soRegisterWidgetBlocks = async ( widgets ) => {
	const { __ } = wp.i18n;
	const { el } = wp.element;

	if ( widgets['siteorigin-widget-accordion-widget'] ) {
		wp.blocks.registerBlockType( 'sowb/siteorigin-widget-accordion-widget', {
			title: __( 'SiteOrigin Accordion', 'so-widgets-bundle' ),
			description: __( 'Efficiently display content in expandable sections, maximizing space for improved organization.', 'so-widgets-bundle' ),
			attributes: {
				widgetClass: { type: 'string' },
				anchor: { type: 'string' },
				widgetData: { type: 'object' },
				widgetMarkup: { type: 'string' },
				widgetIcons: { type: 'array' },
			},
			edit: () => el( 'div', {}, __( 'Loading Widget', 'so-widgets-bundle' ) ),
			save: () => null
		} );
	}

	if ( widgets['siteorigin-widget-anything-carousel-widget'] ) {
		wp.blocks.registerBlockType( 'sowb/siteorigin-widget-anything-carousel-widget', {
			title: __( 'SiteOrigin Anything Carousel', 'so-widgets-bundle' ),
			description: __( 'Display images, text, or any content in a highly customizable and responsive carousel slider.', 'so-widgets-bundle' ),
			attributes: {
				widgetClass: { type: 'string' },
				anchor: { type: 'string' },
				widgetData: { type: 'object' },
				widgetMarkup: { type: 'string' },
				widgetIcons: { type: 'array' },
			},
			edit: () => el( 'div', {}, __( 'Loading Widget', 'so-widgets-bundle' ) ),
			save: () => null
		} );
	}

	if ( widgets['siteorigin-widget-author-box-widget'] ) {
		wp.blocks.registerBlockType( 'sowb/siteorigin-widget-author-box-widget', {
			title: __( 'SiteOrigin Author Box', 'so-widgets-bundle' ),
			description: __( 'Display author information, including avatar, name, bio, and post links in a customizable box.', 'so-widgets-bundle' ),
			attributes: {
				widgetClass: { type: 'string' },
				anchor: { type: 'string' },
				widgetData: { type: 'object' },
				widgetMarkup: { type: 'string' },
				widgetIcons: { type: 'array' },
			},
			edit: () => el( 'div', {}, __( 'Loading Widget', 'so-widgets-bundle' ) ),
			save: () => null
		} );
	}

	if ( widgets['siteorigin-widget-blog-widget'] ) {
		wp.blocks.registerBlockType( 'sowb/siteorigin-widget-blog-widget', {
			title: __( 'SiteOrigin Blog', 'so-widgets-bundle' ),
			description: __( 'Showcase blog content in personalized list or grid layouts with flexible design and display settings.', 'so-widgets-bundle' ),
			attributes: {
				widgetClass: { type: 'string' },
				anchor: { type: 'string' },
				widgetData: { type: 'object' },
				widgetMarkup: { type: 'string' },
				widgetIcons: { type: 'array' },
			},
			edit: () => el( 'div', {}, __( 'Loading Widget', 'so-widgets-bundle' ) ),
			save: () => null
		} );
	}

	if ( widgets['siteorigin-widget-button-widget'] ) {
		wp.blocks.registerBlockType( 'sowb/siteorigin-widget-button-widget', {
			title: __( 'SiteOrigin Button', 'so-widgets-bundle' ),
			description: __( 'Create a custom button with flexible styling, icon support, and click tracking functionality.', 'so-widgets-bundle' ),
			attributes: {
				widgetClass: { type: 'string' },
				anchor: { type: 'string' },
				widgetData: { type: 'object' },
				widgetMarkup: { type: 'string' },
				widgetIcons: { type: 'array' },
			},
			edit: () => el( 'div', {}, __( 'Loading Widget', 'so-widgets-bundle' ) ),
			save: () => null
		} );
	}

	if ( widgets['siteorigin-widget-button-grid-widget'] ) {
		wp.blocks.registerBlockType( 'sowb/siteorigin-widget-button-grid-widget', {
			title: __( 'SiteOrigin Button Grid', 'so-widgets-bundle' ),
			description: __( 'Add multiple buttons in one go, customize individually, and present them in a neat grid layout.', 'so-widgets-bundle' ),
			attributes: {
				widgetClass: { type: 'string' },
				anchor: { type: 'string' },
				widgetData: { type: 'object' },
				widgetMarkup: { type: 'string' },
				widgetIcons: { type: 'array' },
			},
			edit: () => el( 'div', {}, __( 'Loading Widget', 'so-widgets-bundle' ) ),
			save: () => null
		} );
	}

	if ( widgets['siteorigin-widget-cta-widget'] ) {
		wp.blocks.registerBlockType( 'sowb/siteorigin-widget-cta-widget', {
			title: __( 'SiteOrigin Call To Action', 'so-widgets-bundle' ),
			description: __( 'Prompt visitors to take action with a customizable title, subtitle, button, and design settings.', 'so-widgets-bundle' ),
			attributes: {
				widgetClass: { type: 'string' },
				anchor: { type: 'string' },
				widgetData: { type: 'object' },
				widgetMarkup: { type: 'string' },
				widgetIcons: { type: 'array' },
			},
			edit: () => el( 'div', {}, __( 'Loading Widget', 'so-widgets-bundle' ) ),
			save: () => null
		} );
	}

	if ( widgets['siteorigin-widgets-contactform-widget'] ) {
		wp.blocks.registerBlockType( 'sowb/siteorigin-widgets-contactform-widget', {
			title: __( 'SiteOrigin Contact Form', 'so-widgets-bundle' ),
			description: __( 'Add a contact form with custom fields, design options, spam protection, and email notifications.', 'so-widgets-bundle' ),
			attributes: {
				widgetClass: { type: 'string' },
				anchor: { type: 'string' },
				widgetData: { type: 'object' },
				widgetMarkup: { type: 'string' },
				widgetIcons: { type: 'array' },
			},
			edit: () => el( 'div', {}, __( 'Loading Widget', 'so-widgets-bundle' ) ),
			save: () => null
		} );
	}

	if ( widgets['siteorigin-widget-editor-widget'] ) {
		wp.blocks.registerBlockType( 'sowb/siteorigin-widget-editor-widget', {
			title: __( 'SiteOrigin Editor', 'so-widgets-bundle' ),
			description: __( 'Insert and customize content with a rich text editor offering extensive formatting options.', 'so-widgets-bundle' ),
			attributes: {
				widgetClass: { type: 'string' },
				anchor: { type: 'string' },
				widgetData: { type: 'object' },
				widgetMarkup: { type: 'string' },
				widgetIcons: { type: 'array' },
			},
			edit: () => el( 'div', {}, __( 'Loading Widget', 'so-widgets-bundle' ) ),
			save: () => null
		} );
	}

	if ( widgets['siteorigin-widget-features-widget'] ) {
		wp.blocks.registerBlockType( 'sowb/siteorigin-widget-features-widget', {
			title: __( 'SiteOrigin Features', 'so-widgets-bundle' ),
			description: __( 'Showcase features with icons, titles, text, and links in a customizable grid layout.', 'so-widgets-bundle' ),
			attributes: {
				widgetClass: { type: 'string' },
				anchor: { type: 'string' },
				widgetData: { type: 'object' },
				widgetMarkup: { type: 'string' },
				widgetIcons: { type: 'array' },
			},
			edit: () => el( 'div', {}, __( 'Loading Widget', 'so-widgets-bundle' ) ),
			save: () => null
		} );
	}

	if ( widgets['siteorigin-widget-googlemap-widget'] ) {
		wp.blocks.registerBlockType( 'sowb/siteorigin-widget-googlemap-widget', {
			title: __( 'SiteOrigin Google Maps', 'so-widgets-bundle' ),
			description: __( 'Embed a customizable Google Map with markers, directions, styling options, and interactive elements.', 'so-widgets-bundle' ),
			attributes: {
				widgetClass: { type: 'string' },
				anchor: { type: 'string' },
				widgetData: { type: 'object' },
				widgetMarkup: { type: 'string' },
				widgetIcons: { type: 'array' },
			},
			edit: () => el( 'div', {}, __( 'Loading Widget', 'so-widgets-bundle' ) ),
			save: () => null
		} );
	}

	if ( widgets['siteorigin-widget-headline-widget'] ) {
		wp.blocks.registerBlockType( 'sowb/siteorigin-widget-headline-widget', {
			title: __( 'SiteOrigin Headline', 'so-widgets-bundle' ),
			description: __( 'Engage visitors with a prominent, stylish headline and optional divider and sub-headline to convey key messages.', 'so-widgets-bundle' ),
			attributes: {
				widgetClass: { type: 'string' },
				anchor: { type: 'string' },
				widgetData: { type: 'object' },
				widgetMarkup: { type: 'string' },
				widgetIcons: { type: 'array' },
			},
			edit: () => el( 'div', {}, __( 'Loading Widget', 'so-widgets-bundle' ) ),
			save: () => null
		} );
	}

	if ( widgets['siteorigin-widget-hero-widget'] ) {
		wp.blocks.registerBlockType( 'sowb/siteorigin-widget-hero-widget', {
			title: __( 'SiteOrigin Hero Image', 'so-widgets-bundle' ),
			description: __( 'Build an impressive hero image section with custom content, buttons, background image, color, and video.', 'so-widgets-bundle' ),
			attributes: {
				widgetClass: { type: 'string' },
				anchor: { type: 'string' },
				widgetData: { type: 'object' },
				widgetMarkup: { type: 'string' },
				widgetIcons: { type: 'array' },
			},
			edit: () => el( 'div', {}, __( 'Loading Widget', 'so-widgets-bundle' ) ),
			save: () => null
		} );
	}

	if ( widgets['siteorigin-widget-icon-widget'] ) {
		wp.blocks.registerBlockType( 'sowb/siteorigin-widget-icon-widget', {
			title: __( 'SiteOrigin Icon', 'so-widgets-bundle' ),
			description: __( 'Display a customizable icon with color, size, alignment, and optional link settings.', 'so-widgets-bundle' ),
			attributes: {
				widgetClass: { type: 'string' },
				anchor: { type: 'string' },
				widgetData: { type: 'object' },
				widgetMarkup: { type: 'string' },
				widgetIcons: { type: 'array' },
			},
			edit: () => el( 'div', {}, __( 'Loading Widget', 'so-widgets-bundle' ) ),
			save: () => null
		} );
	}

	if ( widgets['siteorigin-widget-image-widget'] ) {
		wp.blocks.registerBlockType( 'sowb/siteorigin-widget-image-widget', {
			title: __( 'SiteOrigin Image', 'so-widgets-bundle' ),
			description: __( 'Add a responsive image with custom dimensions, positioning, caption, link, and styling options.', 'so-widgets-bundle' ),
			attributes: {
				widgetClass: { type: 'string' },
				anchor: { type: 'string' },
				widgetData: { type: 'object' },
				widgetMarkup: { type: 'string' },
				widgetIcons: { type: 'array' },
			},
			edit: () => el( 'div', {}, __( 'Loading Widget', 'so-widgets-bundle' ) ),
			save: () => null
		} );
	}

	if ( widgets['siteorigin-widgets-imagegrid-widget'] ) {
		wp.blocks.registerBlockType( 'sowb/siteorigin-widgets-imagegrid-widget', {
			title: __( 'SiteOrigin Image Grid', 'so-widgets-bundle' ),
			description: __( 'Showcase images in a responsive grid layout with custom size, spacing, alignment, and captions.', 'so-widgets-bundle' ),
			attributes: {
				widgetClass: { type: 'string' },
				anchor: { type: 'string' },
				widgetData: { type: 'object' },
				widgetMarkup: { type: 'string' },
				widgetIcons: { type: 'array' },
			},
			edit: () => el( 'div', {}, __( 'Loading Widget', 'so-widgets-bundle' ) ),
			save: () => null
		} );
	}

	if ( widgets['siteorigin-widget-layoutslider-widget'] ) {
		wp.blocks.registerBlockType( 'sowb/siteorigin-widget-layoutslider-widget', {
			title: __( 'SiteOrigin Layout Slider', 'so-widgets-bundle' ),
			description: __( 'Design responsive slider frames with unique layouts, backgrounds, and content built with Page Builder.', 'so-widgets-bundle' ),
			attributes: {
				widgetClass: { type: 'string' },
				anchor: { type: 'string' },
				widgetData: { type: 'object' },
				widgetMarkup: { type: 'string' },
				widgetIcons: { type: 'array' },
			},
			edit: () => el( 'div', {}, __( 'Loading Widget', 'so-widgets-bundle' ) ),
			save: () => null
		} );
	}

	if ( widgets['siteorigin-widget-lottie-player-widget'] ) {
		wp.blocks.registerBlockType( 'sowb/siteorigin-widget-lottie-player-widget', {
			title: __( 'SiteOrigin Lottie Player', 'so-widgets-bundle' ),
			description: __( 'Bring your content to life using interactive Lottie animations with personalized settings and links.', 'so-widgets-bundle' ),
			attributes: {
				widgetClass: { type: 'string' },
				anchor: { type: 'string' },
				widgetData: { type: 'object' },
				widgetMarkup: { type: 'string' },
				widgetIcons: { type: 'array' },
			},
			edit: () => el( 'div', {}, __( 'Loading Widget', 'so-widgets-bundle' ) ),
			save: () => null
		} );
	}

	if ( widgets['siteorigin-widget-postcarousel-widget'] ) {
		wp.blocks.registerBlockType( 'sowb/siteorigin-widget-postcarousel-widget', {
			title: __( 'SiteOrigin Post Carousel', 'so-widgets-bundle' ),
			description: __( 'Display blog posts or custom post types in a responsive, customizable carousel layout.', 'so-widgets-bundle' ),
			attributes: {
				widgetClass: { type: 'string' },
				anchor: { type: 'string' },
				widgetData: { type: 'object' },
				widgetMarkup: { type: 'string' },
				widgetIcons: { type: 'array' },
			},
			edit: () => el( 'div', {}, __( 'Loading Widget', 'so-widgets-bundle' ) ),
			save: () => null
		} );
	}

	if ( widgets['siteorigin-widget-pricetable-widget'] ) {
		wp.blocks.registerBlockType( 'sowb/siteorigin-widget-pricetable-widget', {
			title: __( 'SiteOrigin Price Table', 'so-widgets-bundle' ),
			description: __( 'Display pricing plans in a professional table format with custom columns, features, and design.', 'so-widgets-bundle' ),
			attributes: {
				widgetClass: { type: 'string' },
				anchor: { type: 'string' },
				widgetData: { type: 'object' },
				widgetMarkup: { type: 'string' },
				widgetIcons: { type: 'array' },
			},
			edit: () => el( 'div', {}, __( 'Loading Widget', 'so-widgets-bundle' ) ),
			save: () => null
		} );
	}

	if ( widgets['siteorigin-widget-recent-posts-widget'] ) {
		wp.blocks.registerBlockType( 'sowb/siteorigin-widget-recent-posts-widget', {
			title: __( 'SiteOrigin Recent Posts', 'so-widgets-bundle' ),
			description: __( 'Drive traffic to your latest content with a visually appealing, fully customizable recent posts showcase.', 'so-widgets-bundle' ),
			attributes: {
				widgetClass: { type: 'string' },
				anchor: { type: 'string' },
				widgetData: { type: 'object' },
				widgetMarkup: { type: 'string' },
				widgetIcons: { type: 'array' },
			},
			edit: () => el( 'div', {}, __( 'Loading Widget', 'so-widgets-bundle' ) ),
			save: () => null
		} );
	}

	if ( widgets['siteorigin-widget-simple-masonry-widget'] ) {
		wp.blocks.registerBlockType( 'sowb/siteorigin-widget-simple-masonry-widget', {
			title: __( 'SiteOrigin Simple Masonry Layout', 'so-widgets-bundle' ),
			description: __( 'Display images in an attractive masonry grid with adjustable columns, gutters, and optional captions.', 'so-widgets-bundle' ),
			attributes: {
				widgetClass: { type: 'string' },
				anchor: { type: 'string' },
				widgetData: { type: 'object' },
				widgetMarkup: { type: 'string' },
				widgetIcons: { type: 'array' },
			},
			edit: () => el( 'div', {}, __( 'Loading Widget', 'so-widgets-bundle' ) ),
			save: () => null
		} );
	}

	if ( widgets['siteorigin-widget-slider-widget'] ) {
		wp.blocks.registerBlockType( 'sowb/siteorigin-widget-slider-widget', {
			title: __( 'SiteOrigin Image Slider', 'so-widgets-bundle' ),
			description: __( 'Create a responsive slider with customizable image and video frames, navigation, and appearance settings.', 'so-widgets-bundle' ),
			attributes: {
				widgetClass: { type: 'string' },
				anchor: { type: 'string' },
				widgetData: { type: 'object' },
				widgetMarkup: { type: 'string' },
				widgetIcons: { type: 'array' },
			},
			edit: () => el( 'div', {}, __( 'Loading Widget', 'so-widgets-bundle' ) ),
			save: () => null
		} );
	}

	if ( widgets['siteorigin-widget-socialmediabuttons-widget'] ) {
		wp.blocks.registerBlockType( 'sowb/siteorigin-widget-socialmediabuttons-widget', {
			title: __( 'SiteOrigin Social Media Buttons', 'so-widgets-bundle' ),
			description: __( 'Add social media buttons to your site with personalized icons, colors, and design settings.', 'so-widgets-bundle' ),
			attributes: {
				widgetClass: { type: 'string' },
				anchor: { type: 'string' },
				widgetData: { type: 'object' },
				widgetMarkup: { type: 'string' },
				widgetIcons: { type: 'array' },
			},
			edit: () => el( 'div', {}, __( 'Loading Widget', 'so-widgets-bundle' ) ),
			save: () => null
		} );
	}

	if ( widgets['siteorigin-widget-tabs-widget'] ) {
		wp.blocks.registerBlockType( 'sowb/siteorigin-widget-tabs-widget', {
			title: __( 'SiteOrigin Tabs', 'so-widgets-bundle' ),
			description: __( 'Create tabbed content panels with customizable titles, content, initial tab, and design settings.', 'so-widgets-bundle' ),
			attributes: {
				widgetClass: { type: 'string' },
				anchor: { type: 'string' },
				widgetData: { type: 'object' },
				widgetMarkup: { type: 'string' },
				widgetIcons: { type: 'array' },
			},
			edit: () => el( 'div', {}, __( 'Loading Widget', 'so-widgets-bundle' ) ),
			save: () => null
		} );
	}

	if ( widgets['siteorigin-widget-taxonomy-widget'] ) {
		wp.blocks.registerBlockType( 'sowb/siteorigin-widget-taxonomy-widget', {
			title: __( 'SiteOrigin Taxonomy', 'so-widgets-bundle' ),
			description: __( 'Automatically display the taxonomies of the current post with customizable labels, colors, and link settings.', 'so-widgets-bundle' ),
			attributes: {
				widgetClass: { type: 'string' },
				anchor: { type: 'string' },
				widgetData: { type: 'object' },
				widgetMarkup: { type: 'string' },
				widgetIcons: { type: 'array' },
			},
			edit: () => el( 'div', {}, __( 'Loading Widget', 'so-widgets-bundle' ) ),
			save: () => null
		} );
	}

	if ( widgets['siteorigin-widgets-testimonials-widget'] ) {
		wp.blocks.registerBlockType( 'sowb/siteorigin-widgets-testimonials-widget', {
			title: __( 'SiteOrigin Testimonials', 'so-widgets-bundle' ),
			description: __( 'Feature testimonials from satisfied customers with tailored layouts, images, text, colors, and mobile compatibility.', 'so-widgets-bundle' ),
			attributes: {
				widgetClass: { type: 'string' },
				anchor: { type: 'string' },
				widgetData: { type: 'object' },
				widgetMarkup: { type: 'string' },
				widgetIcons: { type: 'array' },
			},
			edit: () => el( 'div', {}, __( 'Loading Widget', 'so-widgets-bundle' ) ),
			save: () => null
		} );
	}

	if ( widgets['siteorigin-widget-video-widget'] ) {
		wp.blocks.registerBlockType( 'sowb/siteorigin-widget-video-widget', {
			title: __( 'SiteOrigin Video Player', 'so-widgets-bundle' ),
			description: __( 'Embed self-hosted or externally hosted videos with a customizable player, controls, and responsive sizing.', 'so-widgets-bundle' ),
			attributes: {
				widgetClass: { type: 'string' },
				anchor: { type: 'string' },
				widgetData: { type: 'object' },
				widgetMarkup: { type: 'string' },
				widgetIcons: { type: 'array' },
			},
			edit: () => el( 'div', {}, __( 'Loading Widget', 'so-widgets-bundle' ) ),
			save: () => null
		} );
	}
}
