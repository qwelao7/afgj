require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function() {
    'use strict';
    $(document).on('pageInit', '#event-create-success', function(e, id, page) {
        var url = common.getRequest();

        $('#back').click(function() {
            location.href = 'event-started.html';
        });

        var pings = env.pings;pings();
    });

    $.init();
})