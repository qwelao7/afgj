require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#ecard-trans", function (e, id, page) {
        function loadData() {
            common.ajax('GET', '/events/get-sex', {}, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                    if (data.qr_url == '') {
                        location.href = 'ecard-add.html';
                        return false;
                    }
                    if (data.sex == 1) {
                        location.href = 'ecard-index-boy.html?qr_code=' + data.qr_url;
                    } else {
                        location.href = 'ecard-index-girl.html?qr_code=' + data.qr_url;
                    }
                } else {
                    location.href = 'ecard-add.html';
                }
            })
        }

        loadData();

        var pings = env.pings;pings();
    });

    $.init();
});