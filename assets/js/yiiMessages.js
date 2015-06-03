(function ($) {
	$.fn.yiiMessages = function (config) {

		var _obj = $(this),
			_table = $('<table>', {
			'class': 'table table-striped',
			'html': '<tbody></tbody>'
		})
			, _head = $('<thead><tr><th></th><th></th></tr></thead>')
			, $sources = {};

		for (var $key in config.sources) {
			if (!config.sources[$key].translations || config.sources[$key].translations.length == 0)
				config.sources[$key].translations = {}
			$sources['id' + config.sources[$key].id] = config.sources[$key];
		}

		var _tabs = $('<ul>', {'class': 'nav nav-tabs'});
		var new_rows_count = 0;
		var active_lang = config.languages[0];

		for (var $i = 0; $i < config.languages.length; $i++) {
			var _a = $('<a>', {
				'href': '#',
				'text': config.languages[$i]
			});
			_a.data('lang', config.languages[$i]);
			_tabs.append($('<li></li>', {'html': _a}));
		}

		$('li:first', _tabs).addClass('active');

		$('a', _tabs).click(function() {
			var _a = $(this);
			$('li.active').removeClass('active');
			_a.closest('li').addClass('active');
			active_lang = _a.data('lang');
			$('tbody td', _table).each(function() {
				if ($(this).data('lang')) {
					if ($(this).data('lang') == active_lang)
						$(this).show();
					else
						$(this).hide();
				}
			});
			return false;
		});
		$('th', _head).css('border', '0').css('padding', 0).eq(1).attr('colspan', config.languages.length).html(_tabs);
		if (config.edit_source)
			$('th:first', _head).attr('colspan', '2');
		_table.prepend(_head);

		var add_table_row = function(source) {
			var _tr = $('<tr>'),
				empty_validate = function(evt, newValue) {
					if (newValue.trim() == '') {
						return false;
					}
					var _source = parseInt($(this).closest('tr').data('source'));
					if ($sources['id'+_source])
						$sources['id'+_source].message = newValue;
				},
				_source = $('<td>', {
					'html': source.message,
					'tabindex' : '1'
				});
			_tr.data('source', source.id + '');

			if (config.edit_source) {
				_source.on('change', empty_validate).data('editable', true);

				var _remove_a = $('<a>', {
					'class': 'glyphicon glyphicon-remove',
					'href': '#'
				});
				_remove_a.click(function() {
					var _tr = $(this).closest('tr'),
						_id = parseInt(_tr.data('source'));

					if (_tr.data('deleted')) {
						delete $sources['id'+_id].deleted;
						_tr.data('deleted', false);
						_tr.removeClass('danger');

						_remove_a.attr('class', 'glyphicon glyphicon-remove');
					} else {
						$sources['id'+_id].deleted = true;
						_tr.data('deleted', true);
						_tr.addClass('danger');

						_remove_a.attr('class', 'glyphicon glyphicon-refresh');
					}

					return false;
				});
				_tr.append($('<td>', {
					'style': 'width: 10px;',
					'html': _remove_a
				}));

				_source.data('editable', true);
			}
			_tr.append(_source);


			for (var $i = 0; $i < config.languages.length; $i++) {
				var lang = config.languages[$i];
				var _td = $('<td>', {
					'tabindex' : '1'
				});

				if (source.translations && source.translations[lang]) {
					_td.text(source.translations[lang]);
				} else {
					_td.addClass('warning');
				}
				_td.data('lang', lang).on('change', function(evt, newValue) {
					var source_id = $(this).closest('tr').data('source')
						, td_lang = $(this).data('lang');
					if (newValue.trim() == '') {
						delete $sources['id'+source_id]['translations'][td_lang];
						$(this).addClass('warning');
					} else {
						$(this).removeClass('warning');
						$sources['id'+source_id]['translations'][td_lang] = newValue;
					}
				});
				if (lang !== active_lang)
					_td.hide();
				_tr.append(_td);
			}
			$('tbody', _table).append(_tr);
		};

		for (var $key in $sources) {
			add_table_row($sources[$key]);
		}

		var _tfoot = $('<tfoot><tr><th style="text-align:center"></th><th colspan="'+config.languages.length+'"></th></tr></tfoot>');

		if (config.edit_source) {
			var _add_button = $('<a>', {
				'class': 'glyphicon glyphicon-plus',
				'href': '#'
			});
			_add_button.click(function() {
				new_rows_count--;
				var new_source = {
					'id': new_rows_count,
					'message': '-',
					'translations': {}
				};
				$sources['id' + new_rows_count] = new_source;

				add_table_row(new_source);
				return false;
			});
			$('th:first', _tfoot).append(_add_button).attr('colspan', '2');
		}

		var save_button = $('<button class="btn btn-default" type="submit">Save</button>', {
			'class': 'btn btn-default',
			'type': 'submit',
			'text': config.save_text
		});
		save_button.click(function() {
			$('#loader').show();
			$.ajax({
				url: '',
				'type': 'post',
				data: {sources: $sources},
				dataType: 'json'
			})
				.always(function() { $('#loader').hide() })
				.fail(function() { alert('error while saving data'); })
				.done(function(data) {
					console.log(data);
					_obj.html('').yiiMessages(data);
				});

			return false;
		});
		$('th:eq(1)', _tfoot).append(save_button);

		_table.append(_tfoot);
		_obj.append(_table);

		_table.editableTableWidget({
			canEdit: function(_td) {
				return (_td.data('editable') || _td.data('lang')) && !_td.closest('tr').data('deleted');
			}
		});
	}


})(window.jQuery);