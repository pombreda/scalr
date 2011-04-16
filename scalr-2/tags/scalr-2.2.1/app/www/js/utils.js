Ext.ns('Scalr.utils');

Scalr.utils.CreateProcessBox = function (config) {
	if (config.type) {
		var a = {};

		switch (config.type) {
			case 'delete':
				a = {
					title: 'Deleting',
					icon: 'scalr-mb-icon-delete'
				};
				break;

			case 'reboot':
				a = {
					title: 'Rebooting',
					icon: 'scalr-mb-icon-reboot'
				};
				break;

			case 'terminate':
				a = {
					title: 'Terminating',
					icon: 'scalr-mb-icon-terminate'
				};
				break;


			case 'launch':
				a = {
					title: 'Launching',
					icon: 'scalr-mb-icon-launch'
				};
				break;

			case 'save':
				a = {
					title: 'Saving',
					icon: 'scalr-mb-icon-save'
				};
				break;

			case 'action': default:
				a = {
					title: 'Processing',
					icon: 'scalr-mb-icon-action'
				};
				break;
		}
		a['msg'] = 'Please wait...';

		config = Ext.applyIf(config, a);
	}

	config = Ext.applyIf(config, {
		wait: true,
		width: 400
	});

	Ext.MessageBox.show(config);
};

Scalr.utils.CloneObject = function (o) {
	if (o == null || typeof(o) != 'object')
		return o;

	if(o.constructor == Array)
		return [].concat(o);

	var t = {};
	for (i in o)
		t[i] = Scalr.utils.CloneObject(o[i]);

	return t;
};

Scalr.utils.Confirm = function (config) {
	config['icon'] = 'scalr-mb-icon-' + config['type'];
	var a = '';
	switch (config['type']) {
		case 'delete':
			a = 'Delete'; break;
		case 'reboot':
			a = 'Reboot'; break;
		case 'terminate':
			a = 'Terminate'; break;
		case 'launch':
			a = 'Launch'; break;
	};

	config['ok'] = config['ok'] || a;

	var w = new Ext.Window({
		title: 'Confirmation',
		resizable: false,
		minimizable: false,
		maximizable: false,
		closable: false,
		modal: true,
		width: 400,
		autoHeight: true,
		plain: true,
		cls: 'x-window-dlg',
		buttonAlign: 'center',
		html: '<div class="ext-mb-icon ' + (config['type'] ? config['icon'] : 'ext-hidden') + '"></div><div class="ext-mb-content"><span class="ext-mb-text">' + config['msg'] + '</span></div>'
	});

	w.addButton({
		text: config['ok'] || 'OK',
		handler: function () {
			config.success.call(config.scope || this);
			this.close();
		},
		scope: w
	});

	w.addButton({
		text: 'Cancel',
		handler: function () {
			this.close();
		},
		scope: w
	});

	w.show();
};

Scalr.utils.Request = function (config) {
	var currentUrl = document.location.href;

	config = Ext.apply(config, {
		callback: function (options, success, response) {
			Ext.MessageBox.hide();

			if (success == true) {
				try {
					var result = Ext.decode(response.responseText);
					if (result.success == true) {
						options.successF.call(this, result, response, options);
						return;
					} else {
						Scalr.Message.Error(result.error || 'Cannot proceed your request at the moment. Please try again later.');
					}
				} catch (e) {
					Scalr.Message.Error('Received incorrect response from server. Please create ticket <a href="http://support.scalr.net/" target="_blank">here</a> to get help (' + e + ')');
					console.log(this);
					debugger;
					Scalr.utils.PostReport(this, response);
				}
			}
			// else nothing, global error handler used

			options.failureF.call(this, response, options);
		}
	});

	config.successF = config.success || function () {};
	config.failureF = config.failure || function () {};
	config.scope = config.scope || config;

	delete config.success;
	delete config.failure;

	var pf = function (config) {
		if (config.processBox) {
			Scalr.Utils.CreateProcessBox(config.processBox);
			delete config.processBox;
		}

		Ext.Ajax.request(config);
	};

	if (Ext.isObject(config.confirmBox)) {
		config.confirmBox['success'] = function () {
			delete config.confirmBox;
			pf(config);
		};

		Scalr.Confirm(config.confirmBox);
	} else {
		pf(config);
	}
};

Scalr.utils.UserLoadFile = function (path) {
	Ext.getBody().createChild({
		tag: 'iframe',
		src: path,
		width: 0,
		height: 0,
		frameborder: 0
	}).remove.defer(1000);
};

// TODO
Scalr.utils.PostReport = function (obj, response) {
	var url = document.location;
	console.log(obj); //, response); //, response.getAllResponseHeaders(), response.responseText);

};

// shorter name
Scalr.Confirm = Scalr.utils.Confirm;
Scalr.Request = Scalr.utils.Request;




// BACKWARD COMPATIBILITY
Scalr.Utils.CreateProcessBox = Scalr.utils.CreateProcessBox;
Scalr.Viewers.userLoadFile = Scalr.utils.UserLoadFile;
