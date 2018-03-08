require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#library-index", function (e, id, page) {
        var tpl = $('#tpl').html(),
            content = $('#content');

        common.img();

        function loadData() {
            common.ajax('GET', '/library/library-card', {}, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data);

                    content.append(html);

                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无您的信息!</h3>";
                    content.append(template);
                }
            })
        }

        /**
         * 点击tag标签
         **/
        $(document).on('click', '#tag', function () {
            $('#popup').css('display', 'block');
            $('.modal-overlay').toggleClass('modal-overlay-visible');
        });
        $(document).on('click', '.modal-overlay', function () {
            $('#popup').css('display', 'none');
            $('.modal-overlay').toggleClass('modal-overlay-visible');
        });

        /**
         * 返回
         */
        $(document).on('click', '#back', function() {
           window.location.href = common.ectouchUrl + '&c=user&a=index';
        });

        /**
         * donate
         */
        $(document).on('click', '#donate', function() {
            window.location.href = 'library-donate.html';
        });

        /**
         * 图书详情
         */
        $(document).on('click', '.book-list', function() {
            var id = $(this).data('id');

            window.location.href = 'library-book-detail.html?id=' + id + '&ref=libraryindex';
        })

        loadData();

        var pings = env.pings;pings();
    });

    $.init();
});
