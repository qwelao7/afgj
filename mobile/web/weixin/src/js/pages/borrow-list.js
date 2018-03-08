require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#borrow-list", function (e, id, page) {
        //参数
        var tpl = $('#tpl').html(),
            header = $('#header').html(),
            title = $('#title'),
            content = $('.content-block');

        var values = [],
            ids = [],
            communitys = [],
            pageSize = 4,
            page = 2,
            loading = false,
            state = true,
            borrow = {},
            pages,
            community;

        var url = common.getRequest();

        /**
         * 自定义函数
         */
        var format = function (data) {
            data = data.replace(/\-/g, '/');
            var date = new Date(data),
                M = (date.getMonth() + 1 < 10 ? '0' + (date.getMonth() + 1) : date.getMonth() + 1) + '-',
                D = (date.getDate() < 10 ? '0' + date.getDate() : date.getDate()) + ' ',
                h = (date.getHours() < 10 ? '0' + date.getHours() : date.getHours()) + ':',
                m = date.getMinutes() < 10 ? '0' + date.getMinutes() : date.getMinutes();
            return M + D + h + m;
        };
        juicer.register('format', format);
        common.img();


        /**
         * 点击选项卡
         */
        $(document).on('click', '.tab-link', function () {
            var index = $(this).index();

            window.location.href = 'borrow-list.html?id=' + url.id + '&classify=' + index;
        });

        /**
         * 加载小区
         */
        function loadCommunity() {
            common.ajax('GET', '/ride-sharing/account-community', {}, false, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        info = {};
                    ids = data.id;
                    communitys = data.name;

                    if (url.id == 0) {
                        info.name = communitys[0];

                    } else {
                        var index = ids.indexOf(url.id);
                        info.name = communitys[index];
                    }

                    //设置默认小区
                    community = (url.id == 0) ? ids[0] : url.id;

                    $.each(communitys, function (index, item) {
                        values.push(item + '▾');
                    });

                    var html = juicer(header, info);
                    title.prepend(html);
                    $('.tab-link').eq(url.classify).addClass('active');

                    loadData();
                    picker();
                } else {
                    $.modal({
                        title: '温馨提示', text: '借用是小区内认证业主互助共享服务', buttons: [{
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
         * 选择小区
         */
        function picker() {
            $("#picker").picker({
                toolbarTemplate: '<header class="bar bar-nav">' +
                '<button class="button button-link pull-right close-picker font-white">确定</button>' +
                '<h1 class="title font-white">请选择小区</h1></header>',
                cols: [
                    {
                        textAlign: 'center',
                        values: values,
                        displayValues: communitys
                    }
                ],
                onOpen: function () {
                    var template = "<div class='modal-overlay modal-overlay-visible'></div>";
                    $('.page').append(template);
                },
                onClose: function () {
                    var str = $('#picker').val();
                    str = $.trim(str);

                    var index = values.indexOf(str);
                    community = ids[index];

                    window.location.href = 'borrow-list.html?id=' + community + '&classify=' + url.classify;
                }
            });
        }

        /**
         * 点击遮罩层
         */
        $(document).on('click', '.modal-overlay', function () {
            $(this).removeClass('modal-overlay-visible');
            $(".picker").picker("close");
        });

        /**
         * 加载数据
         */
        function loadData() {
            common.ajax('GET', '/borrowing/index', {
                'id': community,
                'type': url.classify,
                'page': 1,
                'per-page': pageSize
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data);
                    pages = data.pagination.pageCount;

                    content.append(html);
                    console.log(data);

                    if (pages == 1) {
                        // 加载完毕，则注销无限加载事件，以防不必要的加载
                        $.detachInfiniteScroll($('.infinite-scroll'));
                        // 删除加载提示符
                        $('.infinite-scroll-preloader').remove();
                        return;
                    }
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无借用信息!</h3>";
                    content.append(template);
                    $('.infinite-scroll-preloader').remove();
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

            common.ajax('GET', '/borrowing/index', {
                'id': community,
                'type': url.classify,
                'page': page,
                'per-page': pageSize
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    loading = false;

                    var data = rsp.data.info,
                        html = juicer(tpl, data);

                    content.append(html);

                    page++;
                }
            });
            $.refreshScroller();
        });

        /**
         * 跳转详情页
         */
        $(document).on('click', '.lg-margin-item', function () {
            var self = $(this),
                id = self.data('id');

            community = (url.id == 0) ? ids[0] : url.id;

            borrow.community = community;
            borrow.classify = url.classify;
            window.localStorage.setItem('borrow_cid', JSON.stringify(borrow));

            window.location.href = 'borrow-detail.html?id=' + id;
        });

        /**
         * 点击感谢按钮感谢
         */
        $(document).on('click', '.to-thank', function () {
            var self = $(this),
                parent = self.parent(),
                id = parent.data('id');

            community = (url.id == 0) ? ids[0] : url.id;
            borrow.community = community;
            borrow.classify = url.classify;
            window.localStorage.setItem('borrow_cid', JSON.stringify(borrow));

            window.location.href = 'borrow-detail.html?id=' + id;
        });

        /**
         * 点击评论
         */
        $(document).on('click', '.to-comment', function () {
            var _self = $(this),
                _parent = _self.parent(),
                id = _parent.data('id');

            community = (url.id == 0) ? ids[0] : url.id;
            borrow.community = community;
            borrow.classify = url.classify;
            window.localStorage.setItem('borrow_cid', JSON.stringify(borrow));

            window.location.href = 'borrow-detail.html?id=' + id;
        });

        /**
         * 点赞
         */
        $(document).on('click', '.to-praise', function (event) {
            var _this = $(this),
                _that = _this.parent(),
                id = _that.data('id'),
                val = parseInt(_this.text()),
                isPraise = _this.data('ispraise');

            if (!state) {
                return;
            }

            state = false;

            if (isPraise) {
                common.ajax('POST', '/borrowing/praise', {'id': id, 'type': 2}, true, function (rsp) {
                    if (rsp.data.code == 0) {
                        _this.html('<i class="iconfont icon-zan1" style="padding: 0;"></i>&nbsp;<span class="font-grey">' + (val - 1) + '</span>');
                        _this[0].setAttribute('data-ispraise', false);
                        state = true;
                    } else {
                        $.alert('很抱歉,取消点赞失败,请重试!', function () {
                            state = true;
                        });
                    }
                })
            } else {
                common.ajax('POST', '/borrowing/praise', {'id': id, 'type': 1}, true, function (rsp) {
                    if (rsp.data.code == 0) {
                        _this.html('<i class="iconfont icon-dianzanhll font-green" style="padding: 0;"></i>&nbsp;<span class="font-green">' + (val + 1) + '</span>');
                        _this[0].setAttribute("data-ispraise", true);
                        state = true;
                    } else {
                        $.alert('很抱歉,点赞失败,请重试!', function () {
                            state = true;
                        });
                    }
                })
            }
        });

        /**
         * 返回
         */
        $(document).on('click', '#back', function () {
            window.location.href = common.ectouchPic;
        });

        /**
         * 创建借用
         */
        $(document).on('click', '#create', function () {
            community = (url.id == 0) ? ids[0] : url.id;
            borrow.community = community;
            borrow.classify = url.classify;
            window.localStorage.setItem('borrow_cid', JSON.stringify(borrow));

            window.location.href = 'borrow-add.html';
        });

        loadCommunity();
        var pings = env.pings;
        pings();
    });

    $.init();
});

