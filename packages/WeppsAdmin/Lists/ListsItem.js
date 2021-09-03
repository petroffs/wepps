var readyListsItemInit = function() {
	$('form.list-data').find('.field-translit').off('click');
	$('form.list-data').find('.field-translit').on('click',function(event) {
		event.preventDefault();
		var source = $(this).closest('.item').siblings('.item[data-id="Name"]').find('input').eq(0);
		var dest = $(this).closest('.item').find('input').eq(0);
		if (dest.val()=='') dest.val(urlRusLat(source.val()));
	});
	$('form.list-data').find('.list-item-date').find('input').datepicker({
		dateFormat: "yy-mm-dd"
	},$.datepicker.regional[ "ru" ]);
	
	$('form.list-data').find('.field-ve').off('click');
	$('form.list-data').find('.field-ve').on('click',function(event) {
		event.preventDefault();
		var dest = $(this).closest('.item').find('textarea').eq(0).attr('id');
		var tinymceOpen = 1;
		if (tinymce.editors.length) {
			for (i=0; i < tinyMCE.editors.length; i++){
				if (tinymce.editors[i].id == dest) {
					tinymce.remove('#'+tinymce.editors[i].id);
					tinymceOpen = 0;
				}
			}
		}
		if (tinymceOpen == 1) {
			tinymce.init({
				  selector: '#'+dest,
				  language: 'ru',
				  language_url : '/packages/vendor_local/tinymce_pps/languages/ru.js',
				  height: 500,
				  menubar: 'insert',
				  convert_urls: false,
				  entity_encoding : "raw",
				  fontsize_formats: "12px 13px 15px 18px 20px 24px 36px",			  
				  plugins: 
				    ' autolink lists link image charmap print preview anchor textcolor ' +
				    'searchreplace visualblocks code fullscreen ' +
				    'insertdatetime media table contextmenu paste code help'
				  ,
				  contextmenu: "cut copy paste | link image table",
				  content_css: [
						'/packages/vendor_local/tinymce_pps/styles.css'
					  ],
				  style_formats: [
						{title: "Headers", items: [
						{title: "Header 1", format: "h1"},
						{title: "Header 2", format: "h2"},
						{title: "Header 3", format: "h3"},
						{title: "Header 4", format: "h4"},
						{title: "Header 5", format: "h5"},
						{title: "Header 6", format: "h6"}
					]},
						{title: "Inline", items: [
						{title: "Bold", icon: "bold", format: "bold"},
						{title: "Italic", icon: "italic", format: "italic"},
						{title: "Underline", icon: "underline", format: "underline"},
						{title: "Strikethrough", icon: "strikethrough", format: "strikethrough"},
						{title: "Superscript", icon: "superscript", format: "superscript"},
						{title: "Subscript", icon: "subscript", format: "subscript"},
						{title: "Code", icon: "code", format: "code"}
					]},
						{title: "Blocks", items: [
						{title: "Paragraph", format: "p"},
						{title: "Blockquote", format: "blockquote"},
						{title: "Div", format: "div"},
						{title: "Pre", format: "pre"}
					]},
						{title: "Alignment", items: [
						{title: "Left", icon: "alignleft", format: "alignleft"},
						{title: "Center", icon: "aligncenter", format: "aligncenter"},
						{title: "Right", icon: "alignright", format: "alignright"},
						{title: "Justify", icon: "alignjustify", format: "alignjustify"}
					]},
						{title: "Мой стиль", items: [
						{title: "Видео блок", selector:'p', classes:'video'},
						{title: "Желтая плашка", selector:'p', classes:'mkcite1'},
						{title: "Стиль 1: Заголовок", selector:'p', classes:'style1header'},
						{title: "Стиль 1: Текст", selector:'p', classes:'style1text'},
						{title: "Цитата серая плашка", selector:'p', classes:'style2cite'},
						{title: "Кнопка-ссылка", selector:'a', classes:'hrefbutton'}
					]},
					],
					menubar: 'edit insert format table tc help',
				    toolbar: 'styleselect alignleft aligncenter alignright alignjustify bullist numlist outdent indent',
					//toolbar: []
				});
		}
	});
	$('form.list-data').find('a.files-item-copy-link').off('click');
	$('form.list-data').find('a.files-item-copy-link').on('click',function(event) {
		event.preventDefault();
		var element = $(this).closest('.files-item').find('input').eq(0);
		element.select();
		document.execCommand("copy");
		$("#dialog").html('<p>Ссылка на файл скопирована</p>').dialog({
			'title':'Сообщение',
			'modal': true,
			'buttons':[]
		});
		setTimeout(function() {
			$("#dialog").dialog('close');
		},1500);
	});
	$('form.list-data').find('a.files-item-remove-link').off('click');
	$('form.list-data').find('a.files-item-remove-link').on('click',function(event) {
		event.preventDefault();
		var parent1 = $(this).closest('.files-item');
		var element = parent1.find('input').eq(0);
		element.select();
		$("#dialog").html('<p>Вы действительно желаете удалить файл: '+parent1.data('title')+'?</p>').dialog({
			'title':'Внимание!',
			'modal': true,
			'buttons' : [{
				text : "Удалить",
				icon : "ui-icon-close",
				click : function() {
					var str = 'action=fileRemove&id='+parent1.data('id');
					layoutWepps.request(str, '/packages/WeppsAdmin/Lists/Request.php');
					//console.log('удаление файла, реальное (из базы, из фс)');
					$(this).dialog("close");
					element.closest('.files-item').remove();
					
				}
			},{
				text : "Отмена",
				click : function() {
					$(this).dialog("close");
				}
			}]
		});
	});
	$('form.list-data').find('a.file-remove').off('click');
	$('form.list-data').find('a.file-remove').on('click',function(event) {
		event.preventDefault();
		let item = $(this).closest('.item');
		let str = 'action=uploadRemove&filesfield='+item.data('id')+'&filename='+$(this).attr('rel')
		$(this).parent().remove();
		layoutWepps.request(str, '/packages/WeppsAdmin/Lists/Request.php');
		
	});
	$('form.list-data').find('a.list-item-save').off('click');
	$('form.list-data').find('a.list-item-save').on('click',function(event) {
		event.preventDefault();
		let element = $(this).closest('form');
		element.submit();
	});
	
	$('form.list-data').find('a.list-item-copy').off('click');
	$('form.list-data').find('a.list-item-copy').on('click',function(event) {
		event.preventDefault();
		
		let element = $(this).closest('form');
		
		$("#dialog").html('<p>Копировать этот элемент?</p>').dialog({
			'title':'Внимание!',
			'modal': true,
			'buttons' : [{
				text : "Копировать",
				icon : "ui-icon-check",
				click : function() {
					let tableName = element.find('input[name="pps_tablename"]').val();  
					let tableNameId = element.find('input[name="pps_tablename_id"]').val();
					let path = element.find('input[name="pps_path"]').eq(0).val();
					let str = 'action=copy&list='+tableName+'&id='+tableNameId+'&pps_path='+path;
					layoutWepps.request(str, '/packages/WeppsAdmin/Lists/Request.php');
					$(this).dialog("close");
				}
			},{
				text : "Отмена",
				click : function() {
					$(this).dialog("close");
				}
			}]
		});
		
		
		
	});
	
	
	$('form.list-data').find('a.list-item-remove').off('click');
	$('form.list-data').find('a.list-item-remove').on('click',function(event) {
		event.preventDefault();
		var element = $(this).closest('form').eq(0);
		$("#dialog").html('<p>Вы действительно желаете удалить этот элемент?</p>').dialog({
			'title':'Внимание!',
			'modal': true,
			'buttons' : [{
				text : "Удалить",
				icon : "ui-icon-close",
				click : function() {
					let id = element.find('input[name="pps_tablename_id"]').eq(0).val();
					let list = element.find('input[name="pps_tablename"]').eq(0).val();
					let path = element.find('input[name="pps_path"]').eq(0).val();
					let str = 'action=remove&id='+id+'&list='+list+'&pps_path='+path;
					layoutWepps.request(str, '/packages/WeppsAdmin/Lists/Request.php');
					$(this).dialog("close");
				}
			},{
				text : "Отмена",
				click : function() {
					$(this).dialog("close");
				}
			}]
		});
	});
	$('form.list-data').find('.list-item-properties').off('change');
	$('form.list-data').find('.list-item-properties').on('change',function(event) {
		$('form.list-data').find('a.list-item-save').trigger('click');
	});
	$('form.list-data').find('.properties-item-option-add').on('click',function(event) {
		event.preventDefault();
		var select1 = $(this).closest('.labels2').find('label.pps_select').find('select').eq(0);
		var input1 = $(this).closest('.labels2').find('label.pps_input').find('input').eq(0);
		
		var id = input1.data('id');
		if (input1.val()=='') {
			$("#dialog").html('<p>Введите значение опции</p>').dialog({
				'title':'Ошибка',
				'modal': true,
				'buttons':[]
			});
			return;
		}
		var str = 'action=propOptionAdd&id='+id+'&value='+input1.val();
		if (select1.find("option[value='" + input1.val() + "']").length) {
			$("#dialog").html('<p>Опция уже существует</p>').dialog({
				'title':'Ошибка',
				'modal': true,
				'buttons':[]
			});
			return;
		} else {
			select1.append("<option value=\""+input1.val()+"\" selected=\"selected\">"+input1.val()+"</option>");
			layoutWepps.request(str, '/packages/WeppsAdmin/Lists/Request.php');
			input1.val('');
	    }
		$("#dialog").html('<p>Опция добавлена</p>').dialog({
			'title':'Сообщение',
			'modal': true,
			'buttons':[]
		});
		setTimeout(function() {
			$("#dialog").dialog('close');
			input1.focus();
		},1500);
	});
	$('form.list-data').find('.controls-tabs').find('a').on('click',function(event) {
		event.preventDefault();
		var group1 = $(this).data('id');
		var siblings1 = $(this).siblings('a'); 
		siblings1.removeClass('active');
		siblings1.find('i.fa-caret-down').addClass('fa-caret-right');
		siblings1.find('i.fa-caret-down').removeClass('fa-caret-down');
		$(this).find('i').addClass('fa-caret-down');
		$(this).find('i').removeClass('fa-caret-right');
		$(this).addClass('active');
		
		if (group1=='FieldAll') {
			var fields1 = $('form.list-data').find('.item[data-group]');
			fields1.removeClass('pps_hide');
		} else {
			var fields1 = $('form.list-data').find('.item[data-group]');
			fields1.addClass('pps_hide');
			var fields1 = $('form.list-data').find('.item[data-group="'+group1+'"]');
			fields1.removeClass('pps_hide');
		}
	});
	if ($('form.list-data').find('.controls-tabs').find('a').eq(1)) {
		$('form.list-data').find('.controls-tabs').find('a').eq(1).trigger('click');
	}
	$('form.list-data').find('.files').sortable({
      placeholder: "sortable-active",
      axis: "y",
      update: function( event, ui ) {
    	  let items = ui.item.parent()
    	  var str = '';
    	  items.children().each(function(index) {
    		  //console.log($(this).data('id'));
    		  str += $(this).data('id')+',';
    	  });
    	  str = str.substr(0,str.length - 1);
    	  //console.log(str);
    	  str = 'action=fileSortable&id='+str;
    	  layoutWepps.request(str, '/packages/WeppsAdmin/Lists/Request.php');
      }
    });
	$('form.list-data').find('.files').disableSelection();
}

