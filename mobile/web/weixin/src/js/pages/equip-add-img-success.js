require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function() {
    'use strict';
    $(document).on('pageInit', '#equip-add-img-success', function(e, id, page) {
        var url = common.getRequest(),
            path;

        $('#back').click(function () {
            path = '?id=' + url.id;

            location.href = 'equip-list.html' + path;
        });

        var pings = env.pings;pings();
    });

    $.init();
})