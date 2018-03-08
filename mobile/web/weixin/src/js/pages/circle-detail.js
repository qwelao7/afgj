require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#circle-detail", function (e, id, page) {
        var url = common.getRequest();

        var container = $('#container'),
            tpl = $('#tpl').html(),
            action = $('#action').html(),
            radio = $('#radio').html(),
            auth = $('#auth').html(),
            text = $('#text').html();
        var page = 2,
            pages,
            loading = false;
        
        //自定义模板函数
        common.img();
        var format = function(data) {
            return (!data || data == undefined) ? '无': data;
        };
        juicer.register('format', format);
        
        //加载数据
        function loadData(){
            common.ajax('GET','/forum/summary',{
                'bbsId':url.id,
                'page': 1,
                'per-page': 10
            },true,function(rsg){
                if (rsg.data.code == 0) {
                    var data = rsg.data.info,
                        html = juicer(tpl, data),
                        sub = juicer(text, data),
                        tag = juicer(radio, data),
                        xhtml = juicer(auth, data),
                        htm = juicer(action,data);

                    container.append(html);
                    container.append(sub);
                    $('.title').after(tag);
                    $('#popup').append(xhtml);
                    container.before(htm);
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

            common.ajax('GET', '/forum/summary', {
                'bbsId':url.id,
                'page': page,
                'per-page': 10
            }, true, function (rsg) {
                if (rsg.data.code == 0) {
                    loading = false;
                    var data = rsg.data.info,
                        sub = juicer(text, data);
                    container.append(sub);

                    page++;
                }
            });
            $.refreshScroller();
        });

        //加入社团
        $(document).on('click','#join',function(){
            var self = $(this),
                bbsId = self.data('bbsid');
            common.ajax('GET','/forum/join',{bbsId:bbsId},true,function(rsg){
                if (rsg.data.code == 0) {
                    $.alert('加入成功!', function() {
                        common.ajax('GET','/forum/summary',{
                            'bbsId':url.id,
                            'page': 1,
                            'per-page': 10
                        },true,function(rsg){
                            if(rsg.data.code == 0){
                                container.empty();
                                $('#back').remove();
                                loadData();
                            }
                        })
                    });
                }else{
                    $.alert('很抱歉,加入失败,请重试!');
                }
            });
        });
        //退出社团
        $(document).on('click','#cancel',function(){
            var self = $(this),
                arr = [],
                bbsId = self.data('bbsid');
            $('.member').forEach(function (item) {
                arr.push($(item).data('id'));
            });
            common.ajax('GET','/forum/quit',{bbsId:bbsId},true,function(rsg){
                if (rsg.data.code == 0) {
                    $.alert('退出成功!', function() {
                        $('.member').forEach(function (item) {
                            arr.push($(item).data('id'));
                        });
                        var index = arr.indexOf(parseInt(rsg.data.info));
                        $('.member').eq(index).remove();
                        self.replaceWith('<nav class="bar bar-tab" id="join" data-bbsid="' + bbsId + '"><a class="tab-item external"><span class="font-white">加入社团</span></a></nav>');
                        $('#tag').remove();
                    });
                }else{
                    $.alert('很抱歉,退出失败,请重试!');
                }
            });
        });
        // 点击tag标签
        $(document).on('click','#back',function(){
            var self = $(this),
                bbsId = self.data('bbsid');
            window.location.href = 'bbs-list.html?id='+bbsId;
        });
        $(document).on('click', '#tag', function () {
            $('#popup').css('display', 'block');
            $('.modal-overlay').toggleClass('modal-overlay-visible');
        });
        $(document).on('click', '.modal-overlay', function () {
            $('#popup').css('display', 'none');
            $('.modal-overlay').toggleClass('modal-overlay-visible');
        });
        //解禁
        $(document).on('click','.unblock-member',function () {
            var self = $(this),
                arr = [],
                bbsId = self.data('bbsid'),
                user_role = self.data('userrole'),
                account_id = self.data('accountid');
            common.ajax('GET','/forum/remove',{
                'bbsId': bbsId,
                'account_id': account_id,
                'user_role': user_role,
                'type': 1
            },true,function(rsg){
                if (rsg.data.code == 0) {
                    $.alert('解禁成功!', function() {
                        $('.member').forEach(function (item) {
                            arr.push($(item).data('id'));
                        });
                        var index = arr.indexOf(parseInt(rsg.data.info));
                        $('.member').eq(index).find('.h4').remove();
                        $('.member').eq(index).find('.unblock-member').remove();
                        //window.location.href = 'circle-detail.html?id='+bbsId;
                    });
                }else{
                    $.alert(rsg.data.message);
                }
            });
        });
        //查看黑名单
        $(document).on('click','.black',function(){
            var self = $(this),
                bbsId = self.data('bbsid');
            window.location.href = 'circle-blocklist.html?id='+bbsId;
        });
        //设置副社长
        $(document).on('click','.setup', function () {
            var self = $(this),
                bbsId = self.data('bbsid');
            window.location.href = 'circle-setvp.html?id='+bbsId;
        });
        // 点击删除按钮的confirm事件，具体删除数据和跳转事件待添加
        $(document).on('click','.delete-confirm', function () {
            var self = $(this),
                bbsId = self.data('bbsid');
            $('#popup').css('display','none');
            $.confirm('您确认要解散此社团吗？解散后将不可恢复！', '警告',
                function () {
                    common.ajax('GET','/forum/destroy',{bbsId:bbsId},true, function (rsg) {
                        if (rsg.data.code == 0) {
                            $.alert('解散成功!', function() {
                                window.location.href = 'home.html';
                            });
                        }else{
                            $.alert('很抱歉,解散失败,请重试!');
                        }
                    })
                },
                function () {
                }
            );
        });
        loadData();
        var pings = env.pings;pings();
    });

    $.init();
});
