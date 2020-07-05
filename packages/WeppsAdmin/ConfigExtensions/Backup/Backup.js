var readyBackupExtInit = function() {
	$('*[data-file]').on('click',function(e) {
		var file = $(this).data('file');
		var fileRestore = $(this).data('restore');
		var fileRemove = $(this).data('remove');
		
		$("#dialog").html('<p>Выберите действие с файлом бекапа:<br/>'+file+'?</p>').dialog({
			'title':'Внимание!',
			'modal': true,
			'buttons' : [{
				text : "Восстановить",
				icon : "ui-icon-check",
				click : function() {
					var str = 'action='+fileRestore+'&id='+file+'&form=list-data-form';
					layoutWepps.request(str, '/packages/WeppsAdmin/ConfigExtensions/Backup/Request.php');
					//console.log('восстановить');
					$(this).dialog("close");
				}
			},{
				text : "Удалить",
				icon : "ui-icon-trash",
				click : function() {
					var str = 'action='+fileRemove+'&id='+file+'&form=list-data-form';
					layoutWepps.request(str, '/packages/WeppsAdmin/ConfigExtensions/Backup/Request.php');
					$(this).dialog("close");
				}
			}]
		});
	});
	$('#backupListStructure').on('click',function(e) {
		e.preventDefault();
		let id = $('#lists').val();
		let str = 'action=list&id='+id;
		//layoutWepps.request(str, '/packages/WeppsAdmin/ConfigExtensions/Backup/Request.php');
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