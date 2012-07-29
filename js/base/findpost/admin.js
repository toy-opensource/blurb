/****************************************************************
* file: 	plugins/custom-blurb/js/base/findpost/admin.js
* author:	loushou
* date:		02jul11
* re:		js for admin features of custom blrub widgdet
*
*
* rev history
* [toy 02july11]		orig version
* [toy 08.29.11] 	adjusted from singular to multi choice

********************************************************************************/

(function($) {
	// create the namespace to hold this plugin, if it does not already exist
	$.QSTools = $.QSTools || {};

	// add this new feature to the the namespace we created
	$.QSTools.findPostsLb = function(o) {
		// create the container that will hold our lightbox
		this.e = {};
		this.e.w = $('<div class="find-post-form-wrapper"></div>').appendTo('body').hide();
		// merge the settings that were passed in, on top of the default settings
		this.o = $.extend(true, {}, this._defs, {author:'loushou', version:'1.0-lou'}, o);
		// setup this object
		this.init();
	}

	$.QSTools.findPostsLb.prototype = {
		_defs: {
			def_ajax: {},
			types:[],
			def_type:'',
			submit: function(){alert('suqsitting');},
			ajact:'qs-find-posts',
			ajsa: {
				'form':'f',
				search:'s'
			}
		},

		init: function() {
			var self = this;
			// create the request that will load the find posts form
			var data = $.extend({}, this.o.def_ajax, {
				action: self.o.ajact,
				sa: self.o.ajsa.form,
				types: self.o.types,
				'default': self.o.def_type
			});
			// load the find posts form, and pass the result of this ajax request to the function that will draw it on the screen and setup it's actions
			$.post(ajaxurl, data, function(r) { self.loadForm.apply(self, [r]); }, 'html');
		},

		loadForm: function(r) {
			var self = this;
			// if we actually have a result from the ajax that loaded the form
			if (r) {
				// put the resulting html from the ajax request into container for the lightbox
				$(r).appendTo(this.e.w);
				// transfer the title from the result to the title of the lightbox, so that jquery ui knows what the ligthbox title is
				this.e.w.attr('title', $(r).attr('title'));
				// create teh lightbox
				this.e.w.dialog({
					width:600,
					height:500,
					modal:true, // add faded background
					close: function(ev, ui) { $(this).dialog('destroy').remove() }, // when closing, make sure to destory this entire dialog, html and all
					buttons: {
						'Use this Post': function() {
							// get the selected value from the list of posts, and pass that to the reciever function, as well as a function to close the lightbox after the
							// storing of that selected value is complete
							var lb = this;
							var selected = $('.search-result:checked').filter(':eq(0)');

							// [toy 08.29.11] added this part to use arrays for multichoice
							var s = $('.search-result:checked');
							var ids = new Array();
							var posts = new Array();
							$('#cb:checked').each(function() {
								ids.push($(this).val());
								posts.push($(this));
							});

							//self.o.submit.apply(this, [selected, function(){$(lb).dialog('close');}])
							//self.o.submit.apply(this, [ids, function(){$(lb).dialog('close');}])
							self.o.submit.apply(this, [posts, function(){$(lb).dialog('close');}])
						}
					}
				});
				// setup the search action for the search form at the top of thel ightbox
				this.setupSearchForm();
			// if we do not have a valid result from the ajax request, pop an error
			} else {
				alert('Something bad happened while trying to load the search form.');
			}
		},

		setupSearchForm: function() {
			var self = this;
			// when the search form gets submitted
			$('form', this.e.w).submit(function(e) {
				e.preventDefault();
				// hide and clear out any previous errors, if they exist
				$('.cmp-errors', self.e.m).hide().empty();
				// setup the base ajax request
				var data = $.extend({}, self.o.def_ajax);
				var form = this;
				// merge the form fields inside of the search form, on top of the base request, to create the complete request
				data = $(form).qsSerialize(data);
				// provide a vidual representation of the system performing a search
				self.searching();
				// perform the actual search, and send the results to the function that will draw them on th screen
				$.post(ajaxurl, data, function(r) { self.processResults.apply(self, [r]); }, 'html');
				return false;
			});
		},

		searching: function() {
			var tbody = $('tbody', $('.search-results-wrapper', this.e.w));
			// disable all the form fields, so that if they hit the use this post button, it will do nothing
			$('input, textarea, select', tbody).attr('disabled', 'disabled');
			// cover the body of the table with a div that indicates that a search is being performed
			var pos = tbody.length == 0 ? {'top':0,'left':0} : tbody.position();
			var dims = {w:parseInt(tbody.outerWidth()), h:parseInt(tbody.outerHeight())};
			$('<div>Searching...</div>').css({
				position:'absolute',
				'top':pos['top'],
				'left':pos['left'],
				width:dims.w,
				height:dims.h,
				backgroundColor:'black',
				color:'#b2b2b2',
				fontWeight:'bold'
			}).appendTo($('.search-results-wrapper', this.e.w));
		},

		// put the results of the search into the container that is designed to hold it
		processResults: function(r) {
			$('.search-results-wrapper', this.e.w).empty();
			$(r).appendTo('.search-results-wrapper');
		}
	}
})(jQuery);

