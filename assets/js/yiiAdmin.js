
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
			$('body').click(function(e) {
				var _target = $(e.target);

				if (_target.is('a[href]:not(.dropdown-toggle):not([data-direct])') && !e.isDefaultPrevented() && _target.attr('href').charAt(0) === '/') {
					methods.loadPage(_target.attr('href'));
					return false;
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
			window.addEventListener('popstate', function(event) {
				methods.loadPage(event.target.location.pathname + event.target.location.search, {noHistory: true});
				return event.preventDefault();
			});
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
		popupForm: function(url, onClose) {
			if (url === false) {
				$('#modal-form').modal('hide');
				if (config.onPopupClose)
					config.onPopupClose();
				config.onPopupClose = null;
				return;
			}

			config.onPopupClose = onClose ? onClose : null;

			methods.loadPage(url, {
				loadOnly: true,
				data: {popup: true},
				onContent: function(content, title) {
					var modal = $('#modal-form');
					$('.modal-body', modal).html(content);
					$('.modal-title', modal).html(title ? title : '');
					$('.modal-footer .btn-primary', modal).unbind('click').click(function() {
						$('.modal-body form:first', modal).submit();
					});
					modal.modal('show');
				}
			});
		},
		listActions: function(id) {
			$('.grid-action-column a', $('#' + id)).each(function() {
				if ($(this).is('[data-popup]') || $(this).is('[data-confirm]') || $(this).is('[data-load-only]')) {
					$(this).click(function(e) {
						if ($(this).is('[data-confirm]')) {
							if (!confirm($(this).attr('data-confirm'))) {
								e.preventDefault();
								return false;
							}
						}

						if ($(this).is('[data-load-only]')) {
							methods.loadPage($(this).attr('href'), {
								loadOnly: true,
								onContent: function() {
									methods.listUpdate(id);
								}
							});
							e.preventDefault();
							return false;
						}

						if ($(this).is('[data-popup]')) {
							methods.popupForm($(this).attr('data-popup'), function() {
								methods.listUpdate(id);
							});
							e.preventDefault();
							return false;
						}
					});
				}
			});
		},
		listUpdate: function(id) {
			var list = $('#' + id);

			var rel = list.closest('.relation-content');
			if (rel.length) {
				methods.loadChild(rel.attr('id'));
			} else {
				methods.loadPage(config.url, {
					loadOnly: true
				});
			}
		},
		loadChild: function(id) {
			var rel = $('#' + id);
			methods.loadPage(rel.attr('data-url'), {
				loader: false,
				noHistory: true,
				onContent: function(content) {
					rel.html(content);
				}
			});
		},
		onContent: function(func) {
			config.onContent = func;
		},
		processContent: function() {
			var content = $('#content');
			$('a[data-popup]').click(function(e) {
				methods.popupForm($(this).attr('href'));
				e.stopPropagation();
				return false;
			});
		}
	};
})(window.jQuery);