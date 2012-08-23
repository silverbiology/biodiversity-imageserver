Ext.define('BIS.view.MainViewport', {
	extend: 'Ext.panel.Panel',
	requires: [
		'BIS.view.CtxMnuAttribute',
		'BIS.view.CtxMnuCategory',
		'BIS.view.CtxMnuCollection',
		'BIS.view.CtxMnuEvent',
		'BIS.view.CtxMnuEventType',
		'BIS.view.FormCreateCategory',
		'BIS.view.FormCreateAttribute',
		'BIS.view.FormCreateCollection',
		'BIS.view.ImagesPanel',
		'BIS.view.SetTreePanel',
		'BIS.view.CategoryTreePanel',
		'BIS.view.CollectionTreePanel',
		'BIS.view.EventTreePanel',
		'BIS.view.ImageDetailPanel'
	],
	layout: {
		type: 'border'
	},
	initComponent: function() {
		var me = this;

		Ext.applyIf(me, {
			items: [{
				xtype: 'imagespanel',
				region: 'center',
				border: false
			},{
				xtype: 'imagedetailpanel',
				collapseDirection: 'right',
				collapsed: false,
				collapsible: true,
				region: 'east',
				width: 200,
				border: false,
				split: true
			},{
				xtype: 'panel',
				id: 'viewsPanel',
				activeItem: 0,
				border: false,
				layout: {
					type: 'card'
				},
				defaults: {
					border: false,
					autoScroll: true,
				},
				titleCollapse: false,
				region: 'west',
				width: 350,
				split: true,
				dockedItems: [{
					xtype: 'toolbar',
					id: 'viewsPagingToolbar',
					dock: 'top',
					layout: {
						pack: 'start',
						align: 'center',
						type: 'hbox'
					},
					items: [{
						xtype: 'button',
						flex: 1,
						text: '<',
						scope: this,
						handler: this.decrementView
					},{
						xtype: 'label',
						flex: 6,
						style: 'text-align: center',
						cls: 'x-panel-header-text x-panel-header-text-default-framed',
						id: 'viewsPagingTitle',
						text: 'Sets'
					},{
						xtype: 'button',
						flex: 1,
						text: '>',
						scope: this,
						handler: this.incrementView
					}]
				}],
				items: [{
					xtype: 'settreepanel',
				},{
					xtype: 'categorytreepanel',
				},{
					xtype: 'collectiontreepanel',
				},{
					xtype: 'panel',
					id: 'toolPanel',
					listeners: {
						show: function( el, opts ) {
							Ext.getCmp('viewsPagingTitle').setText('Tools');
						}
					}
				},{
					xtype: 'gridpanel',
					id: 'queuePanel',
					store: 'QueueStore',
					columns: [{
						text:'Identifier',
						dataIndex:'image_id',
						flex:2
					},{
						text:'Compeleted?',
						dataIndex:'processed',
						renderer: function( value ) {
							if ( value ) { 
								return 'Yes' 
							} 
							return ' ';
						},
						flex: 1
					}],
					listeners: {
						show: function( el, opts ) {
							Ext.getCmp('viewsPagingTitle').setText('Queue');
						}
					}
				},{
					xtype: 'panel',
					id: 'geographyPanel',
					listeners: {
						show: function( el, opts ) {
							Ext.getCmp('viewsPagingTitle').setText('Geography');
						}
					}
				},{
					xtype: 'eventtreepanel',
				}]
			}],

				dockedItems: [{
					xtype: 'toolbar',
					id: 'masterToolbar',
					dock: 'top',
					items: [{
						text: 'View',
						iconCls: 'icon_view',
						menu: {
							id: 'viewMenu',
							defaults: {
								scope: this,
								handler: this.switchView
							},
							items: [{
								text: 'Sets',
								iconCls: 'icon_sets',
								panelIndex: 0
							},{
								text: 'Metadata',
								iconCls: 'icon_metadata',
								panelIndex: 1
							},{
								text: 'Collections',
								iconCls: 'icon_collections',
								panelIndex: 2
							},{
								text: 'Tools',
								iconCls: 'icon_tools',
								panelIndex: 3
							},{
								text: 'Queue',
								iconCls: 'icon_queue',
								panelIndex: 4
							},{
								text: 'Geography',
								iconCls: 'icon_geography',
								panelIndex: 5
							},{
								text: 'Events',
								iconCls: 'icon_eventTypes',
								panelIndex: 6
							}]
						}
					},{
						xtype: 'tbseparator'
					},{
						xtype: 'button',
						text: 'Tools',
						iconCls: 'icon_toolbar',
						menu: {
							xtype: 'menu',
							id: 'toolsMenu',
							defaults: {
								scope: this
							},
							items: [{
								text: 'Storage Settings',
								iconCls: 'icon_devices',
								handler: this.openStorageSettings
							},{
								text: 'User Manager',
								iconCls: 'icon_users',
								handler: this.openUserManager
							},{
								text: 'Server Information',
								iconCls: 'icon_info',
								handler: this.openServerInfo
							}]
						}
					},{
							xtype: 'tbfill'
					},{
						xtype: 'label',
						style: 'font-weight: bold;',
						text: 'Welcome, Administrator'
					}]
				}]
			});

			me.callParent(arguments);
    },
    incrementView: function( btn, e ) {
			var viewCard = Ext.getCmp('viewsPanel').getLayout();
			if ( viewCard.getLayoutItems().indexOf( viewCard.getActiveItem() ) == viewCard.getLayoutItems().length-1 ) {
				viewCard.setActiveItem( 0 );
			} else {
				viewCard.next();
			}
    },
    decrementView: function( btn, e ) {
			var viewCard = Ext.getCmp('viewsPanel').getLayout();
			if ( viewCard.getLayoutItems().indexOf( viewCard.getActiveItem() ) == 0 ) {
				viewCard.setActiveItem( viewCard.getLayoutItems().length-1 );
			} else {
				viewCard.prev();
			}
    },
    switchView: function( menuItem, e ) {
        Ext.getCmp('viewsPanel').getLayout().setActiveItem(menuItem.panelIndex);
    },
    openStorageSettings: function( menuItem, e ) {
			Ext.create('Ext.window.Window', {
				title: 'Storage Settings',
				iconCls: 'icon_devices',
				modal: true,
				height: 500,
				width: 800,
				layout: 'fit',
				bodyBorder: false,
				items: [{ 
					xtype: 'storagesettingspanel' 
				}],
				dockedItems: [{
					xtype: 'toolbar',
					dock: 'bottom',
					ui: 'footer',
					items: [{ 
						xtype: 'component', flex: 1 
					},{
						text: 'Close',
						xtype: 'button',
						width: 80,
						handler: function() {
							this.ownerCt.ownerCt.close();
						}
					}]
				}]
			}).show();
    },
    openUserManager: function( menuItem, e ) {
			Ext.create('Ext.window.Window', {
				title: 'User Management',
				iconCls: 'icon_users',
				modal: true,
				resizeable: false,
				height: 500,
				width: 800,
				layout: 'fit',
				bodyBorder: false,
				items: [{
					xtype: 'usermanagerpanel' 
				}]
			}).show();
    },
    openServerInfo: function( menuItem, e ) {
			Ext.create('Ext.window.Window', {
				title: 'Server Information',
				iconCls: 'icon_info',
				modal: true,
				resizeable: false,
				height: 400,
				width: 600,
				layout: 'fit',
				items: [{
					xtype: 'panel',
					border: false,
					tpl: new Ext.XTemplate('<div>Server Info</div>')
				}]
			}).show();
    }

});