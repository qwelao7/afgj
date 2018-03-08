require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on('pageInit', '#square-building-log', function (e, id, page) {
        var url = common.getRequest();
        
        var lists = $('#lists').html(),
            content = $('#content');
        
        var num = 2,
            loading = false,
            nums;

        common.img();
        var format = function (data) {
            return data = data.replace(/\-/g, '.');
        };
        juicer.register('format', format);
        
        function loadList() {
            common.ajax('GET', '/forum/details', {'id': url.id, 'page': 1}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(lists, data);

                    nums = data.pagination.pageCount;
                    content.append(html);

                    if (nums == 1) {
                        stopInfinite()
                    }
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>暂无楼盘成长日志!</h3>";
                    content.append(template);

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

        $(document).on('infinite', '.infinite-scroll', function () {
            // 如果正在加载，则退出
            if (loading) return;
            loading = true;

            if (num > nums) {
                stopInfinite();
                return;
            }

            common.ajax('GET', '/forum/details', {'id': url.id, 'page': num}, true, function (rsp) {
                if(rsp.data.code == 0) {
                    loading = false;

                    var data = rsp.data.info,
                        html = juicer(lists, data);

                    content.append(html);

                    num++;
                }
            });
            
            $.refreshScroller();
        });
        
        loadList();
        
        var pings = env.pings;
        pings();
    });

    $.init();
});