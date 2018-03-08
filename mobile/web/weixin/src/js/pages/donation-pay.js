require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#donation-pay", function (e, id, page) {
        var url = common.getRequest();

        var status = true,
            value='';

        common.img();

        $('#back').live('click', function () {
            location.href = 'donation-index.html';
        });

        $(page).on('input propertychange', '#money', function () {
            var self = $(this);
            this.value=this.value.replace(/^(\-)*(\d+)\.(\d\d).*$/,'$1$2.$3');
            value = $.trim(self.val());
            $('#order_amount').html('￥' + value);
            if (value == '') {
                $('#order_amount').html('￥0');
            }
        });

        /**
         * 支付相关
         * @param event
         * @param jsApi
         * @param url
         */
        function jsApiCall(event, jsApi, url) {
            WeixinJSBridge.invoke("getBrandWCPayRequest", jsApi, function (res,url) {
                if (res.err_msg == "get_brand_wcpay_request:ok") {
                    location.href = 'donation-index.html';
                } else {
                    $('#submit').text('重新支付').css('backgroundColor', '#be0b21 !important');
                    location.href = 'donation-pay.html?id='+url.id;
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
                }
            } else {
                jsApiCall(event, jsApi, url);
            }
        }

        function pay(id) {
            common.ajax('GET', '/benefit/wx-pay-params', {donateId: id}, false, function (rsp) {
                console.log(rsp)
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        jsApi = data['jsApi'],
                        return_url = data['url'];

                    jsApi = JSON.parse(jsApi);
                    callpay(jsApi, return_url);
                } else if (rsp.data.code == 200) {
                    $.alert('订单支付成功', '支付成功', function () {
                        location.href = 'donation-index.html';
                    })
                } else {
                    $.alert('很抱歉,支付失败!', '支付失败', function () {
                        location.href = 'donation-pay.html?id=' + url.id;
                    });
                }
            })
        }

        /**
         * 订单提交
         */
        $('#submit').live('click', function () {
            if(value==0||0.0||0.00){
                $.alert('请填写有效捐款金额！');
            }else{
                if (!status) return false;
                status = false;

                var post={};

                post.id = url.id;
                post.money = value;
                post.wish = $('#comment').val();
                console.log(post)

                var self = $(this);
                self.text('提交中...').css('backgroundColor', '#888 !important');
                common.ajax('POST', '/benefit/donate', {'data': post}, true, function (rsp) {
                    console.log(rsp)
                    if (rsp.data.code == 0) {
                        var orderId = rsp.data.info;
                        pay(orderId);
                        status = true;
                    } else {
                        $.alert('很抱歉,捐款失败,' + rsp.data.message + ',请重试!', '创建失败', function () {
                            status = true;
                            self.text('重新支付').css('backgroundColor', '#be0b21 !important');
                        })
                    }
                })
            }
        });


        var pings = env.pings;
        pings();
    });

    $.init();
});
