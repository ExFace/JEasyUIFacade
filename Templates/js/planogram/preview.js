$( document ).ready(function() {
    console.log( "ready!" );
	var data = {};
    data.action = 'exface.Core.ReadData';
	data.resource = "315";
	data.element = "SplitHorizontal_SplitPanel_DataTable";
	data.object = "0x11E6B0C8227136B78943E4B318306B9A";
	data.filter_VM_SHELF = 61;
    
    $.post("exface/api/jeasyui", data, function(json){
    	try {
			var data = $.parseJSON(json);
		} catch (err) {
			console.log(err);
		}
		if (data.rows.length > 0) {
			var template = Handlebars.compile($('#DataList_tpl').html().replace(/\{\s\{\s\{/g, '{{{').replace(/\{\s\{/g, '{{'));
	        var elements = $(template(data));
	        $('#productList .row').append(elements);
        } else {
        	alert('No data found!');
        }
	}).fail(function(){
		$("#DataList").parents(".box").find(".overlay").remove();
		alert("Sorry, your request could not be processed correctly. Please contact an administrator!");
	});
});