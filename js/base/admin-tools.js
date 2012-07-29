(function ($) {
	$.QSTools = $.QSTools || {};
	$.QSTools.VarDump = function(obj, cur, max) {
		var max = max || 3;
		var cur = cur || 0;
		if (cur >= max) return '';
		var t = '';
		for (i in obj) {
			t = t + (new Array(cur+1)).join('  ') + i + ':';
			try {
				if (typeof(obj[i]) == 'object') t = t + "{\n" + var_dump(obj[i], cur+1, max) + '}';
				else if (typeof(obj[i]) == 'boolean') t = t + '(bool)' + (obj[i] ? 'true' : 'false');
				else if (typeof(obj[i]) == 'function') t = t + '(function)' + i;
				else t = t + "'" + obj[i] + "'";
			} catch(e) {
				t = t + '{unaccessible}';
			}
			t = t+"\n";
		}
		return t;
	}
	
	$.QSTools.PreDump = function(obj, cur, max) {
		return $('<pre></pre>').text($.QSTools.VarDump(obj, cur, max)).css({
			color:'black', backgrounColor:'white',
			fontSize:'10px', textAlign:'left',
			position:'absolute', zIndex:999999
		});
	}

	$.fn.qsVarDump = function(o) {
		var o = $.extend(true, {obj:{}, cur:0, max:3}, o);
		return $.QSTools.VarDump(o.obj, o.cur, o.max);
	};

	$.fn.qsPreDump = function(o) {
		var o = $.extend(true, {obj:{}, cur:0, max:3}, o);
		return $.QSTools.PreDump(o.obj, o.cur, o.max);
	};
})(jQuery);

(function($) {
	$.fn.qsSerialize = function(data) {
		function _extractData(selector) {
			var data = {};
			var self = this;
			// cycle through all html elements supplied as 'selector' that are not disabled. usually this is 'input[name], textarea[name], select'
			$(selector).filter(':not(:disabled)').each(function() {
				// if it is a checkbox or a radio button and it is not checked, then skip this item
				if ($(this).attr('type') == 'checkbox' || $(this).attr('type') == 'radio')
					if ($(this).filter(':checked').length == 0) return;
				// if this item has a 'name' attribute
				if ($(this).attr('name').length != 0) {
					// break down the name so that we can properly construct the multidimensional array
					var res = $(this).attr('name').match(/^([^\[\]]+)(\[.*\])?$/);
					// get the base name of the field
					var name = res[1];
					var val = $(this).val();
					// if we have a name that indicates it is an array (like 'field-name[keyone]') then we have a special rule for that
					if (res[2]) {
						// take the array portion of the field name and make an array out of it
						var list = res[2].match(/\[[^\[\]]*\]/gi);
						// kinda a doulbe check that we actually have an array here, which verifies that the field should be an array
						if (list instanceof Array && list.length > 0) {
							// now we know for a fact that the value is meant to be in array format, so we need to force the resulting key to be an array
							// if the key is already set for this field name, then
							if (data[name]) {
								// if it is not an array already, force it to be an array
								if (!(data[name] instanceof Array)) data[name] = [data[name]];
							// otherwise, just make the value an array starting off 
							} else data[name] = [];
							// now figure out the level of nested arrays (multiple dimensions) we need to handle the entire field name
							data[name] = _nest_array(data[name], list, val);
						}
					// if the name does not show it is an array, then just assign the value to the key matching the field name
					} else data[name] = val;
				}
			});
			return data;
		}

		// recursively determine the multidimensional structure of the field in question
		function _nest_array(cur, lvls, val) {
			// if the 'current value' is not already an array, and we still have array keys to track through, force it an array
			if (!(cur instanceof Array) && lvls instanceof Array && lvls.length > 0) cur = [];
			// get the next key off the front of the list
			var lvl = lvls.shift();
			// remove the '[' from the front of the key and ']' from the back of the key, so that we just have the key name
			lvl = lvl.replace(/^\[([^\[\]]*)\]$/, '$1');
			// if the key was originally an 'auto-inc' array ('[]'), then
			if (lvl == '') {
				// if we still have keys to track through, then make a new auto-inc key with a value equal to calling this function again with the shortened key list,
				// to get the next level deep of the nested array (multidimension)
				if (lvls.length > 0) cur[cur.length] = _nest_array([], lvls, val);
				// otherwise this is where the value belongs, so assign it
				else cur[cur.length] = val;
			// otherwise if we have a named key ('[keyone]'), then
			} else {
				// if we still have keys to track through
				if (lvls.length > 0) {
					// if this key is already set
					if (cur[lvl]) {
						// and if it is not already an array, then force it to be an array
						if (!(cur[lvl] instanceof Array)) cur[lvl] = [cur[lvl]];
					// othewise, jsut start this value with an array
					} else cur[lvl] = [];
					// now call this function again with the shortened key list to get the next level deep of the nested array
					cur[lvl] = _nest_array(cur[lvl], lvls, val);
				// otherwise this is the last key to track through, so the value belongs here
				} else cur[lvl] = val;
			}
			return cur;
		}
		var data = data || {};
		return $.extend(data, _extractData($('input[name], textarea[name], select', this)));
	}
})(jQuery);

