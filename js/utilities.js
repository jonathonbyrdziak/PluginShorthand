
/* 
 * The MIT License (MIT)
 * 
 * Copyright (c) 2014 Jonathon Byrd jonathonbyrd@gmail.com
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

(function(j){	
var Shorthand_Tabs = Class.extend({
	defaults : {
		container:	null,
		activeClass:	'ui-state-active',
		hoveredClass:	'ui-state-hover',
		activeWrapperClass: 'active-wrapper'
	},
	
	// Initializing
	init: function(o) {
		this.setOptions(o);
		this.addListeners();
	},
 
	addListeners: function() {
		this.element().find('li a').each(function(k,el){
			j(el).bind('click', this.li_a_clicked.bind(this));
		}.bind(this));
		this.element().find('li').each(function(k,el){
			j(el).bind('mouseover', this.li_mouseover.bind(this));
		}.bind(this));
		this.element().find('li').each(function(k,el){
			j(el).bind('mouseout', this.li_mouseout.bind(this));
		}.bind(this));
	},
	
	li_mouseover: function(e) {
		j(e.currentTarget).addClass(this.get('hoveredClass'));
	},
	
	li_mouseout: function(e) {
		j(e.currentTarget).removeClass(this.get('hoveredClass'));
	},
	
	li_a_clicked: function(e) {
		e.preventDefault();
		$a = j(e.currentTarget);
		var href = $a.attr('href');
		
		// setting the tab classes
		this.element().find('li').each(function(k,el){
			j(el).removeClass(this.get('activeClass'));
		}.bind(this));
		$a.closest('li').addClass(this.get('activeClass'));
		
		// setting the wrapper styles
		j('.tab-wrapper').each(function(k,el){
			j(el).removeClass(this.get('activeWrapperClass'));
		}.bind(this));
		j(href).addClass(this.get('activeWrapperClass'));
	}
});
j.fn.Shorthand_Tabs = function(o){
	// initializing
	var args = arguments;
	var o = o || {'container':''};
	return this.each(function(){
		// load the saved object
		var api = j.data(this, 'Shorthand_Tabs');
		// create and save the object if it does not exist
		if (!api) {
			o.container = j(this);
			api = new Shorthand_Tabs(o);
			j.data(this, 'Shorthand_Tabs', api);
		}
                if (typeof api[o] == 'function') {
                        if (args[0] == o) delete args[0];
                        api[o].bind(api);
                        var parameters = Array.prototype.slice.call(args, 1);
                        return api[o].apply(api,parameters);
               }
		return api;
	});
};
})(jQuery);
