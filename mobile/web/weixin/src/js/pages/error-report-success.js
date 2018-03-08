require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#error-report-success", function (e, id, page) {
        var url = common.getRequest();

        $('#back').on('click', function() {
            location.href = 'decoration-detail.html?id=' + url.id + '&type=1';
        });

        var pings = env.pings;
        pings();
    });

    $.init();
});