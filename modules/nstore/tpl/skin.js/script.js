function number_format(nStr)
{
    nStr += '';
    x = nStr.split('.');
    x1 = x[0];
    x2 = x.length > 1 ? '.' + x[1] : '';
    var rgx = /(\d+)(\d{3})/;
    while (rgx.test(x1)) {
        x1 = x1.replace(rgx, '$1' + ',' + '$2');
    }
    return x1 + x2;
}

function change_period(days, month) {
	var currdate = new Date();
	if (days) {
		currdate = addDays(currdate, -1 * days);
	}
	if (month) {
		currdate = addMonth(currdate, -1 * month);
	}
	var startdate = jQuery.datepicker.formatDate('yymmdd', currdate);
	var startdateStr = jQuery.datepicker.formatDate('yy-mm-dd', currdate);
	jQuery('#orderlist .period input[name=startdate]').val(startdate);
	jQuery('#orderlist .period #startdateInput').val(startdateStr);
	jQuery('#fo_search').submit();
}

function addDays(myDate, days) {
        return new Date(myDate.getTime() + days*24*60*60*1000);
}

function addMonth(currDate, month) {
        var currDay   = currDate.getDate();
        var currMonth = currDate.getMonth();
        var currYear  = currDate.getFullYear();
        var ModMonth = currMonth + month;
        if (ModMonth > 12) {
                ModMonth = ModMonth - 12;
                currYear = currYear + 1;
        }
        if (ModMonth < 0) {
                ModMonth = 12 + (ModMonth);
                currYear = currYear - 1;
        }
        return new Date(currYear, ModMonth, currDay);
}
