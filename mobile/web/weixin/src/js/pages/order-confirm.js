require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');


/**
 * sessionStorage 定义
 * bonus: { id, money }
 * pay  : { id, name },
 * deliver: {id, money, name}
 */
$(function () {
    'use strict';

    $(document).on("pageInit", "#order-confirm", function (e, id, page) {
        var url = common.getRequest();

        var tpl = $('#tpl').html(),
            bottom = $('#bottom').html(),
            container = $('#container');

        var path,
            params = {},
            post = {},
            local = {},
            arr = [],
            status = true;

        var goodsId;

        var img = function (data) {
            var host = 'http://' + location.host.replace('www', 'mall') + '/';
            if(data.substring(0,4) == 'data'){
                return host + data;
            }else{
                return 'http://pub.huilaila.net/'+data;
            }
        };
        juicer.register('img', img);

        function loadData() {

            var data = {'address_id': url.address_id};
            var order_config = sessionStorage.getItem('order_other');
            if (order_config) {
                order_config = JSON.parse(order_config);
                data.shipping_id = order_config.shipping.shipping_id;
                data.payment_id = order_config.pay.pay_id;
            }
            console.log(data);
            common.ajax('GET', '/order/place-order', data, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data),
                        htm = juicer(bottom, data);

                    container.append(html);
                    container.after(htm);

                    goodsId = data.goods.goods_id;

                    paramsInit(data);
                    getData();
                    updateAmount();
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无订单数据!</h3>";
                    container.append(template);
                }
            })
        }

        /**
         * 参数初始化
         * @param data
         */
        function paramsInit(data) {
            params['payment'] = data.payment;
            params['shipping'] = data.shipping;
            params['goods'] = data.goods;
            params['integral'] = data.goods.integral;
            params['order_amount'] = (parseInt(data.goods.order_money * 100) - parseInt(params['integral'] * 100)) / 100;
            params['address_id'] = (data.address.address_id) ? data.address.address_id : '';
            params['address_type'] = (data.address.address_id) ? data.address.type : '';
            params['bonus'] = 0.00;
            params['bonus_id'] = 0;
            params['shipping_id'] = data.shipping.shipping_id;

            //金额结算数组
            arr['3'] = Number(data.goods.order_money * 100);
            arr['2'] = -Number(data.goods.integral * 100);
            arr['1'] = Number(params['bonus']);
            arr['0'] = Number(data.shipping.shipping_fee * 100);

            //set sessionStorage
            sessionStorage.setItem('order_params', JSON.stringify(params));

            local['pay'] = data.payment;
            local['shipping'] = data.shipping;
            local['shipping']['shipping_fee'] = local['shipping']['shipping_fee'].toFixed(2);

            sessionStorage.setItem('order_other', JSON.stringify(local));
        }

        /**
         * 从缓存红取数据
         */
        function getData () {
            var diverGetter = sessionStorage.getItem('order_other'),
                bonusGetter = sessionStorage.getItem('order_bonus');

            if (diverGetter && diverGetter != '') {
                diverGetter = JSON.parse(diverGetter);
                arr[0] = Number(diverGetter.shipping.shipping_fee * 100);
                params.shipping_id = diverGetter.shipping.shipping_id;
                params.shipping_fee = diverGetter.shipping.shipping_fee;

                params.payment_id = diverGetter.pay.pay_id;

                var str = diverGetter.pay.pay_name + '<br/>' + diverGetter.shipping.shipping_name;
                $('#pay_ship').html(str);
                $('#deliver_amount').text('+￥ ' + diverGetter.shipping.shipping_fee);
            }

            if (bonusGetter && bonusGetter != '') {
                bonusGetter = JSON.parse(bonusGetter);

                arr[1] = -Number(bonusGetter.value * 100);
                params.bonus = bonusGetter.value;
                params.bonus_id = bonusGetter.id;

                $('#bonus_amount').text('￥' + bonusGetter.value);
                $('#red_amount').text('-￥ ' + bonusGetter.value);
            }
        }

        /**
         * 更新应付金额
         * [0-deliver , 1-bonus , 2-integral, 3-order_amount]
         */
        function updateAmount() {
            var total = $('.order_amount');

            if (Math.abs(arr[1]) >= Math.abs(arr[3])) {
                arr[2] = 0;
                $('input[name=points]').prop('checked', false);
                $('#points_amount').text('-￥ ' + arr[2].toFixed(2));
            }

            var result = arr.reduce(function(pre,cur){return pre + cur});
            if (result < 0) result = 0;

            result = (result / 100).toFixed(2);
            params['order_amount'] = result;
            total.text('￥' + result);
        }

        /**
         * 验证收货地址
         */
        function validAddress() {
            var info = $('#ship_address_info'),
                detail = $('#ship_address_detail'),
                contacts = $.trim(info.find('span:first-child').text()),
                mobile = $.trim(info.find('span:nth-child(2)').text()),
                address = $.trim(detail.text());

            if (address != '' && contacts != '' && mobile != '') {
                return true;
            } else {
                $.modal({
                    title: '收货信息不完整',
                    text: '当前收货信息不完整,马上去完善吧。',
                    buttons: [
                        {
                            text: '知道了'
                        },
                        {
                            text: '确认',
                            bold: true,
                            onClick: function () {
                                if (params.address_id == '') {
                                    location.href = 'address-add.html';
                                } else {
                                    location.href = 'address-edit.html?id=' + params.address_id + '&refer=order_confirm';
                                }
                            }
                        }
                    ]
                });
                return false;
            }

        }

        /**
         * 支付相关
         * @param event
         * @param jsApi
         * @param url
         */
        function jsApiCall(event, jsApi, url) {
            WeixinJSBridge.invoke("getBrandWCPayRequest", jsApi , function (res) {
                if (res.err_msg == "get_brand_wcpay_request:ok") {
                    location.href = url + '&status=1';
                } else {
                    location.href = url + '&status=0';
                }
            });
        }

        function callpay(jsApi, url) {
            if (typeof WeixinJSBridge == "undefined") {
                if (document.addEventListener) {
                    document.addEventListener("WeixinJSBridgeReady", function(event){jsApiCall(event, jsApi, url);}, false);
                } else if (document.attachEvent) {
                    document.attachEvent("WeixinJSBridgeReady", function(event){jsApiCall(event, jsApi, url);});
                    document.attachEvent("onWeixinJSBridgeReady", function(event){jsApiCall(event, jsApi, url);});
                }
            } else {
                jsApiCall(event,jsApi, url);
            }
        }

        function pay(id) {
            common.ajax('GET', '/order/wx-pay-params', {orderId: id}, false, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        jsApi = data['jsApi'],
                        return_url = data['url'];

                    jsApi = JSON.parse(jsApi);
                    callpay(jsApi, return_url);
                }else if (rsp.data.code == 200) {
                    $.alert('订单支付成功', '支付成功', function () {
                        location.href = 'order-list.html?classify=1';
                    })
                } else {
                    $.alert('很抱歉,支付失败!','支付失败', function() {
                        location.href = 'order-detail.html?classify=2&id=' + id;
                    });
                }
            })
        }

        /**
         * 清空sessionStorage
         */
        function cleanStorage () {
            sessionStorage.clear();
        }

        /**
         * 选择收货地址
         */
        $('.address_check').live('click', function () {
            path = (url.address_id) ? '?address_id=' + url.address_id : '';

            location.href = 'order-address.html' + path;
        });

        /**
         * 红包数据跳转
         */
        $('#red_coupon').live('click', function () {
            location.href = 'order-bonus.html?goods_id='+params.goods.goods_id+'&money=' + params.goods.order_money;
        });

        /**
         * 配送数据 跳转
         */
        $('#deliver').live('click', function() {
            var self = $(this),
                isreal = self.data('isreal');

            if (isreal == 1) {
                if (params.address_id == '' || params.address_id == undefined) {
                    $.alert('请先选择收货地址!', '信息缺失');
                    return false;
                }

                var path = '?address_id=' + params.address_id
                    + '&goods_id=' + params.goods.goods_id
                    + '&money=' + params.goods.order_money
                    + '&shipping=' + params.shipping_id
                    + '&goods_num=' + params.goods.goods_number;
                location.href = 'order-deliver.html' + path;
            }
        });

        /**
         * 友元
         */
        $(document).on('change', 'input[name=points]', function(){
            var self = $(this),
                value = $.trim(self.val());

            if (self.prop('checked')) {
                $('#points_amount').text('-￥' + value);
                params['integral'] = value;
            } else {
                $('#points_amount').text('-￥0.00');
                 params['integral'] = '0.00';
            }

            arr['2'] = -Number(params['integral'] * 100);
            updateAmount();
        });

        /**
         * 返回商品页
         */
        $('#back').live('click', function() {
            common.ajax('GET', '/order/clear-cart', {}, true, function(rsp) {
                cleanStorage();

                location.href = common.ectouchUrl + '&c=goods&a=index&id=' + goodsId;
            });
        });

        /**
         * 订单提交
         */
        $('#submit').live('click', function() {
            if (!status) return false;
            status = false;

            if (params.address_id == undefined) {
                $.alert('请先选择您的收货地址!', '提交失败', function () {
                    status = true;
                });
                return false;
            }

            post.address_id = params.address_id;
            post.shipping_id = params.shipping.shipping_id;
            post.payment_id = params.payment.pay_id;
            post.goods_amount = params.goods.order_money;
            post.order_amount = params.order_amount;
            post.shipping_fee = params.shipping.shipping_fee;
            post.integral_money = params.integral;
            post.bonus = params.bonus;
            post.bonus_id = params.bonus_id;
            post.shop_price = params.goods.shop_price;
            post.goods_num = params.goods.goods_number;
            post.business_id = params.goods.business_id;

            var self = $(this);
            var validate = validAddress();
            if (validate) {
               self.text('提交中...').css('backgroundColor', '#888 !important');
                common.ajax('POST', '/order/submit-order', {'data': post}, true, function(rsp) {
                    if (rsp.data.code == 0) {
                        var orderId = rsp.data.info;
                        
                        pay(orderId);
                        cleanStorage();
                        status = true;
                    } else {
                        $.alert('很抱歉,订单创建失败,' + rsp.data.message + ',请重试!', '创建失败', function() {
                            status = true;
                            self.text('提交').css('backgroundColor', '#009042 !important');
                        })
                    }
                    cleanStorage();
                })
            }
        });

        history.replaceState({}, "order_confirm", "order-confirm.html#123");
        loadData();
        var pings = env.pings;
        pings();
    });
    
    $.init();
});
