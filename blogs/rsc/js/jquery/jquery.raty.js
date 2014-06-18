/**
 * jQuery Raty - A Star Rating Plugin - http://wbotelhos.com/raty
 * ---------------------------------------------------------------------------------
 *
 * jQuery Raty is a plugin that generates a customizable star rating automatically.
 *
 * Licensed under The MIT License
 *
 * @version			1.4.3
 * @since			06.11.2010
 * @author			Washington Botelho dos Santos
 * @documentation	http://wbotelhos.com/raty
 * @twitter			http://twitter.com/wbotelhos
 * @license			http://opensource.org/licenses/mit-license.php
 * @package			jQuery Plugins
 *
 * Usage with default values:
 * ---------------------------------------------------------------------------------
 * $('#star').raty();
 *
 * <div id="star"></div>
 *
 *
 * $('.star').raty();
 *
 * <div class="star"></div>
 * <div class="star"></div>
 * <div class="star"></div>
 *
 */

;(function($) {

	$.fn.raty = function(settings) {

		if (this.length == 0) {
			return;
		} else if (this.length > 1) {
			return this.each(function() {
				$.fn.raty.apply($(this), [settings]);
			});
		}

		var opt			= $.extend({}, $.fn.raty.defaults, settings),
			$this		= $(this),
			id			= this.attr('id'),
			start		= 0,
			starClass	= opt.starOn,
			hint		= '',
			target		= opt.target,
			width		= (opt.width) ? opt.width : (opt.number * opt.size + opt.number * 4);

		if (id === undefined) {
			id = 'raty-' + $this.index();
			$this.attr('id', id); 
		}

		if (opt.number > 20) {
			opt.number = 20;
		} else if (opt.number < 0) {
			opt.number = 0;
		}

		$this.data('options', opt);

		if (!isNaN(parseInt(opt.start)) && opt.start > 0) {
			start = (opt.start > opt.number) ? opt.number : opt.start;
		}

		for (var i = 1; i <= opt.number; i++) {
			starClass = (start >= i) ? opt.starOn: opt.starOff;

			hint = (i <= opt.hintList.length && opt.hintList[i - 1] !== null) ? opt.hintList[i - 1] : i;

			$this
			.append('<span id="' + id + '-' + i + '" alt="' + i + '" title="' + hint + '" class="' + id + ' ' + starClass + '"></span>')
			.append((i < opt.number) ? '&nbsp;' : '');
		}

		if (opt.iconRange && start > 0) {
			fillStar(id, start, opt);	
		}

		var $score = $('<input/>', {
			id:		id + '-score',
			type:	'hidden',
			name:	opt.scoreName
		}).appendTo($this);

		if (start > 0) {
			$score.val(start);
		}

		if (opt.half) {
			splitStar($this, $('input#' + id + '-score').val(), opt);
		}

		if (!opt.readOnly) {
			if (target !== null) {
				target = $(target);

				if (target.length == 0) {
					debug('Target selector invalid or missing!');
				}
			}

			if (opt.cancel) {
				var stars	= $('span.' + id),
					cancel	= '<span alt="x" title="' + opt.cancelHint + '" class="button-cancel ' + opt.cancelOff + '"/>';

				if (opt.cancelPlace == 'left') {
					$this.prepend(cancel + '&nbsp;');
				} else {
					$this.append('&nbsp;').append(cancel);
				}

				$('#' + id + ' span.button-cancel').mouseenter(function() {
					$(this).removeClass(opt.cancelOff).addClass(opt.cancelOn);
					stars.removeClass(opt.starOn).addClass(opt.starOff);
					setTarget(target, '', opt);
				}).mouseleave(function() {
					$(this).removeClass(opt.cancelOn).addClass(opt.cancelOff);
					$this.mouseout();
				}).click(function(evt) {
					$('input#' + id + '-score').removeAttr('value');

					if (opt.click) {
			          opt.click.apply($this, [null, evt]);
			        }
				});

				$this.css('width', width + opt.size + 4);
			} else {
				$this.css('width', width);
			}

			$this.css('cursor', 'pointer');
			bindAll($this, opt, target);
		} else {
			$this.css('cursor', 'default');
			fixHint($this, start, opt);
		}

		return $this;
	};
	
	function bindAll(context, opt, target) {
		var id = context.attr('id'),
			score = $('input#' + id + '-score'),
			stars = context.children('span.' + id);

		context.mouseleave(function() {
			var current_score = $('input#' + this.id + '-score');
			initialize(context, current_score.val(), opt);
			clearTarget(target, current_score, opt);
		});

		stars.bind(((opt.half) ? 'mousemove' : 'mouseover'), function(e) {
			fillStar(id, $(this).attr('alt'), opt);
			if (opt.half) {
				var percent = parseFloat(((e.pageX - $(this).offset().left) / opt.size).toFixed(1));
				percent = (percent >= 0 && percent < 0.5) ? 0.5 : 1;

				context.data('score', parseFloat($(this).attr('alt')) + percent - 1);

				splitStar(context, context.data('score'), opt);
			} else {
				fillStar(id, $(this).attr('alt'), opt);
			}

			setTarget(target, $(this).attr('alt'), opt);
		}).click(function(evt) {
			score.val(opt.half ? context.data('score') : $(this).attr('alt'));

			if (opt.click) {
				opt.click.apply(context, [score.val(), evt]);
			}
		});
	};

	function clearTarget(target, score, opt) {
		if (target !== null) {
			var value = '';

			if (opt.targetKeep) {
				value = score.val();

				if (opt.targetType == 'hint') {
					if (score.val() == '' && opt.cancel) {
						value = opt.cancelHint;
					} else {
						value = opt.hintList[Math.ceil(score.val()) - 1];
					}
				}
			}

			if (isField(target)) {
				target.val(value);
			} else {
				target.html(value);
			}
		}
	};

	function fillStar(id, score, opt) {
		var qtyStar	= $('span.' + id).length,
			item	= 0,
			range	= 0,
			star,
			starOn = opt.starOn,
			starOff = opt.starOff;

		for (var i = 1; i <= qtyStar; i++) {
			star = $('span#' + id + '-' + i);
			if (i <= score) {
				if (opt.iconRange && opt.iconRange.length > item) {
					starOn = opt.iconRange[item][0];
					range = opt.iconRange[item][1];

					if (i <= range) {
						star.removeClass(starOff).addClass(starOn);
					}

					if (i == range) {
						item++;
					}
				} else {
					star.removeClass(starOff).addClass(starOn);
				}
			} else {
				star.removeClass(starOn).addClass(starOff);
			}
		}
	};

	function fixHint(context, score, opt) {
		var hint = '';

		if (score != 0) {
			score = parseInt(score);
			hint = (score > 0 && opt.number <= opt.hintList.length && opt.hintList[score - 1] !== null) ? opt.hintList[score - 1] : score;
		} else {
			hint = opt.noRatedMsg;
		}

		context.attr('title', hint).children('span').attr('title', hint);
	};

	function isField(target) {
		return target.is('input') || target.is('select') || target.is('textarea');
	};

	function initialize(context, score, opt) {
		var id = context.attr('id');

		if (isNaN(parseInt(score))) {
			context.children('span.' + id).removeClass(opt.starOn).addClass(opt.starOff);
			$('input#' + id + '-score').removeAttr('value');
			return;
		}

		if (score < 0) {
			score = 0;
		} else if (score > opt.number) {
			score = opt.number;
		}

		fillStar(id, score, opt);

		if (score > 0) {
			$('input#' + id + '-score').val(score);

			if (opt.half) {
				splitStar(context, score, opt);
			}
		}

		if (opt.readOnly || context.css('cursor') == 'default') {
			fixHint(context, score, opt);
		}
	};

	function setTarget(target, alt, opt) {
		if (target !== null) {
			var value = alt;

			if (opt.targetType == 'hint') {
				if (alt == 0 && opt.cancel) {
					value = opt.cancelHint;
				} else {
					value = opt.hintList[alt - 1];
				}
			}

			if (isField(target)) {
				target.val(value);
			} else {
				target.html(value);
			}
		}
	};

	function splitStar(context, score, opt) {
		var id		= context.attr('id'),
			rounded	= Math.ceil(score),
			diff	= (rounded - score).toFixed(1);

		if (diff > 0.25 && diff <= 0.75) {
			rounded = rounded - 0.5;
			$('span#' + id + '-' + Math.ceil(rounded)).removeClass(starOff).removeClass(starOn).addClass(starHalf);
		} else if (diff > 0.75) {
			rounded--;
		} else {
			$('span#' + id + '-' + rounded).removeClass(starOff).addClass(starOn);
		}
	};

	$.fn.raty.cancel = function(idOrClass, isClickIn) {
		var isClick = (isClickIn === undefined) ? false : true;

		if (isClick) {
			return $.fn.raty.click('', idOrClass, 'cancel');
		} else {
			return $.fn.raty.start('', idOrClass, 'cancel');
		}
	};

	$.fn.raty.click = function(score, idOrClass) {
		var context = getContext(score, idOrClass, 'click'),
			options = $(idOrClass).data('options');

		if (idOrClass.indexOf('.') >= 0) {
			return;
		}

		initialize(context, score, options);

		if (options.click) {
			options.click.apply(context, [score]);
		} else {
			debug('You must add the "click: function(score, evt) { }" callback.');
		}

		return context;
	};

	$.fn.raty.readOnly = function(boo, idOrClass) {
		var context	= getContext(boo, idOrClass, 'readOnly'),
			options	= $(idOrClass).data('options'),
			cancel	= context.children('span.button-cancel');

		if (idOrClass.indexOf('.') >= 0) {
			return;
		}

		if (cancel[0]) {
			(boo) ? cancel.hide() : cancel.show();
		}

		if (boo) {
			$('span.' + context.attr('id')).unbind();
			context.css('cursor', 'default').unbind();
		} else { 
			bindAll(context, options);
			context.css('cursor', 'pointer');
		}

		return context;
	};

	$.fn.raty.start = function(score, idOrClass) {
		var context = getContext(score, idOrClass, 'start'),
			options = $(idOrClass).data('options');

		if (idOrClass.indexOf('.') >= 0) {
			return;
		}

		initialize(context, score, options);

		return context;
	};

	function getContext(value, idOrClass, name) {
		var context = undefined;

		if (idOrClass == undefined) {
			debug('Specify an ID or class to be the target of the action.');
			return;
		}

		if (idOrClass) {
			if (idOrClass.indexOf('.') >= 0) {
				var idEach;

				return $(idOrClass).each(function() {
					idEach = '#' + $(this).attr('id');

					if (name == 'start') {
						$.fn.raty.start(value, idEach);
					} else if (name == 'click') {
						$.fn.raty.click(value, idEach);
					} else if (name == 'readOnly') {
						$.fn.raty.readOnly(value, idEach);
					}
				});
			}

			context = $(idOrClass);

			if (!context.length) {
				debug('"' + idOrClass + '" is a invalid identifier for the public funtion $.fn.raty.' + name + '().');
				return;
			}
		}

		return context;
	};

	function debug(message) {
		if (window.console && window.console.log) {
			window.console.log(message);
		}
	};

	$.fn.raty.defaults = {
		cancel:			false,
		cancelHint:		'cancel this rating!',
		cancelOff:		'raty_cancel_off',
		cancelOn:		'raty_cancel_on',
		cancelPlace:	'left',
		click:			null,
		half:			false,
		hintList:		['bad', 'poor', 'regular', 'good', 'gorgeous'],
		noRatedMsg:		'not rated yet',
		number:			5,
		iconRange:		[],
		readOnly:		false,
		scoreName:		'score',
		size:			16,
		starHalf:		'raty_star_half',
		starOff:	'raty_star_off',
		starOn:	'raty_star_on',
		start:			0,
		target:			null,
		targetKeep:		false,
		targetType:		'hint',
		width:			null
	};

})(jQuery);
