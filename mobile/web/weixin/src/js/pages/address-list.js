require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';
    
    $(document).on("pageInit", "#address-list", function (e, id, page) {
        /** 无限滚动 **/
        var tpl = $('#tpl').html(),
            admins = $('#admins').html(),
            total = $('#total').html(),
            specialTpl = $('#special').html(),
            title = $('#title').html(),
            neighbors = $('#neighbors'),
            container = $('#container'),
            header = $('#header'),
            layer = $('#layer');

        //获取url参数
        var url = common.getRequest();

        /** http **/
        var http = "http://" + location.host + '/site';

        //获取storage
        var storage = window.localStorage,
            loupan = storage.getItem('skip');

        //模板自定义函数
        var bbsImg = function(data) {
            var qiniu = common.QiniuDamain;
            if(data == null || data == '') {
                data = env.defaultCommunityImg;
            }
            return qiniu + data;
        };
        var transform = function (data) {
            var str = '';
            switch (data) {
                case 0:
                    str = '未关注';
                    break;
                case 1:
                    str = '已关注';
                    break;
                case 2:
                    str = '相互关注';
                    break;
            }
            return str;
        };
        juicer.register('transform', transform);
        juicer.register('bbsImg', bbsImg);

        //分页页数
        var pages,
            lastBuildingNum,
            pageSize = 10;

        /*ajax*/
        function loadData() {
            common.ajax('GET', '/community/contacts', {
                'per-page': pageSize,
                'loupanId': url.id,
                'page': 1
            }, false, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    pages = data.pagination.pageCount;
                    data.loupan = loupan;

                    if(data.list.length > 0) {
                        //上一页最后一个数据的楼栋号
                        lastBuildingNum = data.list[data.list.length - 1].building_num;
                        data.list = common.groupBy('classify', data.list);
                    }

                    var adHtml = juicer(admins, data),
                        ngHtml = juicer(tpl, data),
                        totalHtml = juicer(total, data),
                        titleHtml = juicer(title, data);

                    container.prepend(adHtml);
                    neighbors.append(ngHtml);
                    container.append(totalHtml);
                    header.append(titleHtml);

                    if (pages == 1) {
                        // 加载完毕，则注销无限加载事件，以防不必要的加载
                        $.detachInfiniteScroll($('.infinite-scroll'));
                        $('.infinite-scroll-preloader').remove();
                        $('#count').removeClass('hide');
                        return;
                    }
                }
            })
        }

        $(document).ready(function () {
            loadData();
            setTimeout(1000);
            /*无限滚动*/
            var loading = false,
                page = 2;
            $('.infinite-scroll').on('infinite', function () {
                // 如果正在加载，则退出
                if (loading) return;

                if (page > pages) {
                    // 加载完毕，则注销无限加载事件，以防不必要的加载
                    $.detachInfiniteScroll($('.infinite-scroll'));
                    $('.infinite-scroll-preloader').remove();
                    $('#count').removeClass('hide');
                    return;
                }
                loading = true;
                /*ajax*/
                common.ajax('GET', '/community/contacts',
                    {'per-page': pageSize, 'loupanId': url.id, 'page': page}, false, function (rsp) {
                        if (rsp.data.code == 0) {
                            var data = rsp.data.info;
                            var last = data.list[data.list.length - 1].building_num;
                            data.loupan = loupan;
                            data.list = common.groupBy('classify', rsp.data.info.list);

                            loading = false;
                            //是否存在索引为上一页最后楼栋号
                            if (data.list[lastBuildingNum] != []) {
                                //获取同一楼栋的数组
                                var special = [];
                                special['items'] = data.list[lastBuildingNum];
                                var specialHtml = juicer(specialTpl, special);
                                neighbors.append(specialHtml);
                                delete data.list[lastBuildingNum];
                            }
                            page++;
                            if (data.list[last] != undefined) {
                                var ngHtml = juicer(tpl, data);
                                neighbors.append(ngHtml);
                                //更新lastBuildNum
                                lastBuildingNum = last;
                            }
                        }
                    });

                $.refreshScroller();
            });
        });


        /*关注用户*/
        $('#container').on('click', '.follow', function () {
            event.stopPropagation();
            var it = $(this),
                self = it.find('span');
            if (self.length < 1) return;
            var userId = self.data('id');
            common.ajax('GET', '/user/follow', {userId: userId}, true, function (rsp) {
                if (rsp.data.code == 0) self.replaceWith('已关注');
                else $.alert('关注失败');
            })
        });

        /*跳转搜索界面*/
        $('#search').on('click', function () {
            window.location.href = 'neighbor-search.html?id=' + url.id;
        });

        /*跳转用户详情页*/
        container.on('click', '.skip', function () {
            event.preventDefault();
            var userId = $(this).data('id');
            window.location.href = 'neighbor-detail.html?id=' + userId;
        });

        /*跳转个人信息页*/
        $('#myself').live('click', function () {
            window.location.href = common.ectouchUrl + '&c=user&a=index';
        });

        /*上拉刷新*/
        $('.pull-to-refresh-content').live('refresh', function () {
            window.location.reload();
            // 加载完毕需要重置
            $.ready(function () {
                $.pullToRefreshDone('.pull-to-refresh-content');
            })
        })

        /*加入社团*/
        $('.join').live('click', function () {
            var self = $(this),
                id = self.data('id'),
                state = self.data('state'),
                total = self.parents('.forum').find('span'),
                num = total.text().replace(/[^0-9]/ig, "");

            if (state == 2) {
                $.alert('很抱歉,您已被拉黑,无法加入');
                return;
            }

            common.ajax('GET', '/forum/join', {bbsId: id}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    self.replaceWith('已加入');
                    total.text((Number(num) + 1) + '人');
                } else {
                    $.alert(rsp.data.message);
                }
            })
        });

        /*跳转社团列表*/
        $('.href').live('click', function () {
            var id = $(this).data('id');

            window.location.href = 'bbs-list.html?id=' + id;
        });

        /** 添加公共信息 **/
        $('#add').live('click', function () {
            location.href = 'public-info-add.html?id=' + url.id;
        });
        
        /** 报错 **/
        $('#error').live('click', function () {
           location.href = 'public-info-error.html?id=' + url.id;
        });

        /** 新建社团 **/
        $('#create').live('click', function () {
            location.href = 'circle-add.html?id=' + url.id;
        });

        /** 返回 **/
        $('#back').on('click', function () {
            location.href = 'square-tab-index.html?id=' + url.id;
        });

        var pings = env.pings;pings();
    });

    $.init();
});
