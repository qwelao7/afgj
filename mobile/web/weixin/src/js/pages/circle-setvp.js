require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#circle-setvp", function (e, id, page) {
        var url = common.getRequest();
        var page = 2,
            pages,
            bbsId = url.id,
            loading = false,
            tpl = $('#tpl').html(),
            container = $('#container');

        function loadData(){
            common.ajax('GET','/forum/block-or-set',{
                'bbsId':bbsId,
                'type':2,
                'page': 1,
                'per-page': 10
            },true,function(rsg){
                if (rsg.data.code == 0) {
                    var data = rsg.data.info,
                        html = juicer(tpl, data);

                    container.append(html);

                    pages = data.pagination.pageCount;

                    if (pages == 1) {
                        // 加载完毕，则注销无限加载事件，以防不必要的加载
                        $.detachInfiniteScroll($('.infinite-scroll'));
                        // 删除加载提示符
                        $('.infinite-scroll-preloader').remove();
                        return;
                    }
                }
            });
        }
        /**
         * 无限滚动
         */
        $(document).on('infinite', '.infinite-scroll', function () {
            if (loading) return;

            loading = true;

            if (page > pages) {
                // 加载完毕，则注销无限加载事件，以防不必要的加载
                $.detachInfiniteScroll($('.infinite-scroll'));
                // 删除加载提示符
                $('.infinite-scroll-preloader').remove();
                return;
            }

            common.ajax('GET', '/forum/block-or-set', {
                'bbsId':bbsId,
                'type': 2,
                'page': page,
                'per-page': 10
            }, true, function (rsg) {
                if (rsg.data.code == 0) {
                    loading = false;
                    var data = rsg.data.info,
                        html = juicer(tpl, data);
                    container.append(html);

                    page++;
                }
            });
            $.refreshScroller();
        });

        //设置副社长
        $(document).on('click','.set',function(){
            var self = $(this),
                account_id = self.data('id'),
                role = self.data('role');
            common.ajax('GET','/forum/remove',
                {
                    'bbsId':bbsId,
                    'account_id':account_id,
                    'user_role': role,
                    'type': 2
                },true,function(rsg){
                    if (rsg.data.code == 0) {
                        $.alert('设置成功!', function() {
                            window.location.href = 'circle-setvp.html?id='+bbsId;
                        });
                    }else{
                        $.alert('很抱歉,设置失败,请重试!');
                    }
            });
        });
        //取消副社长
        $(document).on('click','.quit',function(){
            var self = $(this),
                account_id = self.data('id'),
                role = self.data('role');
            common.ajax('GET','/forum/remove',
                {
                    'bbsId':bbsId,
                    'account_id':account_id,
                    'user_role': role,
                    'type': 3
                },true,function(rsg){
                    if (rsg.data.code == 0) {
                        $.alert('取消成功!', function() {
                            window.location.href = 'circle-setvp.html?id='+bbsId;
                        });
                    }else{
                        $.alert('很抱歉,取消失败,请重试!');
                    }
                });
        });
        $(document).on('click','#back',function(){
            window.location.href = 'circle-detail.html?id='+bbsId;
        });

        loadData();
        var pings = env.pings;pings();
    });

    $.init();
});
