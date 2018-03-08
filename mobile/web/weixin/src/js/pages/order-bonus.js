require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#order-bonus", function (e, id, page) {
        var url = common.getRequest();

        var tpl = $('#tpl').html(),
            container = $('#container');

        function loadData() {
            common.ajax('GET', '/order/bonus-list', {'order_money': url.money,'goods_id': url.goods_id}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data);

                    container.append(html);

                    setChoose();
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,你暂无红包!</h3>";
                    container.append(template);
                }
            })
        }

        function setChoose() {
            var local = sessionStorage.getItem('order_bonus');
            console.log(local)
            if (!local || local == '') {
                return false;
            } else {
                local = JSON.parse(local);
                $('input[name=bonus]').each(function(index){
                    if ($(this).data('id') == local.id) {
                        $(this).attr('checked', true);
                    }
                })
            }
        }

        $('input[name=bonus]').live('change', function () {
            var self = $(this),
                id = self.data('id'),
                value = $.trim(self.attr('value'));

            var params = {
                'id': id,
                'value': value
            };

            sessionStorage.setItem('order_bonus', JSON.stringify(params));
        });

        $('#back').on('click', function (e) {
            e.preventDefault();

            var path = (url.address_id) ? '?address_id=' + url.address_id : '';
            location.href = 'order-confirm.html' + path;
        });

        loadData();

        var pings = env.pings;
        pings();
    });

    $.init();
});