// Compatibility
if (typeof String.prototype.trim != 'function') {
	String.prototype.trim = function () {
		return this.replace(/^\s+|\s+$/g, '');
	};
}

if (typeof String.prototype.startsWith != 'function') {
	String.prototype.startsWith = function (str){
		return this.substring(0, input.length) === input;
	};
}

if (typeof String.prototype.endsWith != 'function') {
	String.prototype.endsWith = function (str){
		return this.slice(-str.length) == str;
	};
}

if (typeof Object.prototype.hasOwnProperty != 'function') {
	Object.prototype.hasOwnProperty = function(str) {
		if (this.getAttribute(str)) { return true; }
		return false;
	};
}

if (typeof Object.prototype.hasAttribute != 'function') {
	Object.prototype.hasAttribute = function(str) {
		return this.hasOwnProperty(str);
	};
}

if ( typeof Object.prototype.stop != 'function' ) {
	Object.prototype.stop = function(e) {
		var ev = window.event ? window.event : e;
		if ( ev.preventDefault ) {
			ev.preventDefault(e);
		} else {
			ev.returnValue = false;
		}
		if ( ev.cancelBubble ) {
			ev.cancelBubble = true;
		}
		return false;
	}
}

function eId(e) {
	if (typeof e === 'string') {
		return document.getElementById(e);
	}
	return e;
}


function elements(e) {
	return document.getElementsByTagName(e);
}

function hide(e) {
	e = eId(e);
	e.style.display = 'none';
}

function show(e) {
	e = eId(e);
	e.style.display = '';
}

function tooltip(e) {
	//TODO
}

function has(e, t, c, r) {
	t = t || 'item';
	c = c || false;
	r = r || false;
	
	if (t === 'item' && eId(e)) {
		if (r) {
			return eId(e);
		}
		return true;
	}
	
	if (t === 'list' && elements(e).length) {
		if (r) {
			return elements(e);
		}
		return true;
	}
	
	if (c && r) {
		if (t === 'item') {
			return create(e);
		}
	} else if (c) {
		if (t === 'item') {
			create(e);
			return true;
		}
	}
	return false;
}


function create(e, p) {
	var e = document.createElement(e);
	
	if (typeof p !== 'undefined') {
		p.appendChild(e);
	}
	return e;
}

function remove(e, p) {
	if (typeof p !== 'undefined' && typeof e !== 'undefined') {
		p.removeChild(e);
	}
}

function matches(el, selector) {
  return (el.matches || 
		el.matchesSelector || 
		el.msMatchesSelector || 
		el.mozMatchesSelector || 
		el.webkitMatchesSelector || 
		el.oMatchesSelector).call(el, selector);
}

function next(e) {
	e.nextElementSibling;
}

function attr(e, a, v) {
	if (typeof v === 'undefined') {
		return (e.getAttribute(a)) ? 
			e.getAttribute(a) : null ;
	}
	e.setAttribute(a, v);
}


function empty(d) {
	while(d.firstChild) {
		d.removeChild(d.firstChild);
	}
}

function ajax(url, rtype, callback) {
	var x;
	url	= url	|| '/';
	rtype	= rtype	|| 'text';
	
	if (typeof JSON !== 'object' && rtype === 'json') {
		load('/json2.js');
	}
	
	if (window.ActiveXObject) {
		x = new ActiveXObject("Microsoft.XMLHTTP");
	} else {
		x = new XMLHttpRequest();
	}
	
	x.open('GET', url, true);
	x.send(null);
	
	if (typeof callback !== 'function') {
		return;
	}
	
	x.onreadystatechange = function() {
		if (x.readyState === 4 && x.status === 200) {
			switch(rtype) {
				case 'xml':
					callback(x.responseXml);
					break;
				
				case 'json':
					callback(
						JSON.parse(x.responseText)
					);
					break;
				
				default:
					callback(x.responseText);
					break;
			}
		}
	};
}

function load(f, callback) {
	var h = elements('head')[0];
	if (f.endsWith('.js')) {
		var s = create('script', h);
		attr(s, 'type', 'text/javascript');
		attr(s, 'src', f);
	}
	else if (f.endsWith('.css')) {
		var s = create('link', h);
		attr(s, 'rel', 'stylesheet');
		attr(s, 'type', 'text/css');
		attr(s, 'href', f);
	}
}

function ready(f) {
	var ol = window.onload;
	if (typeof window.onload != 'function') {
		window.onload = f;
	} else {
		window.onload = function() {
			if (ol) { ol(); }
		}
		f();
	}
}

/**
 * UI
 **/
function vote(e, v) {
	var c = e.parentNode;
	var s = c.parentNode;
	var p = s.parentNode;
	var i = p.getAttribute('id');
	
	if ( null == i ) {
		p = p.parentNode;
		i = p.getAttribute('id');
	}
	
	var u = ( v == 1 )? '/vote/'+i+'/up' : '/vote/'+i+'/down';
	ajax( u, 'text', function( r ) {
		if ( r == 'problem' ) {
			c.innerHTML = 'E';
		} else {
			c.className += ( v == 1 )? ' p1' : ' m1';
			c.innerHTML = ( v == 1 ) ? '+1' : '-1';
		}
	});
}

