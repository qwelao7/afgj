require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#market-list", function (e, id, page) {
        var url = common.getRequest();

        var communitys = $('#communitys').html(),
            choose = $('#choose').html(),
            tpl = $('#tpl').html(),
            inquiry = $('#inquiry').html(),
            check = $('#check'),
            menus = $('#menus'),
            container = $('#container'),
            header = $('#header');

        var pageSize = 4,
            page = 2,
            loading = false,
            state = true,
            pages,
            classifys = ['全部','女装','数码','母婴','美妆','童装','其他'],
            market = {};

        common.img();
        var integer = function (data) {
            var string = parseFloat(data).toFixed(1);
            if(string == '0.0'){
                string = '免费'
            }else {
                string = '￥' + string;
            }
            return string;

        };
        var format = function (data) {
            data = data.replace(/\-/g, '/');
            var date = new Date(data),
                M = (date.getMonth() + 1 < 10 ? '0' + (date.getMonth() + 1) : date.getMonth() + 1) + '-',
                D = (date.getDate() < 10 ? '0' + date.getDate() : date.getDate()) + ' ',
                h = (date.getHours() < 10 ? '0' + date.getHours() : date.getHours()) + ':',
                m = date.getMinutes() < 10 ? '0' + date.getMinutes() : date.getMinutes();
            return M + D + h + m;
        };
        juicer.register('integer', integer);
        juicer.register('format', format);

        if (!url.id || !url.classify) {
            $.alert('很抱歉,暂未有相关数据,请重试!', function () {
                window.history.go(-1);
            })
        }

        /**
         * 加载小区
         */
        function loadCommunity() {
            common.ajax('GET', '/market/communitys', {}, false, function (rsp) {
                if (rsp.data.code == 0) {
                    window.localStorage.setItem('marketUid', rsp.data.info[0].community_id);

                    var defaultChoose = {},
                        defaultKeywords = {},
                        c_id = window.localStorage.getItem('marketUid');
                    if(url.id == 0) url.id = c_id;
                    if(!url.keywords) url.keywords = '';

                    $.each(rsp.data.info, function(index, item) {
                        if (item.community_id == url.id) {
                            defaultChoose.community = item;
                        }
                    });

                    defaultChoose.classify = classifys[url.classify];
                    defaultKeywords.keywords = url.keywords;

                    var html = juicer(communitys, rsp.data),
                        htm = juicer(choose, defaultChoose),
                        htl = juicer(inquiry, defaultKeywords);

                    menus.append(html);
                    check.prepend(htm);
                    header.prepend(htl);

                    //添加选中样式
                    $('.market-select-classify').eq(url.classify).addClass('font-green');
                    if(url.id == 0) {
                        $('.market-select-community').eq(0).addClass('font-green');
                    }else {
                        $('.market-select-community').each(function(index, item) {
                            if($(item).data('id') == url.id) {
                                $(item).addClass('font-green');
                            }
                        })
                    }

                    loadData();
                } else {
                    $.modal({
                        title: '温馨提示', text: '小市是小区内认证业主互助共享服务', buttons: [{
                            text: '知道了', onClick: function () {
                                window.history.go(-1);
                            }
                        }, {
                            text: '前往认证', bold: true, onClick: function () {
                                window.location.href = 'estate-manage.html';
                            }
                        }]
                    });
                }
            });
        }

        /**
         * 加载数据
         */
        function loadData() {
            if(!url.keywords) url.keywords = '';

            var c_id = window.localStorage.getItem('marketUid');
            if (!c_id) {
                $.alert('很抱歉,请重新加载本页!', function () {
                    window.location.reload();
                })
            }

            if (url.id == 0) {
                url.id = c_id;
            }

            common.ajax('GET', '/market/index', {
                'id': url.id,
                'type': url.classify,
                'keywords': url.keywords,
                'per-page': pageSize,
                'page': 1
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        htl = juicer(tpl, data);

                    pages = data.pagination.pageCount;

                    container.empty().append(htl);

                    if (pages == 1) {
                        // 加载完毕，则注销无限加载事件，以防不必要的加载
                        $.detachInfiniteScroll($('.infinite-scroll'));
                        // 删除加载提示符
                        $('.infinite-scroll-preloader').remove();
                        return;
                    }
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无闲置物品信息!</h3>";
                    container.empty().append(template);
                    $('.infinite-scroll-preloader').remove();
                }
            })
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

            common.ajax('GET', '/market/index', {
                'id': url.id,
                'type': url.classify,
                'keywords': url.keywords,
                'per-page': pageSize,
                'page': page
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    loading = false;

                    var data = rsp.data.info,
                        htm = juicer(tpl, data);

                    container.append(htm);

                    page++;
                }
            });

            $.refreshScroller();
        });

        /**
         * 切换二级菜单
         */
        $(document).on('click', '.classify', function () {
            var index = $(this).index(),
                another = 1 - index;

            $('.market-select-menu').eq(another).addClass('visibility-hidden');
            $('.market-select-menu').eq(index).toggleClass('visibility-hidden');
            $('.modal-overlay').eq(another).removeClass('modal-overlay-visible');
            $('.modal-overlay').eq(index).toggleClass('modal-overlay-visible');
        });

        /**
         * 选择小区,类型
         */
        $(document).on('click', '.market-select-singleline', function () {
            var self = $(this),
                parent = self.parent(),
                index = parent.index(),
                c_id = url.id,
                c_index = url.classify;

            if (index == 0) {
                c_id = self.data('id');
            } else if (index == 1) {
                c_index = self.index();
            }

            window.location.href = 'market-list.html?id=' + c_id + '&classify=' + c_index;
        });

        /**
         * 点击遮罩层
         */
        $(document).on('click', '.modal-overlay', function () {
            $('.market-select-menu').addClass('visibility-hidden');
            $(this).toggleClass('modal-overlay-visible');
        });

        /**
         * 小市详情
         */
        $(document).on('click', '.market-list', function() {
            var self = $(this),
                id = self.data('id');

            var c_id = window.localStorage.getItem('marketUid');
            url.id = (url.id == 0)?c_id:url.id;
            market.community = url.id;
            market.classify = url.classify;
            window.localStorage.setItem('market', JSON.stringify(market));

            window.location.href = 'market-detail.html?id=' + id;
        });

        /**
         * 单聊
         */
        $(document).on('click', '.to-hi', function() {
            var self = $(this),
                parent = self.parent(),
                id = parent.data('id');

            var c_id = window.localStorage.getItem('marketUid');
            url.id = (url.id == 0)?c_id:url.id;
            market.community = url.id;
            market.classify = url.classify;
            window.localStorage.setItem('market', JSON.stringify(market));

            window.location.href = 'market-detail.html?id=' + id;
        });

        /**
         * 点赞
         */
        $(document).on('click', '.to-praise', function(event) {
            var self = $(this),
                parent = self.parent(),
                id = parent.data('id'),
                isPraise = parent.data('ispraise'),
                c_id = window.localStorage.getItem('marketUid'),
                val = parseInt(self.text());

            if(url.id == 0) {
                url.id = c_id;
            }

            if(!state) {
                return;
            }

            state = false;

            if(isPraise) {
                //取消赞
                common.ajax('POST', '/borrowing/praise', {'id': id, 'type': 2}, true, function (rsp) {
                    if (rsp.data.code == 0) {
                        self.html('<i class="iconfont icon-zan1" style="padding: 0;"></i>&nbsp;<span class="font-grey">'+(val-1)+'</span>');
                        parent[0].setAttribute('data-ispraise', false);
                        state = true;
                    } else {
                        $.alert('很抱歉,取消点赞失败,请重试!', function() {
                            state = true;
                        });
                    }
                })
            }else {
                //点赞
                common.ajax('POST', '/borrowing/praise', {'id': id, 'type': 1}, true, function (rsp) {
                    if (rsp.data.code == 0) {
                        self.html('<i class="iconfont icon-dianzanhll font-green" style="padding: 0;"></i>&nbsp;<span class="font-green">'+ (val+1) + '</span>');
                        parent[0].setAttribute('data-ispraise', true);
                        state = true;
                    } else {
                        $.alert('很抱歉,点赞失败,请重试!', function() {
                            state = true;
                        });
                    }
                })
            }
        });

        /**
         * 返回
         */
        $(document).on('click', '#back', function() {
            window.location.href = common.ectouchPic;
        });

        /**
         * 搜索
         */
        $(document).on('click', '#submit', function() {
            var query = $('#search').val().trim();

            if(query == '') {
                $.alert('搜索内容不能为空,请重新填写!', function() {
                    $('#search').val('');
                    return;
                })
            }

            window.location.href = 'market-list.html?id=' + url.id + '&classify=' + url.classify + '&keywords=' + query;
        });

        /**
         * 监听input输入
         */
        $(document).on('input propertychange', '#search', function () {
            var str = $(this).val().trim();
            if (str == "") {
                container.empty();

                window.location.href = 'market-list.html?id=' + url.id + '&classify=' + url.classify;
            }
        });

        /**
         * 创建小市
         */
        $(document).on('click', '#create', function() {
            window.location.href  = 'market-post.html?id=' + url.id + '&classify=' + url.classify;
        });

        /**
         * 评论跳转
         */
        $(document).on('click', '.to-comment', function() {
            var self = $(this),
                parent = self.parent(),
                id = parent.data('id');

            var c_id = window.localStorage.getItem('marketUid');
            url.id = (url.id == 0)?c_id:url.id;
            market.community = url.id;
            market.classify = url.classify;
            window.localStorage.setItem('market', JSON.stringify(market));


            window.location.href = 'market-detail.html?id=' + id;
        });

        loadCommunity();
        var pings = env.pings;pings();
    });

    $.init();
});
