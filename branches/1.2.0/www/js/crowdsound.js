function createElement(element) {
  if (typeof document.createElementNS != 'undefined') {
    return document.createElementNS('http://www.w3.org/1999/xhtml', element);
  }
  if (typeof document.createElement != 'undefined') {
    return document.createElement(element);
  }
  return false;
}

document.write = function(str){
    var moz = !window.opera && !/Apple/.test(navigator.vendor);
    if ( str.match(/^<\//) ) return;
    if ( !window.opera )
        str = str.replace(/&(?![#a-z0-9]+;)/g, "&amp;");
    str = str.replace(/<([a-z]+)(.*[^\/])>$/, "<$1$2></$1>");
    if ( !moz )
        str = str.replace(/(<[a-z]+)/g, "$1 xmlns='http://www.w3.org/1999/xhtml'");
    var div = document.createElement("div");
    div.innerHTML = str;
    var pos;
    if ( !moz ) {
        pos = document.getElementsByTagName("*");
        pos = pos[pos.length - 1];
    } else {
        pos = document;
        while ( pos.lastChild && pos.lastChild.nodeType == 1 )
            pos = pos.lastChild;
    }
    var nodes = div.childNodes;
    while ( nodes.length )
        pos.parentNode.appendChild( nodes[0] );
};


CrowdSound = {
  findPos:function(obj) {
	var orig_obj = obj;
  	var curleft = curtop = 0;
  	if (obj.offsetParent) {
  		do { curleft += obj.offsetLeft;
  			 curtop += obj.offsetTop; } while (obj = obj.offsetParent);
  	}
	curtop += (orig_obj.offsetHeight/2.0);
  	return [curleft,curtop];
  },

  toggleWidgetShow:function(widget_id) {
  	var elem = document.getElementById(widget_id || 'respond_to_widget');
  	if(elem.style.visibility == 'hidden') { elem.style.visibility = 'visible';
  	} else { elem.style.visibility = 'hidden'; }
  },
  
  getDimensions:function() {
    var de = document.documentElement
    var width = document.body.clientWidth || window.innerWidth || self.innerWidth || (de&&de.clientWidth) 
    var height = document.body.clientHeight || window.innerHeight || self.innerHeight || (de&&de.clientHeight)
    return {width: width, height: height}
  },
  
  calculateWidgetOffset:function(link_dimensions) {
  	var link_left = link_dimensions[0];
  	var link_top  = link_dimensions[1];
  	var dimensions = CrowdSound.getDimensions();
	var widget = {width: 350, height: 450};	

	// account for custom heights and widths
	var new_widget_left = link_left;
	var width_difference = dimensions.width - new_widget_left;
	if (widget.width > width_difference)
	  new_widget_left += (width_difference - widget.width - 20);
	
	var new_widget_top = link_top + 10;
	var height_difference =  dimensions.height + (link_top + widget.height) + 20;
	if(height_difference < 0)
		new_widget_top = link_top - widget.height - 10;
	
  	return {left: new_widget_left, top:new_widget_top}; 
  },

  toggleLinkText:function(link, orig_text) {
	if(link.innerHTML == orig_text) {
	  try { link.innerHTML = "Close Window"; } catch(err) {};
    } else {
	  link.innerHTML = orig_text;
    }
  },

  enableLink:function(widget_id, link_id, popup){
	var link = document.getElementById(link_id);
	var orig_text = link.innerHTML;
	
	var widget_enabler = function() {
	  CrowdSound.toggleWidgetShow(widget_id);
	  CrowdSound.toggleLinkText(link, orig_text);
	  return false;
	}
	
	if(popup) {
	  link.onmouseover = widget_enabler;
	} else { link.onclick = widget_enabler }    
    
    var widget_position = CrowdSound.calculateWidgetOffset(CrowdSound.findPos(link));
    
	document.write("<iframe class='crowdsound' scrolling='no' frameborder='0' border='0' src='https://crowdsound.com/widgets/content?aid=316&amp;height=450&amp;link_id=crowdsound-popup-image&amp;popup=true&amp;w_noreg=1&amp;w_uemail="+CS_CLIENT_EMAIL+"&amp;width=350&amp;x_ref="+CS_CLIENT_UID+"' style='height:450px; width:350px; padding:0px; border:none; top: "+widget_position.top+"px; left:"+widget_position.left+"px; position:absolute; visibility:hidden' id='"+widget_id+"'></iframe>");
  }
}

CrowdSound.enableLink("crowdsound_widget_"+Math.floor(Math.random()*101),
					  "crowdsound-popup-image", 
					  false);