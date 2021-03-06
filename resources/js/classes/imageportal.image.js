/**
 * @copyright SilverBiology, LLC
 * @author Shashank
 * @website http://www.silverbiology.com
*/
/*
var staticIndex = 0;
var mirrorIndex = false;
Ext.XTemplate.prototype.testMirror = function(value){
		var mirrorIndex = this.getMirror(value);
		if(typeof (mirrorIndex) != 'undefined'){
			if (staticIndex+1 > this.mirrorObj[mirrorIndex].mirrors.length){
				staticIndex = 0 ;
				return value;
			}else{ 
				var val = value.replace(this.mirrorObj[mirrorIndex].main,this.mirrorObj[mirrorIndex].mirrors[staticIndex]);//Config.mirrors[0].mirrors
				staticIndex++;
				return val;
			}
		}
	}

Ext.XTemplate.prototype.getMirror = function(value){
		for (var i = 0; i< this.mirrorObj.length;i++){
			var index = value.search(this.mirrorObj[i].main);//Config.mirrors[0].mirrors
				if( index!= -1 ){
					return i;
				}
		}; 
	}


Ext.XTemplate.prototype.setMirror = function(value){
		this.mirrorObj = value;
	}	
	*/

Ext.namespace('ImagePortal');
ImagePortal.Image = function(config) {

	this.proxy = '';
	var staticIndex = 0;
	
	if (Config.mode == 'local') {
		this.proxy = new Ext.data.HttpProxy({
			url: Config.baseUrl + 'resources/api/api.php'
		});
	} else {
		this.proxy = new Ext.data.ScriptTagProxy({
			url: Config.baseUrl + 'resources/api/api.php'
		})
	}
	
	this.store =  new Ext.data.GroupingStore({
			proxy: this.proxy 
		,	baseParams: { 
					cmd: 'images'
				,	filters: ''
				,	code: ''
			}
		,	reader: new Ext.data.JsonReader({
					root: 'data'
				,	totalProperty: 'totalCount'
				, fields: [
							{name: 'image_id'}
						,	{name: 'filename'}
						,	'ext'
						,	{name: 'timestamp_modified'}
						,	{name: 'barcode'}								
						,	{name: 'Family'}
						,	{name: 'Genus'}
						,	{name: 'SpecificEpithet'}
						,	{name: 'flickr_PlantID'}
						,	{name: 'flickr_modified'}
						,	{name: 'picassa_PlantID'}
						,	{name: 'picassa_modified'}
						,	{name: 'gTileProcessed'}
						,	{name: 'zoomEnabled'}
						,	{name: 'processed'}
						,	{name: 'path'}
						,	{name: 'server'}
						,	{name: 'farm'}
					]
			})
		,	remoteSort: true
		,	sortInfo: 'login'
		,	groupField: ''
	});
	
	var encode = false;
	var filters = new Ext.ux.grid.GridFilters({
			encode: encode 
		,	filters: [{
					type: 'string'
				,	dataIndex: 'filename'
			},{
					type: 'string'
				,	dataIndex: 'barcode'
			},{
					type: 'string'
				,	dataIndex: 'Family'
			},{
					type: 'string'
				,	dataIndex: 'Genus'
			},{
					type: 'string'
				,	dataIndex: 'SpecificEpithet'
			},{
					type: 'date'
				,	dataIndex: 'timestamp_modified'
			},{
					type: 'date'
				,	dataIndex: 'picassa_modified'
				,	format:'mm-dd-yyyy'
			},{
					type: 'string'
				,	dataIndex: 'flickr_PlantID'
		//,	options: ['0', '1']
			},{
					type: 'numeric'
				,	dataIndex: 'picassa_PlantID'
				,	options: ['0', '1']	
			},{
					type: 'numeric'
				,	dataIndex: 'gTileProcessed'
				,	options: ['0', '1']
			},{
					type: 'numeric'
				,	dataIndex: 'processed'
				,	options: ['0', '1']
			}]
	});    

	this.comboStore = new Ext.data.JsonStore({
			fields: ['collection_id', 'name','code'] 
		,	proxy: this.proxy
		,	baseParams: {
				cmd: 'collections'
			}
		,	root: 'records'
		,	autoLoad: false
	});	
	
	this.search_value = new Ext.ux.TwinComboBox({
			fieldLabel: 'Collections'
		, name: 'Collections'
		, triggerAction: 'all'
		,	store: this.comboStore
		,	displayField: 'name'
		,	typeAhead: false
		,	hideTrigger2: false
		,	hideTrigger1: true
		,	editable:false
		,	value: ''
		,	width: 250
		, listeners: {
					select: function(combo, record) {
						Ext.getCmp('imageGrid').store.baseParams.collectionCode = record.data.code;
						Ext.getCmp('imageGrid').store.load({params:{start:0, limit:100}});
					}
				,	clear: function() {
						Ext.getCmp('imageGrid').store.baseParams.collectionCode = '';
						Ext.getCmp('imageGrid').store.load({params:{start:0, limit:100}});
					}
			}
	});
	
	this.search_evernote = new Ext.ux.form.SearchField({
			store: this.store
		,	width: 250
		,	paramName: 'value'
		,	onTrigger1Click : function(){
				if(this.hasSearch){
					this.el.dom.value = '';
					var o = {start:0, limit:100};
					this.store.baseParams = this.store.baseParams || {};
					this.store.baseParams.cmd = 'images';
					this.store.baseParams[this.paramName] = '';
					this.store.reload({params:o});
					this.triggers[0].hide();
					this.hasSearch = false;
				}
			}
		,	onTrigger2Click : function(){
				var v = this.getRawValue();
				if(v.length < 1){
					this.onTrigger1Click();
					return;
				}
				var o = {start:0, limit:100};
				this.store.baseParams = this.store.baseParams || {};
				this.store.baseParams.cmd = 'searchEnLabels';
				this.store.baseParams[this.paramName] = v;
				this.store.reload({params:o});
				this.hasSearch = true;
				this.triggers[0].show();
			}
	});
	
	this.both = new Ext.ux.XTemplate(
		'<div class="x-grid3-row ux-explorerview-item ux-explorerview-mixed-item">' +
			'<tpl if="gTileProcessed == 1">'+
				'<div class="divZoom bothIconZoomIn"  title="Double click to view large image.">&nbsp;</div>'+
			'</tpl>'+
			'<div class="ux-explorerview-icon"><img onerror="this.src=\'resources/images/no-image.gif\'" src="{path:this.testMirror}{[values.filename.replace("." + values.ext, "")]}_s.{ext}"></div>'+
				'<div class="ux-explorerview-text"><div class="x-grid3-cell x-grid3-td-name" unselectable="on">{barcode} {Family}<br/>{Genus} {SpecificEpithet}<br/>'+
				'<tpl if="barcode != 0">'+
					'<span>Barcode: {barcode}</span><br>'+
				'</tpl>'+
				'<span>Date Added: {timestamp_modified:this.convDate}</span></div>'+
			'</div>'+
		'</div>'
	);
	
	this.both.setMirror(Config.mirrors || [] );
	
	this.smallIcons = new Ext.ux.XTemplate(
		'<div class="x-grid3-row ux-explorerview-item ux-explorerview-small-item">'+
		'<tpl if="gTileProcessed == 1">'+
			'<div class="divZoom smallIconZoomIn"  title="Double click to view large image.">&nbsp;</div>'+
		'</tpl>'+	
		'<div class="ux-explorerview-icon"><img  ' +
		  	'<tpl if="Family != \'\' || Genus != \'\' || SpecificEpithet != \'\' ">'+
				' ext:qtip="' +
				'<tpl if="Family != \'\' " >{Family}<br></tpl>'+
				'<tpl if="Genus != \'\' " >{Genus} {SpecificEpithet}"</tpl>'+
			'</tpl>' +
			'src="{path:this.testMirror}{[values.filename.replace("." + values.ext, "")]}_s.{ext}" onerror="this.src=\'resources/images/no-image.gif\'" /></div>'+
		'</div>'
	);

	this.smallIcons.setMirror(Config.mirrors);
	this.tileIcons = new Ext.ux.XTemplate(
		'<div class="x-grid3-row ux-explorerview-item ux-explorerview-tiles-item">'+
		'<tpl if="gTileProcessed == 1">'+
			'<div class="divZoom largeIconZoomIn" title="Double click to view large image.">&nbsp;</div>'+
		'</tpl>'+
		'<div class="ux-explorerview-icon"><img onerror="this.src=\'resources/images/no-image.gif\'" src="{path:this.testMirror}{[values.filename.replace("." + values.ext, "")]}_m.{ext}"></div>'+
		'<div class="ux-explorerview-text"><div class="x-grid3-cell x-grid3-td-name" unselectable="on">{barcode}<br/> {Family}<span>{Genus} {SpecificEpithet}</span></div></div></div>'
	);
	this.tileIcons.setMirror(Config.mirrors);
	this.rotatedImages = [];
	
	this.views = new Ext.CycleButton({
			showText: true
		,	width: 150
		,	scope: this
		,	prependText: 'View as '
		,	changeHandler: this.changeView
		,	items: [{
					text:'Large' //this.largeText
				,	value: 'large'
				,	iconCls:'icon_cycleImages'
			},{
					text:'Small' //this.smallText
				,	value: 'small'
				,	checked:true
				,	iconCls:'icon_cycleImages'
			},{
					text:'Both'  //this.mixedText
				,	value: 'both'
				,	iconCls:'icon_cycleImages'
			},{
					text:'Details' //this.detailsText
				,	value: 'details'
				,	iconCls:'icon_cycleImages'
			}]
	});
	
	Ext.apply(this,config,{
			title: 'Images'		
		,	enableColumnMove: false
		,	enableColumnHide: false
		,	store: this.store
		,	scope:	this
		,	plugins: [filters]	
		,	loadMask: true
		,	id:'imageGrid'
		,	loadedFirst:false
		,	width:700
		,	height:400
		,	audit: []
		,	tbar: [ 
					'Collection: '
				, ' ', this.search_value
				, ' ', 'Search: '
				, ' ', this.search_evernote
				, ' ',	this.views
				,'->' , {
						iconCls: 'icon-rss'
					,	handler: function(){ 
							window.open(Config.baseUrl + 'resources/api/api.php?cmd=images&code=&dir=ASC&filters=&output=rss', '_blank');
						}
					}	
				/*,	{
							text:"Save Image Changes"
						,	iconCls:'icon_saveImageChanges'
						,	scope:this
						,	handler:this.sendRotateRequest
					}*/
			]		
		,	columns: [{
					header: "Image Id"
				,	dataIndex: 'image_id'
				,	width: 50
				,	sortable: true
				,	hidden: Config.image_id || false
			},{
					header: "Collection"
				,	dataIndex: ''
				,	width: 80
				,	sortable: true
			},{
					header: "Filename"
				,	dataIndex: 'filename'
				,	width: 85				
				,	sortable: true
				,	hidden: true
			},{
					header: "Barcode"
				,	dataIndex: 'barcode'
				,	width: 80				
				,	filterable:true				
				,	sortable: true
			},{
					header: "Last Modified"
				,	dataIndex: 'timestamp_modified'
				,	width: 120				
				,	sortable: true
				,	scope:this	
				,	hidden: Config.lastModified || false
				,	renderer:function(a){
						return(this.rendererDatehandling(a));
					}					
			},{
					header: "Family"
				,	dataIndex: 'Family'
				,	width: 120
				,	scope:this				
				,	sortable: true
			},{
					header: "Genus"
				,	dataIndex: 'Genus'
				,	width: 120
				,	scope:this				
				,	sortable: true
			},{
					header: "Specific Epithet"
				,	dataIndex: 'SpecificEpithet'
				,	width: 120
				,	scope:this				
				,	sortable: true
			},{
					header: "Flickr Avail"
				,	dataIndex: 'flickr_PlantID'
				,	width: 80
				,	scope:this				
				,	filterable:true
				,	filter: {type: 'string'}
				,	hidden: Config.flickr_PlantID || false
				,	sortable: true
				,	renderer:function(a){
						return(this.rendererPlantID(a));
					}
			},{
					header: "Picassa Avail"
				,	dataIndex: 'picassa_PlantID'
				,	width: 80
				,	scope:this	
				,	filterable:true
				,	hidden: Config.picassa_PlantID || false
				,	filter: {type: 'numeric'}				
				,	sortable: true
				,	renderer:function(a){
						return(this.rendererPlantID(a));
					}
			},{
					header: "Picassa Modified"
				,	dataIndex: 'picassa_modified'
				,	width: 120
				,	sortable: true
				,	scope:this	
				,	hidden: true
				,	renderer:function(a){
						return(this.rendererDatehandling(a));
					}					
			},{
					header: "Tiled Processed"
				,	dataIndex: 'gTileProcessed'
				,	width: 80
				,	scope:this	
				,	filterable:true
				,	filter: {type: 'numeric'}				
				,	sortable: true
				,	hidden: Config.gTileProcessed || false
				,	renderer:function(a){
						return(this.renderergTileProcess(a));
					}
			},{
					header: "Zoom Enabled"
				,	dataIndex: 'zoomEnabled'
				,	width: 80
				,	hidden: true
				,	scope:this				
				,	sortable: true
			},{
					header: "Processed"
				,	dataIndex: 'processed'
				,	width: 80
				,	scope:this	
				,	filterable:true
				,	filter: {type: 'numeric'}			
				,	sortable: true
				,	hidden: Config.processed || false
				,	renderer:function(a){
						return(this.renderergTileProcess(a));
					}
			}]
		,	sm: new Ext.grid.RowSelectionModel({singleSelect: false})		
		,	viewConfig: {
					rowTemplate: this.smallIcons
				,	multiSelect: false
				, singleSelect: true	
				,	emptyText: 'No images available.'
				,	deferEmptyText: false
				,	forceFit: true
				,	hideColumns: true
			}		
		,	bbar: new Ext.PagingToolbar({
					pageSize: 100
				,	store: this.store
				,	scope:this
				,	emptyMsg: 'No images available.'
				,	displayInfo: true
				,	displayMsg: 'Displaying Specimen Images {0} - {1} of {2}' 
				,	ref:'../pgtoolbar'
				,	items:['',{
						xtype:'button'
					,	text:'View Image'   
					,	scope:this 
					,	handler: this.viewImage
					}
					,' '
					,{
						text: "Send all to HelpingScience"
					,	scope: this
					,	iconCls: ''
					,	id: 'sendToHS'
					,	handler: function(){
							Ext.Msg.show({
								msg: 'Are you sure ?'
							,	buttons: Ext.Msg.YESNO
							,	icon: Ext.MessageBox.QUESTION
							,	scope:this
							,	fn: function(btn) {
									if (btn == 'yes') {
										var filterList = [];
										var record = this.filters.getFilterData()
										var fillter = Ext.encode(filterList);
										var comaprison = Ext.isDefined(record[0])? record[0].data.comparison : '';
										var value = Ext.isDefined(record[0])? record[0].data.value : '';
										Ext.Ajax.request({
												scope: this
											,	url: 'resources/api/bis2hs.php'
											,	params: {
														'filter[0][data][comparison]': comaprison
													,	'filter[0][data][type]': 'date'
													,	'filter[0][data][value]': value
													,	'filter[0][field]': 'timestamp_modified'
												}
											,	success: function(response){
													var response = Ext.decode(response.responseText);
	//														console.log("Success",response);
												}
											,	failure: function(result){
	//														console.log("Fail",result)
												}
										});
									}
								}
							})
						}
					}]
			})
		,	listeners:{
						rowcontextmenu: this.rightClickMenu
			   ,	rowdblclick: function(grid, index, e) {
							var imv = this.launchImage(index)
							imv.show();
							var barcode = grid.getStore().getAt(index).get('barcode');
							var image_id = grid.getStore().getAt(index).get('image_id');
							var path = grid.getStore().getAt(index).get('path');
							var data = grid.getSelectionModel().getSelected(index);
							var fId = data.get('flickr_PlantID');
							imv.hideInteractiveTab(data.get('gTileProcessed'),data.data.path,data.data.filename);
							imv.hideFlickerTab(fId,data);
							imv.setBarcode(barcode,image_id);								
							imv.showInfoData(data);
						}
				}			
	})

	ImagePortal.Image.superclass.constructor.call(this, config);

} 
 
