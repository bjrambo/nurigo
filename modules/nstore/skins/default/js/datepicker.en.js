jQuery(function($){
        jQuery(function($) {
                $.datepicker.regional['en'] = {
                        closeText: 'Close',
                        prevText: 'Previous',
                        nextText: 'Next',
                        currentText: 'Today',
                        monthNames: ['January','February','March','April','May','June',
                        'July','August','September','October','November','December'],
                        monthNamesShort: ['Jan','Feb','Mar','Apr','May','Jun',
                        'Jul','Aug','Sep','Oct','Nov','Dec'],
                        dayNames: ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'],
                        dayNamesShort: ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'],
                        dayNamesMin: ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'],
                        weekHeader: 'Wk',
                        dateFormat: 'yy-mm-dd',
                        firstDay: 0,
                        isRTL: false,
                        showMonthAfterYear: true,
                        yearSuffix: 'year'};
                $.datepicker.setDefaults($.datepicker.regional['en']);
        });
});