function utcFormat( time ) {
	time = time.replace(/\.\d+/, ""); // remove milliseconds
	time = time.replace(/-/, "/").replace(/-/, "/");
	time = time.replace(/T/, " ").replace(/Z/, " UTC");
	time = time.replace(/([\+\-]\d\d)\:?(\d\d)/, " $1$2"); // -04:00 -> -0400
	time = new Date(time * 1000 || time);
	
	return time;
}

function mods(e) {
	var id = e.getAttribute('id');
	var m = e.querySelectorAll('.meta')[0];
	var t = attr( e.querySelectorAll('time')[0], 'datetime' );
	var n = new Date();
	t = utcFormat( t );
	
	var d = ((n.getTime() - t) * .001) >> 0;
	if ( ( d / 86400 ) > 1 ) { // Max vote/edit period is 2 days 68.95
		return;
	}
	
	if ( votes[id] != undefined ) {
		if ( votes[id] === '1' ) {
			m.innerHTML += " <span class='vote p1'>+1</span>";
		} else if ( votes[id] === '-1' ) {
			m.innerHTML += " <span class='vote m1'>-1</span>";
		}
	} else if ( edits[id] != undefined ) {
		m.innerHTML += " <span class='vote self'>"+
			"( <a href='/posts/"+ id +"/edit/"+ edits[id] +"'>edit</a> )</span>";
	} else {
		m.innerHTML += " <span class='vote'>" +
		"<a href='#' title='Upvote' onclick='javascript:vote(this, 1); return false;'>&nbsp;</a> / " +
		"<a href='#' title='Downvote' onclick='javascript:vote(this, -1); return false;'>&nbsp;</a></span>";
	}
}

function light(e) {
	var p	= e.parentNode;
	var h	= p.querySelector('.highlight');
	var c	= p.querySelector('.container');
	
	if  ( h.style.display === 'none' ) {
		h.style.display = 'block';
		c.style.display = 'none';
		e.innerHTML = '[view plain]';
	} else {
		h.style.display = 'none';
		c.style.display = 'block';
		c.select();
		e.innerHTML = '[view formatted]';
	}
}

function code(e) {
	var k = e.getClientRects().length;
	if ( k <= 1 ) {
		return;
	}
	var h = e.innerHTML.trim();
	var l = h.match(/^.*((\r\n|\n|\r)|$)/gm);
	var d = "<a href='#' class='anchors' onclick='javascript:light(this); return false;'>[view plain]</a>";
	var o = '';
	if (l.length == 0) { return; }
	e.className = 'styled';
	var i;
	for(i = 0; i < l.length; i++) {
		o += '<span>' + l[i] + '</span>';
	}
	//alert ( i);
	e.innerHTML = d + "<textarea class='container' rows='"+ ( i + 1 ) +"'>"+ h +'</textarea>';
	e.innerHTML += "<div class='highlight'>"+ o +'</div>';
}

ready(function() {
	var c = elements('code');
	var p = document.querySelectorAll('.post');
	var f = elements('form');
	var t = document.querySelectorAll('[rel="tool"]');
	
	if (c.length) {
		for (var i = 0; i<c.length; i++) {
			code(c[i]);
		}
	}
	if (p.length && p[0].tagName == 'DIV' ) {
		for (var i = 0; i<p.length; i++) {
			mods(p[i]);
			//vlinks(p[i]);
		}
	}
	if (t.length) {
		for ( var i = 0; i < t.length; i++ ) {
			tooltip(t[i]);
		}
	}
	if (f.length) {
		for (var i = 0; i<f.length; i++) {
			f[i].addEventListener('submit', function(e) { return validate(this); }, false );
		}
	}
});

// Courtesy of Goker Cebeci at https://coderwall.com/p/uub3pw
(function ago(selector) {
	var templates = {
		prefix: "",
		suffix: " ago",
		seconds: "seconds",
		minute: "a minute",
		minutes: "%d minutes",
		hour: "an hour",
		hours: "%d hours",
		day: "a day",
		days: "%d days",
		month: "a month",
		months: "%d months",
		year: "a year",
		years: "%d years"
	};
	var template = function(t, n) {
		return templates[t] && templates[t].replace(/%d/i, Math.abs(Math.round(n)));
	};
	var timer = function(time) {
		if (!time)
			return;
		time = utcFormat( time );
		//time = new Date(time * 1000 || time);
		
		var now = new Date();
		var seconds = ((now.getTime() - time) * .001) >> 0;
		
		if ( seconds < 45 ) {
			return 'now';
		}
		
		var minutes = seconds / 60;
		var hours = minutes / 60;
		var days = hours / 24;
		var years = days / 365;
		
		return templates.prefix + (
			seconds < 45 && template('seconds', seconds) ||
			seconds < 90 && template('minute', 1) ||
			minutes < 45 && template('minutes', minutes) ||
			minutes < 90 && template('hour', 1) ||
			hours < 24 && template('hours', hours) ||
			hours < 42 && template('day', 1) ||
			days < 30 && template('days', days) ||
			days < 45 && template('month', 1) ||
			days < 365 && template('months', days / 30) ||
			years < 1.5 && template('year', 1) ||
			template('years', years)
		) + templates.suffix;
	};
	var el = elements('time');
	for (var i in el) {
		var $this = el[i];
		if (typeof $this === 'object') {
			$this.innerHTML = timer(attr($this, 'datetime'));
		}
	}
	// update time every minute
	setTimeout(ago, 60000);
})();
