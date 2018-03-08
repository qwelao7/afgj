require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#brand-info", function (e, id, page) {
        var pings = env.pings;pings();
    });

    $.init();
});