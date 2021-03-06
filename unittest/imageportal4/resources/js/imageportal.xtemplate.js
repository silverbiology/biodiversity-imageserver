Ext.define('ImagePortal.XTemplate', {
		extend: 'Ext.XTemplate'
	,	staticIndex:0
	
	,	convDate: function(value){
			if (value == '0000-00-00 00:00:00') 
				return '';
			else {
				var d = new Date(value.split(' ')[0]);
				return Ext.util.Format.date(d, 'd-M-Y');
			}
		}
	
	,	getMirror: function(value){
			for (var i = 0; i< this.mirrorObj.length;i++){
				var index = value.search(this.mirrorObj[i].main);
					if( index!= -1 ){
						if(this.mirrorObj[i].mirrors != null){
							if(!Ext.isEmpty(this.mirrorObj[i].mirrors)){
								return i;
							}
						}		
					}
			};
		}
	
	,	isViewChange: function(v) {
			return v.view;
		}
		
	,	setDefaultView: function(view){
			this.view = view;
		}	
		
	,	setMirror: function(value){
			if(value != null){
				if(!Ext.isEmpty(value)){
					this.mirrorObj = value;
				}
			}	
		}

	,	testMirror: function(value){
			if(typeof(this.mirrorObj) != 'undefined'){
				var mirrorIndex = this.getMirror(value);
				if(typeof (mirrorIndex) != 'undefined'){
					if (this.staticIndex+1 > this.mirrorObj[mirrorIndex].mirrors.length){
						this.staticIndex = 0 ;
						return value;
					}else{ 
						var val = value.replace(this.mirrorObj[mirrorIndex].main,this.mirrorObj[mirrorIndex].mirrors[this.staticIndex]);//Config.mirrors[0].mirrors
						this.staticIndex++;
						return val;
					}
				}else{
					return value;
				}
			}else{
				return value;
			}
		}
});
