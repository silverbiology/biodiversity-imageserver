Ext.define('BIS.view.CtxMnuEventType', {
    extend: 'Ext.menu.Menu',
    requires: ['BIS.view.FormCreateEventType'],
    scope: this,
    listeners: {
        click: function( menu, item ) {
            switch( item.identifier ) {
                case 'update':
                    this.update();
                    break;
                case 'delete':
                    Ext.Msg.confirm('Remove ' + this.record.data.title + '?', 'Are you sure you want remove ' + this.record.data.title + '?', function( btn, nothing, item ) {
                        this.remove();
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
                    text: 'Edit Event Type',
                    iconCls: 'icon_editEventType',
                    identifier: 'update'
                },
                {
                    text: 'Remove Event Type',
                    iconCls: 'icon_removeEventType',
                    identifier: 'delete'
                }
            ]
        });
        me.callParent(arguments);
    },
    remove: function() {
        var cmd = 'deleteEventType'
            params = { eventTypeId: this.record.eventTypeId }
    },
    update: function() {
        Ext.create('Ext.window.Window', {
            title: 'Edit Event Type ' + this.record.data.title,
            iconCls: 'icon_editEventType',
            modal: true,
            height: 500,
            width: 800,
            layout: 'fit',
            items: [
                Ext.create('widget.formcreateeventtype', {
                    record: this.record,
                    mode: 'edit'
                })
            ]
        }).show();
    }
});
