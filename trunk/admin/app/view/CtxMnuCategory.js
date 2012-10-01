Ext.define('BIS.view.CtxMnuCategory', {
    extend: 'Ext.menu.Menu',
    scope: this,
    listeners: {
        click: function( menu, item ) {
            switch( item.identifier ) {
                case 'create':
                    var me = this;
                    var tmpWindow = Ext.create('Ext.window.Window', {
                        title: 'Add Attribute to ' + this.record.data.title,
                        iconCls: 'icon_newAttribute',
                        modal: true,
                        height: 100,
                        width: 350,
                        layout: 'fit',
                        items: [
                            Ext.create('widget.formcreateattribute', {
                                record: this.record,
                                mode: 'add'
                            })
                        ]
                    }).show();
                    tmpWindow.on('attributeCreated', function( data ) {
                        tmpWindow.close();
                        Ext.getCmp('categoryTreePanel').getStore().load();
                    });
                    tmpWindow.on( 'cancel', function( data ) {
                        tmpWindow.close();
                    });
                    break;
                case 'update':
                    var me = this;
                    var tmpWindow = Ext.create('Ext.window.Window', {
                        title: 'Edit ' + this.record.data.title,
                        iconCls: 'icon_editCategory',
                        modal: true,
                        height: 150,
                        width: 350,
                        layout: 'fit',
                        items: [
                            Ext.create('widget.formcreatecategory', {
                                record: this.record,
                                mode: 'edit'
                            })
                        ]
                    }).show();
                    tmpWindow.on('categoryCreated', function( data ) {
                        tmpWindow.close();
                        Ext.getCmp('categoryTreePanel').getStore().load();
                    });
                    tmpWindow.on( 'cancel', function( data ) {
                        tmpWindow.close();
                    });
                    break;
                case 'delete':
                    Ext.Msg.confirm('Remove Category', 'Are you sure you want remove "' + this.record.data.title + '"?', function( btn, nothing, item ) {
                        if ( btn == 'yes' ) this.remove();
                    }, this);
                    break;
            }
        }
    },
    initComponent: function() {
        var me = this;

        Ext.applyIf(me, {
            items: [
                {
                    text: 'Add Attribute',
                    iconCls: 'icon_newAttribute',
                    identifier: 'create'
                },
                '-',
                {
                    text: 'Edit Category',
                    iconCls: 'icon_editCategory',
                    identifier: 'update'
                },
                {
                    text: 'Remove Category',
                    iconCls: 'icon_removeCategory',
                    identifier: 'delete'
                }
            ]
        });
        me.callParent(arguments);
    },
    remove: function() {
        Ext.Ajax.request({
            method: 'POST',
            url: Config.baseUrl + 'resources/api/api.php',
            params: {
                cmd: 'categoryDelete',
                categoryId: this.record.data.categoryId
            },
            scope: this,
            success: function( resObj ) {
                var res = Ext.decode( resObj.responseText );
                if ( res.success ) {
                    Ext.getCmp('categoryTreePanel').getStore().load();
                }
            },
            failure: function( form, action ) {
                Ext.Msg.alert('Failed', 'Request failed.');
            }
        });
    }
});