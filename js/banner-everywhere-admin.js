(function ($) {
	'use strict';
	var dtTm = {dt:{},tm:{}}, allTypes = 'okay warning error';

	function giveFeedback (msg, type) {
		type = type || 'okay';
		$('#instruction').removeClass(allTypes).addClass(type).text(msg);
	}

	function isValid (pDtTm) { // test p[ossible]DtTm as possible value for new dtTm
		var start, end, now;

		now = (new Date()).getTime();
		start = new Date(pDtTm.dt.start + ' ' + pDtTm.tm.start).getTime();
		end = new Date(pDtTm.dt.end + ' ' + pDtTm.tm.end).getTime();
		if ((! pDtTm.dt.start) || (! pDtTm.dt.end) || (! pDtTm.tm.start) || (! pDtTm.tm.end)) {
			giveFeedback('Please enter a date range in the fields above.', 'okay');
			// let the value through because not all values are present yet
			return true;
		}
		if (isNaN(start) || isNaN(end)) {
			giveFeedback('Please enter a date range in the fields above.', 'okay');
			return false;
		}
		if (start > end) {
			giveFeedback('Start date and time cannot be after end date and time.', 'error');
			return false;
		}
		if (start === end) {
			giveFeedback('Start date and time are equal to end date and time.', 'warning');
			return true;
		}
		if (now > end) {
			giveFeedback('End date and time seem to be in the past.', 'warning');
			return true;
		}
		if (now > start) {
			giveFeedback('Start date and time are in the past. Banner will show immediately.', 'warning');
			return true;
		}
		if (start < end) {
			giveFeedback('Save to schedule the banner.', 'okay');
			return true;
		}
		return false;
	}

	function dtSaver (which) {
		return function saveDate(dtText, inst) {
			// use jQuery's extend functionality to test the validity of the possible new date/time
			var tmpObj = {dt:{}};
			tmpObj.dt[which] = dtText;
			if (isValid($.extend(true, {}, dtTm, tmpObj))) {
				dtTm.dt[which] = dtText;
			}
		};
	}

	function tmSaver (which) {
		return function (e) {
			var tmpObj = {tm:{}};
			tmpObj.tm[which] = $(this).val();
			e.preventDefault();
			if (isValid($.extend(true, {}, dtTm, tmpObj))) {
				dtTm.tm[which] = tmpObj.tm[which];
			}
		};
	}

	function dpObj (which) {
		var dpo = {
			'altField'	: '#date_' + which,
			'altFormat'	: 'yy-mm-dd',
			'onSelect'	: dtSaver(which)
		}, tmpDt;

		if (dtTm.dt[which]) {
			tmpDt = new Date(dtTm.dt[which] + ' ' + (dtTm.tm[which] ? dtTm.tm[which] : '12:00'));
			dpo.defaultDate = tmpDt;
		}

		return dpo;
	}

	function formatDate (dt) {
		return dt.getFullYear() + '-' + (dt.getMonth() + 1) + '-' + (dt.getDate() > 9 ? dt.getDate() : '0' + dt.getDate());
	}

	function initDates () {
		if (! dtTm.dt.start) {
			dtTm.dt.start = formatDate($('#dt_start').datepicker('getDate'));
		}
		if (! dtTm.dt.end) {
			dtTm.dt.end = formatDate($('#dt_end').datepicker('getDate'));
		}
	}

	function init() {
		// initialize values from any stored values
		dtTm.dt.start = $('#date_start').val();
		dtTm.dt.end = $('#date_end').val();
		dtTm.tm.start = $('#time_start').val();
		dtTm.tm.end = $('#time_end').val();

		$('#dt_start').datepicker(dpObj('start'));
		$('#dt_end').datepicker(dpObj('end'));

		$('#time_start').on('keyup', tmSaver('start'));
		$('#time_end').on('keyup', tmSaver('end'));

		initDates();
	}

	init();
}(jQuery));
