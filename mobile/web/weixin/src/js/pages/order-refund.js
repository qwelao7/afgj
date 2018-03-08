require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#order-refund", function (e, id, page) {
        var url = common.getRequest();

        var tpl = $('#tpl').html(),
            add = $('#add').html(),
            container = $('#container'),
            content = $('#content'),
            status = true;

        function loadData() {
            common.ajax('GET', '/order/after-sale-records', {orderId: url.id}, true, function(res) {
                if (res.data.code == 0) {
                    var data = res.data.info,
                        html = juicer(tpl, data);

                    container.append(html);

                    trigger(data.ret_id, data.cur);
                }else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无操作记录!</h3>";
                    container.append(template);
                }
            })
        }
        
        function trigger(retId, cur) {
            $(document).on('click', '#submit', function() {
                if (content.val() == '') {
                    $.alert('请输入内容!', '提交失败!');
                    return;
                }

                if (!status) return;
                status = false;

                var record = {
                    action_user_id: cur,
                    ret_id: retId,
                    action_info: content.val()
                };

                common.ajax('POST', '/order/add-record', {data: record}, true, function(res) {
                    if (res.data.code == 0) {
                        var result = res.data.info;
                        result['val'] = content.val();

                        var htm = juicer(add, result);
                        container.append(htm);
                        content.val('');
                    }else {
                        $.alert('很抱歉,提交失败,请重试!', '提交失败');
                    }
                    status = true;
                })
            });
        }

        $(document).on('click', '#back', function() {
            window.location.href = 'order-detail.html?classify=' + url.classify + '&id=' + url.id;
        });

        loadData();

        var pings = env.pings;pings();
    });

    $.init();
});