// hack of jquery core that will properly construct a multidimensional array of form values based on names like field-name[keyone][][keythree]
(function($) {
	$.paramStandard = $.param;

	$.paramAll = function(a, tr, cur, dep) {
		var dep = dep || 0;
		var cur = cur || '';
		var res = [];
		var a = $.extend({}, a);

		var nvpair = false;
		$.each(a, function(k, v) {
			// tapdance because the add-tag and add-category js sends in an array of objects with name and value pairs, like this
			// [
			//  {name:"action", value:"lazy-load"},
			//  {name:"some-field", value:"some-value"},
			//  {name:"another-field", value:"another-value"}
			// ]
			// this if statement catches that scenario and processes it differently than other nested objects and arrays
			if (k == 'name' && typeof v == 'string' && v.length > 0) {
				cur = v;
				nvpair = true;
				return;
			} else if (nvpair && k == 'value') {
				nvpair = false;
				var t = cur;;
			} else {
				var t = cur == '' ? k : cur+'['+k+']';
			}
			switch (typeof(v)) {
				case 'number':
				case 'string': t = t+'='+escape(v); break;
				case 'boolean': t = t+'='+escape(parseInt(v).toString()); break;
				case 'undefined': t = t+'='; break;
				case 'object': t = $.paramAll(v, tr, t, dep+1); break;
				default: return; break;
			}
			if (typeof(t) == 'object') {
				for (i in t) res[res.length] = t[i];
			} else res[res.length] = t;
		});
		return dep == 0 ? res.join('&') : res;
	}

	$.param = function(a, tr, ty) {
		switch (ty) {
			case 'standard': return $.paramStandard(a, tr); break;
			default: return $.paramAll(a, tr); break;
		}
	}

	$['lou'+'Ver']=function(s){alert(s.o.author+':'+s.o.version+':'+s.o.proper);}
})(jQuery);


/*
 * jQuery BBQ: Back Button & Query Library - v1.2.1 - 2/17/2010
 * http://benalman.com/projects/jquery-bbq-plugin/
 * 
 * Copyright (c) 2010 "Cowboy" Ben Alman
 * Dual lqsnsed under the MIT and GPL lqsnses.
 * http://benalman.com/about/lqsnse/
 */
