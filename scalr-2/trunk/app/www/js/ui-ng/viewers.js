Ext.ns("Scalr");
Ext.ns("Scalr.Viewers");
Ext.ns("Scalr.Viewers.Message");
Ext.ns("Scalr.state");
Ext.ns("Scalr.Viewers.Plugins");
Ext.ns("Scalr.data");
Ext.ns("Scalr.Module");
Ext.ns("Scalr.Toolbar");
Ext.ns('Scalr.Message');
Ext.ns('Scalr.Utils');

Scalr.Utils.CreateProcessBox = function (config) {
	if (config.type) {
		var a = {};

		switch (config.type) {
			case 'action':
				a = {
					title: 'Processing',
					icon: 'scalr-mb-icon-action'
				};
				break;

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

Scalr.Request = function (config) {
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

Scalr.Confirm = function (config) {
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



Scalr.fireOnInputChange = function(el, obj, handler) {
	el.on('keyup', function() {
		var len = this.getValue().length;
		if (typeof(this.prevLength) == "undefined" || len != this.prevLength) {
			handler.call(obj);
		}
		this.prevLength = len;
	}, el);
};

Scalr.Viewers.userLoadFile = function (path) {
	Ext.getBody().createChild({
		tag: 'iframe',
		src: path,
		width: 0,
		height: 0,
		frameborder: 0
	}).remove.defer(1000);
};

Scalr.Toolbar.TimeItem = Ext.extend(Ext.Toolbar.TextItem, {
	updateText: function () {
		var cur = new Date(), diff = cur.getTime() - this.systemTime.getTime();
		this.systemTime = cur;
		this.time = this.time.add(Date.SECOND, diff / 1000);
		this.setText(this.time.format("M j, Y H:i:s"));
		if (! this.isDestroyed)
			this.updateText.defer(1000, this);
	},
	
	onRender: function(ct, position) {
		this.systemTime = new Date();
		this.time = new Date(this.time);
		this.time = this.time.add(Date.SECOND, parseInt(this.timeOffset));
		this.time = this.time.add(Date.SECOND, 0 - this.systemTime.format('Z'));

		Scalr.Toolbar.TimeItem.superclass.onRender.call(this, ct, position);
		this.updateText();
	}
});

Scalr.Viewers.autoSize = Ext.extend(Ext.util.Observable, {
	constructor: function (config) {
		Ext.apply(this, config);
		Scalr.Viewers.autoSize.superclass.constructor.call(this);
	},

	init: function(panel) {
		this.panel = panel;

		if (this.panel.rendered) {
			this.initEvents();
		} else {
			this.panel.on('render', this.initEvents, this);
		}
	},

	initEvents: function() {
		Ext.select("html").setStyle("overflow", "hidden");
		Ext.select("body").setStyle("overflow", "hidden");
		this.autoSize();

		Ext.EventManager.onWindowResize(this.autoSize, this);
	},

	autoSize: function () {
		if (this.panel.rendered) {
	   		var el = this.panel.getEl();
	    	this.panel.setHeight(Math.max(300, Ext.lib.Dom.getViewHeight() - el.getY() - el.getPadding("tb") - el.getBorderWidth("tb")) - 5);
		}
	}
});

Scalr.Viewers.Message.Add = function(message, errorId, timeout, type) {
	var msgCt = Ext.get('top-messages');

	message = message || '';
	if (!Ext.isArray(message) && message != '') {
		message = [message];
	}

	errorId = errorId || '';
	if (errorId) {
		// clear all messages with errorId
		var childs = msgCt.query('div');
		for (var i = 0, len = childs.length; i < len; i++) {
			var elem = Ext.get(childs[i]);
			if (elem.getAttribute('errorId') == errorId) {
				elem.ghost('t', {
					remove: true,
					callback: i ? Ext.emptyFn : Scalr.Viewers.Message.Update // call only once after clearing all elements
				});
			}
		}
		Scalr.Viewers.Message.Update();
	}

	// count visible messages
	var childs = msgCt.query('div'), cnt = 0;
	for (var i = 0, len = childs.length; i < len; i++) {
		var elem = Ext.get(childs[i]);
		if (! elem.hasClass('viewers-hiddenmessage'))
			cnt++;
	}

	if (Ext.isArray(message)) {
		var typeF = 'viewers-' + type + 'message' || 'viewers-errormessage';
		for (var i = 0, len = message.length; i < len; i++) {
			var m = msgCt.createChild({
				tag: 'div',
				cls: 'viewers-messages ' + typeF + ((i + cnt) >= Scalr.Viewers.Message.ShowMessages ? ' viewers-hiddenmessage' : ''),
				errorId: errorId,
				html: message[i] + '<div class="viewers-messages-close"><img src="/images/ui-ng/icons/message/' + type + '_close.png"></div>'
			});

			m.on('mouseenter', function () {
				this.addClass('viewers-messages-close-show');
			}, m.child('div.viewers-messages-close'));

			m.on('mouseleave', function () {
				this.removeClass('viewers-messages-close-show');
			}, m.child('div.viewers-messages-close'));

			m.child('div.viewers-messages-close').on('click', function() {
				this.ghost('t', {
					remove: true,
					callback: Scalr.Viewers.Message.Update
				});
			}, m);

			if (timeout)
				m.pause(timeout).ghost('t', {
					remove: true,
					callback: Scalr.Viewers.Message.Update
				});
		}
		//Ext.get('top-messages-icons').child('img.close').show();
		Scalr.Viewers.Message.Update();
	}

	scroll(0, 0);
};

Scalr.Viewers.Message.ShowMessages = 5;

Scalr.Viewers.Message.Update = function () {
	var msgCt = Ext.get('top-messages');

	// count visible messages
	var childs = msgCt.query('div'), cnt = 0;
	for (var i = 0, len = childs.length; i < len; i++) {
		var elem = Ext.get(childs[i]);
		if (! elem.hasClass('viewers-hiddenmessage'))
			cnt++;
		else {
			if (cnt >= Scalr.Viewers.Message.ShowMessages)
				//Ext.get('top-messages-icons')
				// add icon
				break;
			else {
				cnt++;
				elem.removeClass('viewers-hiddenmessage');
			}
		}
	}

	if (! childs.length) {
		// remove icon
	}
};

Scalr.Viewers.ErrorMessage = function(message, errorId, timeout) {
	Scalr.Viewers.Message.Add(message, errorId, timeout, 'error');
};

Scalr.Viewers.InfoMessage = function(message, errorId, timeout) {
	Scalr.Viewers.Message.Add(message, errorId, timeout, 'info');
};

Scalr.Viewers.SuccessMessage = function(message, errorId, timeout) {
	Scalr.Viewers.Message.Add(message, errorId, timeout, 'success');
};

Scalr.Viewers.WarningMessage = function(message, errorId, timeout) {
	Scalr.Viewers.Message.Add(message, errorId, timeout, 'warning');
};

Scalr.Message.Error = Scalr.Viewers.ErrorMessage;
Scalr.Message.Success = Scalr.Viewers.SuccessMessage;
Scalr.Message.Warning = Scalr.Viewers.WarningMessage;

Scalr.Viewers.FilterField = Ext.extend(Ext.form.TwinTriggerField, {
	initComponent : function() {
		if (this.store.baseParams['query'] != '')
			this.value = this.store.baseParams['query'];

		Scalr.Viewers.FilterField.superclass.initComponent.call(this);
		this.on('specialkey', function(f, e) {
			if(e.getKey() == e.ENTER){
				e.stopEvent();
				(this.hasSearch && this.getRawValue() == this.prevValue )? this.onTrigger1Click() : this.onTrigger2Click();
			}
		}, this);
	},

	validationEvent: false,
	validateOnBlur: false,
	trigger1Class: 'x-form-clear-trigger',
	trigger2Class: 'x-form-search-trigger',
	hideTrigger1: true,
	width: 180,
	hasSearch: false,
	paramName: 'query',
	prevValue: '',

	setValue: function(v) {
		Scalr.Viewers.FilterField.superclass.setValue.call(this, v);

		if (v.length) {
			this.prevValue = v;
			this.store.setBaseParam(this.paramName, v);
			this.hasSearch = true;
			if (this.rendered) {
				this.triggers[0].show();
			}
		}
	},

	onRender: function(ct, position) {
		Scalr.Viewers.FilterField.superclass.onRender.call(this, ct, position);

		if (this.hasSearch) {
			this.triggers[0].show();
		}
	},

	onTrigger1Click: function() {
		if (this.hasSearch) {
			this.el.dom.value = '';
			this.prevValue = '';
			this.store.setBaseParam(this.paramName, '');
			this.store.load();
			this.triggers[0].hide();
			this.hasSearch = false;
		}
	},

	onTrigger2Click : function() {
		var v = this.getRawValue();
		if (v.length < 1){
			this.onTrigger1Click();
			return;
		}
		this.prevValue = v;
		this.store.setBaseParam(this.paramName, v);
		this.store.load();
		this.hasSearch = true;
		this.triggers[0].show();
	}
});

/*Scalr.state.StorageProvider = function(config) {
	Scalr.state.StorageProvider.superclass.constructor.call(this);

	this.enabled = false;
	if (typeof(localStorage) == "object") {
		this.enabled = true;
		this.localStorage = localStorage;
	}

	Ext.apply(this, config);
};

Ext.extend(Scalr.state.StorageProvider, Ext.state.Provider, {
	// private
	set: function(name, value) {
		if (! this.enabled)
			return;

		if (typeof value == "undefined" || value === null) {
			this.clear(name);
			return;
		}

		this.localStorage.setItem(name, Ext.encode(value));
		Scalr.state.StorageProvider.superclass.set.call(this, name, value);
	},

	get: function(name, defaultValue) {
		if (! this.enabled)
			return;

		try {
			return Ext.decode(this.localStorage.getItem(name)) || defaultValue;
		} catch(e) {
			return defaultValue;
		}
	},

	// private
	clear: function(name) {
		if (! this.enabled)
			return;

		this.localStorage.removeItem(name);
		Scalr.state.StorageProvider.superclass.clear.call(this, name);
	},

	clearAll: function() {
		if (! this.enabled)
			return;

		this.localStorage.clear();
	}
});
*/
Scalr.Viewers.WarningPanel = Ext.extend(Ext.Container, {
	autoHeight: true,
	cls: 'viewers-warningpanel'
});

Scalr.Viewers.InfoPanel = Ext.extend(Ext.Container, {
	autoHeight: true,
	cls: 'viewers-infopanel'
});

Scalr.Viewers.Plugins.findOne = Ext.extend(Ext.util.Observable, {
	init: function (comp) {
		Ext.apply(comp, {
			findOne: this.findOne
		});
	},

	findOne: function (name, value, comp) {
		comp = comp || this;

		if (Ext.isObject(comp.items)) {
			var items = comp.items.items;
			for (var i = 0; i < items.length; i++) {
				if (items[i][name] == value)
					return items[i];

				var r = this.findOne(name, value, items[i]);
				if (r)
					return r;
			}
		}
	}
});

Scalr.Viewers.Plugins.sessionStorage = Ext.extend(Ext.util.Observable, {
	get: 'sessionGet',
	set: 'sessionSet',
	clear: 'sessionClear',

	init: function (comp) {
		var apply = {};

		if (typeof(sessionStorage) == "object" && comp.stateful && comp.stateful != '') {
			apply[this.get] = this.getF;
			apply[this.set] = this.setF;
			apply[this.clear] = this.clearF;
		} else {
			apply[this.get] = Ext.emptyFn;
			apply[this.set] = Ext.emptyFn;
			apply[this.clear] = Ext.emptyFn;
		}

		Ext.apply(comp, apply);
	},

	getF: function(name, defaultValue) {
		try {
			return Ext.decode(sessionStorage.getItem(this.stateful + '-' + name)) || defaultValue;
		} catch(e) {
			return defaultValue;
		}
	},

	setF: function(name, value) {
		if (typeof value == "undefined" || value === null) {
			sessionStorage.removeItem(this.stateful + '-' + name);
			return;
		}

		sessionStorage.setItem(this.stateful + '-' + name, Ext.encode(value));
	},

	clearF: function(name) {
		sessionStorage.removeItem(this.stateful + '-' + name);
	}
});


Scalr.Viewers.Plugins.localStorage = Ext.extend(Ext.util.Observable, {
	get: 'localGet',
	set: 'localSet',
	clear: 'localClear',

	init: function (comp) {
		var apply = {};

		if (typeof(localStorage) == "object" && comp.stateId && comp.stateId != '') {
			apply[this.get] = this.getF;
			apply[this.set] = this.setF;
			apply[this.clear] = this.clearF;
		} else {
			apply[this.get] = Ext.emptyFn;
			apply[this.set] = Ext.emptyFn;
			apply[this.clear] = Ext.emptyFn;
		}

		Ext.apply(comp, apply);
	},

	getF: function(name, defaultValue) {
		try {
			return Ext.decode(localStorage.getItem(this.stateId ? (this.stateId + '-' + name) : name)) || defaultValue;
		} catch(e) {
			return defaultValue;
		}
	},

	setF: function(name, value) {
		if (typeof value == "undefined" || value === null) {
			localStorage.removeItem(this.stateId ? (this.stateId + '-' + name) : name);
			return;
		}

		localStorage.setItem(this.stateId ? (this.stateId + '-' + name) : name, Ext.encode(value));
	},

	clearF: function(name) {
		localStorage.removeItem(this.stateId ? (this.stateId + '-' + name) : name);
	}
});

Scalr.Viewers.Plugins.prepareForm = Ext.extend(Ext.util.Observable, {
	init: function (comp) {
		comp.getForm().on('beforeaction', function (form, action) {
			if (action.options.proccessBox)
				Scalr.Utils.CreateProcessBox(action.options.proccessBox);
		});

		comp.getForm().on('actionfailed', Scalr.data.ExceptionFormReporter);

		comp.getForm().on('actioncomplete', function (form, action) {
			if (action.options.successMessage)
				Scalr.Message.Success(action.options.successMessage);

			Ext.MessageBox.hide();
			Scalr.Viewers.EventMessager.fireEvent('close');
		});
	}
});
