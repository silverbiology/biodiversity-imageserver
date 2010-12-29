/**
 * @copyright SilverBiology, LLC
 * @author Shashank
 * @website http://www.silverbiology.com
*/

	Ext.namespace("ImaginProgress")

	ImaginProgress.ZoomImage = function(config){

	Ext.apply( this, config, {
			dockedItems: [{
						xtype: 'toolbar'
					,	dock: 'top'
					,	title: 'Image Viewer'
					,	ui:'light'
					,	items: [{
								text: 'Back'
							,	ui: 'back'
							,	handler: function(){
										CFLABUS.fireEvent('ChangeMainMenu',2,false,this);
									}
					}]
				}]	
		,	mapConfOpts:['enableScrollWheelZoom','enableDoubleClicZoom','enableDragging']
		,	mapControls:['GSmallControl','NonExistantControl','GLargeMapControl']
		,	listeners:{
							activate:this.drawImage
					}
	});
	ImaginProgress.ZoomImage.superclass.constructor.call(this, config);
	
}

Ext.extend(ImaginProgress.ZoomImage, Ext.ux.GMapPanel, {

		varMap: function(){
			var map = this.map
			return this.map;
		}

	,	CustomGetTileUrl: function( a, b ) {
			var path = this.path + "google_tiles/" + (5 - b) + "/tile_"+ (5 - b) + "_" + a.x + "_" + a.y + ".jpg";
			return path;
		}

	,	drawImage: function( ) {		

			this.path = this.storeUrl.path; //"http://images.cyberfloralouisiana.com/images/specimensheets/nlu/0/6/32/89/";
			
			// ====== Create a copyright entry =====
			var copyright = new GCopyright(1, new GLatLngBounds(new GLatLng(-90, -180),	new GLatLng(90, 180)), 0, this.copyright );
				
			// ====== Create a copyright collection =====
			// ====== and add the copyright to it   =====
			var copyrightCollection = new GCopyrightCollection('(Specimen: ...)');
			copyrightCollection.addCopyright(copyright);
					
			// == Write our own getTileUrl function ========
			// In this case the tiles are names like  8053_5274_3.jpg      
				
			// ===== Create the GTileLayer =====
			// ===== adn apply the CustomGetTileUrl to it
			var tileLayers = [ new GTileLayer(copyrightCollection , 1, 5)];
			tileLayers[0].getTileUrl = this.CustomGetTileUrl.createDelegate( this );
					
			//var gmaptype = new GMapTypeControl();
    	   	//this.map.addControl(gmaptype);
			
			// ===== Create the GMapType =====
			// ===== and add it to the map =====

			var map = this.getMap();
			map.removeMapType(map.getCurrentMapType());
			var custommap = new GMapType(tileLayers, new GMercatorProjection(18), "Images" );
			
			map.addMapType(custommap);
			
			map.removeMapType(G_SATELLITE_MAP); 
			map.removeMapType(G_HYBRID_MAP);
			map.removeMapType(G_NORMAL_MAP);
			var centerLat = 0, centerLong = 0, initialZoom = 0;
			map.setCenter(new GLatLng(centerLat, centerLong), initialZoom, custommap);
			map.setZoom( parseInt(initialZoom) );

		}
		
});

Ext.reg('IPZoomImage', ImaginProgress.ZoomImage );