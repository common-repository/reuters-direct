/*!
 * Bootstrap v3.3.5 (http://getbootstrap.com)
 * Copyright 2011-2015 Twitter, Inc.
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 */

// Tags Input
! function(a) {"use strict"; function b(b, c) {this.itemsArray = [], this.$element = a(b), this.$element.hide(), this.isSelect = "SELECT" === b.tagName, this.multiple = this.isSelect && b.hasAttribute("multiple"), this.objectItems = c && c.itemValue, this.placeholderText = b.hasAttribute("placeholder") ? this.$element.attr("placeholder") : "", this.inputSize = Math.max(1, this.placeholderText.length), this.$container = a('<div class="bootstrap-tagsinput"></div>'), this.$input = a('<input type="text" placeholder="' + this.placeholderText + '"/>').appendTo(this.$container), this.$element.after(this.$container); this.build(c) } function c(a, b) {if ("function" != typeof a[b]) {var c = a[b]; a[b] = function(a) {return a[c] } } } function d(a, b) {if ("function" != typeof a[b]) {var c = a[b]; a[b] = function() {return c } } } function e(a) {return a ? i.text(a).html() : ""} function f(a) {var b = 0; if (document.selection) {a.focus(); var c = document.selection.createRange(); c.moveStart("character", -a.value.length), b = c.text.length } else(a.selectionStart || "0" == a.selectionStart) && (b = a.selectionStart); return b } function g(b, c) {var d = !1; return a.each(c, function(a, c) {if ("number" == typeof c && b.which === c) return d = !0, !1; if (b.which === c.which) {var e = !c.hasOwnProperty("altKey") || b.altKey === c.altKey, f = !c.hasOwnProperty("shiftKey") || b.shiftKey === c.shiftKey, g = !c.hasOwnProperty("ctrlKey") || b.ctrlKey === c.ctrlKey; if (e && f && g) return d = !0, !1 } }), d } var h = {tagClass: function() {return "label label-info"}, itemValue: function(a) {return a ? a.toString() : a }, itemText: function(a) {return this.itemValue(a) }, freeInput: !0, addOnBlur: !0, maxTags: void 0, maxChars: void 0, confirmKeys: [13, 44], onTagExists: function(a, b) {b.hide().fadeIn() }, trimValue: !1, allowDuplicates: !1 }; b.prototype = {constructor: b, add: function(b, c) {var d = this; if (!(d.options.maxTags && d.itemsArray.length >= d.options.maxTags || b !== !1 && !b)) {if ("string" == typeof b && d.options.trimValue && (b = a.trim(b)), "object" == typeof b && !d.objectItems) throw "Can't add objects when itemValue option is not set"; if (!b.toString().match(/^\s*$/)) {if (d.isSelect && !d.multiple && d.itemsArray.length > 0 && d.remove(d.itemsArray[0]), "string" == typeof b && "INPUT" === this.$element[0].tagName) {var f = b.split(","); if (f.length > 1) {for (var g = 0; g < f.length; g++) this.add(f[g], !0); return void(c || d.pushVal()) } } var h = d.options.itemValue(b), i = d.options.itemText(b), j = d.options.tagClass(b), k = a.grep(d.itemsArray, function(a) {return d.options.itemValue(a) === h })[0]; if (!k || d.options.allowDuplicates) {if (!(d.items().toString().length + b.length + 1 > d.options.maxInputLength)) {var l = a.Event("beforeItemAdd", {item: b, cancel: !1 }); if (d.$element.trigger(l), !l.cancel) {d.itemsArray.push(b); var m = a('<span class="tag ' + e(j) + '">' + e(i) + '<span data-role="remove"></span></span>'); if (m.data("item", b), d.findInputWrapper().after(m), d.isSelect && !a('option[value="' + encodeURIComponent(h) + '"]', d.$element)[0]) {var n = a("<option selected>" + e(i) + "</option>"); n.data("item", b), n.attr("value", h), d.$element.append(n) } c || d.pushVal(), (d.options.maxTags === d.itemsArray.length || d.items().toString().length === d.options.maxInputLength) && d.$container.addClass("bootstrap-tagsinput-max"), d.$element.trigger(a.Event("itemAdded", {item: b })) } } } else if (d.options.onTagExists) {var o = a(".tag", d.$container).filter(function() {return a(this).data("item") === k }); d.options.onTagExists(b, o) } } } }, remove: function(b, c) {var d = this; if (d.objectItems && (b = "object" == typeof b ? a.grep(d.itemsArray, function(a) {return d.options.itemValue(a) == d.options.itemValue(b) }) : a.grep(d.itemsArray, function(a) {return d.options.itemValue(a) == b }), b = b[b.length - 1]), b) {var e = a.Event("beforeItemRemove", {item: b, cancel: !1 }); if (d.$element.trigger(e), e.cancel) return; a(".tag", d.$container).filter(function() {return a(this).data("item") === b }).remove(), a("option", d.$element).filter(function() {return a(this).data("item") === b }).remove(), -1 !== a.inArray(b, d.itemsArray) && d.itemsArray.splice(a.inArray(b, d.itemsArray), 1) } c || d.pushVal(), d.options.maxTags > d.itemsArray.length && d.$container.removeClass("bootstrap-tagsinput-max"), d.$element.trigger(a.Event("itemRemoved", {item: b })) }, removeAll: function() {var b = this; for (a(".tag", b.$container).remove(), a("option", b.$element).remove(); b.itemsArray.length > 0;) b.itemsArray.pop(); b.pushVal() }, refresh: function() {var b = this; a(".tag", b.$container).each(function() {var c = a(this), d = c.data("item"), f = b.options.itemValue(d), g = b.options.itemText(d), h = b.options.tagClass(d); if (c.attr("class", null), c.addClass("tag " + e(h)), c.contents().filter(function() {return 3 == this.nodeType })[0].nodeValue = e(g), b.isSelect) {var i = a("option", b.$element).filter(function() {return a(this).data("item") === d }); i.attr("value", f) } }) }, items: function() {return this.itemsArray }, pushVal: function() {var b = this, c = a.map(b.items(), function(a) {return b.options.itemValue(a).toString() }); b.$element.val(c, !0).trigger("change") }, build: function(b) {var e = this; if (e.options = a.extend({}, h, b), e.objectItems && (e.options.freeInput = !1), c(e.options, "itemValue"), c(e.options, "itemText"), d(e.options, "tagClass"), e.options.typeahead) {var i = e.options.typeahead || {}; d(i, "source"), e.$input.typeahead(a.extend({}, i, {source: function(b, c) {function d(a) {for (var b = [], d = 0; d < a.length; d++) {var g = e.options.itemText(a[d]); f[g] = a[d], b.push(g) } c(b) } this.map = {}; var f = this.map, g = i.source(b); a.isFunction(g.success) ? g.success(d) : a.isFunction(g.then) ? g.then(d) : a.when(g).then(d) }, updater: function(a) {e.add(this.map[a]) }, matcher: function(a) {return -1 !== a.toLowerCase().indexOf(this.query.trim().toLowerCase()) }, sorter: function(a) {return a.sort() }, highlighter: function(a) {var b = new RegExp("(" + this.query + ")", "gi"); return a.replace(b, "<strong>$1</strong>") } })) } if (e.options.typeaheadjs) {var j = e.options.typeaheadjs || {}; e.$input.typeahead(null, j).on("typeahead:selected", a.proxy(function(a, b) {e.add(j.valueKey ? b[j.valueKey] : b), e.$input.typeahead("val", "") }, e)) } e.$container.on("click", a.proxy(function() {e.$element.attr("disabled") || e.$input.removeAttr("disabled"), e.$input.focus() }, e)), e.options.addOnBlur && e.options.freeInput && e.$input.on("focusout", a.proxy(function() {0 === a(".typeahead, .twitter-typeahead", e.$container).length && (e.add(e.$input.val()), e.$input.val("")) }, e)), e.$container.on("keydown", "input", a.proxy(function(b) {var c = a(b.target), d = e.findInputWrapper(); if (e.$element.attr("disabled")) return void e.$input.attr("disabled", "disabled"); switch (b.which) {case 8: if (0 === f(c[0])) {var g = d.prev(); g && e.remove(g.data("item")) } break; case 46: if (0 === f(c[0])) {var h = d.next(); h && e.remove(h.data("item")) } break; case 37: var i = d.prev(); 0 === c.val().length && i[0] && (i.before(d), c.focus()); break; case 39: var j = d.next(); 0 === c.val().length && j[0] && (j.after(d), c.focus()) } {var k = c.val().length; Math.ceil(k / 5) } c.attr("size", Math.max(this.inputSize, c.val().length)) }, e)), e.$container.on("keypress", "input", a.proxy(function(b) {var c = a(b.target); if (e.$element.attr("disabled")) return void e.$input.attr("disabled", "disabled"); var d = c.val(), f = e.options.maxChars && d.length >= e.options.maxChars; e.options.freeInput && (g(b, e.options.confirmKeys) || f) && (e.add(f ? d.substr(0, e.options.maxChars) : d), c.val(""), b.preventDefault()); {var h = c.val().length; Math.ceil(h / 5) } c.attr("size", Math.max(this.inputSize, c.val().length)) }, e)), e.$container.on("click", "[data-role=remove]", a.proxy(function(b) {e.$element.attr("disabled") || e.remove(a(b.target).closest(".tag").data("item")) }, e)), e.options.itemValue === h.itemValue && ("INPUT" === e.$element[0].tagName ? e.add(e.$element.val()) : a("option", e.$element).each(function() {e.add(a(this).attr("value"), !0) })) }, destroy: function() {var a = this; a.$container.off("keypress", "input"), a.$container.off("click", "[role=remove]"), a.$container.remove(), a.$element.removeData("tagsinput"), a.$element.show() }, focus: function() {this.$input.focus() }, input: function() {return this.$input }, findInputWrapper: function() {for (var b = this.$input[0], c = this.$container[0]; b && b.parentNode !== c;) b = b.parentNode; return a(b) } }, a.fn.tagsinput = function(c, d) {var e = []; return this.each(function() {var f = a(this).data("tagsinput"); if (f) if (c || d) {if (void 0 !== f[c]) {var g = f[c](d); void 0 !== g && e.push(g) } } else e.push(f); else f = new b(this, c), a(this).data("tagsinput", f), e.push(f), "SELECT" === this.tagName && a("option", a(this)).attr("selected", "selected"), a(this).val(a(this).val()) }), "string" == typeof c ? e.length > 1 ? e : e[0] : e }, a.fn.tagsinput.Constructor = b; var i = a("<div />"); a(function() {a("input[data-role=tagsinput], select[multiple][data-role=tagsinput]").tagsinput() }) }(window.jQuery);

