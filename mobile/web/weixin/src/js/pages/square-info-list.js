require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on('pageInit', '#square-info-list', function (e, id, page) {
        var url = common.getRequest();

        var title = $('#title').html(),
            tpl = $('#tpl').html(),
            container = $('#container');

        var num = 2,
            nums,
            loading = false;

        var defaultThumb = 'http://pub.huilaila.net/thumb001.png';

        common.img();

        $('#back').on('click', function () {
            location.href = 'square-tab-index.html?id=' + url.community;
        });

        $('.user-item').live('click', function () {
            var self = $(this),
                id = self.data('id'),
                type = self.data('type'),
                url = self.data('url');

            if (type == 1) {
                location.href = 'square-info-detail.html?id=' + id;
            } else {
                location.href = url;
            }
        });

        $(document).on('infinite', '.infinite-scroll', function () {
            if (loading) return;
            loading = true;

            if (num > nums) {
                stopInfinite();
                return;
            }

            common.ajax('GET', '/official/article-list', {
                'id': url.id,
                'community_id': url.community,
                'page': num
            }, true, function (rsp) {
                loading = false;

                var data = rsp.data.info,
                    tem = juicer(tpl, data);

                container.append(tem);

                num++;
            });

            $.refreshScroller();
        });

        function loadData() {
            common.ajax('GET', '/official/article-list', {
                'id': url.id,
                'community_id': url.community,
                'page': 1
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                    nums = data.pagination.pageCount;
                    data['defaultThumb'] = defaultThumb;

                    var htm = juicer(title, data),
                        html = juicer(tpl, data);

                    $('header').append(htm);
                    container.append(html);

                    if (nums == 1) {
                        stopInfinite();
                    }
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>暂无相关数据!</h3>";
                    container.append(template);

                    stopInfinite();
                }
            })
        }

        function stopInfinite() {
            // 加载完毕，则注销无限加载事件，以防不必要的加载
            $.detachInfiniteScroll($('.infinite-scroll'));
            // 删除加载提示符
            $('.infinite-scroll-preloader').remove();
        }

        loadData();

        var pings = env.pings;
        pings();
    });

    $.init();
});