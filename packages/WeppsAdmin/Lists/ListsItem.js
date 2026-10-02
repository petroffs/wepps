var trimBr = function (str) {
	return str.replace(/^(<br\s*\/?>)+|(<br\s*\/?>)+$/gi, ' ');
};
var readyListsItemInit = function () {
	$('form.list-data').find('a.list-item-save').off('click');
	$('form.list-data').find('a.list-item-save').on('click', function (event) {
		event.preventDefault();
		$('.minitable.minitable-active').each(function (i, e) {
			let rows = $(e).find('.minitable-body');
			var str = '';
			rows.each(function (k, v) {
				let cells = $(v).find('[contenteditable]');
				cells.each(function (k2, v2) {
					str += trimBr($(v2).html()) + ':::';
				});
				str = str.substring(0, str.length - 3);
				str += "\n";
			});
			str = str.substring(0, str.length - 1);
			$('#formArea' + $(e).data('field')).val(str);
		});
		let element = $(this).closest('form');
		element.submit();
	});
	$(document).keydown(function (event) {
		if (event.ctrlKey && event.which === 83) {
			$('form.list-data').find('a.list-item-save').eq(0).trigger('click');
			event.preventDefault();
		}
	});
	$('form.list-data').find('a.list-item-copy').off('click');
	$('form.list-data').find('a.list-item-copy').on('click', function (event) {
		event.preventDefault();
		let element = $(this).closest('form');
		$("#dialog").html('<p>Копировать этот элемент?</p>').dialog({
			'title': 'Внимание!',
			'modal': true,
			'buttons': [{
				text: "Копировать",
				icon: "ui-icon-check",
				click: function () {
					let tableName = element.find('input[name="w_tablename"]').val();
					let tableNameId = element.find('input[name="w_tablename_id"]').val();
					let path = element.find('input[name="w_path"]').eq(0).val();
					let str = 'action=copy&list=' + tableName + '&id=' + tableNameId + '&w_path=' + path;
					let settings = {
						data: str,
						url: '/packages/WeppsAdmin/Lists/Request.php'
					};
					layoutWepps.request(settings);
					$(this).dialog("close");
				}
			}, {
				text: "Отмена",
				click: function () {
					$(this).dialog("close");
				}
			}]
		});
	});
	$('form.list-data').find('a.list-item-remove').off('click');
	$('form.list-data').find('a.list-item-remove').on('click', function (event) {
		event.preventDefault();
		var element = $(this).closest('form').eq(0);
		$("#dialog").html('<p>Вы действительно желаете удалить этот элемент?</p>').dialog({
			'title': 'Внимание!',
			'modal': true,
			'buttons': [{
				text: "Удалить",
				icon: "ui-icon-close",
				click: function () {
					let id = element.find('input[name="w_tablename_id"]').eq(0).val();
					let list = element.find('input[name="w_tablename"]').eq(0).val();
					let path = element.find('input[name="w_path"]').eq(0).val();
					let str = 'action=remove&id=' + id + '&list=' + list + '&w_path=' + path;
					let settings = {
						data: str,
						url: '/packages/WeppsAdmin/Lists/Request.php',
					};
					layoutWepps.request(settings);
					$(this).dialog("close");
				}
			}, {
				text: "Отмена",
				click: function () {
					$(this).dialog("close");
				}
			}]
		});
	});
	$('form.list-data').find('.field-translit').off('click');
	$('form.list-data').find('.field-translit').on('click', function (event) {
		event.preventDefault();
		var source = $(this).closest('.item').siblings('.item[data-id="Name"]').find('input').eq(0);
		var dest = $(this).closest('.item').find('input').eq(0);
		if (dest.val() == '') dest.val(urlRusLat(source.val()));
	});
	$('form.list-data').find('select[name="list-item-language"]').off('select2:select');
	$('form.list-data').find('select[name="list-item-language"]').on('select2:select', function (event) {
		$(this).trigger('change');
		console.log($(this).val())
	});
	$('form.list-data').find('.list-item-date').find('input').datepicker({
		dateFormat: "yy-mm-dd"
	}, $.datepicker.regional["ru"]);
	$('form.list-data').find('.list-item-properties').off('change');
	$('form.list-data').find('.list-item-properties').on('change', function (event) {
		$('form.list-data').find('a.list-item-save').trigger('click');
	});
	$('form.list-data').find('.properties-item-option-add').on('click', function (event) {
		event.preventDefault();
		var select1 = $(this).closest('.labels2').find('label.w_select').find('select').eq(0);
		var input1 = $(this).closest('.labels2').find('label.w_input').find('input').eq(0);

		var id = input1.data('id');
		if (input1.val() == '') {
			$("#dialog").html('<p>Введите значение опции</p>').dialog({
				'title': 'Ошибка',
				'modal': true,
				'buttons': []
			});
			return;
		};
		var str = 'action=propOptionAdd&id=' + id + '&value=' + input1.val();
		if (select1.find("option[value='" + input1.val() + "']").length) {
			$("#dialog").html('<p>Опция уже существует</p>').dialog({
				'title': 'Ошибка',
				'modal': true,
				'buttons': []
			});
			return;
		} else {
			select1.append("<option value=\"" + input1.val() + "\" selected=\"selected\">" + input1.val() + "</option>");
			let settings = {
				data: str,
				url: '/packages/WeppsAdmin/Lists/Request.php',
			};
			layoutWepps.request(settings);
			input1.val('');
		};
		$("#dialog").html('<p>Опция добавлена</p>').dialog({
			'title': 'Сообщение',
			'modal': true,
			'buttons': []
		});
		setTimeout(function () {
			$("#dialog").dialog('close');
			input1.focus();
		}, 1500);
	});
	$('form.list-data').find('.controls-tabs').find('a').on('click', function (event) {
		event.preventDefault();
		var group1 = $(this).data('id');
		var siblings1 = $(this).siblings('a');
		siblings1.removeClass('active');
		siblings1.find('i.bi-caret-down').addClass('bi-caret-right');
		siblings1.find('i.bi-caret-down').removeClass('bi-caret-down');
		$(this).find('i').addClass('bi-caret-down');
		$(this).find('i').removeClass('bi-caret-right');
		$(this).addClass('active');

		if (group1 == 'FieldAll') {
			var fields1 = $('form.list-data').find('.item[data-group]');
			fields1.removeClass('w_hide');
		} else {
			var fields1 = $('form.list-data').find('.item[data-group]');
			fields1.addClass('w_hide');
			var fields1 = $('form.list-data').find('.item[data-group="' + group1 + '"]');
			fields1.removeClass('w_hide');
		}
	});
};
// Ссылка на CSS внутри content-iframe с cache-busting: $rand берём из
// data-headers-rand на <body> (Admin.php -> Admin.tpl), а путь file.RAND.css
// срезается правилом .htaccess обратно до file.css
var wIframeCssUrl = function (path, name) {
	var rand = document.body.getAttribute('data-headers-rand') || '';
	return path + name + (rand ? '.' + rand : '') + '.css';
};
// Текущая тема редактора — по data-theme админки (light/dark)
var wJoditTheme = function () {
	return document.documentElement.getAttribute('data-theme') === 'dark' ? 'dark' : 'default';
};
// Синхронизация темы у всех открытых редакторов
var wJoditApplyTheme = function () {
	if (!window.Jodit) {
		return;
	}
	var theme = wJoditTheme();
	Object.keys(Jodit.instances).forEach(function (id) {
		var inst = Jodit.instances[id];
		if (!inst) {
			return;
		}
		inst.o.theme = theme;
		// data-theme внутрь content-iframe — стили контента по теме
		try {
			var doc = inst.iframe && inst.iframe.contentDocument;
			if (doc && doc.documentElement) {
				doc.documentElement.setAttribute('data-theme', theme === 'dark' ? 'dark' : 'light');
			}
		} catch (e) {}
	});
	// Контейнеры редакторов, открытые диалоги и попапы
	Array.prototype.forEach.call(
		document.querySelectorAll('.jodit_theme_default, .jodit_theme_dark'),
		function (el) {
			el.classList.remove('jodit_theme_default', 'jodit_theme_dark');
			el.classList.add(theme === 'dark' ? 'jodit_theme_dark' : 'jodit_theme_default');
		}
	);
};
var readyListsItemVEInit = function () {
	// Переключение темы админки меняет тему уже открытых редакторов
	if (window.MutationObserver && !window.__wJoditThemeObserver) {
		window.__wJoditThemeObserver = new MutationObserver(wJoditApplyTheme);
		window.__wJoditThemeObserver.observe(document.documentElement, {
			attributes: true,
			attributeFilter: ['data-theme']
		});
	}
	$('form.list-data').find('.field-ve').off('click');
	$('form.list-data').find('.field-ve').on('click', function (event) {
		event.preventDefault();
		// Ищем только textarea с id: зеркало Jodit (jodit-source__mirror) id не имеет
		var dest = $(this).closest('.item').find('textarea[id]').eq(0).attr('id');
		if (!dest || !window.Jodit) {
			return;
		}
		// Повторный клик по кнопке — закрыть редактор
		if (Jodit.instances[dest]) {
			Jodit.instances[dest].destruct();
			var $taClosed = $('#' + dest);
			var $labelClosed = $taClosed.closest('label.w_label');
			if (!$labelClosed.length) {
				// textarea вынесена из label при открытии — возвращаем обратно
				$labelClosed = $taClosed.siblings('label.w_label').first();
				if ($labelClosed.length) {
					$taClosed.appendTo($labelClosed);
				}
			}
			$labelClosed.removeClass('w_hide');
			$taClosed.show();
			return;
		}
		// Выносим textarea из label ДО инициализации: Jodit вставляет контейнер
		// перед textarea, и он попадает сразу вне label (стили label.w_label
		// не перебивают стили редактора). Переносить контейнер ПОСЛЕ make
		// нельзя: это перезагружает его iframe и ломает ввод текста.
		var $ta = $('#' + dest);
		var $label = $ta.closest('label.w_label');
		if ($label.length) {
			$ta.insertAfter($label);
			$label.addClass('w_hide');
		}
		var editor = Jodit.make('#' + dest, {
			theme: wJoditTheme(),
			height: 500,
			language: 'ru',
			// Весь набор кнопок всегда виден: без адаптивной подмены и «трoеточия»
			toolbarAdaptive: false,
			// Как и TinyMCE: контент в изолированном iframe + свои content-стили
			iframe: true,
			// Settings.css — системные css-переменные (--font, --color-*, --s*),
			// styles.css — стили контента; обе со схемой file.RAND.css
			iframeCSSLinks: [
				wIframeCssUrl('/packages/WeppsAdmin/Admin/Layout/', 'Settings'),
				wIframeCssUrl('/packages/vendor_local/jodit_wepps/', 'styles')
			],
			buttons: [
				'paragraph', 'bold', 'italic', 'underline', 'strikethrough', '|',
				'align', 'ul', 'ol', 'outdent', 'indent', 'mystyles', '|',
				'link', 'image', 'table', '|',
				'fullsize', 'source'
			],
			controls: {
				// Аналог TinyMCE style_formats, раздел «Мой стиль»
				mystyles: {
					icon: 'class-span',
					tooltip: 'Мой стиль',
					list: {
						video: 'Видео блок',
						mkcite1: 'Желтая плашка',
						style1header: 'Стиль 1: Заголовок',
						style1text: 'Стиль 1: Текст',
						style2cite: 'Цитата серая плашка',
						hrefbutton: 'Кнопка-ссылка'
					},
					childExec: function (editor, current, options) {
						var cls = options.control.args && options.control.args[0];
						if (!cls) {
							return;
						}
						// Как и в TinyMCE: класс вешаем на <p>, для кнопки-ссылки — на <a>
						var tag = (cls === 'hrefbutton') ? 'A' : 'P';
						var node = editor.s.current();
						var el = (node && node.nodeType === 1) ? node : (node ? node.parentNode : null);
						var root = editor.editor;
						var target = null;
						while (el && el !== root) {
							if (el.tagName === tag) {
								target = el;
								break;
							}
							el = el.parentNode;
						}
						if (!target) {
							return;
						}
						if (target.classList.contains(cls)) {
							target.classList.remove(cls);
						} else {
							target.classList.add(cls);
						}
					}
				}
			}
		});
		// textarea уже вынесена из label, контейнер Jodit вставлен сразу вне
		// label — переносить его после make нельзя (перезагрузка iframe)
		// data-theme внутрь content-iframe для стилей контента по теме
		wJoditApplyTheme();
	});
};
var readyListsItemFilesInit = function () {
	$('form.list-data').find('.field-file-select').off('click');
	$('form.list-data').find('.field-file-select').on('click', function (event) {
		event.preventDefault();
		var status = ($(this).data('status') == 0) ? 1 : 0;
		if ($(this).data('status') == 0) {
			status = 1;
			$(this).addClass('active');
			$('.field-file-action').removeClass('w_hide');
		} else {
			status = 0;
			$(this).removeClass('active');
			$('.field-file-action').addClass('w_hide');
		};
		$(this).data('status', status);
	});
	$('form.list-data').find('.files-upload').off('click');
	$('form.list-data').find('.files-upload').on('click', function (event) {
		var status = $(this).closest('.item').find('.field-file-select').eq(0).data('status');
		if (status == 1) {
			event.preventDefault();
			var el = $(this).closest('.files-item');
			if (el.hasClass('active')) {
				el.removeClass('active');
			} else {
				el.addClass('active');
			};
			return;
		};
	});
	$('form.list-data').find('.field-file-edit').off('click');
	$('form.list-data').find('.field-file-edit').on('click', function (event) {
		event.preventDefault();
		el = $('.files-item.active');
		var ids = '';
		el.each(function (i, e) {
			ids += $(e).data('id') + ','
		});
		ids = ids.substr(0, ids.length - 1);
		$('#dialog').html('<p>Описание выбранных файлов:</p><p><label class="w_label w_input" style="min-width:calc(100% - 10px)"><input type="text" id="file-input-edit"></label></p>').dialog({
			'title': 'Сообщение',
			'modal': true,
			'buttons': [
				{
					text: 'Сохранить',
					icon: 'ui-icon-check',
					click: function () {
						let text = $('#file-input-edit').val();
						el.each(function (i, e) {
							let id = '.files-item[data-id="' + $(e).data('id') + '"';
							$(id).find('div.descr').addClass('descr-fill');
							$(id).find('div.descr > div.input + div').text(text);
						});
						let str = 'action=fileDescription&ids=' + ids + '&text=' + text;
						let settings = {
							data: str,
							url: '/packages/WeppsAdmin/Lists/Request.php'
						};
						layoutWepps.request(settings);
						$(this).dialog('close');
					}
				}, {
					text: 'Отмена',
					click: function () {
						$(this).dialog('close');
					}
				}]
		});
	});
	$('form.list-data').find('.field-file-remove').off('click');
	$('form.list-data').find('.field-file-remove').on('click', function (event) {
		event.preventDefault();
		el = $('.files-item.active');
		var ids = '';
		el.each(function (i, e) {
			ids += $(e).data('id') + ','
		});
		ids = ids.substr(0, ids.length - 1);
		//console.log(ids);
		$('#dialog').html('<p>Удалить выбранные файлы?</p>').dialog({
			'title': 'Сообщение',
			'modal': true,
			'buttons': [
				{
					text: 'Удалить',
					icon: 'ui-icon-trash',
					click: function () {
						el.each(function (i, e) {
							$(e).remove();
						});
						$(this).dialog("close");
						$('form.list-data').find('.field-file-select.active').trigger('click');
						let str = 'action=fileRemove&id=' + ids;
						let settings = {
							data: str,
							url: '/packages/WeppsAdmin/Lists/Request.php'
						};
						layoutWepps.request(settings);
					}
				}, {
					text: 'Отмена',
					click: function () {
						$(this).dialog('close');
					}
				}]
		});
	});
	$('form.list-data').find('a.files-item-copy-link').off('click');
	$('form.list-data').find('a.files-item-copy-link').on('click', function (event) {
		event.preventDefault();
		var element = $(this).closest('.files-item').find('input').eq(0);
		element.select();
		document.execCommand("copy");
		$("#dialog").html('<p>Ссылка на файл скопирована</p>').dialog({
			'title': 'Сообщение',
			'modal': true,
			'buttons': []
		});
		setTimeout(function () {
			$("#dialog").dialog('close');
		}, 1500);
	});
	$('form.list-data').find('a.files-item-remove-link').off('click');
	$('form.list-data').find('a.files-item-remove-link').on('click', function (event) {
		event.preventDefault();
		var parent1 = $(this).closest('.files-item');
		var element = parent1.find('input').eq(0);
		element.select();
		$("#dialog").html('<p>Вы действительно желаете удалить файл: ' + parent1.data('title') + '?</p>').dialog({
			'title': 'Внимание!',
			'modal': true,
			'buttons': [{
				text: "Удалить",
				icon: "ui-icon-close",
				click: function () {
					let str = 'action=fileRemove&id=' + parent1.data('id');
					let settings = {
						data: str,
						url: '/packages/WeppsAdmin/Lists/Request.php'
					};
					layoutWepps.request(settings);
					//console.log('удаление файла, реальное (из базы, из фс)');
					$(this).dialog("close");
					element.closest('.files-item').remove();
				}
			}, {
				text: "Отмена",
				click: function () {
					$(this).dialog("close");
				}
			}]
		});
	});
	$('form.list-data').find('a.file-remove').off('click');
	$('form.list-data').find('a.file-remove').on('click', function (event) {
		event.preventDefault();
		let item = $(this).closest('.item');
		let str = 'action=uploadRemove&filesfield=' + item.data('id') + '&filename=' + $(this).attr('rel');
		$(this).parent().remove();
		let settings = {
			data: str,
			url: '/packages/WeppsAdmin/Lists/Request.php'
		};
		layoutWepps.request(settings);
	});
	if ($('form.list-data').find('.controls-tabs').find('a').eq(1)) {
		$('form.list-data').find('.controls-tabs').find('a').eq(1).trigger('click');
	};
	$('form.list-data').find('.files').sortable({
		placeholder: "sortable-active",
		update: function (event, ui) {
			let items = ui.item.parent();
			var str = '';
			items.children().each(function (index) {
				//console.log($(this).data('id'));
				str += $(this).data('id') + ',';
			});
			str = str.substr(0, str.length - 1);
			str = 'action=fileSortable&id=' + str;
			let settings = {
				data: str,
				url: '/packages/WeppsAdmin/Lists/Request.php'
			};
			layoutWepps.request(settings);
		}
	});
	$('form.list-data').find('.files').disableSelection();
};
var readyListsItemMinitableInit = function () {
	$('form.list-data').find('a.minitable-remove').off('click');
	$('form.list-data').find('a.minitable-remove').on('click', function (event) {
		event.preventDefault();
		console.log('remove');
		$(this).closest('.minitable-body').remove();
	});
	$('form.list-data').find('a.minitable-add').off('click');
	$('form.list-data').find('a.minitable-add').on('click', function (event) {
		event.preventDefault();
		console.log('add');
		let el = $(this).closest('.minitable-headers').siblings('.minitable-body-tpl').eq(0).clone();
		el.removeClass('minitable-body-tpl').addClass('minitable-body');
		$(this).closest('.minitable').append(el);
		readyListsItemMinitableInit();
	});
};
$(document).ready(readyListsItemInit);
$(document).ready(readyListsItemVEInit);
$(document).ready(readyListsItemFilesInit);
$(document).ready(readyListsItemMinitableInit);

