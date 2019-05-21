/* globals wp, jQuery, _, soWidgets, confirm, tinymce, sowbForms */

var sowbForms = window.sowbForms || {};

(function ($) {

	$.fn.sowSetupForm = function () {

		return $(this).each(function (i, el) {
			var $el = $(el),
				$mainForm,
				formId,
				formInitializing = true;

			var $body = $( 'body' );
			// Skip this if the widget has any fields with an __i__
			var $inputs = $el.find('input[name]');
			if ($inputs.length && $inputs.attr('name').indexOf('__i__') !== -1) {
				return this;
			}

			// Skip this if we've already set up the form
			if ( $el.is('.siteorigin-widget-form-main') ) {
				if ($el.data('sow-form-setup') === true) {
					return true;
				}
				// If we're in the main widgets interface and the form isn't visible and it isn't contained in a
				// panels dialog (when using the Layout Builder widget), don't worry about setting it up.
				if ($body.hasClass('widgets-php') && !$el.is(':visible') && $el.closest('.panel-dialog').length === 0) {
					return true;
				}

				// Listen for a state change event if this is the main form wrapper
				$el.on('sowstatechange', function (e, incomingGroup, incomingState) {

					// Find all wrappers that have state handlers on them
					$el.find('[data-state-handler]').each(function () {
						var $$ = $(this);
						// Create a copy of the current state handlers. Add in initial handlers if the form is initializing.
						var handler = $.extend({}, $$.data('state-handler'), formInitializing ? $$.data('state-handler-initial') : {});
						if (Object.keys(handler).length === 0) {
							return true;
						}

						// We need to figure out what the incoming state is
						var handlerStateParts, handlerState, thisHandler, $$f, runHandler, handlerStateNames;

						// Indicates if the handler has run
						var handlerRun = {};

						var repeaterIndex = sowbForms.getContainerFieldId( $$, 'repeater', '.siteorigin-widget-field-repeater-item' );
						if (repeaterIndex !== false) {
							var repeaterHandler = {};
							for ( var rptrState in handler) {
								repeaterHandler[rptrState.replace('{$repeater}', repeaterIndex)] = handler[rptrState];
							}
							handler = repeaterHandler;
						}
						
						var widgetFieldId = sowbForms.getContainerFieldId( $$, 'widget', '.siteorigin-widget-widget' );
						if ( widgetFieldId !== false ) {
							var widgetFieldHandler = {};
							for ( var wdgFldState in handler) {
								var stMatches = wdgFldState.match( /_else\[(.*)\]|(.*)\[(.*)\]/ );
								var st = '';
								if ( stMatches && stMatches.length && stMatches[1] === undefined ) {
									st = stMatches[ 2 ] + '_' + widgetFieldId + '[' + stMatches[ 3 ] + ']';
								} else {
									st = '_else[' + stMatches[ 1 ] + '_' + widgetFieldId + ']';
								}
								
								widgetFieldHandler[st] = handler[wdgFldState];
							}
							handler = widgetFieldHandler;
						}

						// Go through all the handlers
						for (var state in handler) {
							runHandler = false;

							// Parse the handler state parts
							handlerStateParts = state.match(/^([a-zA-Z0-9_-]+)(\[([a-zA-Z0-9_\-,]+)\])?(\[\])?$/);
							if (handlerStateParts === null) {
								// Skip this if there's a problem with the state parts
								continue;
							}

							handlerState = {
								'group': 'default',
								'name': '',
								'multi': false
							};

							// Assign the handlerState attributes based on the parsed state
							if (handlerStateParts[2] !== undefined) {
								handlerState.group = handlerStateParts[1];
								handlerState.name = handlerStateParts[3];
							}
							else {
								handlerState.name = handlerStateParts[0];
							}
							handlerState.multi = (handlerStateParts[4] !== undefined);

							if (handlerState.group === '_else') {
								// This is the special case of an group else handler
								// Always run if no handlers from the current group have been run yet
								handlerState.group = handlerState.name;
								handlerState.name = '';

								// We will run this handler because none have run for it yet
								runHandler = ( handlerState.group === incomingGroup && typeof handlerRun[handlerState.group] === 'undefined' );
							}
							else {
								// Evaluate if we're in the current state
								handlerStateNames = handlerState.name.split(',').map(function (a) {
									return a.trim()
								});
								for (var i = 0; i < handlerStateNames.length; i++) {
									runHandler = (handlerState.group === incomingGroup && handlerStateNames[i] === incomingState);
									if (runHandler) break;
								}
							}

							// Run the handler if previous checks have determined we should
							if (runHandler) {
								thisHandler = handler[state];

								// Now we can handle the the handler
								if (!handlerState.multi) {
									thisHandler = [thisHandler];
								}

								for (var i = 0; i < thisHandler.length; i++) {
									// Choose the item we'll be acting on here
									if (typeof thisHandler[i][1] !== 'undefined' && Boolean(thisHandler[i][1])) {
										// thisHandler[i][1] is the sub selector
										$$f = $$.find(thisHandler[i][1]);
									}
									else {
										$$f = $$;
									}

									// Call the function on the wrapper we've selected
									$$f[thisHandler[i][0]].apply($$f, typeof thisHandler[i][2] !== 'undefined' ? thisHandler[i][2] : []);
									
									if ( $$f.is( '.siteorigin-widget-field:visible' ) ) {
										if ( $$f.is( '.siteorigin-widget-field-type-section' ) ) {
											var $fields = $$f.find( '> .siteorigin-widget-section > .siteorigin-widget-field' );
											$fields.trigger( 'sowsetupformfield' );
										} else {
											$$f.trigger( 'sowsetupformfield' );
										}
									}
									
								}

								// Store that we've run a handler
								handlerRun[handlerState.group] = true;
							}
						}

					});
				});

				// Lets set up the preview
				$el.sowSetupPreview();
				$mainForm = $el;

				var $teaser = $el.find('.siteorigin-widget-teaser');
				$teaser.find('.dashicons-dismiss').click(function () {
					var $$ = $(this);
					$.get($$.data('dismiss-url'));

					$teaser.slideUp('normal', function () {
						$teaser.remove();
					});
				});
				
				
				if ( ! $el.data( 'backupDisabled' ) ) {
					var _sow_form_id = $el.find( '> .siteorigin-widgets-form-id' ).val();
					var $timestampField = $el.find( '> .siteorigin-widgets-form-timestamp' );
					var _sow_form_timestamp = parseInt( $timestampField.val() || 0 );
					var data = JSON.parse( sessionStorage.getItem( _sow_form_id ) );
					if ( data ) {
						if ( data['_sow_form_timestamp'] > _sow_form_timestamp ) {
							sowbForms.displayNotice(
								$el,
								soWidgets.backup.newerVersion,
								soWidgets.backup.replaceWarning,
								[
									{
										label: soWidgets.backup.restore,
										callback: function ( $notice ) {
											sowbForms.setWidgetFormValues( $mainForm, data );
											$notice.slideUp( 'fast', function () {
												$notice.remove();
											} );
										},
									},
									{
										label: soWidgets.backup.dismiss,
										callback: function ( $notice ) {
											$notice.slideUp( 'fast', function () {
												sessionStorage.removeItem( _sow_form_id );
												$notice.remove();
											} );
										},
									},
								]
							);
						} else {
							sessionStorage.removeItem( _sow_form_id );
						}
					}
					$el.change( function () {
						$timestampField.val( new Date().getTime() );
						var data = sowbForms.getWidgetFormValues( $el );
						sessionStorage.setItem( _sow_form_id, JSON.stringify( data ) );
					} );
				}
			}
			else {
				$mainForm = $el.closest('.siteorigin-widget-form-main');
			}
			formId = $mainForm.find('> .siteorigin-widgets-form-id').val();

			// Find any field or sub widget fields.
			var $fields = $el.find('> .siteorigin-widget-field');

			// Process any sub sections
			$fields.find('> .siteorigin-widget-section').sowSetupForm();
			
			var $subwidgetFields = $fields.find('> .siteorigin-widget-widget');
			$subwidgetFields.find('> .siteorigin-widget-section').sowSetupForm();

			// Process any sub widgets whose fields aren't contained in a section
			$subwidgetFields.filter(':not(:has(> .siteorigin-widget-section))').sowSetupForm();

			// Store the field names
			$fields.find('.siteorigin-widget-input').each(function (i, input) {
				if ($(input).data('original-name') === null) {
					$(input).data('original-name', $(input).attr('name'));
				}
			});

			// Setup all the repeaters
			$fields.find('> .siteorigin-widget-field-repeater').sowSetupRepeater();

			// For any repeater items currently in existence
			$el.find('.siteorigin-widget-field-repeater-item').sowSetupRepeaterItems();

			// Set up any color fields
			$fields.find('> .siteorigin-widget-input-color').each(function () {
				var colorField = $(this);
				var colorFieldOptions = {
					change: function (event, ui) {
						setTimeout(function () {
							$(event.target).trigger('change');
						}, 100);
					}
				};
				if (colorField.data('defaultColor')) {
					colorFieldOptions.defaultColor = colorField.data('defaultColor');
				}
				colorField.wpColorPicker(colorFieldOptions);
			});

			///////////////////////////////////////
			// Handle the sections
			var expandContainer = function () {
				$(this).toggleClass('siteorigin-widget-section-visible');
				$(this).parent().find('> .siteorigin-widget-section, > .siteorigin-widget-widget > .siteorigin-widget-section')
					.slideToggle('fast', function () {
						$(window).resize();
						$(this).find('> .siteorigin-widget-field-container-state').val($(this).is(':visible') ? 'open' : 'closed');

						if ( $( this ).is( ':visible' ) ) {
							var $fields = $( this ).find( '> .siteorigin-widget-field' );
							$fields.trigger( 'sowsetupformfield' );
						}
					} );
			};
			$fields.filter('.siteorigin-widget-field-type-widget, .siteorigin-widget-field-type-section').find('> label').click(expandContainer);
			$fields.filter('.siteorigin-widget-field-type-posts').find('.posts-container-label-wrapper').click(expandContainer);

			///////////////////////////////////////
			// Handle the slider fields

			$fields.filter('.siteorigin-widget-field-type-slider').each(function () {
				var $$ = $(this);
				var $input = $$.find('input[type="number"]');
				var $c = $$.find('.siteorigin-widget-value-slider');

				$c.slider({
					max: parseFloat($input.attr('max')),
					min: parseFloat($input.attr('min')),
					step: parseFloat($input.attr('step')),
					value: parseFloat($input.val()),
					slide: function (event, ui) {
						$input.val( parseFloat( ui.value ) );
						$input.trigger( 'change' );
						$$.find('.siteorigin-widget-slider-value').html(ui.value);
					},
				});
				$input.change(function(event, data) {
					if ( ! ( data && data.silent ) ) {
						$c.slider( 'value', parseFloat( $input.val() ) );
					}
				});
			});

			///////////////////////////////////////
			// Setup the URL fields

			$fields.filter('.siteorigin-widget-field-type-link').each(function () {
				var $$ = $(this);

				// Function that refreshes the list of
				var request = null;
				var refreshList = function () {
					if (request !== null) {
						request.abort();
					}

					var $contentSearchInput = $$.find('.content-text-search');
					var query = $contentSearchInput.val();
					var postTypes = $contentSearchInput.data('postTypes');

					var $ul = $$.find('ul.posts').empty().addClass('loading');
					$.get(
						soWidgets.ajaxurl,
						{action: 'so_widgets_search_posts', query: query, postTypes: postTypes},
						function (data) {
							for (var i = 0; i < data.length; i++) {
								if (data[i].label === '') {
									data[i].label = '&nbsp;';
								}

								// Add all the post items
								$ul.append(
									$('<li>')
										.addClass('post')
										.html(data[i].label + '<span>(' + data[i].type + ')</span>')
										.data(data[i])
								);
							}
							$ul.removeClass('loading');
						}
					);
				};

				// Toggle display of the existing content
				$$.find('.select-content-button, .button-close').click(function (e) {
					e.preventDefault();

					$(this).blur();
					var $s = $$.find('.existing-content-selector');
					$s.toggle();

					if ($s.is(':visible') && $s.find('ul.posts li').length === 0) {
						refreshList();
					}

				});

				// Clicking on one of the url items
				$$.on('click', '.posts li', function (e) {
					e.preventDefault();
					var $li = $(this);
					$$.find('input.siteorigin-widget-input').val('post: ' + $li.data('value'));
					$$.change();
					$$.find('.existing-content-selector').toggle();
				});

				var interval = null;
				$$.find('.content-text-search').keyup(function () {
					if (interval !== null) {
						clearTimeout(interval);
					}

					interval = setTimeout(function () {
						refreshList();
					}, 500);
				});
			});

			///////////////////////////////////////
			// Setup the Builder fields
			if (typeof jQuery.fn.soPanelsSetupBuilderWidget !== 'undefined') {
				$fields.filter('.siteorigin-widget-field-type-builder').each(function () {
					$( this ).find( '> .siteorigin-page-builder-field' ).each( function () {
						var $$ = $( this );
						$$.soPanelsSetupBuilderWidget( { builderType: $$.data( 'type' ) } );
					} );
				});
			}

			///////////////////////////////////////
			// Now lets handle the state emitters

			var stateEmitterChangeHandler = function () {
				var $$ = $(this);

				// These emitters can either be an array or a
				var emitters = $$.closest('[data-state-emitter]').data('state-emitter');

				if (typeof emitters !== 'undefined') {
					var handleStateEmitter = function (emitter, currentStates) {
						if (typeof sowEmitters[emitter.callback] === 'undefined' || emitter.callback.substr(0, 1) === '_') {
							// Skip if the function doesn't exist, or it starts with an underscore (internal functions).
							return currentStates;
						}
						
						// Skip if this is an unselected radio input.
						if ( $$.is( '[type="radio"]' ) && !$$.is( ':checked' ) ) {
							return currentStates;
						}

						// Check if this is inside a repeater
						var repeaterIndex = sowbForms.getContainerFieldId( $$, 'repeater', '.siteorigin-widget-field-repeater-item' );
						if (repeaterIndex !== false) {
							emitter.args = emitter.args.map(function (a) {
								return a.replace('{$repeater}', repeaterIndex);
							});
						}
						
						var widgetFieldId = sowbForms.getContainerFieldId( $$, 'widget', '.siteorigin-widget-widget' );
						if ( widgetFieldId !== false && ! emitter.hasOwnProperty( 'widgetFieldId' ) ) {
							emitter.widgetFieldId = widgetFieldId;
							emitter.args = emitter.args.map(function (arg) {
								if ( emitter.callback === 'conditional' ) {
									arg = arg.replace( /(.*)(\[.*)/, '$1_' + widgetFieldId + '$2' );
								} else {
									arg = arg + '_' + widgetFieldId;
								}
								return arg;
							});
						}

						var val = $$.is('[type="checkbox"]') ? $$.is(':checked') : $$.val();
						// Return an array that has the new states added to the array
						return $.extend(currentStates, sowEmitters[emitter.callback](val, emitter.args));
					};

					// Run the states through the state emitters
					var states = {'default': ''};

					// Go through the array of emitters
					if (typeof emitters.length === 'undefined') {
						emitters = [emitters];
					}

					for (var i = 0; i < emitters.length; i++) {
						states = handleStateEmitter(emitters[i], states);
					}

					// Check which states have changed and trigger appropriate sowstatechange
					var formStates = $mainForm.data('states');
					if (typeof formStates === 'undefined') {
						formStates = {'default': ''};
					}
					for (var k in states) {
						if ( typeof formStates[k] === 'undefined' || states[k] !== formStates[k] ) {
							// If the state is different from the original formStates, then trigger a state change
							formStates[k] = states[k];
							$mainForm.trigger('sowstatechange', [k, states[k]]);
						}
					}

					// Store the form states back in the form
					$mainForm.data('states', formStates);
				}
			};

			$fields.filter('[data-state-emitter]').each(function () {
				
				var $input = $( this ).find( '.siteorigin-widget-input' );
				
				// Listen for any change events on an emitter field
				$input.on('keyup change', stateEmitterChangeHandler);

				// Trigger initial state emitter changes
				$input.each(function () {
					var $$ = $(this);
					if ($$.is(':radio')) {
						// Only checked radio inputs must have change events
						if ($$.is(':checked')) {
							stateEmitterChangeHandler.call($$[0]);
						}
					}
					else {
						stateEmitterChangeHandler.call($$[0]);
					}
				});

			});

			// Give plugins a chance to influence the form
			$el.trigger('sowsetupform', $fields).data('sow-form-setup', true);

			$fields.trigger('sowsetupformfield');

			$el.find('.siteorigin-widget-field-repeater-item').trigger('updateFieldPositions');

			if ( $body.hasClass( 'wp-customizer' ) || $body.hasClass( 'widgets-php' ) ) {
				// Reinitialize widget fields when they're dragged and dropped.
				$el.closest( '.ui-sortable' ).on( 'sortstop', function (event, ui) {
					var $fields = ui.item.find( '.siteorigin-widget-form' ).find( '> .siteorigin-widget-field' );
					$fields.trigger( 'sowsetupformfield' );
				} );
			}

			/////////////////////////////
			// The end of the form setup.
			/////////////////////////////

			formInitializing = false;
		});
	};

	$.fn.sowSetupPreview = function () {
		var $el = $(this);
		var previewButton = $el.siblings('.siteorigin-widget-preview');

		previewButton.find('> a').click(function (e) {
			e.preventDefault();

			var data = sowbForms.getWidgetFormValues($el);

			// Create a new modal window
			var modal = $($('#so-widgets-bundle-tpl-preview-dialog').html().trim()).appendTo('body');
			modal.find('input[name="data"]').val(JSON.stringify(data));
			modal.find('input[name="class"]').val($el.data('class'));
			modal.find('iframe').on('load', function () {
				$(this).css('visibility', 'visible');
			});
			modal.find('form').submit();

			modal.find('.close').click(function () {
				modal.remove();
			});
		});
	};

	$.fn.sowSetupRepeater = function () {

		return $(this).each(function (i, el) {
			var $el = $(el);
			var $items = $el.find('.siteorigin-widget-field-repeater-items');
			var name = $el.data('repeater-name');

			$items.bind('updateFieldPositions', function () {
				var $$ = $(this);
				var $rptrItems = $$.find('> .siteorigin-widget-field-repeater-item');

				// Set the position for the repeater items
				$rptrItems.each(function (i, el) {
					$(el).find('.siteorigin-widget-input').each(function (j, input) {
						var pos = $(input).data('repeater-positions');
						if (typeof pos === 'undefined') {
							pos = {};
						}

						pos[name] = i;
						$(input).data('repeater-positions', pos);
					});
				});
				
				// Update the field names for all the input items
				$$.find('.siteorigin-widget-input').each( function ( i, input ) {
					var $in = $( input );
					var pos = $in.data( 'repeater-positions' );
					
					if ( typeof pos !== 'undefined' ) {
						var newName = $in.attr( 'data-original-name' );
						
						if ( !newName ) {
							$in.attr( 'data-original-name', $in.attr( 'name' ) );
							newName = $in.attr( 'name' );
						}
						if ( !newName ) {
							return;
						}
						
						if ( pos ) {
							for ( var k in pos ) {
								newName = newName.replace( '#' + k + '#', pos[ k ] );
							}
						}
						$in.attr( 'name', newName );
					}
				} );
				
				if ( !$$.data( 'initialSetup' ) ) {
					// Setup default checked values, now that we've updated input names.
					// Without this radio inputs in repeaters will be rendered as if they all belong to the same group.
					$$.find('.siteorigin-widget-input').each(function (i, input) {
						var $in = $(input);
						$in.prop('checked', $in.prop('defaultChecked'));
					});
					$$.data('initialSetup', true);
				}

				//Setup scrolling.
				var scrollCount = $el.data('scroll-count') ? parseInt($el.data('scroll-count')) : 0;
				if (scrollCount > 0 && $rptrItems.length > scrollCount) {
					var itemHeight = $rptrItems.first().outerHeight();
					$$.css('max-height', itemHeight * scrollCount).css('overflow', 'auto');
				}
				else {
					//TODO: Check whether there was a value before overriding and set it back to that.
					$$.css('max-height', '').css('overflow', '');
				}
			});

			$items.sortable({
				handle: '.siteorigin-widget-field-repeater-item-top',
				items: '> .siteorigin-widget-field-repeater-item',
				update: function () {
					// Clear `name` attributes for radio inputs. They'll be reassigned on update.
					// This prevents some radio inputs values being cleared during the update process.
					$items.find( 'input[type="radio"].siteorigin-widget-input' ).attr( 'name', '' );
					$items.trigger('updateFieldPositions');
					$el.trigger( 'change' );
				},
				sortstop: function (event, ui) {
					if ( ui.item.is( '.siteorigin-widget-field-repeater-item' ) ) {
						ui.item.find( '> .siteorigin-widget-field-repeater-item-form' ).each( function () {
							var $fields = $( this ).find( '> .siteorigin-widget-field' );
							$fields.trigger( 'sowsetupformfield' );
						} );
					}
					else {
						var $fields = ui.item.find( '.siteorigin-widget-form' ).find( '> .siteorigin-widget-field' );
						$fields.trigger( 'sowsetupformfield' );
					}
					$el.trigger( 'change' );
				}
			});
			$items.trigger('updateFieldPositions');

			$el.find('> .siteorigin-widget-field-repeater-add').disableSelection().click(function (e) {
				e.preventDefault();
				$el.closest('.siteorigin-widget-field-repeater')
					.sowAddRepeaterItem()
					.find('> .siteorigin-widget-field-repeater-items').slideDown('fast', function () {
					$(window).resize();
				});
			});

			$el.find('> .siteorigin-widget-field-repeater-top > .siteorigin-widget-field-repeater-expand').click(function (e) {
				e.preventDefault();
				$el.closest('.siteorigin-widget-field-repeater').find('> .siteorigin-widget-field-repeateritems-').slideToggle('fast', function () {
					$(window).resize();
				});
			});
		});
	};

	$.fn.sowAddRepeaterItem = function () {
		return $(this).each(function (i, el) {

			var $el = $(el);
			var $nextIndex = $el.find('> .siteorigin-widget-field-repeater-items').children().length + 1;

			// Create an object with the repeater html so we can make some changes to it.
			var repeaterObject = $('<div>' + $el.find('> .siteorigin-widget-field-repeater-item-html').html() + '</div>');
			repeaterObject.find('.siteorigin-widget-input[data-name]').each(function () {
				var $$ = $(this);
				// Skip out items that are themselves inside repeater HTML wrappers
				if ($$.closest('.siteorigin-widget-field-repeater-item-html').length === 0) {
					$$.attr('name', $(this).data('name'));
				}
			});
			
			// Replace repeater item id placeholders with the index of the repeater item.
			var repeaterHtml = '';
			repeaterObject.find( '> .siteorigin-widget-field' )
			.each( function ( index, element ) {
				var html = element.outerHTML;
				// Skip child repeaters, so they can setup their own id's when necessary.
				if ( ! $( element ).is( '.siteorigin-widget-field-type-repeater' ) ) {
					html = html.replace( /_id_/g, $nextIndex );
				}
				repeaterHtml += html;
			} );

			var readonly = typeof $el.attr('readonly') !== 'undefined';
			var item = $('<div class="siteorigin-widget-field-repeater-item ui-draggable" />')
				.append(
					$('<div class="siteorigin-widget-field-repeater-item-top" />')
						.append(
							$('<div class="siteorigin-widget-field-expand" />')
						)
						.append(
							readonly ? '' : $('<div class="siteorigin-widget-field-copy" />')
						)
						.append(
							readonly ? '' : $('<div class="siteorigin-widget-field-remove" />')
						)
						.append($('<h4 />').html($el.data('item-name')))
				)
				.append(
					$('<div class="siteorigin-widget-field-repeater-item-form" />')
						.html(repeaterHtml)
				);

			// Add the item and refresh
			$el.find('> .siteorigin-widget-field-repeater-items').append(item).sortable("refresh").trigger('updateFieldPositions');
			item.sowSetupRepeaterItems();
			item.hide().slideDown('fast', function () {
				$(window).resize();
			});
			$el.trigger( 'change' );
		});
	};

	$.fn.sowRemoveRepeaterItem = function () {
		return $(this).each(function (i, el) {
			var $itemsContainer = $(this).closest('.siteorigin-widget-field-repeater-items');
			$(this).remove();
			$itemsContainer.sortable("refresh").trigger('updateFieldPositions');
			$( el ).trigger( 'change' );
		});
	};

	$.fn.sowSetupRepeaterItems = function () {
		return $(this).each(function (i, el) {
			var $el = $(el);

			if (typeof $el.data('sowrepeater-actions-setup') === 'undefined') {
				var $parentRepeater = $el.closest('.siteorigin-widget-field-repeater');
				var itemTop = $el.find('> .siteorigin-widget-field-repeater-item-top');
				var itemLabel = $parentRepeater.data('item-label');
				if (itemLabel && itemLabel.selector) {
					var updateLabel = function () {
						var functionName = ( itemLabel.hasOwnProperty('valueMethod') && itemLabel.valueMethod ) ? itemLabel.valueMethod : 'val';
						var txt = $el.find(itemLabel.selector)[functionName]();
						if (txt) {
							if (txt.length > 80) {
								txt = txt.substr(0, 79) + '...';
							}
							itemTop.find('h4').text(txt);
						}
					};
					updateLabel();
					var eventName = ( itemLabel.hasOwnProperty('updateEvent') && itemLabel.updateEvent ) ? itemLabel.updateEvent : 'change';
					$el.bind(eventName, updateLabel);
				}

				itemTop.click(function (e) {
					if (e.target.className === "siteorigin-widget-field-remove" || e.target.className === "siteorigin-widget-field-copy") {
						return;
					}
					e.preventDefault();
					$(this).closest('.siteorigin-widget-field-repeater-item').find('.siteorigin-widget-field-repeater-item-form').eq(0).slideToggle('fast', function () {
						$(window).resize();
						if ($(this).is(':visible')) {
							$(this).trigger('slideToggleOpenComplete');
							
							$( this ).find( '.siteorigin-widget-field-type-section > .siteorigin-widget-section > .siteorigin-widget-field,> .siteorigin-widget-field' )
							.each( function (index, element) {
								var $field = $( element );
								if ( $field.is( ':visible' ) ) {
									$field.trigger( 'sowsetupformfield' );
								}
								
							} );
						}
						else {
							$(this).trigger('slideToggleCloseComplete');
						}
					});
				});

				itemTop.find('.siteorigin-widget-field-remove').click(function (e, params) {
					e.preventDefault();
					var $s = $( this ).closest( '.siteorigin-widget-field-repeater-items' );
					var $item = $( this ).closest( '.siteorigin-widget-field-repeater-item' );
					var removeItem = function () {
						$item.remove();
						$s.sortable( "refresh" ).trigger( 'updateFieldPositions' );
						$( window ).resize();
						$parentRepeater.trigger( 'change' );
					};
					if ( params && params.silent ) {
						removeItem();
					} else if ( confirm( soWidgets.sure ) ) {
						$item.slideUp('fast', removeItem );
					}
				});
				itemTop.find('.siteorigin-widget-field-copy').click(function (e) {
					e.preventDefault();
					var $form = $(this).closest('.siteorigin-widget-form-main');
					var $item = $(this).closest('.siteorigin-widget-field-repeater-item');
					var $copyItem = $item.clone();
					var $items = $item.closest('.siteorigin-widget-field-repeater-items');
					//var $nextIndex = $item.index()+1;
					var $nextIndex = $items.children().length;
					var newIds = {};

					$copyItem.find('*[name]').each(function () {
						var $inputElement = $(this);
						var id = $inputElement.attr('id');
						var nm = $inputElement.attr('name');
						// TinyMCE field :/
						if ($inputElement.is('textarea') && $inputElement.parent().is('.wp-editor-container') && typeof tinymce != 'undefined') {
							$inputElement.parent().empty().append($inputElement);
							$inputElement.css('display', '');
							var curEd = tinymce.get(id);
							if (curEd) {
								$inputElement.val(curEd.getContent());
							}
						}
						// Color field :/
						else if ($inputElement.is('.wp-color-picker')) {
							var $wpPickerContainer = $inputElement.closest('.wp-picker-container');
							var $soWidgetField = $inputElement.closest('.siteorigin-widget-field');
							$wpPickerContainer.remove();
							$soWidgetField.append($inputElement.remove());
						}
						else {
							var $originalInput = id ? $item.find( '#' + id ) : $item.find('[name="' + nm + '"]');
							if ($originalInput.length && $originalInput.val() != null) {
								$inputElement.val($originalInput.val());
							}
						}
						if (id) {
							var idRegExp;
							var idBase;
							var newId;

							// Radio inputs are slightly different because there are multiple `input` elements for
							// a single field, i.e. multiple `inputs` for selecting a single value.
							if ($inputElement.is('[type="radio"]')) {
								// Radio inputs have their position appended to the id.
								idBase = id.replace(/-\d+-\d+$/, '');
								var radioIdBase = id.replace(/-\d+$/, '');
								if (!newIds[idBase]) {
									var radioNames = {};
									newIds[idBase] = $form
										// find all inputs containing idBase in their id attribute
											.find('.siteorigin-widget-input[id^=' + idBase + ']')
											// exclude inputs from templates
											.not('[id*=_id_]')
											// reduce to one element per radio input group.
											.filter(function (index, element) {
												var eltName = $(element).attr('name');
												if (radioNames[eltName]) {
													return false;
												} else {
													radioNames[eltName] = true;
													return true;
												}
											}).length + 1;
								}
								var newRadioIdBase = idBase + '-' + newIds[idBase];
								newId = newRadioIdBase + id.match(/-\d+$/)[0];
								$copyItem.find('label[for=' + radioIdBase + ']').attr('for', newRadioIdBase);
							} else {
								idRegExp = new RegExp('-\\d+$');
								idBase = id.replace(idRegExp, '');
								if (!newIds[idBase]) {
									newIds[idBase] = $form.find('.siteorigin-widget-input[id^=' + idBase + ']').not('[id*=_id_]').length + 1;
								}
								newId = idBase + '-' + newIds[idBase]++;
							}

							$inputElement.attr('id', newId);
							if ( $inputElement.is( '.wp-editor-area' ) ) {
								var tmceContainer = $inputElement.closest( '.siteorigin-widget-tinymce-container' );
								var mediaButtons = tmceContainer.data( 'media-buttons' );
								if ( mediaButtons && mediaButtons.html ) {
									var idRegExp = new RegExp( id, 'g');
									mediaButtons.html = mediaButtons.html.replace( idRegExp, newId );
									tmceContainer.data( 'media-buttons', mediaButtons );
								}
							}
							$copyItem.find('label[for=' + id + ']').attr('for', newId);
							$copyItem.find('[id*=' + id + ']').each(function () {
								var oldIdAttr = $(this).attr('id');
								var newIdAttr = oldIdAttr.replace(id, newId);
								$(this).attr('id', newIdAttr);
							});
							if (typeof tinymce !== 'undefined' && tinymce.get(newId)) {
								tinymce.get(newId).remove();
							}
						}
						var nestLevel = $item.parents('.siteorigin-widget-field-repeater').length;
						var $body = $('body');
						if (($body.hasClass('wp-customizer') || $body.hasClass('widgets-php')) && $el.closest('.panel-dialog').length === 0) {
							nestLevel += 1;
						}
						var newName = nm.replace(new RegExp('((?:.*?\\[\\d+\\]){' + (nestLevel - 1).toString() + '})?(.*?\\[)\\d+(\\])'), '$1$2' + $nextIndex.toString() + '$3');
						$inputElement.attr('name', newName);
						$inputElement.data('original-name', newName);
					});

					//$item.after($copyItem);
					//$items.sortable( "refresh").trigger('updateFieldPositions');
					$items.append($copyItem).sortable("refresh").trigger('updateFieldPositions');
					$copyItem.sowSetupRepeaterItems();
					$copyItem.hide().slideDown('fast', function () {
						$(window).resize();
					});
					$el.trigger( 'change' );
				});

				$el.find('> .siteorigin-widget-field-repeater-item-form').sowSetupForm();

				$el.data('sowrepeater-actions-setup', true);
			}
		});
	};

	// Widgets Bundle utility functions
	/**
	 * Get the unique index of a repeated item. Could be in a repeater or if multiple widget fields with the same
	 * widget class.
	 *
	 * @param $el
	 * @param containerType
	 * @param containerClass
	 * @return {*}
	 */
	sowbForms.getContainerFieldId = function ( $el, containerType, containerClass ) {
		var fieldIdPropName = containerType + 'FieldId';
		if ( ! this.hasOwnProperty( fieldIdPropName ) ) {
			this[ fieldIdPropName ] = 1;
		}
		
		var $field = $el.closest( containerClass );
		if ( $field.length ) {
			var fieldId = $field.data( 'field-id' );
			if ( fieldId === undefined ) {
				fieldId = this[ fieldIdPropName ]++;
			}
			$field.data( 'field-id', fieldId );
			
			return fieldId;
		}
		else {
			return false;
		}
	};

	/**
	 * Retrieve a variable for a field with the given identifier, elementName.
	 *
	 * @return {*}
	 * @param widgetClass The class name of the widget for which to retrieve a variable.
	 * @param elementName The name of the field for which to retrieve a variable.
	 * @param key The name of the variable to retrieve.
	 */
	sowbForms.getWidgetFieldVariable = function (widgetClass, elementName, key) {
		var widgetVars = window.sow_field_javascript_variables[widgetClass];
		// Get rid of any index placeholders
		elementName = elementName.replace(/\[#.*?#\]/g, '');
		var variablePath = /[a-zA-Z0-9\-]+(?:\[c?[0-9]+\])?\[(.*)\]/.exec(elementName)[1];
		var variablePathParts = variablePath.split('][');
		var elementVars = variablePathParts.length ? widgetVars : null;
		while (variablePathParts.length) {
			elementVars = elementVars[variablePathParts.shift()];
		}
		return elementVars[key];
	};

	sowbForms.fetchWidgetVariable = function (key, widget, callback) {
		window.sowVars = window.sowVars || {};

		if (typeof window.sowVars[widget] === 'undefined') {
			$.post(
				soWidgets.ajaxurl,
				{'action': 'sow_get_javascript_variables', 'widget': widget, 'key': key},
				function (result) {
					window.sowVars[widget] = result;
					callback(window.sowVars[widget][key]);
				}
			);
		}
		else {
			callback(window.sowVars[widget][key]);
		}
	};
	
	sowbForms.getWidgetIdBase = function ( formContainer ) {
		return formContainer.data( 'id-base' );
	};

	sowbForms.getWidgetFormValues = function ( formContainer ) {

		if ( _.isUndefined( formContainer ) ) {
			return null;
		}

		var data = {};

		formContainer.find('*[name]').each(function () {
			var $$ = $(this);

			try {
				var name = /[a-zA-Z0-9\-]+\[[a-zA-Z0-9]+\]\[(.*)\]/.exec( $$.attr( 'name' ) );

				if ( _.isEmpty( name ) ) {
					return true;
				}

				// Create an array with the parts of the name
				name = name[1];
				var parts = name.split( '][' );

				// Make sure we either have numbers or strings
				parts = parts.map( function ( e ) {
					if ( ! isNaN( parseFloat( e ) ) && isFinite( e ) ) {
						return parseInt( e );
					}
					else {
						return e;
					}
				} );

				var sub = data;
				var fieldValue = null;

				var fieldType = _.isString( $$.attr( 'type' ) ) ? $$.attr( 'type' ).toLowerCase() : null;

				if ( fieldType === 'checkbox' ) {
					if ( $$.is( ':checked' ) ) {
						fieldValue = $$.val() !== '' ? $$.val() : true;
					} else {
						fieldValue = false;
					}
				} else if ( fieldType === 'radio' ) {
					if ( $$.is( ':checked' ) ) {
						fieldValue = $$.val();
					} else {
						return;
					}
				} else if ( $$.prop( 'tagName' ) === 'TEXTAREA' && $$.hasClass( 'wp-editor-area' ) ) {
					// This is a TinyMCE editor, so we'll use the tinyMCE object to get the content
					var editor = null;
					if ( typeof tinyMCE !== 'undefined' ) {
						editor = tinyMCE.get( $$.attr( 'id' ) );
					}
					
					if ( editor !== null && typeof( editor.getContent ) === "function" && !editor.isHidden() ) {
						fieldValue = editor.getContent();
					}
					else {
						fieldValue = $$.val();
					}
				} else if ( $$.prop( 'tagName' ) === 'SELECT' ) {
					var selected = $$.find( 'option:selected' );
					if ( selected.length === 1 ) {
						fieldValue = $$.find( 'option:selected' ).val();
					}
					else if ( selected.length > 1 ) {
						// This is a mutli-select field
						fieldValue = _.map( $$.find( 'option:selected' ), function ( n, i ) {
							return $( n ).val();
						} );
					}
				} else {
					fieldValue = $$.val();
				}
				for ( var i = 0; i < parts.length; i++ ) {
					if ( i === parts.length - 1 ) {
						if ( parts[i] === '' ) {
							// This needs to be an array
							sub.push( fieldValue );
						} else {
							sub[ parts[ i ] ] = fieldValue;
						}
					}
					else {
						if ( _.isUndefined( sub[ parts[ i ] ] ) ) {
							// We assume that a numeric key means it's an array. (or empty string??)
							if ( _.isNumber( parts[ i + 1 ] ) || parts[ i + 1 ] === '' ) {
								sub[ parts[ i ] ] = [];
							} else {
								sub[ parts[ i ] ] = {};
							}
						}
						// Go deeper into the data and continue
						sub = sub[ parts[ i ] ];
					}
				}
			} catch ( error ) {
				console.error( 'Field [' + $$.attr( 'name' ) + '] could not be processed and was skipped - ' + error.message );
			}
		});
		return data;
	};
	
	
	/**
	 * Sets all the widget form fields in the given container with the given data values.
	 *
	 * @param formContainer The jQuery element containing the widget form fields.
	 * @param data The data from which to set the widget form field values.
	 * @param skipMissingValues If `true`, this will skip form fields for which the data values are missing.
	 * 							If `false`, the form fields will be cleared. Default is `false`.
	 * @param triggerChange If `true`, trigger a 'change' event on each element after it's value is set. Default is `true`.
	 */
	sowbForms.setWidgetFormValues = function (formContainer, data, skipMissingValues, triggerChange) {
		skipMissingValues = skipMissingValues || false;
		triggerChange = (typeof triggerChange !== 'undefined' && triggerChange) || typeof triggerChange === 'undefined';
		// First check if this form has any repeaters.
		var depth = 0;
		var updateRepeaterChildren = function ( formParent, formData ) {
			if ( ++depth === 10 ) {
				--depth;
				return;
			}
			// Only direct child fields which are repeaters.
			formParent.find( '> .siteorigin-widget-field-type-repeater,> .siteorigin-widget-field-type-section > .siteorigin-widget-section > .siteorigin-widget-field-type-repeater' )
			.each( function ( index, element ) {
				var $this = $( this );
				var $repeater = $this.find( '> .siteorigin-widget-field-repeater' );
				var repeaterName = $repeater.data( 'repeaterName' );
				var repeaterData = formData.hasOwnProperty( repeaterName ) ? formData[ repeaterName ] : null;
				var isInSection = $this.parent().is( '.siteorigin-widget-section' );
				if ( isInSection ) {
					var elementName = $repeater.data( 'element-name' );
					// Get rid of any index placeholders
					elementName = elementName.replace(/\[#.*?#\]/g, '');
					var variablePath = /[a-zA-Z0-9\-]+(?:\[c?[0-9]+\])?\[(.*)\]/.exec(elementName)[1];
					var variablePathParts = variablePath.split('][');
					var elementVars = variablePathParts.length ? formData : null;
					while (variablePathParts.length) {
						var key = variablePathParts.shift();
						elementVars = elementVars.hasOwnProperty( key ) ? elementVars[ key ] : elementVars;
					}
					repeaterData = elementVars;
				}
				
				if ( ! repeaterData || ! Array.isArray( repeaterData ) ) {
					return;
				}
				// Check that the number of child items matches the number of data items.
				var repeaterChildren = $repeater.find( '> .siteorigin-widget-field-repeater-items > .siteorigin-widget-field-repeater-item' );
				var numItems = repeaterData.length;
				var numChildren = repeaterChildren.length;
				if ( numItems > numChildren ) {
					// If data items > child items, create extra child items.
					for ( var i = 0; i < numItems - numChildren; i++) {
						$repeater.find( '> .siteorigin-widget-field-repeater-add' ).click();
					}

				} else if ( ! skipMissingValues && numItems < numChildren ) {
					// If child items > data items, remove extra child items.
					for ( var j = numItems; j < numChildren; j++) {
						var $child = $( repeaterChildren.eq( j ) );
						$child.find( '> .siteorigin-widget-field-repeater-item-top' )
							.find( '.siteorigin-widget-field-remove' )
							.trigger( 'click', { silent: true } );
					}
				}
				repeaterChildren = $repeater.find( '> .siteorigin-widget-field-repeater-items > .siteorigin-widget-field-repeater-item' );
				for ( var k = 0; k < repeaterChildren.length; k++ ) {
					repeaterChildren.eq( k ).find( '> .siteorigin-widget-field-repeater-item-form' );
					updateRepeaterChildren(
						repeaterChildren.eq( k ).find( '> .siteorigin-widget-field-repeater-item-form' ),
						repeaterData[ k ]
					);
				}
			} );
			--depth;
		};

		updateRepeaterChildren(formContainer, data);

		formContainer.find('*[name]').each(function () {
			var $$ = $(this);
			var name = /[a-zA-Z0-9\-]+\[[a-zA-Z0-9]+\]\[(.*)\]/.exec($$.attr('name'));

			if ( name === undefined || name === null ) {
				return true;
			}

			name = name[1];
			var parts = name.split('][');

			// Make sure we either have numbers or strings
			parts = parts.map( function ( e ) {
				if ( !isNaN( parseFloat( e ) ) && isFinite( e ) ) {
					return parseInt( e );
				} else {
					return e;
				}
			} );

			var sub = data;
			var value;
			for (var i = 0; i < parts.length; i++) {
				// If the field is missing from the data, just leave `value` as `undefined`.
				if ( ! sub.hasOwnProperty( parts[ i ] ) ) {
					if ( skipMissingValues ) {
						return true;
					} else {
						break;
					}
				}
				if (i === parts.length - 1) {
					value = sub[ parts[ i ] ];
				} else {
					sub = sub[ parts[ i ] ];
				}
			}

			// This is the end, so we need to set the value on the field here.
			if ( $$.attr( 'type' ) === 'checkbox' ) {
				$$.prop( 'checked', value );
			} else if ( $$.attr( 'type' ) === 'radio' ) {
				$$.prop( 'checked', value === $$.val() );
			} else if ( $$.prop( 'tagName' ) === 'TEXTAREA' && $$.hasClass( 'wp-editor-area' ) ) {
				// This is a TinyMCE editor, so we'll use the tinyMCE object to get the content
				var editor = null;
				if ( typeof tinyMCE !== 'undefined' ) {
					editor = tinyMCE.get( $$.attr( 'id' ) );
				}

				if ( editor !== null && typeof( editor.setContent ) === "function" && ! editor.isHidden() && $$.parent().is( ':visible' ) ) {
					if ( editor.initialized ) {
						editor.setContent( value );
					} else {
						editor.on('init', function () {
							editor.setContent( value );
						});
					}
				}
				else {
					$$.val( value );
				}
			} else if ( $$.is( '.panels-data' ) ) {
				$$.val( value );
				var builder = $$.data( 'builder' );
				if ( builder ) {
					builder.setDataField( $$ );
				}
			} else {
				$$.val( value );
			}
			
			if ( triggerChange ) {
				$$.trigger( 'change' );
				this.dispatchEvent(new Event('change', {bubbles: true, cancelable: true}));
			}
		});
	};
	
	
	/**
	 * Displays an informational notice either at the top of the supplied container, or above the optionally supplied
	 * element.
	 *
	 * @param $container	The jQuery container in which the notice will be prepended.
	 * @param title			The string title for the notice.
	 * @param message		The string detail message for the notice.
	 * @param buttons		An array of buttons which will be display along with the notice.
	 * @param $element		The optional jQuery element before which the notice will be inserted. If this is supplied it
	 * 						will take precedence over the $container argument.
	 *
	 */
	sowbForms.displayNotice = function ( $container, title, message, buttons, $element ) {
		
		var $notice = $( '<div class="siteorigin-widget-form-notification"></div>' );
		if ( title ) {
			$notice.append( '<span>' + title + '</span>' );
		}
		
		if ( buttons && buttons.length ) {
			buttons.forEach( function ( button ) {
				var buttonClasses = '';
				if ( button.classes && button.classes.length ) {
					buttonClasses = ' ' + button.classes.join( ' ' );
				}
				var $button = $( '<a class="button button-small' + buttonClasses + '">' + button.label + '</a>' );
				
				if ( button.url ) {
					$button.attr( 'href', button.url );
				}
				if ( button.callback ) {
					$button.on( 'click', function () {
						button.callback( $notice );
					});
				}
				
				$notice.append( $button );
			} );
		}
		if ( message ) {
			$notice.append( '<div><small>' + message + '</small></div>' );
		}
		
		if ( $element ) {
			$element.before( $notice );
		} else {
			$container.prepend( $notice );
		}
	};

	// When we click on a widget top
	$('.widgets-holder-wrap').on('click', '.widget:has(.siteorigin-widget-form-main) .widget-top', function () {
		var $$ = $(this).closest('.widget').find('.siteorigin-widget-form-main');
		setTimeout(function () {
			$$.sowSetupForm();
		}, 200);
	});
	var $body = $( 'body' );
	if ( $body.hasClass('wp-customizer') ) {
		// Setup new widgets when they're added in the customizer interface
		$(document).on('widget-added', function (e, widget) {
			widget.find('.siteorigin-widget-form').sowSetupForm();
		});
	}
	
	if ( $body.hasClass('block-editor-page') ) {
		// Setup new widgets when they're previewed in the block editor.
		$(document).on('panels_setup_preview', function () {
			if (window.hasOwnProperty('sowb')) {
				$( sowb ).trigger( 'setup_widgets', { preview: true } );
			}
		});
	}

	$( document ).on( 'open_dialog', function ( e, dialog ) {
		// When we open a Page Builder edit widget dialog
		if ( dialog.$el.find( '.so-panels-dialog' ).is( '.so-panels-dialog-edit-widget' ) ) {
			var $fields = dialog.$el.find( '.siteorigin-widget-form-main' ).find( '> .siteorigin-widget-field' );
			$fields.trigger( 'sowsetupformfield' );
		}
	});

	$(function () {
		$(document).trigger('sowadminloaded');
	});

})(jQuery);

var sowEmitters = {

	/**
	 * Find the group/state and an extra match part.
	 *
	 * @param arg
	 * @param matchPart
	 * @return {*}
	 */
	'_match': function (arg, matchPart) {
		if (typeof matchPart === 'undefined') {
			matchPart = '.*';
		}

		// Create the regular expression to match the group/state and extra match
		var exp = new RegExp('^([a-zA-Z0-9_-]+)(\\[([a-zA-Z0-9_-]+)\\])? *: *(' + matchPart + ') *$');
		var m = exp.exec(arg);

		if (m === null) {
			return false;
		}

		var state = '';
		var group = 'default';

		if (m[3] !== undefined) {
			group = m[1];
			state = m[3];
		}
		else {
			state = m[1];
		}

		return {
			'match': m[4].trim(),
			'group': group,
			'state': state
		};
	},

	'_checker': function (val, args, matchPart, callback) {
		var returnStates = {};
		if (typeof args.length === 'undefined') {
			args = [args];
		}

		var m;
		for (var i = 0; i < args.length; i++) {
			m = sowEmitters._match(args[i], matchPart);
			if (m === false) {
				continue;
			}

			if (m.match === '_true' || callback(val, args, m.match)) {
				returnStates[m.group] = m.state;
			}
		}

		return returnStates;
	},

	/**
	 * A very simple state emitter that simply sets the given group the value
	 *
	 *
	 * @param val
	 * @param args
	 * @returns {{}}
	 */
	'select': function (val, args) {
		if (typeof args.length === 'undefined') {
			args = [args];
		}

		var returnGroups = {};
		for (var i = 0; i < args.length; i++) {
			if (args[i] === '') {
				args[i] = 'default';
			}
			returnGroups[args[i]] = val;
		}

		return returnGroups;
	},

	/**
	 * The conditional state emitter uses eval to check a given conditional argument.
	 *
	 * @param val
	 * @param args
	 * @return {{}}
	 */
	'conditional': function (val, args) {
		return sowEmitters._checker(val, args, '[^;{}]*', function (val, args, match) {
			return eval(match);
		});
	},

	/**
	 * The in state emitter checks if the value is in an array of functions
	 *
	 * @param val
	 * @param args
	 * @return {{}}
	 */
	'in': function (val, args) {
		return sowEmitters._checker(val, args, '[^;{}]*', function (val, args, match) {
			return match.split(',').map(function (s) {
					return s.trim();
				}).indexOf(val) !== -1;
		});
	}
};

window.sowbForms = sowbForms;
