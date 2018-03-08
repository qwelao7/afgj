require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#library-book-comment", function (e, id, page) {
        var url = common.getRequest(),
            rateNum = 5,
            path;

        var status = true,
            commentText = $('#commentText');

        $(document).on('click', '.rate-item', function() {
            var self = $(this),key
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
            if (!status) return;
            status = false;

            var comment = commentText.val();
            $.trim(comment);

            if (comment == '') {
                $.alert('很抱歉,请输入您的评价!', '评价失败');
                status = true;
                return;
            }

            common.ajax('POST', '/library/comment', {id: url.id, rate_star: rateNum, comment: comment}, true, function(rsp) {
                if (rsp.data.code == 0) {
                    $.alert('评价成功!', '评价成功', function() {
                        if (url.ref == 'libraryindex') {
                            path = '&ref=libraryindex';
                        } else if (url.ref == 'search') {
                            path = '&ref=search';
                        } else if (url.ref == 'booklist'){
                            path = '&ref=booklist&library=' + url.library + '&type=' + url.type;
                        }
                        location.href = 'library-book-detail.html?id=' + url.id + path;
                    })
                } else {
                    $.alert('很抱歉,评价失败,请重试!', '评价失败');
                    status = true;
                }
            })
        });

        var pings = env.pings;pings();
    });

    $.init();
});
