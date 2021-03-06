Tickets.page.CreateTicket = function(config) {
	config = config || {record:{}};
	config.record = config.record || {};
	Ext.applyIf(config,{
		panelXType: 'modx-panel-ticket'
	});
	config.canDuplicate = false;
	config.canDelete = false;
	Tickets.page.CreateTicket.superclass.constructor.call(this,config);
};

Ext.extend(Tickets.page.CreateTicket,MODx.page.CreateResource,{
	getButtons: function(cfg) {
		var btns = [];
		if (cfg.canSave == 1) {
			btns.push({
				process: 'create'
				,id: 'modx-abtn-save'
				,text: _('save')
				,method: 'remote'
				,checkDirty: true
				,keys: [{
					key: MODx.config.keymap_save || 's'
					,ctrl: true
				}]
			});
			btns.push('-');
		}
		btns.push({
			process: 'cancel'
			,text: _('cancel')
			,id: 'modx-abtn-cancel'
			,params: { a: MODx.action['welcome'] }
		});
		return btns;
	}

});
Ext.reg('tickets-page-ticket-create',Tickets.page.CreateTicket);