$( document ).ready(function() {
	
	contextBarInit();
	
});

function contextBarInit(){
	$(document).ajaxSuccess(function(event, jqXHR, ajaxOptions, data){
		var extras = {};
		if (jqXHR.responseJSON){
			extras = jqXHR.responseJSON.extras;
		} else {
			try {
				extras = $.parseJSON(jqXHR.responseText).extras;
			} catch (err) {
				extras = {};
			}
		}
		if (extras && extras.ContextBar){
			contextBarRefresh(extras.ContextBar);
		}
	});
	
	contextBarLoad();
	
	// Remove row from object basket table, when the object is removed
	$(document).on('exface.Core.ObjectBasketRemove.action.performed', function(e, requestData, inputElementId){
		var dg = $('#'+inputElementId);
		var rows = [];
		if (dg.data('_rows') === undefined){
			dg.data('_rows', dg.datagrid('getRows').slice(0));
		}
		for (var i in dg.datagrid('getSelections')){
			dg.data('_rows').splice(i,1);
		}
		dg.datagrid('clearSelections');
		dg.datagrid('loadData', {"total":0,"rows":[]});
		for (var i in dg.data('_rows')){
			dg.datagrid('appendRow', dg.data('_rows')[i]);
		}
		dg.datagrid('resize');
	});
}

function contextBarLoad(delay){
	if (delay == undefined) delay = 100;
	
	setTimeout(function(){
		// IDEA had to disable adding context bar extras to every request due to
		// performance issues. This will be needed for asynchronous contexts like
		// user messaging, external task management, etc. So put the line back in
		// place to fetch context data with every request instead of a dedicated one.
		// if ($.active == 0 && $('#contextBar .panel-loading').length > 0){
		if ($('#contextBar .panel-loading').length > 0){
			$.ajax({
				type: 'POST',
				url: 'exface/api/jeasyui',
				dataType: 'json',
				data: {
					action: 'exface.Core.ShowWidget',
					resource: getPageId(),
					element: 'ContextBar'
				},
				success: function(data, textStatus, jqXHR) {
					contextBarRefresh(data);
				},
				error: function(jqXHR, textStatus, errorThrown){
					contextBarRefresh({});
				}
			});
		} else {
			contextBarLoad(delay*3);
		}
	}, delay);
}

function contextBarRefresh(data){
	$('#contextBar').children().not('.login-logout').not('.user-info').remove();
	for (var id in data){
		var color = data[id].color ? 'color:'+data[id].color+';' : '';
		var weight = data[id].visibility === 'emphasized' ? 'font-weight: bold;' : '';
		var btn = $(' \
				<!-- '+data[id].bar_widget_id+' --> \
				<div class="toolbar-element" id="'+id+'"> \
					<div class="toolbar-button" title="'+data[id].hint+'" data-widget="'+data[id].bar_widget_id+'"> \
						<a href="#" style="'+color+weight+'" class="easyui-linkbutton context-button" data-options="plain:true, iconCls:\''+data[id].icon+'\'">'+data[id].indicator+'</a> \
					</div> \
				</div>');
		$('#contextBar').prepend(btn);
	}
	$.parser.parse($('#contextBar'));
	
	$('#contextBar .context-button').tooltip({
        content: function(){return $('<div id="'+$(this).closest('.toolbar-element').attr('id')+'_tooltip"></div>')},
        showEvent: 'click',
        onUpdate: function(content){
        	content.panel({
                width: 200,
                height: 300,
                border: false,
                href: 'exface/api/jeasyui',
                method: 'POST',
                cache: false,
                queryParams: {
                    action: 'exface.Core.ShowContextPopup',
                    resource: getPageId(),
                    element: $(this).parent().data('widget')
                },
                onLoadError: function(response) {
                	jeasyui_create_dialog($("body"), $(this).attr('id')+"_error", {title: response.status + " " + response.statusText, width: 800, height: "80%"}, response.responseText, true);
                }
            });
        },
        onShow: function(){
            var t = $(this);
            t.tooltip('tip').unbind().bind('mouseenter', function(){
                t.tooltip('show');
            });
           $(document).one('click', function(){
        	   t.tooltip('hide');
           })
        },
        onHide: function(){
        	$(this).one('click', function(){
        		$(this).tooltip('update');
        	})
        	var tt = $('#'+$(this).closest('.toolbar-element').attr('id')+'_tooltip');
        	if (tt.hasClass('panel-body')){
        		tt.panel('destroy');
        		tt.closest('tooltip-content').empty();
        	}
		}
    });
	
	// Restore title after tooltip init (tooltip will remove titles)
	$.each($('#contextBar a'), function(){
		$(this).attr('title', $(this).parent().attr('title'));
	});
}

function contextShowMenu(containerSelector){
	$(containerSelector).find('.toolbar-element').empty().append('<li class="header"><div class="overlay text-center"><i class="fa fa-refresh fa-spin"></i></div></li>');
	$.ajax({
		type: 'POST',
		url: 'exface/api/jeasyui',
		dataType: 'html',
		data: {
			action: 'exface.Core.ShowContextPopup',
			resource: getPageId(),
			element: $(containerSelector).data('widget')
		},
		success: function(data, textStatus, jqXHR) {
			var $data = $(data);
			$(containerSelector).find('.dropdown-menu').empty().append('<li></li>').children('li:first-of-type').append($data);
		},
		error: function(jqXHR, textStatus, errorThrown){
			adminLteCreateDialog($("body"), "error", jqXHR.responseText, jqXHR.status + " " + jqXHR.statusText);
		}
	});
}

function getPageId(){
	return $("meta[name='page_id']").attr("content");
}

/**
 * Creates an jEasyUI dialog
 */
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

// compare arrays (http://stackoverflow.com/questions/7837456/how-to-compare-arrays-in-javascript)
// Warn if overriding existing method
if(Array.prototype.equals)
    console.warn("Overriding existing Array.prototype.equals. Possible causes: New API defines the method, there's a framework conflict or you've got double inclusions in your code.");
// attach the .equals method to Array's prototype to call it on any array
Array.prototype.equals = function (array) {
    // if the other array is a falsy value, return
    if (!array)
        return false;

    // compare lengths - can save a lot of time 
    if (this.length != array.length)
        return false;

    for (var i = 0, l=this.length; i < l; i++) {
        // Check if we have nested arrays
        if (this[i] instanceof Array && array[i] instanceof Array) {
            // recurse into the nested arrays
            if (!this[i].equals(array[i]))
                return false;       
        }           
        else if (this[i] != array[i]) { 
            // Warning - two different object instances will never be equal: {x:20} != {x:20}
            return false;   
        }           
    }       
    return true;
}
// Hide method from for-in loops
Object.defineProperty(Array.prototype, "equals", {enumerable: false});