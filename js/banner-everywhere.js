(function (win, doc) {
	'use strict';
	if (! Event.prototype.preventDefault) {
		Event.prototype.preventDefault = function() {
			this.returnValue = false;
		};
	}
	win.SBTS = win.SBTS || {};
	SBTS.bannerEverywhere = (function () {
		var sbe, begin, end, now;

		function init() {
			var ls;
			sbe	= doc.getElementById('sbts_banner_everywhere');
			if (!! sbe) {
				begin	= parseInt(sbe.getAttribute('data-banner_begin'), 10);
				end		= parseInt(sbe.getAttribute('data-banner_end'), 10);
				now		= parseInt(sbe.getAttribute('data-banner_now'), 10);
			}
			try {
				ls = JSON.parse(win.localStorage.getItem('sbts_banner_everywhere')) || {};
				if ((ls.started !== begin) || (ls.ending !== end)) {
					ls = {};
					ls.started = begin;
					ls.ending = end;
					ls.dismissed = false;
					win.localStorage.setItem('sbts_banner_everywhere', JSON.stringify(ls));
				}
			} catch(er) {}
		}

		function listen (el, ev, fun) {
			if (el.addEventListener) {
				el.addEventListener(ev, fun, false);
			} else if (el.attachEvent)  {
				el.attachEvent('on' + ev, fun);
			}
		}

		function isActive () {
			var keepShowing = true, ls;
			try {
				ls = JSON.parse(win.localStorage.getItem('sbts_banner_everywhere'));
				if (!! ls) {
					// this allows us to make sure that they dismissed this banner instead of another
					if ((ls.started === begin) && (ls.ending === end) && (ls.dismissed === true)) {
						keepShowing = false;
					}
				}
			} catch (err) {
				throw "Local Storage could not retrieve item.";
			}
			return (keepShowing && (now > begin) && (now < end));
		}

		function checkActive () {
			var sbeBody;
			if ((!! sbe) && isActive()) {
				sbeBody = doc.getElementsByTagName('body')[0];
				sbeBody.insertBefore(sbe, sbeBody.firstChild); // move to top of body, try to untangle it from some of the inherited styles at least
				sbe.style.display = 'block';
				listen(sbe.getElementsByTagName('a')[0], 'click', function (e) {
					var ls;
					e.preventDefault();
					sbe.style.display = 'none';
					try {
						ls = JSON.parse(win.localStorage.getItem('sbts_banner_everywhere'));
						ls.dismissed = true;
						win.localStorage.setItem('sbts_banner_everywhere', JSON.stringify(ls));
					} catch (err) {
						throw "Local Storage could not add item.";
					}
				});
			}
		}

		function debug () {
			var ls;
			try {
				ls = win.localStorage.getItem('sbts_banner_everywhere');
			} catch (err) {
				ls = err;
			}
			console.log('begin: ' + begin + '; now: ' + now + '; end: ' + end);
			console.log('localStorage("sbts_banner_everywhere"): ' + (ls || 'undefined'));
		}

		function getSBE () {
			return sbe;
		}

		function showAgain () {
			var ls;
			try {
				ls = JSON.parse(win.localStorage.getItem('sbts_banner_everywhere'));
				ls.dismissed = false;
				win.localStorage.setItem(JSON.stringify(ls));
				checkActive();
			} catch (err) {
				console.log('Could not reset dismissed: ' + err);
			}
		}

		init();
		checkActive();

		return {
			active	: isActive,
			debug	: debug,
			sbe		: getSBE,
			reset	: showAgain
		};
	}());
}(window, document));
