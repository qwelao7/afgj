require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';
    $(document).on('pageInit', '#event-borrow-success', function (e, id, page) {
        $(document).on('click', '#back', function () {
            window.location.href = 'unlock-event.html';
        })
    });

    $.init();
})