require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#brand-list", function (e, id, page) {
        var container = $('#container'),
            tpl = $('#tpl').html();

        var loading = false,
            nums,
            num = 2,
            pageSize = 10;

        common.img();

        function loadData() {
            common.ajax('GET', '/buildings/index', {'type': 'all','page': 1, 'per-page':pageSize}, false, function(rsp) {
                if(rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data);
                    nums = data.pagination.pageCount;
                    container.append(html);

                    if (nums == 1) {
                        // 加载完毕，则注销无限加载事件，以防不必要的加载
                        $.detachInfiniteScroll($('.infinite-scroll'));
                        // 删除加载提示符
                        $('.infinite-scroll-preloader').remove();
                    }
                }else {
                    $('.infinite-scroll-preloader').remove();
                    container.append("<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无楼盘信息</h3>");
                }
            })
        }

        //加载
        $(document).on('infinite', '.infinite-scroll', function () {
            // 如果正在加载，则退出
            if (loading) return;
            loading = true;

            if (num > nums) {
                // 加载完毕，则注销无限加载事件，以防不必要的加载
                $.detachInfiniteScroll($('.infinite-scroll'));
                // 删除加载提示符
                $('.infinite-scroll-preloader').remove();
                return;
            }

            common.ajax('GET', '/buildings/index', {'type': 'all','page': num, 'per-page':pageSize}, false, function(rsp) {
                if(rsp.data.code == 0) {
                    loading = false;

                    var data = rsp.data.info,
                        html = juicer(tpl, data);

                    container.append(html);

                    num++;
                }
            });

            $.refreshScroller();
        });

        $('.detail').live('click',function() {
            var id = $(this).data('id');

            window.location.href = 'brand-rich-text.html?id=' + id + '&type=2';
        });

        loadData();

        var pings = env.pings;pings();
    });

    $.init();
});