function urlRusLat(str) {
	str = str.toLowerCase();
	var cyr2latChars = new Array(
		['а', 'a'], ['б', 'b'], ['в', 'v'], ['г', 'g'],
		['д', 'd'], ['е', 'e'], ['ё', 'yo'], ['ж', 'zh'], ['з', 'z'],
		['и', 'i'], ['й', 'y'], ['к', 'k'], ['л', 'l'],
		['м', 'm'], ['н', 'n'], ['о', 'o'], ['п', 'p'], ['р', 'r'],
		['с', 's'], ['т', 't'], ['у', 'u'], ['ф', 'f'],
		['х', 'h'], ['ц', 'c'], ['ч', 'ch'], ['ш', 'sh'], ['щ', 'shch'],
		['ъ', ''], ['ы', 'y'], ['ь', ''], ['э', 'e'], ['ю', 'yu'], ['я', 'ya'],

		['А', 'A'], ['Б', 'B'], ['В', 'V'], ['Г', 'G'],
		['Д', 'D'], ['Е', 'E'], ['Ё', 'YO'], ['Ж', 'ZH'], ['З', 'Z'],
		['И', 'I'], ['Й', 'Y'], ['К', 'K'], ['Л', 'L'],
		['М', 'M'], ['Н', 'N'], ['О', 'O'], ['П', 'P'], ['Р', 'R'],
		['С', 'S'], ['Т', 'T'], ['У', 'U'], ['Ф', 'F'],
		['Х', 'H'], ['Ц', 'C'], ['Ч', 'CH'], ['Ш', 'SH'], ['Щ', 'SHCH'],
		['Ъ', ''], ['Ы', 'Y'],
		['Ь', ''],
		['Э', 'E'],
		['Ю', 'YU'],
		['Я', 'YA'],

		['a', 'a'], ['b', 'b'], ['c', 'c'], ['d', 'd'], ['e', 'e'],
		['f', 'f'], ['g', 'g'], ['h', 'h'], ['i', 'i'], ['j', 'j'],
		['k', 'k'], ['l', 'l'], ['m', 'm'], ['n', 'n'], ['o', 'o'],
		['p', 'p'], ['q', 'q'], ['r', 'r'], ['s', 's'], ['t', 't'],
		['u', 'u'], ['v', 'v'], ['w', 'w'], ['x', 'x'], ['y', 'y'],
		['z', 'z'],

		['A', 'A'], ['B', 'B'], ['C', 'C'], ['D', 'D'], ['E', 'E'],
		['F', 'F'], ['G', 'G'], ['H', 'H'], ['I', 'I'], ['J', 'J'], ['K', 'K'],
		['L', 'L'], ['M', 'M'], ['N', 'N'], ['O', 'O'], ['P', 'P'],
		['Q', 'Q'], ['R', 'R'], ['S', 'S'], ['T', 'T'], ['U', 'U'], ['V', 'V'],
		['W', 'W'], ['X', 'X'], ['Y', 'Y'], ['Z', 'Z'],

		[' ', '-'], ['0', '0'], ['1', '1'], ['2', '2'], ['3', '3'],
		['4', '4'], ['5', '5'], ['6', '6'], ['7', '7'], ['8', '8'], ['9', '9'],
		['-', '-']
	);

	var newStr = new String();
	for (var i = 0; i < str.length; i++) {
		ch = str.charAt(i);
		var newCh = '';
		for (var j = 0; j < cyr2latChars.length; j++) {
			if (ch == cyr2latChars[j][0]) {
				newCh = cyr2latChars[j][1];
			}
		};
		newStr += newCh;
	};
	return newStr.replace(/[_]{2,}/gim, '_').replace(/\n/gim, '');
};

var getSelectRemote = function (obj) {
	let id = obj.id;
	let url = obj.url;
	let token = obj.token;
	$(id).select2({
		language: "ru",
		ajax: {
			headers: (token) ? { 'Authorization': 'Bearer ' + token } : {},
			url: url,
			//delay: 500,
			dataType: 'json',
			data: function (params) {
				var query = {
					search: params.term,
					page: params.page || 1
				};
				return query;
			},
		}
	});
	// console.log (layoutWepps.token());
};
