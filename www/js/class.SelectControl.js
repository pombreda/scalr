// +--------------------------------------------------------------------------+
// | Selector control class				                      |
// +--------------------------------------------------------------------------+
// | Copyright (c) 2003-2006 Webta Inc, http://webta.net/copyright.html       |
// +--------------------------------------------------------------------------+
// | This program is protected by international copyright laws. Any           |
// | use of this program is subject to the terms of the license               |
// | agreement included as part of this distribution archive.                 |
// | Any other uses are strictly prohibited without the written permission    |
// | of "Webta" and all other rights are reserved.                            |
// | This notice may not be removed from this source code file.               |
// | This source file is subject to version 1.1 of the license,               |
// | that is bundled with this package in the file LICENSE.                   |
// | If the backage does not contain LICENSE file, this source file is        |
// | subject to general license, available at http://webta.net/license.html   |
// +--------------------------------------------------------------------------+
// | Authors: Sergey Koksharov <sergey@webta.net>   	 		      |
// +--------------------------------------------------------------------------+
// | Usage:		                                                      |
// |	var menu = [                                                          |
// |		{href: 'some link or javascript action',                      |
// |			target: '_blank', innerHTML: 'item title'},	      |
// |		{type: 'separator'}                                           |
// |                                                            	      |
// |	];								      |
// | 	var control = new SelectControl({menu: menu});			      |
// |	control.attach(linkid);						      |
// +--------------------------------------------------------------------------+

	
var SelectControl = Class.create();
SelectControl.prototype = {
	options:	{},
	controls:	[],
	opened:		false,
	active:		null,
	id:			0,
	
	initialize: function() {
		var options = Object.extend({
			menu: 		[{href: '#', title: 'No items defined', innerHTML: 'No items defined'}],
			menuid:		'menu-' + Math.floor(Math.random()*1000),
			menuClass:	'select-control-menu',
			separatorClass:	'select-control-separator',
			mainClass:	'select-control-main',
			pimpClass:	'select-control-pimp',
			pimpHover:	'select-control-pimp-hover',
			pimpid:		'pimp-' + Math.floor(Math.random()*1000)
		}, arguments[0] || {});
		
		this.options = options;
		this.parseMenu();
		this.id = options.pimpid;
		
		Event.observe(document.body, 'click', this.onDocumentClick.bindAsEventListener(this));
		Event.observe(window, 'resize', this.close.bindAsEventListener(this));
	},
	
	parseMenu: function() {
		var menu = this.options.menu;
		this.menu = document.createElement('DIV');
		this.menu.id = this.options.menuid;
		this.menu.className = this.options.menuClass
		
		for(var i = 0; i < menu.length; i++) {
			var item = menu[i];
			var menuItem = document.createElement('A');
			
			Object.extend(menuItem, item || {});
			
			if (!item.href)
				menuItem.className = this.options.separatorClass;
			
			this.menu.appendChild(menuItem);
		}

	},
	
	attach: function(element) {
		var element = $(element);
		if (!element) {
			alert('Element for SelectControl not found!');
			return;
		}
		
		element.className = this.options.mainClass;
		this.controls.push(element);
			
		var pimp = document.createElement('DIV');
			pimp.className = this.options.pimpClass;
			pimp.id = this.options.pimpid;
			
			pimp.onmouseover = (function(event) {
				var pimpObj = Event.element(event);
				pimpObj.className = this.options.pimpHover;
			}).bindAsEventListener(this);
			
			pimp.onmouseout = (function(event) {
				var pimpObj = Event.element(event);
				pimpObj.className = this.options.pimpClass;
			}).bindAsEventListener(this);
			
			element.appendChild(pimp);
			
			pimp.onclick = function() {
				return false;
			};
	},
	
	close: function(event) {
		this.menu.style.display = 'none';
		this.opened = false;
	},
	
	onDocumentClick: function(event) {
		var element = Event.element(event);
		var id = element.id.replace(/[^0-9]/g, '');
		var name = element.id.replace(/[0-9\-]/g, '');

		if ((name == 'pimp') && (this.active != element) && (element.id == this.id)) {
			this.open(event);
			for(var i =0; i < this.controls.length; i++) {
				try {
					this.controls[i].blur();
				} catch (err) {}
			}
			
			this.active = element;
		} else {
			this.close(event);
			this.active = null;
		}
	},
	
	open: function(event) {
		var pimp = Event.element(event);
		var element = pimp.parentNode;
			//element.parentNode.appendChild(this.menu);
		if (!$(this.menu.id))
			document.body.appendChild(this.menu);

		var position = Position.cumulativeOffset(element);
		
		Element.setStyle(this.menu, {
			display: 'block',
			left: position[0] + 1 + 'px',
			top: position[1] + element.offsetHeight + 'px'
		});
		
		this.opened = true;
	}
	
};