(function($) {
	$('.qs-find-post-button.add-post').die('click.qsFindPosts').live('click.qsFindPosts', function(e) {
		e.preventDefault();
		var tys = $(this).attr('types');
		tys = tys.length ? tys.split(',') : [];
		var args = {};
		if (tys.length) args.types = tys;
		var list = $($(this).attr('list'));
		// do not directly assign this function to args.submit, because otherwise mac ff does not pass it ot the findPostsLb function
		var submit = function(sel, after) {
			// [toy 08.29.11] adjusted this to take an array of ids
			var post_id = 0;
			var title = "";
			var ids = sel;
			for (var i in ids) {
				post_id = ids[i].val();
				title = ids[i].attr("title");
				
				var new_name = list.attr('new-names');
				var new_id = list.attr('new-ids')+post_id;
				var new_row = $('<div class="post-in-bucket" post-id="'+post_id+'"></div>');
				$('<span class="remove-post-from-bucket">X</span>').appendTo(new_row);
				$('<input type="hidden" name="'+new_name+'" id="'+new_id+'" value="'+post_id+'"/>').appendTo(new_row);
				$('<span class="bucket-post-title">'+title+'</span>').appendTo(new_row);
				new_row.appendTo(list);
			}

			if (typeof after == 'function') after();
		}
		args.submit = submit;
		(new $.QSTools.findPostsLb(args));
	});

  $('.remove-post-from-bucket').live('click', function(e) {
    e.preventDefault();
    $(this).closest('.post-in-bucket').remove();
  });
	
	/*
	$('.qs-find-post-button, .qs-find-post-button').die('click.qsFindPosts').live('click.qsFindPosts', function(e) {
		e.preventDefault();
		var tys = $(this).attr('types');
		tys = tys.length ? tys.split(',') : [];
		var args = {};
		if (tys.length) args.types = tys;
		if ($(this).attr('def')) args.def_type = $(this).attr('def');
		var idcont = $($(this).attr('id-tar'));
		var permcont = $($(this).attr('perm-tar'));
		var mba = $($(this).attr('mba-tar'));
		// do not directly assign this function to args.submit, because otherwise mac ff does not pass it ot the findPostsLb function
		var submit = function(sel, after) {
			if (idcont.length) idcont.val(sel.val());
			if (permcont.length) {
				var perm = $(sel).parents('td:eq(0)');
				var edit_link = [];
				if (perm.length) {
					edit_link = perm.find('.permalink-edit-link');
					perm = perm.find('.permalink-preview-value');
				}
				if (perm.length) {
					var tag = permcont.get(0).tagName.toLowerCase();
					if (tag == 'input') permcont.val(perm.val());
					else if (tag == 'select') {}
					else {
						permcont.empty().text(perm.val());
						if (edit_link.length) $('<a class="edit-link" target="_blank" href="'+edit_link.val()+'">Edit post &raquo;</a>').appendTo(permcont);
					}
				}
			}
			if (mba.length) {
				mba.attr('href', mba.attr('href').replace(/post_id=[0-9]+/g, 'post_id='+sel.val()));
			}
			if (typeof after == 'function') after();
		}
		args.submit = submit;
		(new $.QSTools.findPostsLb(args));
	});
	*/
	

	
})(jQuery);


// [toy 090211] added toggles for box
(function($) {
	// for title input
	$("#group-title-text-toggle").live("click", function(){
		if ($("#s").css("display")=="block") {
			$("#s").css("display", "none");
			$("#group-title-text-toggle").html(">");
		}
		else {
			$("#s").css("display", "block");
			$("#group-title-text-toggle").html("^");
		}
	});
	
	// for post types
	$("#group-post-type-toggle").live("click", function(){
		if ($("#post-type-ul").css("display")=="block") {
			$("#post-type-ul").css("display", "none");
			$("#group-post-type-toggle").html(">");
		}
		else {
			$("#post-type-ul").css("display", "block");
			$("#group-post-type-toggle").html("^");
		}

	});
	
	// for cats
	$("#group-category-toggle").live("click", function(){
		if ($("#category-ul").css("display")=="block") {
			$("#category-ul").css("display", "none");
			$("#group-category-toggle").html(">");
		}
		else {
			$("#category-ul").css("display", "block");
			$("#group-category-toggle").html("^");
		}

	});

	// for tags
	$("#group-tag-toggle").live("click", function(){
		if ($("#tag-input").css("display")=="block") {
			$("#tag-input").css("display", "none");
			$("#group-tag-toggle").html(">");
		}
		else {
			$("#tag-input").css("display", "block");
			$("#group-tag-toggle").html("^");
		}

	});
})(jQuery);
