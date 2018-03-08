require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on('pageInit', '#cashier-offline', function (e, id, page) {
        var url = common.getRequest();

        var tpl = $('#tpl').html(),
            tpl1 = $('#tpl1').html(),
            button = $('#button').html(),
            container = $('#container'),
            container1 = $('#container1'),
            submit = $('#submit'),
            status = true,
            data = {},
            amount = 0,
            params = {};

        common.img();


        function loadData() {
            common.ajax('GET', '/cashier/list', {'id': url.id}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    data = rsp.data.info;
                    $.each(data.list, function (index, item) {
                        item.current_num = 0;
                    })
                    var html = juicer(tpl, data),
                        htl = juicer(tpl1, data),
                        htm = juicer(button, {});

                    container.append(html);
                    container1.append(htl);
                    submit.prepend(htm);
                } else {
                    var tips = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无数据信息!</h3>";
                    container.append(tips);
                }
            })
        }

        $(document).on('click', '#submit', function () {
            if (amount > data.payment.avaiable_points) {
                $.alert('您的友元不足！');
            } else {
                params.business_id = url.id;
                params.money = amount;
                params.list = [];
                $.each(data.list, function (index, item) {
                    if (item.current_num > 0) {
                        params.list.push(item)
                    }
                })
                $.each(params.list, function (index, item) {
                    delete item.attr_value;
                    delete item.goods_name;
                    delete item.goods_number;
                    delete item.goods_sn;
                    delete item.goods_thumb;
                    delete item.is_real;
                    delete item.market_price;
                    delete item.sale_num;
                    delete item.shop_price;
                })
                console.log(params);
                common.ajax('POST', '/cashier/order-pay', params, true, function (rsp) {
                    if (rsp.data.code == 0) {
                        $.alert('订单已用友元支付成功！', '支付成功', function () {
                            window.location.href = window.location.href + "&refreshid=" + 10000 * Math.random();
                        });
                    } else {
                        var msg="很抱歉，"+rsp.data.message+"请重新购买！";
                        $.alert(msg, '支付失败', function () {
                            window.location.href = window.location.href + "&refreshid=" + 10000 * Math.random();
                        });
                    }
                });
            }
        });


        $(document).on('click', '.icon-AddTo-hll', function () {
            var shop_num = $(this).parent().parent().parent().parent().data('id');
            var buy_num = parseInt($(this).siblings('.buy_num').html());
            buy_num++;
            $(this).siblings('.buy_num').html(buy_num);
            $(this).siblings().css('visibility', 'visible');
            $.each(data.list, function (index, item) {
                if (shop_num == index) {
                    item.current_num++;
                    amount = amount + parseFloat(item.shop_price);
                    $('#order_amount').html('￥' + amount.toFixed(2));
                }
            })
        });

        $(document).on('click', '.icon-Reduce-hll', function () {
            var shop_num = $(this).parent().parent().parent().parent().data('id');
            var buy_num = parseInt($(this).siblings('.buy_num').html());
            if (buy_num > 1) {
                buy_num--;
            } else {
                buy_num = 0;
                $(this).siblings('.buy_num').css('visibility', 'hidden');
                $(this).css('visibility', 'hidden');
            }
            $(this).siblings('.buy_num').html(buy_num);
            $.each(data.list, function (index, item) {
                if (shop_num == index) {
                    if (item.current_num >= 1) {
                        item.current_num--;
                        amount = amount - parseFloat(item.shop_price);
                        $('#order_amount').html('￥' + amount.toFixed(2));
                    } else {
                        item.current_num = 0;
                    }
                }
            })
        });


        loadData();

        var pings = env.pings;
        pings();
    });

    $.init();
});