// TRAAC
var TRAAC_CONFIG = {'version': 'latest', 'adapters': [{'type': 'Http', 'config': {'url': 'https://pi-collector-prod.corp.aws.thomsonreuters.com/v1/es', 'flush_period': 0, 'headers': {"X-PRODUCT-INSIGHTS-TOKEN": '7843b6e797da418ab23dc3ecb4858582' } } } ] };
(function(a){a.TRAAC_CONFIG||console.error("Missing TRAAC config");a.TRAAC={track:function(b){TRAAC_CONFIG.teq.push(b)},registerOnce:function(b){TRAAC_CONFIG.teq.push({method:"registerOnce",p1:b})},register:function(b){TRAAC_CONFIG.teq.push({method:"register",p1:b})},setUser:function(b){TRAAC_CONFIG.teq.push({method:"setUser",p1:b})},clearUser:function(){TRAAC_CONFIG.teq.push({method:"clearUser"})},deRegister:function(b){TRAAC_CONFIG.teq.push({method:"deRegister",p1:b})},flush:function(b,a){TRAAC_CONFIG.teq.push({method:"flush", p1:b,p2:a})}};a.TRAAC_CONFIG.teq=[];for(var e=a.TRAAC_CONFIG.version+"/TRAAC.Core",c=[],d=0,f=a.TRAAC_CONFIG.adapters.length;d<f;d++)-1===c.indexOf(a.TRAAC_CONFIG.adapters[d].type)&&c.push(a.TRAAC_CONFIG.adapters[d].type);e+="."+c.sort().join(".");a=TRAAC_CONFIG.debug?"":".min";c=document.createElement("script");c.src="//traac-js.product-insight.thomsonreuters.com/"+e+a+".js";c.async=!0;document.getElementsByTagName("head")[0].appendChild(c)})(window);