Ext.extend(ImagePortal.Image, Ext.grid.GridPanel, {
		
		rendererDatehandling:function(value){
			if (value == '0000-00-00 00:00:00') {
				return String.format('');
			} else {
				var dt = Date.parseDate(value, "Y-m-d H:i:s", true);
				var dt1 = new Date(dt);
				var dt2 = dt1.format('m-d-Y');
				return dt2;
			}
		}	
	
	,	rightClickMenu:function(grid,row,e){
			var record = grid.getSelectionModel().getSelections();
			if(record.length <= 1){
				grid.getSelectionModel().selectRow(row);
			}
			var items = [];
			items.push({
					text: "Rotate 90' Right"
				,	iconCls: 'icon_rotate_right'
				,	scope: this
				,	handler: function() {
							//this.sendRotateRequest(grid, row, "right",90);
							this.rotateImageGUI(grid, row, 90);
					}
			}, {
					text: "Rotate 90' Left"
				,	iconCls: 'icon_rotate_left'
				,	scope: this
				,	handler: function() {
						//this.sendRotateRequest(grid, row, "left",270);
						this.rotateImageGUI(grid, row, 270);
					}
			}, {
					text: "Rotate 180'"
				,	iconCls: 'icon_rotate_image'
				,	scope: this
				,	handler: function() {
						//this.sendRotateRequest(grid, row, null,180);
						this.rotateImageGUI(grid, row,180);
					}
			}, {
					text: "Audit"
				,	scope: this
				,	handler: function() {
						this.imageName = [];
						for(i=0; i<record.length; i++){
							this.imageName.push(record[i].data.filename);
						}
						ImagePortal.Notice.msg("Notice","Auditing please wait");
						Ext.Ajax.request({
								scope: this
							,	url: 'resources/api/api.php'
							,	params: {
									cmd : 'audit'
								,	filenames : Ext.encode(this.imageName)
								,	autoProcess: Ext.encode({"small":true,"medium":true,"large":true})
									}
							,	success: function(response){
									var o = Ext.decode(response.responseText);
									var message = ''
									if(o.success){
										for(var i=0; i<o.recordCount; i++){
											var largeFound = (o.stats[i].details.large) ? 'Found.' :'Not Found.';
											var mediumFound = (o.stats[i].details.medium) ? 'Found.' :'Not Found.';
											var smallFound = (o.stats[i].details.small) ? 'Found.' :'Not Found.';
											message = 'Images: '+o.stats[i].file+'<br/>Large: '+largeFound+'<br/>Medium: '+mediumFound+'<br/>Small: '+smallFound;
											ImagePortal.Notice.msg("Notice", message);
										}
									}
								}
							,	failure: function(result){
									console.log("Fail",result)
								}
						});
					}
			}, {
					text: "Process OCR"
				,	scope: this
				,	handler: function() {
						Ext.Ajax.request({
								scope: this
							,	url: 'resources/api/backup_services.php'
							,	params: {
									cmd : 'processOCR'
								,	limit: ''
								,	stop: ''
								}
							,	success: function(response){
									var response = Ext.decode(response.responseText);
									console.log("Success",response);
								}
							,	failure: function(result){
									console.log("Fail",result)
								}
						});
					}
			}, {
					text: "Reprocess Thumbnails"
				,	scope: this
				,	handler: function() {
						var imageId = [];
						for(i=0; i<record.length; i++){
							imageId.push(record[i].data.image_id);
						}
						Ext.Ajax.request({
								scope: this
							,	url: 'resources/api/api.php'
							,	params: {
										cmd: 'rechop'
									,	image_id: Ext.encode(imageId)
								}
							,	success: function(response){
									var response = Ext.decode(response.responseText);
									ImagePortal.Notice.msg('Success', 'Images have been sent to process again.');
								}
							,	failure: function(result){
									ImagePortal.Notice.msg('Error', result);
								}
						});
					}
			}, {
					text: "Process Tiles"
				,	scope: this
				,	handler: function() {
						this.imageName = [];
						for(i=0; i<record.length; i++){
							this.imageName.push(record[i].data.filename);
						}
						Ext.Ajax.request({
								scope: this
							,	url: 'resources/api/api.php'
							,	params: {
									cmd: 'audit'
								,	filenames: Ext.encode(this.imageName)
								,	autoProcess: Ext.encode({"google_tile":true})
								}
							,	success: function(response){
									var o = Ext.decode(response.responseText);
								}
							,	failure: function(result){
									console.log("Fail",result)
								}
						});
					}
			}, {
					text: "Send to HelpingScience"
				,	scope: this
				,	handler: function() {
						var barcodeList = [];
						for(i=0; i<record.length; i++){
							barcodeList.push(record[i].data.barcode);
						}
						if(Ext.isEmpty(barcodeList)){
							ImagePortal.Notice.msg('Notice', 'Please select barcode.');
						} else {	
							this.sendHSQueue(barcodeList);
						}
					}
			}/*,{
					text: "Reset Image"
				,	iconCls: 'icon_reset_image'
				,	scope: this
				,	handler: function() {
						//this.sendRotateRequest(grid, row, null,0);
						this.rotateImageGUI(grid, row, nul,0);
					}
			}*/,'-',{
					text: "Delete Record"
				,	iconCls: 'icon_delete_image'
				,	scope: this
				,	handler: function() {
						this.sendDeleteRequest(grid, row, null);
					}
			});
			
			var menu = new Ext.menu.Menu({
					items: items
				,	record: record
			});
			var xy = e.getXY();
			menu.showAt(xy);
		}
	
	,	sendDeleteRequest: function(grid, index,column){
				var items = grid.getStore().getAt(index).data;
				function process(btn, text){
					if (btn === 'yes') {	
						var params = {};
							Ext.apply(params, {
										cmd:'delete-image'
									,	image_id: items.image_id
							});
							Ext.Ajax.request({
								url: Config.baseUrl + 'resources/api/api.php'
							,	scope: this
							,	params:params
							,	success: function(responseObject){
									var o = Ext.decode(responseObject.responseText);
									if (o.success) {
										Ext.getCmp('imageGrid').store.reload()
										ImagePortal.Notice.msg('Success', 'Image successfuly deletetd');	
									} else {
										Ext.MessageBox.alert('Error: '+o.error.code, o.error.message);
									}
								}
						});
					}
				};		
				Ext.MessageBox.confirm('Delete Image','The selected image will be deleted.<br>Are you sure you wish to delete this image?', process);
		}

	,	sendHSQueue: function(barcodeList){
			Ext.Ajax.request({
					scope: this
				,	url: 'resources/api/bis2hs.php'
				,	params: {
						barcode : Ext.encode(barcodeList)
					}
				,	success: function(response){
						var response = Ext.decode(response.responseText);
						if(response.barcodesAdded == 1){
							ImagePortal.Notice.msg('Notice','Selected barcodes is already sent to helping science.');
						}
					}
				,	failure: function(result){
					}
			});
		}

	,	rotateImageGUI:function(grid, row, degree){
			var data = this.getSelectionModel().getSelections()[0].data;	
			var params = {};
			Ext.apply(params, {
					cmd:'rotate-images'
				,	image_id: data.image_id
				,	degree:degree
			});
			ImagePortal.Notice.msg('Notice', 'Sending request...')
			Ext.Ajax.request({
					url: Config.baseUrl + 'resources/api/api.php'
				,	scope: this
				,	params:params
				,	success: function(responseObject){
						var o = Ext.decode(responseObject.responseText);
						if(o.success) {
							ImagePortal.Notice.msg('Notice', o.message);
						//	this.store.reload();
						} else {
							Ext.MessageBox.alert('Error: ' + o.error.code, o.error.message);
						}	
					}
			});
		}
	
	,	reloadtheStore:function(){
			this.store.reload();
		}
	
	,	changeView: function(item, checked) {
			var tpl;
			switch ( item.activeItem.value ) {
				case 'large':
					tpl = this.tileIcons;
					break;
				case 'small':
					tpl = this.smallIcons;
					break;
				case 'both':
					tpl = this.both;
					break;
				default:
					tmp = null;
			}
			this.getView().changeTemplate(tpl);
		}
	
	,	renderergTileProcess: function(value){
			if (value == 1) return String.format('Yes');
			else return String.format('');
		}	
		
	,	rendererPlantID: function(value){
			if (value != 0 && value > 0) return String.format('Yes');
			else return String.format('');
		}		
	
	,	viewImage: function(){
			if (this.getSelectionModel().getSelections() != '') {
				var index = this.getStore().indexOfId(this.getSelectionModel().getSelected().id);
				var imv = this.launchImage(index)
				var data = this.getStore().getAt(index);
				imv.show();
				imv.hideInteractiveTab(data.data.gTileProcessed,data.data.path,data.data.filename);
				imv.hideFlickerTab(data.data.flickr_PlantID,data);
				var barcode = data.data.barcode;
				imv.setBarcode(barcode,data.data.image_id);
				imv.showInfoData(data);
			}
		}		
	
	,	launchImage:function(index){
			var rowindex = index; 
			var imv = new ImagePortal.ImageViewer({	
					scope: this	
				,	dwnpath:this.store.getAt(rowindex).get('path')			
				,	tools:[{
							id:'left'
						,	qtip: 'Go to previous image'
						,	scope:this
						,	handler: function(event, toolEl, panel){
							rowindex = rowindex - 1;
							//For privious page,when clicks <<,not getting the rowindex.
							if (rowindex < 0) {
								var tb = this.getBottomToolbar();
								if ((tb.items.items[0].enable())) {
									Ext.override(Ext.PagingToolbar, {
										movePrevious: function(){
											this.doLoad(Math.max(0, this.cursor - this.pageSize));
											tb.on('change', function(){
												rowindex = 99;
												if(Ext.isDefined(this.store.getAt(rowindex))){
														var barcode = this.store.getAt(rowindex).get('barcode');
														var path = this.store.getAt(rowindex).get('path');
														var interact = this.store.getAt(rowindex).get('gTileProcessed');
														var fileName = this.store.getAt(rowindex).get('filename');
														imv.dwnpath = path;
														var data = this.store.getAt(rowindex);
														imv.setBarcode(barcode,data.data.image_id, path);
														imv.hideInteractiveTab(interact,path,fileName);
														imv.showInfoData(data);
														this.ownerCt.getSelectionModel().selectRow(99);
														panel.setTitle(fileName);
														var fId = this.store.getAt(rowindex).get('flickr_PlantID');
														imv.hideFlickerTab(fId,data);
												}	
												
											}, this);
										}
								}, this);
								this.getBottomToolbar().movePrevious();
								}
							}	else {
								if (rowindex > -1) {
									if(Ext.isDefined(this.store.getAt(rowindex))){
											var barcode = this.getStore().getAt(rowindex).get('barcode');
											var path = this.getStore().getAt(rowindex).get('path');
											imv.dwnpath = path
											var data = this.getStore().getAt(rowindex);
											var interact = this.store.getAt(rowindex).get('gTileProcessed');
											var fileName = this.store.getAt(rowindex).get('filename');
											imv.setBarcode(barcode,data.data.image_id, path);
											imv.hideInteractiveTab(interact,path,fileName);
											imv.showInfoData(data);
											var fId = this.store.getAt(rowindex).get('flickr_PlantID');
											imv.hideFlickerTab(fId,data);
											panel.setTitle(fileName);
											this.getSelectionModel().selectRow(rowindex);
									}	
								} else {
									rowindex = 0;
								}
							}
						}
					},{
							id: 'right'
						,	qtip: 'Go to next image'
						,	scope: this									
						,	handler: function(event, toolEl, panel){
								rowindex = rowindex + 1;
								var max = this.getStore().getTotalCount();
								if (rowindex < max) { //For next page,when clicks >>,not getting the rowindex. for new page store 0-99
									var tb = this.getBottomToolbar();
									if (rowindex > 99) {
										if ((tb.items.items[7].enable())) {
											Ext.override(Ext.PagingToolbar, {
												moveNext: function(){
													this.doLoad(this.cursor + this.pageSize);
													tb.on('change', function(){
														rowindex = 0;
														if(Ext.isDefined(this.store.getAt(rowindex))){
																var barcode = this.store.getAt(rowindex).get('barcode');
																var path = this.store.getAt(rowindex).get('path');
																imv.dwnpath = path;
																var fId = this.store.getAt(rowindex).get('flickr_PlantID');
																var data = this.store.getAt(rowindex);
																var interact = this.store.getAt(rowindex).get('gTileProcessed');
																var fileName = this.store.getAt(rowindex).get('filename');
																imv.hideFlickerTab(fId,data);
																imv.showInfoData(data);
																imv.setBarcode(barcode,data.data.image_id, path);
																imv.hideInteractiveTab(interact,path,fileName);
																panel.setTitle(fileName);
																this.ownerCt.getSelectionModel().selectRow(rowindex);
														}	
													}, this);
												}
											}, this);
											this.getBottomToolbar().moveNext();
										}
									} else {
										if(Ext.isDefined(this.store.getAt(rowindex))){
												var barcode = this.getStore().getAt(rowindex).get('image_id');
												var path = this.getStore().getAt(rowindex).get('path');
												imv.dwnpath = path;
												var fId = this.getStore().getAt(rowindex).get('flickr_PlantID');
												var data = this.getStore().getAt(rowindex);	
												var interact = this.store.getAt(rowindex).get('gTileProcessed');
												var fileName = this.store.getAt(rowindex).get('filename');
												imv.hideFlickerTab(fId,data);		
												imv.showInfoData(data);
												imv.setBarcode(barcode,data.data.image_id, path);
												imv.hideInteractiveTab(interact,path,fileName);	
												panel.setTitle(fileName);
												this.getSelectionModel().selectRow(rowindex);
										}	
									}
								} else {
									rowindex = max - 1;
								}
							}
					}]
			});
			return imv;
		}
});