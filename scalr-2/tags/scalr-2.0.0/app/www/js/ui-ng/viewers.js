Ext.ns("Scalr");
Ext.ns("Scalr.Viewers");
Ext.ns("Scalr.state");

Scalr.fireOnInputChange = function(el, obj, handler) {
	el.on('keyup', function() {
		var len = this.getValue().length;
		if (typeof(this.prevLength) == "undefined" || len != this.prevLength) {
			handler.call(obj);
		}
		this.prevLength = len;
	}, el);
};

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

Ext.Ajax.on('requestexception', function() {
	if (Ext.MessageBox.isVisible()) 
		Ext.MessageBox.hide();

	Scalr.Viewers.ErrorMessage('Cannot proceed your request at the moment. Please try again later.');
});

Scalr.Viewers.ErrorMessage = function(message, errorId) {
	// @TODO: replace with simpler code, DOM ID would be already defined
	// Clear error message ? Not by '' message (fixed)
	// 3 argument - clear prev messages ?
	var msgId = 'top-messages', msgCt = Ext.get(msgId);
	if (!msgCt) {
		msgCt = Ext.DomHelper.insertFirst(Ext.get('body-container') || Ext.getBody(), { id: msgId }, true);
	}

	if (!Ext.isArray(message) && message != '') {
		message = [message];
	}

	errorId = errorId || '';
	if (errorId && !Ext.isArray(message)) {
		// clear all messages with errorId
		var childs = msgCt.query('div');
		for (var i = 0, len = childs.length; i < len; i++) {
			var elem = Ext.get(childs[i]);
			if (elem.getAttribute('errorId') == errorId) {
				elem.remove();
			}
		}
	}

	if (Ext.isArray(message)) {
		for (var i = 0, len = message.length; i < len; i++) {
			var m = msgCt.createChild({ tag: 'div', cls: 'viewers-messages viewers-errormessage', errorId: errorId, html: message[i] });
			m.on('click', function() {
				this.ghost('t', {remove: true});
			}, m);
		}
	}
	
	scroll(0, 0);
};

Scalr.Viewers.InfoMessage = function(message) {
	var msgId = 'top-messages', msgCt = Ext.get(msgId);
	if (!msgCt) {
		msgCt = Ext.DomHelper.insertFirst(Ext.get('body-container') || Ext.getBody(), { id: msgId }, true);
	}

	if (! Ext.isArray(message)) {
		message = [message];
	}

	for (var i = 0, len = message.length; i < len; i++) {
		var m = msgCt.createChild({ tag: 'div', cls: 'viewers-messages viewers-infomessage', html: message[i] });
		m.on('click', function() {
			this.ghost('t', {remove: true});
		}, m);
	}
};

Scalr.Viewers.SuccessMessage = function(message) {
	var msgId = 'top-messages', msgCt = Ext.get(msgId);
	if (!msgCt) {
		msgCt = Ext.DomHelper.insertFirst(Ext.get('body-container') || Ext.getBody(), { id: msgId }, true);
	}

	if (! Ext.isArray(message)) {
		message = [message];
	}

	for (var i = 0, len = message.length; i < len; i++) {
		var m = msgCt.createChild({ tag: 'div', cls: 'viewers-messages viewers-successmessage', html: message[i] });
		m.on('click', function() {
			this.ghost('t', {remove: true});
		}, m);
	}
};

Scalr.Viewers.FilterField = Ext.extend(Ext.form.TwinTriggerField, {
	initComponent : function(){
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

Scalr.state.StorageProvider = function(config) {
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
