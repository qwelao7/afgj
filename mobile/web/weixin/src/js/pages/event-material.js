require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';


    $(document).on("pageInit", "#event-material", function (e, id, page) {
        var url = common.getRequest();

        var tpl = $('#tpl').html(),
            container = $('#container'),
            nav = $('#nav'),
            status = true;

        var params = {
            goodsId: 0,
            goodsName: '',
            goodsNumber: 0,
            unitPrice: 0.00,
            number: 1,
            goodsAmount: 0,
            shippingFee: 0,
            orderAmount: 0,
            addressId: 0
        };

        function initParams() {
            if (url.id == 1) {
                common.ajax('GET', '/hcho/show-order', {}, true, function (rsp) {
                    if (rsp.data.code == 0) {
                        var data = rsp.data.info;

                        params.goodsId = data.goods_id;
                        params.goodsName = data.goods_name;
                        params.goodsNumber = data.goods_number;
                        params.unitPrice = data.shop_price;
                        params.shippingFee = 5.00;
                        params.addressId = url.address_id;

                        render(params);
                    }
                })
            }
        }

        function render(params) {
            var html = juicer(tpl, params);
            container.append(html);

            changeAmount(params);
        }

        function changeAmount(params) {
            params.goodsAmount = (parseInt(params.unitPrice * 100) * parseInt(params.number)) / 100;
            params.orderAmount = (parseInt(params.goodsAmount * 100) + parseInt(params.shippingFee * 100)) / 100;

            $('.JNumber').text(params.number);
            $('.JGoodsAmount').text(params.goodsAmount.toFixed(2));
            $('.JOrderAmount').text(params.orderAmount.toFixed(2));
        }

        function pay(orderId) {
            common.ajax('GET', '/hcho/wx-pay-params', {orderId: orderId}, false, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        jsApi = data['jsApi'];

                    jsApi = JSON.parse(jsApi);
                    callpay(jsApi);
                } else {
                    $.alert('很抱歉,支付失败!', '支付失败', function () {
                        status = true;
                        self.text('提交').css('backgroundColor', '#009042 !important');
                    });
                }
            })
        }

        /**
         * 支付相关
         * @param event
         * @param jsApi
         * @param url
         */
        function jsApiCall(event, jsApi) {
            WeixinJSBridge.invoke("getBrandWCPayRequest", jsApi, function (res) {
                if (res.err_msg == "get_brand_wcpay_request:ok") {
                    // 成功回调
                    paySuccess();
                } else {
                    // 失败回调
                    $.alert('很抱歉,支付失败!', '支付失败', function () {
                        status = true;
                        $('#submit').text('提交').css('backgroundColor', '#009042 !important');
                    });
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

        function paySuccess() {
            common.ajax('GET', '/hcho/apply-success', {
                apply_id: url.apply_id
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    // 二维码页面
                    var path = '?event_id=' + url.event_id;
                    location.href = 'event-wechat.html' + path;
                }
            })
        }

        $('.JBtn').live('click', function () {
            var self = $(this),
                type = self.data('type');

            params.number = (type == 'plus') ? ++params.number : --params.number;

            if (params.number == 0) {
                if (type == 'plus') {
                    params.number = ++params.number;
                } else {
                    $.alert('很抱歉,商品选购数量不能为0', '温馨提示');
                    params.number = 1;
                    return false
                }

            }

            if (params.number > params.goodsNumber) {
                $.alert('很抱歉,商品库存不足!', '温馨提示');
                return false;
            }

            changeAmount(params);
        });

        $('#back').on('click', function () {
            var path = '?event_id=' + url.event_id + '&address_id=' + params.addressId;

            location.href = 'event-delievry-address.html' + path;
        });

        $('#submit').live('click', function () {
            var self = $(this);

            if (!status) return false;
            status = false;

            self.text('提交中...').css('backgroundColor', '#888 !important');

            var objs = {
                goods_id: params.goodsId,
                address_id: params.addressId,
                goods_amount: params.goodsAmount,
                order_amount: params.orderAmount,
                shipping_fee: params.shippingFee,
                shop_price: params.unitPrice,
                goods_num: params.number
            };

            common.ajax('POST', '/hcho/submit-order', {
                data: objs
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var orderId = rsp.data.info;

                    // 支付回调
                    pay(orderId);
                } else {
                    $.alert('很抱歉,订单创建失败,' + rsp.data.message + ',请重试!', '创建失败', function () {
                        status = true;

                        self.text('提交').css('backgroundColor', '#009042 !important');
                    })
                }
            })
        });

        initParams();

        var pings = env.pings;
        pings();
    });

    $.init();
});