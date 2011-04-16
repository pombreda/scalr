{
	create: function (loadParams, moduleParams) {
		var avail = [], lst = moduleParams.info.platformsList;
		for (var i = 0, len = lst.length; i < len; i++)
			avail += '&bull; ' + lst[i].name + ' in ' + lst[i].locations + '<br>';

		return new Ext.form.FormPanel({
			title: 'Role "' + moduleParams['name'] + '" information',
			scalrOptions: {
				'modal': true,
				'maximize': 'maxHeight'
			},
			tools: [{
				id: 'close',
				handler: function () {
					Scalr.Viewers.EventMessager.fireEvent('close');
				}
			}],
			autoScroll: true,
			width: 700,
			bodyStyle: 'background-color: white; padding: 5px',
			items: [{
				layout: 'column',
				border: false,
				items: [{
					columnWidth: .4,
					layout: 'form',
					border: false,
					labelWidth: 100,
					items: [{
						xtype: 'displayfield',
						fieldLabel: 'Name',
						value: moduleParams.info.name
					}, {
						xtype:'displayfield',
						fieldLabel: 'Group',
						value: moduleParams.info.groupName
					}, {
						xtype:'displayfield',
						fieldLabel: 'Behaviors',
						value: moduleParams.info.behaviorsList
					}, {
						xtype:'displayfield',
						fieldLabel: 'OS',
						value: moduleParams.info.os
					}, {
						xtype:'displayfield',
						fieldLabel: 'Architecture',
						value: moduleParams.info.architecture
					}, {
						xtype:'displayfield',
						fieldLabel: 'Scalr agent',
						value: (moduleParams.info.generation == 1 ? 'ami-scripts' : 'scalarizr') + 
						" ("+(moduleParams.info.szrVersion ? moduleParams.info.szrVersion : 'Unknown')+")"
					}, {
						xtype:'displayfield',
						fieldLabel: 'Tags',
						hidden: moduleParams.info.tagsString == '' ? true : false,
						value: moduleParams.info.tagsString
					}]
				}, {
					columnWidth: .6,
					layout: 'form',
					labelWidth: 110,
					border: false,
					items: [{
						xtype:'displayfield',
						fieldLabel: 'Description',
						value: moduleParams.info.description ? moduleParams.info.description : '<i>Description not available for this role</i>'
					}, {
						xtype:'displayfield',
						fieldLabel: 'Installed software',
						value: moduleParams.info.softwareList ? moduleParams.info.softwareList : '<i>Software list not available for this role</i>'
					}]
				}]
			}, {
				xtype: 'displayfield',
				fieldLabel: 'Available in',
				value: avail
			}]
		});
	}
}
