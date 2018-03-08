require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function() {
    'use strict';
    $(document).on('pageInit', '#requirement-success', function(e, id, page) {
        var url = common.getRequest();

        var pings = env.pings;pings();
    });

    $.init();
})