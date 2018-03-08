require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#gift-exchange", function (e, id, page) {
        var url = common.getRequest();
        var token = '';
        token = common.getCookie('openid');

        function loadData() {
            common.ajax('GET', '/spring/present-index', {'code': url.code}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    if (rsp.data.info == 5) {
                        $('#container').css("background-image", "url('http://pub.huilaila.net/spring/sspa_check_new.jpg')");
                    } else {
                        $('#container').css("background-image", "url('http://pub.huilaila.net/spring/jjyx_check_new.jpg')");
                    }
                } else if (rsp.data.code == 101) {
                    $('#submit').remove();
                    $(document).on('click', '#container', function () {
                        location.href = 'http://www.huilaila.net/order-detail.html?classify=1&id='+rsp.data.info.order_id;
                    });
                    if (rsp.data.info.item_type == 5) {
                        $('#container').css("background-image", "url('http://pub.huilaila.net/spring/sspa_used_new.jpg')");
                    } else {
                        $('#container').css("background-image", "url('http://pub.huilaila.net/spring/jjyx_used_new.jpg')");
                    }
                }else if (rsp.data.code == 102) {
                    $('#submit').remove();
                    $(document).on('click', '#container', function () {
                        location.href = 'http://mall.huilaila.net/index.php?m=default&c=goods&a=index&id='+rsp.data.info.goods_id;
                    });
                    if (rsp.data.info.item_type == 5) {
                        $('#container').css("background-image", "url('http://pub.huilaila.net/spring/sspa_used_new.jpg')");
                    } else {
                        $('#container').css("background-image", "url('http://pub.huilaila.net/spring/jjyx_used_new.jpg')");
                    }
                }else if (rsp.data.code == 103) {
                    $('#submit').remove();
                    $(document).on('click', '#container', function () {
                        location.href = 'http://mall.huilaila.net/index.php?m=default&c=goods&a=index&id='+rsp.data.info.goods_id;
                    });
                    if (rsp.data.info.item_type == 5) {
                        $('#container').css("background-image", "url('http://pub.huilaila.net/spring/sspa_passed.jpg')");
                    } else {
                        $('#container').css("background-image", "url('http://pub.huilaila.net/spring/jjyx_passed.jpg')");
                    }
                }
            });

        }

        $(document).on('click', '#submit', function () {
            common.ajax('GET', '/spring/present-index', { 'code': url.code}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    window.location.href = 'gift-exchange-address.html?type=' + rsp.data.info + '&code=' + url.code;
                } else {
                    $.alert("很抱歉," + rsp.data.message);
                }
            });
        });


        if (token) {
            loadData();
        } else {
            common.ajax('GET', '/order/index', {}, true, function () {
                loadData();
            })
        }
        var pings = env.pings;
        pings();
    });

    $.init();
});
