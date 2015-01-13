
(function ($) {
	$.yiiAdmin = function (method) {
		if (methods[method]) {
			return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
		} else if (typeof method === 'object' || !method) {
			return methods.init.apply(this, arguments);
		} else {
			$.error('Method ' + method + ' does not exist on jQuery.yiiAdmin');
			return false;
		}
	};

	var config = {};
	var methods = {
		init: function (attributes, options) {
			/*$('body').click(function(e) {
				var _target = $(e.target);

				if (_target.is('a[href]:not(.dropdown-toggle):not([data-direct])') && !e.isDefaultPrevented() && _target.attr('href').charAt(0) === '/') {
					methods.loadPage(_target.attr('href'));
					return false;
				}
			});*/
			$('body').click(function(e) {
				var _target = $(e.target);

				if (_target.is('a[data-popup]') && !e.isDefaultPrevented()) {
					if (_target.closest('.grid-action-column').size() == 0) {
						var params = {};
						if (_target.is('[data-list]'))
							params['list'] = _target.attr('data-list');
						methods.popupForm(_target.attr('href'), params);
						e.stopPropagation();
						return false;
					}
				}
			});
			if (config.onContent)
				config.onContent();

			config.url = location.href;

			var al = $('#admin-menu-language');
			$('a[data-language]', al).click(function() {
				$.ajax({
					url: al.attr('data-href'),
					type: 'get',
					data: {code: $(this).attr('data-language')}
				}).done(function(data) {
					window.location.reload();
				}).error(function() {
					alert('error');
				});

				return false;
			});
			methods.processContent();
			/*window.addEventListener('popstate', function(event) {
				methods.loadPage(event.target.location.pathname + event.target.location.search, {noHistory: true});
				return event.preventDefault();
			});*/
		},
		loadPage: function(url, params) {
			config.onContent = null;

			var defaults = {
				data: {},
				noHistory: false,
				loadOnly: false,
				loader: true,
				onContent: function(content, title) {
					$('#content').html(content);
					if (title)
						window.document.title = title;
				}
			};
			var settings = $.extend({}, defaults, params || {});

			if (settings.loadOnly)
				settings.noHistory = true;


			if (settings.loader)
				$('#loader').show().click();

			$.get(url, settings.data)
				.done(function (data) {
					config.url = url;
					if (settings.loader)
						$('#loader').hide();

					var html, title = '';
					try
					{
						var resp = $.parseJSON(data);

						if (!settings.loadOnly && typeof resp.url !== 'undefined') {
							if (resp.url.charAt(0) === '/')
								methods.loadPage(resp.url);
							else
								window.location.replace(resp.url);
							return;
						}
						html = resp.content;
						if (typeof resp.title !== 'undefined')
							title = resp.title;
					}
					catch (e)
					{
						html = data;
					}

					settings.onContent(html, title);
					if (!settings.noHistory)
						history.pushState({}, title, url);

					if (config.onContent)
						config.onContent();
				})
				.fail(function (jqXHR, textStatus) {
					settings.onContent(jqXHR.responseText, false);
				}).always(function () {
					if (settings.loader)
						$('#loader').hide();
				});
		},
		popupForm: function(url, params) {
			if (url === false) {
				$('#modal-form').modal('hide');
				return;
			}

			var defaults = {
				onClose: null,
				list: null
			};
			var settings = $.extend({}, defaults, params || {});

			methods.loadPage(url, {
				loadOnly: true,
				data: {popup: true},
				onContent: function(content, title) {
					var modal = $('#modal-form');
					modal.modal('show');

					$('.modal-body', modal).html(content);
					$('.modal-title', modal).html(title ? title : '');
					$('.modal-footer .btn-primary', modal).unbind('click').click(function() {
						$('.modal-body form:first', modal).submit();
					});

					modal.unbind('hide.bs.modal').on('hide.bs.modal', function (e) {
						if (settings.onClose)
							settings.onClose();
						if (settings.list)
							methods.listUpdate(settings.list);
					})
				}
			});
		},
		listActions: function(id) {
			$('.grid-action-column a', $('#' + id)).each(function() {
				if ($(this).is('[data-popup]')) {
					$(this).click(function(e) {
						/*if ($(this).is('[data-confirm]')) {
							if (!confirm($(this).attr('data-confirm'))) {
								e.preventDefault();
								return false;
							}
						}*/

						/*if ($(this).is('[data-load-only]')) {
							methods.loadPage($(this).attr('href'), {
								loadOnly: true,
								onContent: function() {
									methods.listUpdate(id);
								}
							});
							e.preventDefault();
							return false;
						}*/

						if ($(this).is('[data-popup]')) {
							methods.popupForm($(this).attr('data-popup'), {list: id});
							e.preventDefault();
							return false;
						}
					});
				}
			});
		},
		listUpdate: function(id) {
			var list = $('#' + id);

			if (list.is('[data-url]')) {
				methods.loadPage(list.attr('data-url'), {
					loader: true,
					noHistory: true,
					onContent: function(content) {
						list.parent().html(content);
					}
				});
			} else {
				$('#loader').show();
				window.location.reload();
			}
		},
		loadChild: function(id) {
			var rel = $('#' + id);
			methods.loadPage(rel.attr('data-url'), {
				loader: true,
				noHistory: true,
				onContent: function(content) {
					var _cont = $(content);
					alert(content);
					rel.html($('#' + id, _cont).html());
				}
			});
		},
		onContent: function(func) {
			config.onContent = func;
		},
		initForm: function(id) {
			var _form = jQuery('#' + id);
			_form.on('afterValidateAttribute', function(form, attribute, data, hasError) {
				if (data.length) {
					$(attribute.input).parents('.tab-pane').each(function() {
						$('a[href=\"#'+$(this).attr('id')+'\"]', $(this).parent().prev()).addClass('alert-danger');
					});
				}
			}).on('beforeValidate', function(event) {
				$('.nav-tabs > li a', event.target).removeClass('alert-danger');
			});

			if (_form.is('[target]')) {
				_form.on('beforeSubmit', function() {
					var _form = $(this);
					$('.messages', _form).slideUp(function() { $(this).remove(); });

					var _frame = jQuery('#' + _form.attr('target'));
					_frame.unbind('load').on('load', function() {
						if (_form.closest('#modal-form').size()) {
							$('#modal-form').modal('hide');
						}
					});
				});
			}
		},
		processContent: function() {
			var content = $('#content');
			/*$('a[data-popup]').click(function(e) {
				if ($(this).closest('.grid-action-column').size() == 0) {
					var params = {};
					if ($(this).is('[data-list]'))
						params['list'] = $(this).attr('data-list');
					methods.popupForm($(this).attr('href'), params);
					e.stopPropagation();
					return false;
				}
			});*/
		}
	};
})(window.jQuery);