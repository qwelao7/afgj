require('../../css/style.css');
require('../../css/index.css');
require('../lib/library.js');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#library-book-search", function (e, id, page) {
        var url = common.getRequest();
        $('input[name=search]').val(url.q);

        var distance = function(data) {
            var re = /^(?:0\.\d+|[01](?:\.0)?)$/;
            data = (re.test(data)) ? parseFloat(data*1000).toFixed(0) + '米' : parseFloat(data).toFixed(1) + '公里';
            return data;
        };
        juicer.register('distance', distance);
        
        //返回
        $(document).on('click', '#back', function() {
            location.href = 'library-book-search.html';
        });

        //跳转详情
        $(document).on('click', '.book-list', function() {
            var _this = $(this),
                id = _this.data('id');
            location.href = 'library-book-detail.html?id=' + id + '&ref=search';
        });

        var pings = env.pings;pings();
    });

    $.init();
});
