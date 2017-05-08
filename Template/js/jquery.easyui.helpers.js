function jeasyui_create_dialog(parentElement, id, options, content, parseContent){
	parseContent = parseContent ? true : false;
	var dialog = $('<div class="easyui-dialog" id="'+id+'"></div>');
	parentElement.append(dialog);
	$.parser.parse(content);
	dialog.append(content);
	if (parseContent){
		$.parser.parse(dialog);
	}
	dialog.dialog(options);
	// Lädt man eine Seite neu wenn man an alexa UI aber nicht an alexa RMS angemeldet ist,
	// erscheint in Firefox eine Fehlermeldung in der linken unteren Ecke, in WebView ist
	// die Fehlermeldung gar nicht zu sehen. Deshalb wird sie hier nochmal zentriert.
	setTimeout(function() { dialog.dialog("center"); }, 0);
}

/*$.extend($.fn.textbox.methods, {
	addClearBtn: function(jq, iconCls){
		return jq.each(function(){
			var t = $(this);
			var opts = t.textbox('options');
			opts.icons = opts.icons || [];
			opts.icons.unshift({
				iconCls: iconCls,
				handler: function(e){
					$(e.data.target).textbox('clear').textbox('textbox').focus();
					$(this).css('visibility','hidden');
				}
			});
			t.textbox();
			if (!t.textbox('getText')){
				t.textbox('getIcon',0).css('visibility','hidden');
			}
			t.textbox('textbox').bind('keyup', function(){
				var icon = t.textbox('getIcon',0);
				if ($(this).val()){
					icon.css('visibility','visible');
				} else {
					icon.css('visibility','hidden');
				}
			});
		});
	}
});*/