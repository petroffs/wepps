var readyBackupExtInit = function() {
	$('*[data-file]').on('click',function(e) {
		var file = $(this).data('file');
		var fileRestore = $(this).data('restore');
		var fileRemove = $(this).data('remove');
		let dialogWidth = (window.screen.width<400) ? '90%' : 400;
		$("#dialog").html('<p>Выберите действие с файлом бекапа:<br/>'+file+'?</p>').dialog({
			title:'Внимание!',
			modal: true,
			resizable: false,
			width: dialogWidth,
			buttons : [{
				text : "Восстановить",
				icon : "ui-icon-check",
				click : function() {
					let str = 'action='+fileRestore+'&id='+file+'&form=list-data-form';
					let settings = {
						data:str,
						url:'/packages/WeppsAdmin/ConfigExtensions/Backup/Request.php'
					}
					layoutWepps.request(settings);
					$(this).dialog("close");
				}
			},{
				text : "Удалить",
				icon : "ui-icon-trash",
				click : function() {
					let str = 'action='+fileRemove+'&id='+file+'&form=list-data-form';
					let settings = {
						data:str,
						url:'/packages/WeppsAdmin/ConfigExtensions/Backup/Request.php'
					}
					layoutWepps.request(settings);
					$(this).dialog("close");
				}
			}]
		});
	});
	$('#backupListStructure').on('click',function(e) {
		e.preventDefault();
		let id = $('#lists').val();
		let str = 'action=list&id='+id;
		window.location.href = '/packages/WeppsAdmin/ConfigExtensions/Backup/Request.php?' + str;
	});
	$('#backupListData').on('click',function(e) {
		e.preventDefault();
		let id = $('#lists').val();
		window.location.href = '/packages/WeppsAdmin/Lists/Request.php?action=export&list=' + id;
		return false;
	});
}
$(document).ready(readyBackupExtInit);