$(document).ready(readyListsItemInit);

function urlRusLat(str) {
	str = str.toLowerCase();
	var cyr2latChars = new Array(
		['а', 'a'], ['б', 'b'], ['в', 'v'], ['г', 'g'],
		['д', 'd'],  ['е', 'e'], ['ё', 'yo'], ['ж', 'zh'], ['з', 'z'],
		['и', 'i'], ['й', 'y'], ['к', 'k'], ['л', 'l'],
		['м', 'm'],  ['н', 'n'], ['о', 'o'], ['п', 'p'],  ['р', 'r'],
		['с', 's'], ['т', 't'], ['у', 'u'], ['ф', 'f'],
		['х', 'h'],  ['ц', 'c'], ['ч', 'ch'],['ш', 'sh'], ['щ', 'shch'],
		['ъ', ''],  ['ы', 'y'], ['ь', ''],  ['э', 'e'], ['ю', 'yu'], ['я', 'ya'],
		
		['А', 'A'], ['Б', 'B'],  ['В', 'V'], ['Г', 'G'],
		['Д', 'D'], ['Е', 'E'], ['Ё', 'YO'],  ['Ж', 'ZH'], ['З', 'Z'],
		['И', 'I'], ['Й', 'Y'],  ['К', 'K'], ['Л', 'L'],
		['М', 'M'], ['Н', 'N'], ['О', 'O'],  ['П', 'P'],  ['Р', 'R'],
		['С', 'S'], ['Т', 'T'],  ['У', 'U'], ['Ф', 'F'],
		['Х', 'H'], ['Ц', 'C'], ['Ч', 'CH'], ['Ш', 'SH'], ['Щ', 'SHCH'],
		['Ъ', ''],  ['Ы', 'Y'],
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
		
		['A', 'A'], ['B', 'B'], ['C', 'C'], ['D', 'D'],['E', 'E'],
		['F', 'F'],['G', 'G'],['H', 'H'],['I', 'I'],['J', 'J'],['K', 'K'],
		['L', 'L'], ['M', 'M'], ['N', 'N'], ['O', 'O'],['P', 'P'],
		['Q', 'Q'],['R', 'R'],['S', 'S'],['T', 'T'],['U', 'U'],['V', 'V'],
		['W', 'W'], ['X', 'X'], ['Y', 'Y'], ['Z', 'Z'],
		
		[' ', '-'],['0', '0'],['1', '1'],['2', '2'],['3', '3'],
		['4', '4'],['5', '5'],['6', '6'],['7', '7'],['8', '8'],['9', '9'],
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
        }
        newStr += newCh;
    }
    return newStr.replace(/[_]{2,}/gim, '_').replace(/\n/gim, '');
}

var getSelectRemote = function(obj) {
	let id = obj.id
	let url = obj.url
	$(id).select2({
		language: "ru",
		ajax: {
			headers: {
		        "ClientApiEmail" : '',
		        "ClientApiToken" : '',
		    },
			url: url,
			//delay: 500,
			dataType: 'json',
			data: function(params) {
				var query = {
					search: params.term,
					page: params.page || 1
				}
				return query;
			},
		}
	});
	//console.log ($(id).select2("destroy"));
}