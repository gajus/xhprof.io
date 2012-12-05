/**
 * jQuery toggle-element v0.0.2
 * https://github.com/gajus/toggle-element
 *
 * Licensed under the BSD.
 * https://github.com/gajus/toggle-element/blob/master/LICENSE
 *
 * Author: Gajus Kuizinas <g.kuizinas@anuary.com>
 */
(function ($) {
	'use strict';
	$.fn.ayToggleElement = function (target, options) {
		var trigger = this,
			target_id,
			setState,
			settings = $.extend({
				triggerClass: 'active', // the class that is given to the trigger when the element is active
				targetClass: 'visible' // the class that is given to the target when the target is active
			}, options);
		if (!target || !target instanceof $ || target.length !== 1) {
			throw 'Target is not defined, it is not instance of jQuery, it does not exist or more than once instance of the element is present.';
		}
		target_id = target.attr('id');
		if (!target_id) {
			throw 'Element visibility cannot be tracked if the target element does not have unique ID.';
		}
		setState = function (state) {
			trigger.toggleClass(settings.triggerClass, state);
			target.toggleClass(settings.targetClass, state);
			localStorage['ay.state.' + target_id] = state ? 'on' : 'off';
		};
		setState(localStorage['ay.state.' + target_id] === 'on');
		trigger.on('click', function () {
			setState(!target.hasClass(settings.targetClass));
		});
	};
}($));

