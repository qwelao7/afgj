require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#order-deliver", function (e, id, page) {
        var url = common.getRequest();

        var tpl = $('#tpl').html(),
            container = $('#container');

        var params,
            local = {};

        var img = function (data) {
            var host = 'http://' + location.host.replace('www', 'mall') + '/';
            if(data.substring(0,4) == 'data'){
                return host + data;
            }else{
                return 'http://pub.huilaila.net/'+data;
            }
        };
        juicer.register('img', img);

        function init() {
            params = sessionStorage.getItem('order_params');

            if (!params || params == '') {
                $.alert('很抱歉,数据出错,请返回!', '数据错误', function () {
                    history.go(-1);
                })
            } else {
                loadData();
            }
        }

        function paramsInit (data) {
            local.pay = data.params.payment;
            local.shipping = data.params.shipping;
            local.shipping.shipping_fee = 0;
        }

        function setData() {
            var storage = sessionStorage.getItem('order_other');
            if (!storage || storage == '') {
                return false;
            } else {
                storage = JSON.parse(storage);

                $('#pay').find('a').each(function (index) {
                    var self = $(this),
                        siblings = self.siblings();
                    if (self.data('id') == storage.pay.pay_id) {
                        siblings.removeClass('label-chosen').addClass('label-unchosen');
                        self.removeClass('label-unchosen').addClass('label-chosen');
                    }
                })

                $('#shipping').find('a').each(function (index) {
                    var self = $(this),
                        siblings = self.siblings();
                    if (self.data('id') == storage.shipping.shipping_id) {
                        siblings.removeClass('label-chosen').addClass('label-unchosen');
                        self.removeClass('label-unchosen').addClass('label-chosen');
                    }
                })
            }
        }

        function loadData() {
            common.ajax('GET', '/order/payment-shipping', {
                'address_id': url.address_id,
                'goods_id': url.goods_id,
                'goods_money': url.money,
                'goods_num': url.goods_num
            }, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    data.curShippng = url.shipping;
                    console.log(data)
                    data.params = JSON.parse(params);

                    var html = juicer(tpl, data);
                    container.append(html);

                    paramsInit(data);
                    setData();
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无配送数据!</h3>";
                    container.append(template);
                }
            })
        }

        $('.label-unchosen').live('click', function() {
            var self = $(this),
                siblings = self.siblings(),
                parent = self.parent(),
                id = self.data('id'),
                name = $.trim(self.text()),
                key = parent.attr('id');

            siblings.removeClass('label-chosen').addClass('label-unchosen');
            self.removeClass('label-unchosen').addClass('label-chosen');

            local[key][key+'_id'] = id;
            local[key][key+'_name'] = name;
            if (key == 'shipping') {
                local[key][key+'_fee'] = self.data('fee').toFixed(2);
            }

            sessionStorage.setItem('order_other', JSON.stringify(local));
        });

        $('#back').on('click', function (e) {
            e.preventDefault();
            
            var path = (url.address_id) ? '?address_id=' + url.address_id : '';
            location.href = 'order-confirm.html' + path;
        });

        init();
        var pings = env.pings;
        pings();
    });

    $.init();
});