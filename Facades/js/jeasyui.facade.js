// Load the context bar initially
$( document ).ready(function() {
	var jqPanelWest = $('body > .layout-split-west');
	contextBarInit();
	if (jqPanelWest.length !== 0 && ! jqPanelWest.is(':visible')) {
		jqPanelWest.addClass('exf-hidden');
		$('body').layout('collapse', 'west');
	}
});

function toggleMainMenu(){
	var jqLayout = $('body');
	if (jqLayout.layout('panel', 'west').is(':visible')) {
		jqLayout.layout('collapse', 'west', 0);
	} else {
		jqLayout.layout('expand', 'west', 0);
		// Fix for menu not showing after auto-hide on screen orientation change (on tablets)
		if (! jqLayout.layout('panel', 'west').is(':visible')) {
			jqLayout.layout('collapse', 'west').layout('expand', 'west', 0);
		}
	}
}

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
		// if ($.active == 0 && $('#contextBar .spinner').length > 0){
		if ($('#contextBar .spinner').length > 0){
			$.ajax({
				type: 'GET',
				url: 'api/jeasyui/' + getPageId() + '/context',
				dataType: 'json',
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
		if (data[id].visibility === 'hide_allways') {
			continue;
		}
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

		// Handle JS tracer if it is enabled in the DebugContext
		if (id.endsWith('CoreDebugContext')) {
			_setupTracer(data[id]);
		}
	}
	$.parser.parse($('#contextBar'));
	
	$('#contextBar .context-button').tooltip({
        content: function(){return $('<div id="'+$(this).closest('.toolbar-element').attr('id')+'_tooltip"></div>')},
        showEvent: 'click',
        onUpdate: function(content){
			var jqTooltip = $(this);
			setTimeout(function(){
        	content.panel({
                width: 200,
                height: 300,
                border: false,
                href: 'api/jeasyui',
                method: 'GET',
                cache: false,
                queryParams: {
                    action: 'exface.Core.ShowContextPopup',
                    resource: getPageId(),
                    element: jqTooltip.parent().data('widget')
                },
                onLoad: function() {
                	var $p = $(this);
                	setTimeout(function(){
                		try {
                			$p.panel('resize', {width: 200, height: 300});
                		} catch (e) {
                			// do nothing
                		}
                	}, 0);
                },
                onLoadError: function(response) {
                	jeasyui_create_dialog($("body"), $(this).attr('id')+"_error", {title: response.status + " " + response.statusText, width: 800, height: "80%"}, response.responseText, true);
                }
            });
            }, 100);
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
		type: 'GET',
		url: 'api/jeasyui',
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
			jeasyui_show_error(jqXHR.status + " " + jqXHR.statusText, jqXHR.responseText, $(containerSelector).data('widget'));
		}
	});
}

function _setupTracer(oCtxtData) {
	var jqBar = $('#contextBar');
	if (oCtxtData.indicator !== 'OFF' && oCtxtData.indicator.includes('F')) {
		if (jqBar.data('traceJs') !== true) {
			jqBar.data('traceJs', true);
			if (window.eruda === undefined) {
				var script = document.createElement('script'); 
				script.src="vendor/npm-asset/eruda/eruda.js"; 
				document.body.appendChild(script); 
				script.onload = function () { 
					eruda.init();
				} 
			}
			$(document).on('debugShowJsTrace', function(oEvent) {
				if (window.eruda !== undefined) {
					eruda.show();
				}
				oEvent.preventDefault();
			});
		}
	} else {
		if (jqBar.data('traceJs') === true) {
			jqBar.data('traceJs', false);
			$(document).off('debugShowJsTrace');
		}
	}
}

function getPageId(){
	return $("meta[name='page_id']").attr("content");
}

function jeasyui_show_error(sTitle, sBody, sSrcElementId) {
	var oBody, oError, sMessage, sDetails, sHeading;
	if (sBody && sBody.startsWith('{') && sBody.endsWith('}')) {
        try {
            oBody = JSON.parse(sBody);
        } catch (e) {
        	oBody = undefined;
        }
        if (oBody !== undefined && oBody.error) {
        	oError = oBody.error;
        	
        	// Message
			if (oError.code) {
				sHeading = oError.type + ' ' + oError.code;
				if (oError.title) {
					sMessage = oError.title;
					sDetails = oError.message;
				} else {
					sMessage = oError.message;
				}
			} else {
				sMessage = oError.message;
			}
        	
        	sTitle = oError.logid ? 'Log-ID ' + oError.logid : 'Error';
        	
        	if (sDetails) {
        		sMessage += ' <a href="javascript:;" onclick="$(this).parents(\'.error-summary\').toggle().next().toggle();"><i class="fa fa-chevron-right"></i></a>';
        	}
        	sBody = '<div class="error-summary"><div style="font-weight: bold;">' + sHeading + '</div><div>' + sMessage + '</div></div><div class="error-details" style="font-style: italic; display: none;"><a href="javascript:;" onclick="$(this).parents(\'.error-details\').toggle().prev().toggle();"><i class="fa fa-chevron-left"></i></a> ' + sDetails + '</div>';
        	
        	$.messager.alert(sTitle, sBody, 'error');
        	return;
        }
    }

	jeasyui_create_dialog($("body"), sSrcElementId + "_error", {title: sTitle, width: 800, height: "80%"}, sBody, true);
	return;
}

/**
 * Creates an jEasyUI dialog
 */
function jeasyui_create_dialog(parentElement, id, options, content, parseContent){
	parseContent = parseContent ? true : false;
	var dialog = $('<div id="'+id+'" class="exf-ajax-error"><div class="spinner-bg"><i class="panel-loading"></i><span class="sr-only">Loading...</span></div></div>');
	parentElement.append(dialog);
	dialog.append(content);
	
	options = $.extend({
		 onClose: function(){
			 $('#' + id).dialog('destroy');
		 }
	}, options);
	
	// Open the dialog right away (it will show the spinner as long as the content is not loaded)
	dialog.dialog(options);
	
	setTimeout(function(){
		// Parse the jEasyUI elements inside
		if (parseContent){
			$.parser.parse(dialog);
		}
		// Now hide the spinner
		dialog.children('.spinner-bg').hide();
		// Lädt man eine Seite neu wenn man an alexa UI aber nicht an alexa RMS angemeldet ist,
		// erscheint in Firefox eine Fehlermeldung in der linken unteren Ecke, in WebView ist
		// die Fehlermeldung gar nicht zu sehen. Deshalb wird sie hier nochmal zentriert.
		dialog.dialog("center");
	}, 0);
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

// Add method require(bool) to validatebox controls
$.extend($.fn.textbox.methods, {
    require: function(jq, value) {
        return jq.each(function() {
            const $ctrl = $(this);

            value = value === undefined ? true : Boolean(value);

            $ctrl.textbox('options').required = value;
            $ctrl.textbox('textbox').validatebox('options').required = value;
            $ctrl.textbox('validate');
        });
    },
});
$.extend($.fn.combo.methods, {
    require: function(jq, value) {
        return jq.each(function() {
            const $ctrl = $(this);

            value = value === undefined ? true : Boolean(value);

            $ctrl.combo('options').required = value;
            $ctrl.combo('textbox').validatebox('options').required = value;
            $ctrl.combo('validate');
        });
    },
});

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