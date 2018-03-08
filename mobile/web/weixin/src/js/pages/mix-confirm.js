require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#mix-confirm", function (e, id, page) {
        var url = common.getRequest();

        function loadData() {
            if (url.id == 0) {
                $('#container').css("background-image", "url('http://pub.huilaila.net/mix-confirm0.jpg')");
            } else if (url.id == 1) {
                $('#container').css("background-image", "url('http://pub.huilaila.net/mix-confirm1.jpg')");
            } else if (url.id == 2) {
                $('#container').css("background-image", "url('http://pub.huilaila.net/mix-confirm2.jpg')");
            } else {
                $('#container').css("background-image", "url('http://pub.huilaila.net/mix-confirm3.jpg')");
            }
        }

        var pings = env.pings;
        pings();
        loadData();
    });
    $.init();
});
