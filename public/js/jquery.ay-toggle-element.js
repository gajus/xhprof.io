/**
 * jQuery toggle-element v0.0.4
 * https://github.com/gajus/toggle-element
 *
 * Licensed under the BSD.
 * https://github.com/gajus/toggle-element/blob/master/LICENSE
 *
 * Author: Gajus Kuizinas <g.kuizinas@anuary.com>
 */
(function ($) {
	'use strict';
	$.ay = $.ay || {};
	$.ay.toggleElement = function (options) {
		var trigger = this,
			setState,
			settings = $.extend({
				triggerClass: 'active', // the class that is given to the trigger when the element is active
				targetClass: 'visible' // the class that is given to the target when the target is active
			}, options);
		
		if (!settings.target || !settings.target instanceof $) {
			throw 'Target is not defined or it is not instance of jQuery.';
		}
		setState = function (state) {
			settings.trigger.toggleClass(settings.triggerClass, state);
			settings.target.toggleClass(settings.targetClass, state);
			if (typeof window.localStorage !== 'undefined') {
				localStorage['ay.toggleElement.' + settings.target.selector] = state ? 'on' : 'off';
			}
		};
		if (typeof window.localStorage !== 'undefined') {
			setState(localStorage['ay.toggleElement.' + settings.target.selector] === 'on');
		}
		settings.trigger.on('click', function () {
			setState(!settings.target.eq(0).hasClass(settings.targetClass));
		});
	};
}($));