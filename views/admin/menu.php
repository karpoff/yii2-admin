
<div class="tabs admin-menu" id="menu<?=$id?>">
	<ul><? foreach ($items as $item) { ?>
		<li<? if (!empty($item['_fixed'])) echo ' class="fixed"'; if (!empty($item['id'])) echo ' data-id="'.$item['id'].'"'; ?>><a data-link href="<?=$item['href']?>"><?=$item['title']?></a></li>
	<? } ?></ul>
</div>

<script type="text/javascript">
	$(document).ready(function() {
		var _tabs = $('#menu<?=$id?>');
		_tabs.tabs({
			activate: function(event, ui) {
				if (ui.oldPanel)
					ui.oldPanel.html('');
			}
		}).addClass( "ui-helper-clearfix");
		$('li', _tabs).removeClass( "ui-corner-top" ).addClass( "ui-corner-left").on('form.updated', function(event, data) {
			data = $.parseJSON(data);
			if ($(this).is('[data-id]')) {
				var _li = $(this);
				// category name
				if (_li.hasClass('fixed'))
					_li = $('ul:first li[aria-controls="'+_li.closest('ui-tabs-panel').attr('id')+'"]', _li.closest('ui-tabs').closest('ui-tabs'));

				$('a', _li).html(data.title);
			} else {
				$(this).before($('<li>', {
					'data-id': data['id'],
					html: $('<a>', {
						'href': data['href'].split('&amp;').join('&'),
						'text': data['title']
					})
				}));
				_tabs.tabs("refresh");
				_tabs.tabs("option", "active", -2);
			}
		});

		_tabs.find(".ui-tabs-nav").sortable({
			axis: "y",
			stop: function (e, ui) {
				_tabs.tabs("refresh");

				var index = ui.item.index();
				var item = ui.item;

				<? if ($id) { ?>index--;<? } ?>
				$.get('<?=$sort?>', {id: ui.item.attr('data-id'), sort: index});
			},
			items: "li:not(.fixed)"
		});
	});
</script>
