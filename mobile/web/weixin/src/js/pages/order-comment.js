require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#order-comment", function (e, id, page) {
        var url = common.getRequest(),
            rateNum = 5,
            status = true;

        $(document).on('click', '.rate-item', function() {
            var self = $(this),
                key = self.index();

            $('.rate-item').map(function(index, item) {
                if (index <= key ) {
                    $(item).replaceWith('<i class="iconfont icon-xx1-hll open-panel rate-item" style="color: #efb336"></i>');
                } else {
                    $(item).replaceWith('<i class="iconfont icon-xx2-hll open-panel rate-item"></i>');
                }
            });

            rateNum = key + 1;
        });


        $(document).on('click', '#submit', function() {
            console.log(status);
            if (!status) return;
            status = false;

            var content = $.trim($('#commentText').val());

            if (!content || content == '') {
                $.alert('很抱歉,评论不能为空,请填写评论!', '操作失败');
                status = true;
                return;
            }

            common.ajax('POST', '/order/operation', {'id': url.id, 'type': 3, 'content': content, 'rank': rateNum}, true, function(rsp) {
                if (rsp.data.code == 0) {
                    if (url.refer == 'list') {
                        window.location.href = 'order-list.html?classify=' + url.classify;
                    } else if (url.refer == 'detail') {
                        window.location.href = 'order-detail.html?id=' + url.id;
                    } else {
                        window.location.href = 'order-list.html?classify=1';
                    }
                } else {
                    $.alert('很抱歉,提交评论失败,请重试!', '操作失败');
                }
                status = true;
            });
        });

        $(document).on('click', '#back', function() {
            if (url.refer == 'list') {
                window.location.href = 'order-list.html';
            } else if (url.refer == 'detail') {
                window.location.href = 'order-detail.html?id=' + url.id;
            } else {
                window.location.href = 'order-list.html?classify=1';
            }
        })

        var pings = env.pings;pings();
    });

    $.init();
});