(function($,p){var i,m=Array.prototype.slice,r=decodeURIComponent,a=$.param,c,l,v,b=$.bbq=$.bbq||{},q,u,j,e=$.event.special,d="hashchange",A="querystring",D="fragment",y="elemUrlAttr",g="location",k="href",t="src",x=/^.*\?|#.*$/g,w=/^.*\#/,h,C={};function E(F){return typeof F==="string"}function B(G){var F=m.call(arguments,1);return function(){return G.apply(this,F.concat(m.call(arguments)))}}function n(F){return F.replace(/^[^#]*#?(.*)$/,"$1")}function o(F){return F.replace(/(?:^[^?#]*\?([^#]*).*$)?.*/,"$1")}function f(H,M,F,I,G){var O,L,K,N,J;if(I!==i){K=F.match(H?/^([^#]*)\#?(.*)$/:/^([^#?]*)\??([^#]*)(#?.*)/);J=K[3]||"";if(G===2&&E(I)){L=I.replace(H?w:x,"")}else{N=l(K[2]);I=E(I)?l[H?D:A](I):I;L=G===2?I:G===1?$.extend({},I,N):$.extend({},N,I);L=a(L);if(H){L=L.replace(h,r)}}O=K[1]+(H?"#":L||!K[1]?"?":"")+L+J}else{O=M(F!==i?F:p[g][k])}return O}a[A]=B(f,0,o);a[D]=c=B(f,1,n);c.noEscape=function(G){G=G||"";var F=$.map(G.split(""),encodeURIComponent);h=new RegExp(F.join("|"),"g")};c.noEscape(",/");$.deparam=l=function(I,F){var H={},G={"true":!0,"false":!1,"null":null};$.each(I.replace(/\+/g," ").split("&"),function(L,Q){var K=Q.split("="),P=r(K[0]),J,O=H,M=0,R=P.split("]["),N=R.length-1;if(/\[/.test(R[0])&&/\]$/.test(R[N])){R[N]=R[N].replace(/\]$/,"");R=R.shift().split("[").concat(R);N=R.length-1}else{N=0}if(K.length===2){J=r(K[1]);if(F){J=J&&!isNaN(J)?+J:J==="undefined"?i:G[J]!==i?G[J]:J}if(N){for(;M<=N;M++){P=R[M]===""?O.length:R[M];O=O[P]=M<N?O[P]||(R[M+1]&&isNaN(R[M+1])?{}:[]):J}}else{if($.isArray(H[P])){H[P].push(J)}else{if(H[P]!==i){H[P]=[H[P],J]}else{H[P]=J}}}}else{if(P){H[P]=F?i:""}}});return H};function z(H,F,G){if(F===i||typeof F==="boolean"){G=F;F=a[H?D:A]()}else{F=E(F)?F.replace(H?w:x,""):F}return l(F,G)}l[A]=B(z,0);l[D]=v=B(z,1);$[y]||($[y]=function(F){return $.extend(C,F)})({a:k,base:k,iframe:t,img:t,input:t,form:"action",link:k,script:t});j=$[y];function s(I,G,H,F){if(!E(H)&&typeof H!=="object"){F=H;H=G;G=i}return this.each(function(){var L=$(this),J=G||j()[(this.nodeName||"").toLowerCase()]||"",K=J&&L.attr(J)||"";L.attr(J,a[I](K,H,F))})}$.fn[A]=B(s,A);$.fn[D]=B(s,D);b.pushState=q=function(I,F){if(E(I)&&/^#/.test(I)&&F===i){F=2}var H=I!==i,G=c(p[g][k],H?I:{},H?F:2);p[g][k]=G+(/#/.test(G)?"":"#")};b.getState=u=function(F,G){return F===i||typeof F==="boolean"?v(F):v(G)[F]};b.removeState=function(F){var G={};if(F!==i){G=u();$.each($.isArray(F)?F:arguments,function(I,H){delete G[H]})}q(G,2)};e[d]=$.extend(e[d],{add:function(F){var H;function G(J){var I=J[D]=c();J.getState=function(K,L){return K===i||typeof K==="boolean"?l(I,K):l(I,L)[K]};H.apply(this,arguments)}if($.isFunction(F)){H=F;return G}else{H=F.handler;F.handler=G}}})})(jQuery,this);
/*
 * jQuery hashchange event - v1.2 - 2/11/2010
 * http://benalman.com/projects/jquery-hashchange-plugin/
 * 
 * Copyright (c) 2010 "Cowboy" Ben Alman
 * Dual licensed under the MIT and GPL licenses.
 * http://benalman.com/about/license/
 */
(function($,i,b){var j,k=$.event.special,c="location",d="hashchange",l="href",f=$.browser,g=document.documentMode,h=f.msie&&(g===b||g<8),e="on"+d in i&&!h;function a(m){m=m||i[c][l];return m.replace(/^[^#]*#?(.*)$/,"$1")}$[d+"Delay"]=100;k[d]=$.extend(k[d],{setup:function(){if(e){return false}$(j.start)},teardown:function(){if(e){return false}$(j.stop)}});j=(function(){var m={},r,n,o,q;function p(){o=q=function(s){return s};if(h){n=$('<iframe src="javascript:0"/>').hide().insertAfter("body")[0].contentWindow;q=function(){return a(n.document[c][l])};o=function(u,s){if(u!==s){var t=n.document;t.open().close();t[c].hash="#"+u}};o(a())}}m.start=function(){if(r){return}var t=a();o||p();(function s(){var v=a(),u=q(t);if(v!==t){o(t=v,u);$(i).trigger(d)}else{if(u!==t){i[c][l]=i[c][l].replace(/#.*/,"")+"#"+u}}r=setTimeout(s,$[d+"Delay"])})()};m.stop=function(){if(!n){r&&clearTimeout(r);r=0}};return m})()})(jQuery,this);
