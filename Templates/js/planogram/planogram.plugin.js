// ---------------------------------
// ---------- Planogram ----------
// ---------------------------------
// Display sensitive areas you can fill with data
// ------------------------

;(function ( $, window, document, undefined ) {

    var pluginName = 'planogram';
    //Strings to store svg content

    function Plugin ( element, options ) {
        this.svg_base            = "";
        this.svg_areas           = "";
        this.svg_interactables   = "";
        this.svg_wrapper         = "";
        this.areaData            = {};
        this.elementData         = {};
        this.processedAreas      = {};
        this.reset               = {base:false,areas:false,wrapper:false,interactables:false};
        this.element = element;
        this._name = pluginName;

        this._defaults = $.fn.planogram.defaults;

        this.options = $.extend( {}, this._defaults, options );
        if (options.hasOwnProperty("width")==false || options.hasOwnProperty("boxWidth")==false || options.hasOwnProperty("boxHeight")==false){
            log(this,"You are missing important properties in your setup. Please set width, boxWidth and boxHeight");
        }
        this.init();
    }

    // Avoid Plugin.prototype conflicts
    $.extend(Plugin.prototype, {

        // Initialization logic
        init: function () {
            reset = {base:true,areas:true,wrapper:true,interactables:true};
            this.buildCache();
            this.setUpDisplay();
            this.bindEvents();

        },

        // Remove plugin instance completely
        destroy: function() {
            this.unbindEvents();
            this.$element.removeData();
        },
        buildCache: function () {
            this.$element = $(this.element);
        },
        setUpDisplay: function(){
            this.$element.empty();
            this.svg_wrapper = '';
            this.svg_base = '';
            this.svg_interactables = '';
            var plugin = this;
            setBackgroundImage(plugin, function(){
                setWrapper(this);
                if (typeof this.options.shapeLoader == 'function') {
                    this.options.shapeLoader.call(plugin);
                }
            });
        },
        prepareReset: function(element, value){
                reset[element] = value;
        },
        refreshGraphic: function(){
            this.$element.empty();
            var plugin = this;
            setBackgroundImage(plugin, function(){
                setWrapper(this);
                completeSVG(plugin);
            });

        },
        setUpAreas: function(){
            var plugin = this;
            var soptions = plugin.options.shapeOptionsDefaults;
             var style = soptions.style;
            if(reset.areas){
                plugin.svg_areas = "";
                plugin.processedAreas = {};
            }
            $.each(plugin.areaData, function(id,element){
                var coords = (typeof element[soptions.options] === 'object' ? element[soptions.options] : $.parseJSON(element[soptions.options])).coordinates;
                var ownstyle= $.extend({},soptions.style, element[soptions.options]);
                if (coords){
                    plugin.svg_areas += '<g class="areaDefinition">';
                    if (coords.length == 1 && coords[0].hasOwnProperty('r')){
                        var points = processCirclePoints(coords);
                        plugin.svg_areas += createCircle(plugin,element[soptions.id],points, soptions.titleBoxOffset, ownstyle);
                    }
                    else {
                        var points = processPolygonPoints(coords);
                        plugin.svg_areas += createPolygon(plugin,element[soptions.id], points, element[soptions.label],soptions.titleBoxOffset,ownstyle);
                    }
                    plugin.svg_areas += "</g>";
                    plugin.processedAreas[element[soptions.id]].original = element;
                }
            });

            if(reset.interactables){
                if (typeof this.options.dataLoader == 'function') {
                    this.options.dataLoader.call(plugin);
                }
            }
            else {
                this.setElementData(this.elementData);
            }
        },
        setUpElements: function(){
            var plugin = this;
            plugin.svg_interactables = "";
            var disEl = plugin.options.dataTextField;
            var soptions = plugin.options.dataOptionsDefaults;
            var inOff = soptions.areaListOffset;

            $.each(plugin.processedAreas, function(i,area){
                if (area.min){
                    var width = parseInt(area['width']);
                    area.offset = jQuery.extend([], area.min);
                    var x = parseInt(area['offset'][0]);
                }
                else {
                    area.visible = false;
                }
                $.each(area.elements, function(eindex,element){
                    if(element && area.visible){
                        var ownstyle= $.extend({},soptions.style, element[soptions.options]);
                        var shapeStyle= getStyleForElement('shape',ownstyle);
                        var textStyle= getStyleForElement('text',ownstyle);
                        var tlh = parseInt(ownstyle['text-lineheight']);
                        var oid = element[plugin.options.dataOptionsDefaults.id];
                        var lineAt = 0;
                        var y = area['offset'][1];
                        var altlength = 0;
                        var textentries = "";
                        var tooltip = "";
                        //go through list of elements
                        $.each(disEl, function(entryi, line){
                            var fs = parseInt(ownstyle['text-font-size']);
                            var offsetText = inOff[1]+parseInt(lineAt*tlh)+fs+(Math.floor((tlh-fs)/2));
                            textentries +='<text style="'+textStyle+'" data-textfor="'+oid+'" x="'+inOff[0]+'" y="'+offsetText+'"';
                            var text = '';
                            $.each(line, function(elindex, lineelement){
                                if (lineelement.type == 'param'){
                                    text += element[lineelement.val];
                                }
                                else {
                                    text += lineelement.val;
                                }
                            });
                            tooltip += (tooltip ? "\n" : "") + text;
                            altlength = altlength<(text.length*tlh*0.4) ? (text.length*tlh*0.4) : altlength;
                            textentries += ' mask="url(#poly_'+i+'_mask)">'+text+'</text>';
                            area['offset'][1] = parseInt(area['offset'][1])+tlh;
                            lineAt += 1;
                        });
                        //wrapper element g
                        plugin.svg_interactables +='<g class="dragElement" transform="translate('+x+','+y+')" data-x="'+x+'"  data-y="'+y+'" data-origcoord="'+x+','+y+'" data-shelf-oid="'+i+'" data-oid="'+oid+'" mask="url(#poly_'+i+'_mask_helper)">';
                        plugin.svg_interactables +='<title>'+tooltip+'</title>';
                        //helper for background is calculated by line size
                        var helperheight = tlh*disEl.length;
                        plugin.svg_interactables +='<rect class="helperRect" style="'+shapeStyle+'" x="0" y="'+inOff[1]+'" height="'+helperheight+'" width="'+(altlength > width ? altlength : width)+'"/>';

                        //Close group
                        plugin.svg_interactables += textentries+'</g>';
                    }
                });
            });
            completeSVG(plugin);
        },

        // Bind events that trigger methods
        bindEvents: function() {
            var plugin = this;

            if (typeof interact!== 'undefined'){
                console.log("#"+plugin.$element.attr("id"));
                interact.on('dragmove', function(event) {dragMove(event,plugin);});
                interact.on('dragend', function(event) {dragEnd(event,plugin);});
                interact(plugin.options.draggableElements)
            		.draggables({max: 2});
                interact(plugin.options.dragCopyElements)
                    .on('move', function(event) {move(event,plugin);});
                interact('.area').dropzone({
                    // only accept elements matching this CSS selector
                    accept: plugin.options.acceptedDropElements,
                    overlap: 0.2,
                    // listen for drop related events:
                    ondropactivate: function (event) {
                        // add active dropzone feedback
                        event.target.classList.add('drop-active');
                    },
                    ondragenter: onDragEnter,
                    ondragleave: onDragLeave,
                    ondrop:function(event){
                        var dropArea = $(event.relatedTarget);
                        if (typeof plugin.options.onDrop == 'function') {
                            plugin.options.onDrop(plugin, event.relatedTarget, event.target);
                        }
                    },
                    ondropdeactivate: onDropDeactivate
                });
            } else {
                log(plugin,"You need to implement interact.js");
            }

            plugin.$element.on('click','g.areaDefinition', function(event) {
                var oid = $(this).find(".area").attr("data-oid");

                if (typeof plugin.options.onShapeClick == 'function') {
                    plugin.options.onShapeClick.call(plugin,plugin.processedAreas[oid]);
                }
            });
        },
        setAreaData: function(option) {
            this.areaData = option;
            this.setUpAreas();
        },
        setElementData: function(option){
            this.elementData = option;
            var plugin = this;
            if (reset.interactables){
                $.each(plugin.processedAreas, function(i,area){
                    area.elements = {};
                });
            }
            $.each(option, function(i,article){
                var listIndex = article[plugin.options.dataOptionsDefaults.area_id];
                var elementKey = article[plugin.options.dataOptionsDefaults.id]; //could also be index i for numeric counting
                if (!plugin.processedAreas.hasOwnProperty(listIndex)){
                    plugin.processedAreas[listIndex] = {
                        id:listIndex,
                        type:'unknown',
                        visible: false,
                        offset: [0,0],
                        elements:{}
                    };
                }
                plugin.processedAreas[listIndex]['elements'][elementKey] = article;
            });
            this.setUpElements();
        },
        // Unbind events that trigger methods
        unbindEvents: function() {
            this.$element.off('.'+this._name);
        },

    });

    var completeSVG = function(plugin) {
        $(plugin.element).html(plugin.svg_wrapper+plugin.svg_base+plugin.svg_areas+plugin.svg_interactables+"</svg>");
        plugin.reset = {base:false,areas:false,wrapper:false,interactables:false};
        if (typeof plugin.options.onLoad == 'function') {
            plugin.options.onLoad.call(plugin);
        }
    };
    var setWrapper = function(plugin){
        var widthAbs = parseInt(plugin.options.width),
            heightAbs = parseInt(plugin.options.height);
        	widthRel = String(plugin.options.width).match( /('auto' || '%')/ ) ? String(plugin.options.width) : '';
        	heightRel = String(plugin.options.height).match( /('auto' || '%')/ ) ? String(plugin.options.height) : '';
        var pEl = plugin.options.parentElement;
        if (heightRel && (pEl=='' || $(pEl).height()==0)){
            log(plugin,"You need to define a parent element with a height bigger then 0 to sucessfully render percentual height");
            var dimensions = 'width="'+(widthRel ? widthRel : widthAbs)+'"';
        }else {
            var correlation = parseFloat(plugin.options.boxWidth)/parseFloat(plugin.options.boxHeight);

            if (widthRel == "auto"){
                if (heightRel!="auto"){
                    if (heightRel) {
                        height = parseFloat($(pEl).height())/100*parseInt(heightRel);
                    } else {
                    	height = heightAbs;
                    }
                    var calculatedWidth = height*correlation;
                    var dimensions = 'width="'+calculatedWidth+'" height="'+height+'"';
                }
                else {
                    var dimensions = 'width="'+plugin.options.boxWidth+'"';
                }
            }
            else if (widthRel!="auto"){
                if (heightRel=="auto"){
                    var dimensions = 'width="'+widthRel+'"';
                }else {
                    var calculatedHeight = widthAbs/correlation;
                    if (String(heightRel).indexOf('%')!==-1) {
                        height = parseFloat($(pEl).height())/100*parseInt(heightRel);
                    } else {
                    	height = heightAbs;
                    }

                    if (height>=calculatedHeight){
                        var dimensions = 'width="'+(widthRel ? widthRel : widthAbs)+'"';
                    }
                    else {
                        var calculatedWidth = height*correlation;
                        var dimensions = 'width="'+calculatedWidth+'" height="'+height+'"';
                    }
                }
            }
        }
        plugin.svg_wrapper = '<svg viewBox="0 0 '+plugin.options.boxWidth+' '+plugin.options.boxHeight+'" '+dimensions+' class="planogram">';
    };
    var setBackgroundImage = function(plugin, callback){
        var background = plugin.options.background;
        if (isAHex(background)){
            plugin.svg_base += '<rect x="0" y="0" width="'+plugin.options.boxWidth+'" height="'+plugin.options.boxHeight+'" fill="+background+"></rect>';
            if (typeof callback == 'function') {
                callback.call(plugin);
            }
        }
        else {
            var img = new Image();
            img.onload = function(){
                var bg_height = img.height;
                var bg_width = img.width;
                switch (plugin.options.backgroundStretch){
                    case 'fitx':
                        bg_width = plugin.options.boxWidth;
                        break;
                    case 'fity':
                        bg_height = plugin.options.boxHeight;
                        break;
                    case 'fit':
                        bg_width = plugin.options.boxWidth;
                        bg_height = plugin.options.boxHeight;
                        break;
                }

                plugin.svg_base += '<image width="'+bg_width+'" height="'+bg_height+'" preserveAspectRatio="none" xmlns:xlink="http://www.w3.org/1999/xlink" xlink:href="'+background+'"></image>';
                if (typeof callback == 'function') {
                    callback.call(plugin);
                }
            }
            img.src = background;
        }
    };

    var createPolygon = function(plugin,id,points, label, offset, style){
        var shapeStyle= getStyleForElement('shape',style);
        var textStyle= getStyleForElement('text',style);
        var text = '<polygon style="'+shapeStyle+'" data-oid="'+id+'" id="poly_'+id+'" points="'+points['points']+'" class="area"/>';

        text += '<defs>' +
            '<linearGradient id="Gradient'+id+'">' +
            '<stop offset="0.8" stop-color="white" stop-opacity="1" />' +
            '<stop offset="0.9" stop-color="white" stop-opacity="0" />' +
            '</linearGradient>' +
            '<mask id="poly_'+id+'_mask">' +
            '<polygon transform="translate(-'+points['min'][0]+',-'+points['min'][1]+')" points="'+points['points']+'" fill="url(#Gradient'+id+')"/>' +
            '</mask>' +
            '<mask id="poly_'+id+'_mask_helper" >' +
            '<polygon transform="translate(-'+points['min'][0]+',-'+points['min'][1]+')" points="'+points['points']+'" fill="white"  />' +
            '</mask>' +
            '</defs>';
        //Add Label
        switch(offset[2]){
            case "bottomright":
                text += printText(points['max'][0]-offset[0],points['max'][1]-offset[1], label,"end",textStyle);
                break;
            default:
                text += printText(points['min'][0]-offset[0],points['min'][1]-offset[1], label,"start",textStyle);
        }
        plugin.processedAreas[id] = {
                id:id,
                type:'polygon',
                points:points,
                width:points['size'][0],
                min:points['min'],
                max: points['max'],
                visible: true,
                offset: [0,0],
                elements:{}
        };
        return text;
    }

    var createCircle = function(plugin,id, points, initialOffset, style){
        var shapeStyle= getStyleForElement('shape',style);
        var textStyle= getStyleForElement('text',style);
        var text = '<circle style="'+shapeStyle+'"data-oid="'+id+'" id="poly_'+id+'" cx="'+points['points'][1]+'" cy="'+points['points'][2]+'" r="'+points['points'][0]+'" data-width="'+points['size'][0]+'" data-max="'+points['max'].join()+'" data-min="'+points['min'].join()+'" class="area"/>';
        text += '<defs>' +
                    '<linearGradient id="Gradient'+id+'">' +
                        '<stop offset="0.9" stop-color="white" stop-opacity="1" />' +
                        '<stop offset="1" stop-color="white" stop-opacity="0" />' +
                    '</linearGradient>' +
                    '<mask id="poly_'+id+'_mask">' +
                        '<rect x="0" y="0" width="'+points['size'][1]+'" height="100%" fill="url(#Gradient'+id+')"/>' +
                    '</mask>' +
                    '<mask id="poly_'+id+'_mask_helper" fill="white">' +
                        '<rect x="0" y="0" width="'+points['size'][1]+'" height="100%"/>' +
                    '</mask>' +
                '</defs>'
        plugin.processedAreas[id] = {
            id:id,
            type:'circle',
            cx:points['points'][1],
            cy:points['points'][2],
            r: points['points'][2],
            width:points['size'][0],
            min:points['min'],
            max: points['max'],
            visible: true,
            offset: [0,0],
            elements:{}
        };
        return text;
    }
    var printText = function(x,y,text, textanchor, style){
        return '<text style="'+style+'" x="'+x+'" y="'+y+'" text-anchor="'+textanchor+'">'+text+'</text>';
    }
    var processPolygonPoints = function(coordinates){
        var points = "";
        var minx = 999999, miny = 999999;
        var maxx = 0,maxy = 0;
        $.each(coordinates, function(cID,coord){
            if (coord['x'] > maxx){maxx = coord['x'];}
            if (coord['y'] > maxy){maxy = coord['y'];}
            if (coord['x'] < minx){minx = coord['x'];}
            if (coord['y'] < miny){miny = coord['y'];}
            points += coord['x']+","+coord['y']+" ";
        });
        var width=maxx-minx;
        var height=maxy-miny;
        return {"points": points, "min":[minx,miny], "max":[maxx,maxy], "size":[width,height]}
    }
    var processCirclePoints = function(coordinates){
        var radius = coordinates[0]['r'];
        var cx = coordinates[0]['cx'];
        var cy = coordinates[0]['cy'];
        var isql = Math.round((Math.sqrt((radius*radius)/2))*100)/100;
        return {"points": [radius,cx,cy],"min":[cx-(isql), cy-(isql)], "max":[cx+(isql), cy+(isql)], "size":[isql*2,isql*2]};
    }
    function move(e,plugin) {
    	if ($(e.currentTarget).is('tr.datagrid-row')){
            var interaction = e.interaction;
            if (interaction.pointerIsDown && !interaction.interacting()) {
                var original = e.currentTarget;
                if (!original.classList.contains("dragRow")) {
	                var clone = e.currentTarget.cloneNode(true);
	                $(clone).attr("id", $(original).attr("id") + "_dragRow");
	                $(clone).attr("data-oid", $(clone).find('td[field="OID"] div.datagrid-cell').html());
	                $(clone).attr("data-original", $(original).attr("id"));
	                clone.classList.add("dragRow");
	                $(clone).offset($(original).offset());
	                document.body.appendChild(clone);
	                interaction.start({ name: 'drag' }, e.interactable, clone);
                }
            }
        }
    }
    function dragMove(e,plugin) {

        var target = e.target;
        var oid = $(target).attr("data-oid");
        if ($(target).is(plugin.options.draggableElements)){
            if (isSVGElement(target)) {
                if(!$(target).attr("data-mask")){
                    $(target).attr("data-mask", $(target).attr("mask")).attr("mask","");
                    var textElement = $("text[data-textfor='" + oid + "']");
                    textElement.attr("data-mask", textElement.attr("mask")).attr("mask","");
                }
                setGroupPosition($(target),parseInt($(target).attr("data-x"))+e.dx, parseInt($(target).attr("data-y"))+e.dy);

            } else {
            	if (!$(target).attr("data-origcoord")){
                    $(target).attr("data-origcoord", "["+$(target).offset().left+","+$(target).offset().top+"]")
                }
                target.style.left = parseInt($(target).position().left) +e.dx + 'px';
                target.style.top  = parseInt($(target).position().top) + e.dy + 'px';
            }
        }
        return;
    }
    function setGroupPosition(target,x,y){
        target.attr("transform", 'translate('+x+','+y+')').attr("data-x",x).attr("data-y",y);
    }
    function dragEnd(e,plugin) {
        var target = e.target;
        if ($(target).is(plugin.options.dragCopyElements)){
        	document.body.removeChild(target);
        } else if (!target.classList.contains("can-drop")) {
        	resetElement(target);
        }
        return false;
    }
    function resetElement(target){
        var oid = $(target).attr("data-oid");
        if (isSVGElement(target)) {
            var coords_original = $(target).attr("data-origcoord").split(",");
            var x = parseInt(coords_original[0]);
            var y = parseInt(coords_original[1]);
            $(target).attr("mask", $(target).attr("data-mask")).removeAttr("data-mask");
            var textElement = $("text[data-textfor='" + oid + "']");
            textElement.attr("mask", textElement.attr("data-mask")).removeAttr("data-mask");
            setGroupPosition($(target),x,y);
        } else {
            var coords_original = $(target).attr("data-origcoord").split(",");
            var x = parseInt(coords_original[0]);
            var y = parseInt(coords_original[1]);
            target.style.left = x + 'px';
            target.style.top  = y + 'px';}
    }
    function onDragEnter(event) {
        var draggableElement = event.relatedTarget,
            dropzoneElement = event.target;
        dropzoneElement.classList.add('drop-target');
        draggableElement.classList.add('can-drop');
    }
    function onDragLeave(event){
        event.target.classList.remove('drop-target');
        event.relatedTarget.classList.remove('can-drop');
    }

    function onDropDeactivate(event){
        event.target.classList.remove('drop-active');
        event.target.classList.remove('drop-target');
    }

    function isSVGElement(element){
        return 'SVGElement' in window && element instanceof SVGElement;
    }
    var isAHex = function(val){
        return /^#[0-9A-F]{6}$/i.test(val);
    }
    var log=function(plugin, message){
        switch (plugin.options.debug){
            case 'silent':
                console.log(message);
                break;
            case 'full':
                alert(message);
                break;
            default:
                return false;
        }
    }
    var getStyleForElement = function(element, style){
        var sStyle = "";
        $.each(style, function(key,value){
            if (key.indexOf(element+"-")!==-1){
                sStyle +=key.replace(element+"-","")+':'+value+";";
            }
        });
        return sStyle;
    }
    //Constructor
    $.fn.planogram = function ( options ) {

        this.each(function() {
            if ( !$.data( this, "plugin_" + pluginName ) ) {
                $.data( this, "plugin_" + pluginName, new Plugin( this, options ) );
            }
            else {
                var data = $.data( this, "plugin_" + pluginName);
                switch (options){
                    case 'refresh':
                        data.prepareReset('interactables',true);
                        data.prepareReset('areas',true);
                        data.options.shapeLoader.call(data);
                        break;
                    case 'refreshData':
                        data.prepareReset('interactables',true);
                        data.options.dataLoader.call(data);
                        return;
                        break;
                    default:
                        $.each(options, function(key,value) {
                            if (['width','height','boxWidth','boxHeight'].indexOf(key)!=-1){
                                data.prepareReset('base',true);
                                data.prepareReset('wrapper',true);
                            }
                            if (['dataLoader','data'].indexOf(key)!=-1){
                                data.prepareReset('interactables',true);
                            }
                            if (['shapes','shapeLoader'].indexOf(key)!=-1){
                                data.prepareReset('areas',true);
                            }
                        });

                        $.each(options, function(key,value){
                            switch (key){
                                case 'width':
                                    data.options.width = value;
                                    data.refreshGraphic();
                                    break;
                                case 'height':
                                    data.options.height = value;
                                    data.refreshGraphic();
                                    break;
                                case 'boxWidth':
                                    data.options.boxWidth = value;
                                    data.refreshGraphic();
                                    break;
                                case 'boxHeight':
                                    data.options.boxHeight = value;
                                    data.refreshGraphic();
                                    break;
                                case 'dataLoader':
                                    if (typeof value == 'function') {
                                        data.options.dataLoader = value;
                                        data.options.dataLoader.call(data);
                                    }else {
                                        log(data,"The parameter that was passed to dataLoader is not a function!");
                                    }
                                    break;
                                case 'data':
                                    data.setElementData(value);
                                    break;
                                case 'shapeLoader':
                                    if (typeof value == 'function') {
                                        data.options.shapeLoader = value;
                                        data.options.shapeLoader.call(data);
                                    }else {
                                        log(data,"The parameter that was passed to shapeLoader is not a function!");
                                    }
                                    break;
                                case 'shapes':
                                    data.setAreaData(value);
                                    break;
                            }
                        });
                }
            }
        });
        return this;
    };
    //Defaults
    $.fn.planogram.defaults = {
        debug: 'silent', //set to none to stop, silent to log output to console, or debug for alerts
        background: '#000000', // is set when background image is not set
        backgroundStretch: false, //Stretch background image to fit width and height of SVG. Params: 'fitx', 'fity', 'fit', false
        width: 10,
        height: 10,
        boxWidth: 10,
        boxHeight: 10,
        shapeLoader: function(){this.setAreaData({})},
        dataLoader: function(){this.setElementData({})},
        onLoad: function(){},
        parentElement: '',
        onShapeClick: function(plugin,data){},
        onDrop: function(plugin, dragItem, dropArea){
            var draggableItemShelf = $(dragItem).attr("data-shelf-oid");
            var draggableOID = $(dragItem).attr("data-oid");
            var enteredItemShelf = $(dropArea).attr("data-oid");

            if (draggableItemShelf == enteredItemShelf) {
                resetElement(dragItem);
                log(plugin,"Dropped in same shelf - nothing is accomplished");
                return;
            }
            else {
                log(plugin,"Element with ID " + draggableOID + " from Shelf " + draggableItemShelf + " was dropped in Shelf " + enteredItemShelf);
                return;
            }
        },
        acceptedDropElements: '.dragElement, .externalDrop, .dragRow',
        draggableElements: '.dragElement, .dragRow',
        dragCopyElements: 'tr.datagrid-row',
        shapeOptionsDefaults: {
            style: {'shape-fill': 'rgba(255,255,255,0.5)',
                    'shape-stroke-width': 1,
                    'shape-stroke':'rgb(255,255,255)',
                    'text-fill':'rgb(255,255,255)',
                    'text-stroke-width': 1,
                    'text-stroke':'rgb(255,255,255)',
                    'text-font-family': 'Arial',
                    'text-font-size'   : 18,
                    },
            titleBoxOffset: [5,5,"bottomright"],             //negative offset for area name [x,y,position]
            id: 'OID',
            coordinates: 'COORDINATES',
            label: 'DESCRIPTION',
            options: 'OPTIONS'
        },
        dataOptionsDefaults: {
            area_id: 'VM_SHELF_MODEL_POSITION__OID',
            id: 'OID',
            style: {'shape-fill': 'rgba(255,255,255,0.8)',
                    'text-fill':'black',
                    'text-lineheight':18,
                    'text-font-size'   : 12,
                    'text-font-family': 'Arial',},
            options: 'OPTIONS',
            areaListOffset: [5,0]
        },
        dataTextField: [
            [   {'type':'param', 'val':"ARTICLE_COLOR__STYLE__LABEL"},
                {'type':'text', 'val':" - "},
                {'type':'param', 'val':"ARTICLE_COLOR__COLOR__LABEL"}
            ],
            [
                {'type':'param', 'val':"ARTICLE_COLOR__STYLE__BRAND__LABEL"}
            ]
        ],


    };
})( jQuery, window, document );