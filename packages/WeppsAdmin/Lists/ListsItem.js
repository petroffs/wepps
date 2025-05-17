
var readyListsItemInit = function() {
	$('form.list-data').find('a.list-item-save').off('click');
	$('form.list-data').find('a.list-item-save').on('click',function(event) {
		event.preventDefault();
		$('.minitable').each(function(i,e){
			let rows = $(e).find('.minitable-body');
			var str = '';
			rows.each(function(k,v) {
				let cells = $(v).find('[contenteditable]');
				cells.each(function(k2,v2) {	
					str += $(v2).html()+':::';
				});
				str = str.substring(0,str.length-3);
				str += "\n";
			});
			str = str.substring(0,str.length-1);
			//console.log(str);
			$('#formArea'+$(e).data('field')).val(str);
		});
		//return;
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
					let settings = {
						data:str,
						url:'/packages/WeppsAdmin/Lists/Request.php'
					};
					layoutWepps.request(settings);
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
					let settings = {
						data: str,
						url: '/packages/WeppsAdmin/Lists/Request.php',
					};
					layoutWepps.request(settings);
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
	$('form.list-data').find('.field-translit').off('click');
	$('form.list-data').find('.field-translit').on('click',function(event) {
		event.preventDefault();
		var source = $(this).closest('.item').siblings('.item[data-id="Name"]').find('input').eq(0);
		var dest = $(this).closest('.item').find('input').eq(0);
		if (dest.val()=='') dest.val(urlRusLat(source.val()));
	});
	$('form.list-data').find('select[name="list-item-language"]').off('select2:select');
	$('form.list-data').find('select[name="list-item-language"]').on('select2:select',function(event) {
		$(this).trigger('change');
		console.log($(this).val())
	});
	$('form.list-data').find('.list-item-date').find('input').datepicker({
		dateFormat: "yy-mm-dd"
	},$.datepicker.regional[ "ru" ]);
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
			let settings = {
						data: str,
						url: '/packages/WeppsAdmin/Lists/Request.php',
					}
			layoutWepps.request(settings);
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
}
var readyListsItemVEInit = function() {
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
		};
		if (tinymceOpen == 1) {
			tinymce.init({
				  selector: '#'+dest,
				  language: 'ru',
				  language_url : '/packages/vendor_local/tinymce_wepps/languages/ru.js',
				  height: 500,
				  menubar: 'insert',
				  convert_urls: false,
				  allow_script_urls: true,
				  entity_encoding : "raw",
				  fontsize_formats: "12px 13px 15px 18px 20px 24px 36px",			  
				  plugins: 
				    ' autolink lists link image charmap print preview anchor textcolor ' +
				    'searchreplace visualblocks code fullscreen ' +
				    'insertdatetime media table contextmenu paste code help'
				  ,
				  contextmenu: "cut copy paste | link image table",
				  content_css: [
						'/packages/vendor_local/tinymce_wepps/styles.css'
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
		};
	});
};
var readyListsItemFilesInit = function() {
	$('form.list-data').find('.field-file-select').off('click');
	$('form.list-data').find('.field-file-select').on('click',function(event) {
		event.preventDefault();
		var status = ($(this).data('status')==0)?1:0;
		if ($(this).data('status')==0) {
			status = 1;
			$(this).addClass('active');
			$('.field-file-action').removeClass('pps_hide');
		} else {
			status = 0;
			$(this).removeClass('active');
			$('.field-file-action').addClass('pps_hide');
		}
		$(this).data('status',status);
	});
	$('form.list-data').find('.files-upload').off('click');
	$('form.list-data').find('.files-upload').on('click',function(event) {
		var status = $(this).closest('.item').find('.field-file-select').eq(0).data('status'); 
		if (status==1) {
			event.preventDefault();
			var el =  $(this).closest('.files-item');
			if (el.hasClass('active')) {
				el.removeClass('active');
			} else {
				el.addClass('active');
			}
			return;	
		};
	});
	$('form.list-data').find('.field-file-edit').off('click');
	$('form.list-data').find('.field-file-edit').on('click',function(event) {
		event.preventDefault();
		el = $('.files-item.active');
		var ids = '';
		el.each(function(i,e) {
			ids += $(e).data('id')+','
		});
		ids = ids.substr(0,ids.length-1)
		$('#dialog').html('<p>Описание выбранных файлов:</p><p><label class="pps pps_input" style="min-width:calc(100% - 10px)"><input type="text" id="file-input-edit"></label></p>').dialog({
			'title':'Сообщение',
			'modal': true,
			'buttons' : [
			{
				text : 'Сохранить',
				icon : 'ui-icon-check',
				click : function() {
					let text = $('#file-input-edit').val();
					el.each(function(i,e) {
						let id = '.files-item[data-id="'+$(e).data('id')+'"';
						$(id).find('div.descr').addClass('descr-fill');
						$(id).find('div.descr > div.input + div').text(text);
					});
					let str = 'action=fileDescription&ids='+ids+'&text='+text;
					let settings = {
						data:str,
						url:'/packages/WeppsAdmin/Lists/Request.php'
					};
					layoutWepps.request(settings);
					$(this).dialog('close');
				}
			},{
				text : 'Отмена',
				click : function() {
                    $(this).dialog('close');
				}
			}]
		});
	});
	$('form.list-data').find('.field-file-remove').off('click');
	$('form.list-data').find('.field-file-remove').on('click',function(event) {
		event.preventDefault();
		el = $('.files-item.active');
		var ids = '';
		el.each(function(i,e) {
			ids += $(e).data('id')+','
		});
		ids = ids.substr(0,ids.length-1);
		//console.log(ids);
		$('#dialog').html('<p>Удалить выбранные файлы?</p>').dialog({
			'title':'Сообщение',
			'modal': true,
			'buttons' : [
			{
				text : 'Удалить',
				icon : 'ui-icon-trash',
				click : function() {
					el.each(function(i,e) {
						$(e).remove();
					});
					$(this).dialog("close");
					$('form.list-data').find('.field-file-select.active').trigger('click');
					let str = 'action=fileRemove&id='+ids;
					let settings = {
						data:str,
						url:'/packages/WeppsAdmin/Lists/Request.php'
					};
					layoutWepps.request(settings);
				}
			},{
				text : 'Отмена',
				click : function() {
                    $(this).dialog('close');
				}
			}]
		});
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
					let str = 'action=fileRemove&id='+parent1.data('id');
					let settings = {
						data:str,
						url:'/packages/WeppsAdmin/Lists/Request.php'
					};
					layoutWepps.request(settings);
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
		let str = 'action=uploadRemove&filesfield='+item.data('id')+'&filename='+$(this).attr('rel');
		$(this).parent().remove();
		let settings = {
			data:str,
			url:'/packages/WeppsAdmin/Lists/Request.php'
		};
		layoutWepps.request(settings);
	});
	if ($('form.list-data').find('.controls-tabs').find('a').eq(1)) {
		$('form.list-data').find('.controls-tabs').find('a').eq(1).trigger('click');
	}
	$('form.list-data').find('.files').sortable({
      placeholder: "sortable-active",
      update: function( event, ui ) {
    	  let items = ui.item.parent()
    	  var str = '';
    	  items.children().each(function(index) {
    		  //console.log($(this).data('id'));
    		  str += $(this).data('id')+',';
    	  });
    	  str = str.substr(0,str.length - 1);
    	  str = 'action=fileSortable&id='+str;
		  let settings = {
			  data: str,
			  url: '/packages/WeppsAdmin/Lists/Request.php'
		  };
		  layoutWepps.request(settings);
      }
    });
	$('form.list-data').find('.files').disableSelection();
}
var readyListsItemMinitableInit = function() {
	$('form.list-data').find('a.minitable-remove').off('click');
	$('form.list-data').find('a.minitable-remove').on('click',function(event) {
		event.preventDefault();
		console.log('remove');
		$(this).closest('.minitable-body').remove();
	});
	$('form.list-data').find('a.minitable-add').off('click');
	$('form.list-data').find('a.minitable-add').on('click',function(event) {
		event.preventDefault();
		console.log('add');
		let el = $(this).closest('.minitable-headers').siblings('.minitable-body-tpl').eq(0).clone();
		el.removeClass('minitable-body-tpl').addClass('minitable-body');
		$(this).closest('.minitable').append(el);
		readyListsItemMinitableInit();
	});
}
$(document).ready(readyListsItemInit);
$(document).ready(readyListsItemVEInit);
$(document).ready(readyListsItemFilesInit);
$(document).ready(readyListsItemMinitableInit);

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
};

var getSelectRemote = function(obj) {
	let id = obj.id
	let url = obj.url
	$(id).select2({
		language: "ru",
		ajax: {
			headers: {
		        //"ClientApiEmail" : '',
		        //"ClientApiToken" : '',
		    },
			url: url,
			//delay: 500,
			dataType: 'json',
			data: function(params) {
				var query = {
					search: params.term,
					page: params.page || 1
				};
				return query;
			},
		}
	});
	//console.log ($(id).select2("destroy"));
};