jQuery(function($){
        jQuery(function($) {
                $.datepicker.regional['zh-TW'] = {
                        closeText: '關閉',
                        prevText: '前一頁',
                        nextText: '後一頁',
                        currentText: '今日',
                        monthNames: ['一月','二月','三月','四月','五月','六月',
                        '七月','八月','九月','十月','十一','十二月'],
                        monthNamesShort: ['一月','二月','三月','四月','五月','六月',
                        '七月','八月','九月','十月','十一月','十二月'],
                        dayNames: ['星期日','星期一','星期二','星期三','星期四','星期五','星期六'],
                        dayNamesShort: ['日','一','二','三','四','五','六'],
                        dayNamesMin: ['日', '一', '二', '三', '四', '五', '六'],
                        weekHeader: 'Wk',
                        dateFormat: 'yy-mm-dd',
                        firstDay: 0,
                        isRTL: false,
                        showMonthAfterYear: true,
                        yearSuffix: 'year'};
                $.datepicker.setDefaults($.datepicker.regional['zh-TW']);
        });
});
