require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#order-detail", function (e, id, page) {
        var url = common.getRequest(),
            host = window.location.host;

        //开发环境
        if (host.indexOf('8080') != -1) {
            host = 'www.afguanjia.com';
        }
        host = 'http://' + host.replace('www', 'mall') + '/';

        var tpl = $('#tpl').html(),
            nav = $('#nav').html(),
            container = $('#container');

        var status = true,
            jsApi,
            return_url;

        var img = function (data) {
            if(data.substring(0,4) == 'data'){
                return host + data;
            }else{
                return 'http://pub.huilaila.net/'+data;
            }
        };
        juicer.register('img', img);

        function loadData() {
            common.ajax('GET', '/order/detail', {'id': url.id}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data),
                        htm = juicer(nav, data);

                    container.append(html);
                    $('header').after(htm);
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无订单详情!</h3>";
                    container.append(template);
                }
            })
        }

        $(document).on('click', '#back', function () {
            window.location.href = 'order-list.html?classify=' + url.classify;
        });

        /**
         * 取消订单
         */
        $(document).on('click', '#cancel-order', function () {
            $.confirm('您确定取消该订单?', function () {
                common.ajax('POST', '/order/operation', {'id': url.id, 'type': 4}, true, function (rsp) {
                    if (rsp.data.code == 0) {
                        $.alert('订单取消成功!', '取消成功', function () {
                            location.reload();
                        });
                    } else {
                        $.alert('很抱歉,订单取消失败,请重试!', '取消失败');
                    }
                })
            });
        });

        /**
         * 确认收货
         */
        $(document).on('click', '.to-delivery', function () {
            if (!status) return;
            status = false;

            common.ajax('POST', '/order/operation', {'id': url.id, 'type': 2}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    $.alert('确认收货成功!', '操作成功', function () {
                        location.reload();
                    })
                } else {
                    $.alert('很抱歉,确认收货失败,请重试!', '操作失败');
                }
                status = true;
            });
        });

        /**
         * 申请售后
         */
        $(document).on('click', '#to-customer-service', function () {
            var self = $(this),
                recId = self.data('recid');
            window.location.href = common.ectouchUrl + '&c=user&a=aftermarket&rec_id=' + recId + '&order_id=' + url.id;
        });

        /**
         * 立即付款
         */
        $(document).on('click', '.to-pay', function () {
            if (!status) return;
            status = false;

            var self = $(this),
                parents = self.parents('.bar-tab'),
                id = parents.data('id');

            common.ajax('GET', '/order/wx-pay-params', {orderId: id}, false, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        jsApi = data['jsApi'],
                        return_url = data['url'];

                    jsApi = JSON.parse(jsApi);

                    callpay(jsApi, return_url);

                } else {
                    $.alert('很抱歉,支付失败,请重试!');
                }
                status = true;
            })
        });

        /**
         * 操作记录
         */
        $(document).on('click', '.view-log', function() {
            window.location.href = 'order-refund.html?classify=' + url.classify + '&id=' + url.id;
        });

        /**
         * 立即评价
         */
        $(document).on('click', '.to-comment', function() {
            window.location.href = 'order-comment.html?id=' + url.id + '&refer=detail';
        });

        function jsApiCall(event, jsApi, url) {
            WeixinJSBridge.invoke("getBrandWCPayRequest", jsApi, function (res) {
                if (res.err_msg == "get_brand_wcpay_request:ok") {
                    location.href = url + '&status=1';
                } else if (res.err_msg == 'fail') {
                    location.href = url + '&status=0';
                }
            });
        }

        function callpay(jsApi, url) {
            if (typeof WeixinJSBridge == "undefined") {
                if (document.addEventListener) {
                    document.addEventListener("WeixinJSBridgeReady", function (event) {
                        jsApiCall(event, jsApi, url);
                    }, false);
                } else if (document.attachEvent) {
                    document.attachEvent("WeixinJSBridgeReady", function (event) {
                        jsApiCall(event, jsApi, url);
                    });
                    document.attachEvent("onWeixinJSBridgeReady", function (event) {
                        jsApiCall(event, jsApi, url);
                    });
                }
            } else {
                jsApiCall(event, jsApi, url);
            }
        }

        loadData();

        var pings = env.pings;
        pings();
    });

    $.init();
});