// Custom Script
jQuery(document).ready(function() {
    jQuery('#OLRChannels').show();
    jQuery('label[for="category_checkboxes_SUBJ"]').hover(function() {
        jQuery("#SUBJ").show();
    }, function() {
        jQuery("#SUBJ").hide();
    });
    jQuery('label[for="category_checkboxes_N2"]').hover(function() {
        jQuery("#N2").show();
    }, function() {
        jQuery("#N2").hide();
    });
    jQuery('label[for="category_checkboxes_MCC"]').hover(function() {
        jQuery("#MCC").show();
    }, function() {
        jQuery("#MCC").hide();
    });
    jQuery('label[for="category_checkboxes_MCCL"]').hover(function() {
        jQuery("#MCCL").show();
    }, function() {
        jQuery("#MCCL").hide();
    });
    jQuery('label[for="category_checkboxes_RIC"]').hover(function() {
        jQuery("#RIC").show();
    }, function() {
        jQuery("#RIC").hide();
    });
    jQuery('label[for="category_checkboxes_A1312"]').hover(function() {
        jQuery("#A1312").show();
    }, function() {
        jQuery("#A1312").hide();
    });
    jQuery('label[for="category_checkboxes_Agency_Labels"]').hover(function() {
        jQuery("#Agency_Labels").show();
    }, function() {
        jQuery("#Agency_Labels").hide();
    });
    jQuery('label[for="category_checkboxes_User_Defined"]').hover(function() {
        jQuery("#User_Defined").show();
    }, function() {
        jQuery("#User_Defined").hide();
    });

	jQuery(".rd_button").click(function() {
	    jQuery('.channels').find('.channel_detail').each(function() {
	        $channel = jQuery(this).find('input.channel_info');
	        $category = jQuery(this).find('input.category_info');
	        $channel.val($channel.val() + ':' + $category.val());
	    });
	});

});

function setFilter(category) {
    jQuery('.category').removeClass('selected');
    jQuery('.channels').hide();
    if (category == 1) {
        jQuery('#OLR').addClass('selected');
        jQuery('#OLRChannels').show();
    } else if (category == 2) {
        jQuery('#TXT').addClass('selected');
        jQuery('#TXTChannels').show();
    } else if (category == 3) {
        jQuery('#PIC').addClass('selected');
        jQuery('#PICChannels').show();
    } else if (category == 4) {
        jQuery('#GRA').addClass('selected');
        jQuery('#GRAChannels').show();
    }
}

