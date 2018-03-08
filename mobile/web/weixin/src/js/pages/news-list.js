require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#news-list", function (e, id, page) {
        var url = common.getRequest();

        var newsContent = $('#newsContent'),
            journalContent = $('#journalContent'),
            infoContent = $('#infoContent'),
            header = $('#header'),
            popup = $('#popup'),
            newsTpl = $('#newsTpl').html(),
            jourTpl = $('#jourTpl').html(),
            infoTpl = $('#infoTpl').html(),
            titleTpl = $('#titleTpl').html(),
            tagsTpl = $('#tagsTpl').html();

        var pageSize = 10,
            pagel = 2,
            list = 2,
            loading = false,
            loaded = false,
            pages,
            lists;

        /**
         * 自定义模板
         */
        common.img();
        var format = function (data) {
            return data = data.replace(/\-/g, '.');
        };
        var formate = function (data) {
            var time = new Date(data * 1000),
                M = (time.getMonth() + 1 < 10 ? '0' + (time.getMonth() + 1) : time.getMonth() + 1) + '月',
                D = (time.getDate() < 10 ? '0' + time.getDate() : time.getDate()) + '日';
            return M + D;
        };
        juicer.register('format', format);
        juicer.register('formate', formate);

        //加载头部
        function loadTitle() {
            var skip = window.localStorage.getItem('skip');
            if (!skip) {
                window.location.href = 'news-loupan.html';
            } else {
                skip = JSON.parse(skip);
            }
            var fields = 'hot_line,wx_qr_code';
            common.ajax('GET', '/buildings/tags', {id: url.id, fields: fields}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    data.name = skip.name;
                    var htm = juicer(tagsTpl, data),
                        html = juicer(titleTpl, data);

                    header.prepend(html);
                    popup.append(htm);
                }
            });
        }

        //加载咨询
        function loadNews() {
            common.ajax('GET', '/forum/news', {'id': url.id, 'per-page': pageSize, 'page': 1}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(newsTpl, data);

                    lists = data.pagination.pageCount;
                    if (lists == 1) {
                        $('.infinite-scroll-preloader').eq(0).hide();
                    }

                    newsContent.append(html);
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>暂无楼盘资讯!</h3>";
                    newsContent.append(template);
                    $('.infinite-scroll-preloader').eq(0).hide();
                }
            })
        }

        //加载楼盘成长日志
        function loadList() {
            common.ajax('GET', '/forum/details', {'id': url.id, 'per-page': pageSize, 'page': 1}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(jourTpl, data);

                    pages = data.pagination.pageCount;
                    if (pages == 1) {
                        $('.infinite-scroll-preloader').eq(1).hide();
                    }


                    journalContent.append(html);
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>暂无楼盘成长日志!</h3>";
                    journalContent.append(template);
                    $('.infinite-scroll-preloader').eq(1).hide();
                }
            })
        }

        //加载楼盘信息
        function loadInfo() {
            common.ajax('GET', '/buildings/view', {id: url.id}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    if (data.bannerpic) {
                        data.bannerpic = JSON.parse(data.bannerpic);
                    }
                    var tem = "<h3 class='inline'>" + data.loupan_intro + "</h3>";

                    var htm = juicer(infoTpl, data);

                    infoContent.append(htm);

                    var config = {
                        autoHeight: true,
                        visiblilityFullfit: true,
                        autoplayDisableOnInteraction: false,
                        pagination: '.swiper-pagination',
                        paginationClickable: true,
                        loop: true
                    };
                    $('li:last-child').append(tem);
                    $(".swiper-container").swiper(config);

                    swiperBanner(data.bannerpic);
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>暂无楼盘信息!</h3>";
                    infoContent.append(template);
                }
            })
        }

        /**
         * 图集浏览
         */
        function swiperBanner(data) {
            var arr = [];
            $.each(data, function(index, item) {
                arr.push(common.QiniuDamain + item);
            });

            $(document).on('click','.hll-swiper-slide',function () {
                var myPhotoBrowserPopup = $.photoBrowser({
                    photos : arr,
                    type: 'popup',
                    theme: 'dark'
                });
                myPhotoBrowserPopup.open();
                $('.close-popup').removeClass('icon').addClass('iconfont font-white');
                $('.bar-tab').remove();
            });
        }

        /**
         * 二维码
         */
        $('#code').live('click', function () {
            $('#popup').css('display', 'none');
            $('.modal-overlay').toggleClass('modal-overlay-visible');
            var code = $(this).data('code');
            $.modal({
                title: '楼盘公众号',
                text: '长按二维码即可关注楼盘',
                afterText: '<div class="swiper-container" style="width: auto; margin:1.25rem 0 0.5rem 0">' +
                '<div class="swiper-pagination"></div>' +
                '<div class="swiper-wrapper">' +
                '<div class="swiper-slide"><img src="' + common.QiniuDamain + code + '" height="200" style="display:block;margin: 0 auto;"></div>' +
                '</div>' +
                '</div>',
                buttons: [
                    {
                        text: '知道了',
                        bold: true,
                    },
                ]
            })
        })

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
         * 楼盘信息
         */
        $(document).on('click', '#tab-info', function () {
            infoContent.empty();
            loadInfo();
        });

        /**
         * 返回
         */
        $(document).on('click', '#back', function () {
            window.location.href = 'news-loupan.html';
        });

        /**
         * 进入详情
         */
        $(document).on('click', '.news-consult', function () {
            var self = $(this),
                id = self.data('id');

            window.location.href = 'article.html?id=' + id;
        });

        /**
         * 无限滚动
         */
        $(document).on('infinite', function () {
            var index = $(this).find('.infinite-scroll.active').attr('id');
            if (index == 'tabJournal') {
                infiniteJour();
            } else if (index == 'tabNews') {
                infiniteNews();
            }
        });

        /**
         * 成长日志无限滚动
         */
        function infiniteJour() {
            if (loading) return;

            loading = true;

            if (pagel > pages) {
                // 删除加载提示符
                $('.infinite-scroll-preloader').eq(1).hide();
                return;
            }

            common.ajax('GET', '/forum/details', {
                'id': url.id,
                'per-page': pageSize,
                'page': pagel
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(jourTpl, data);

                    loading = false;

                    journalContent.append(html);

                    pagel++;
                }
            });
        }

        /**
         * 楼盘咨询无限滚动
         */
        function infiniteNews() {
            if (loaded) return;

            loaded = true;

            if (list > lists) {
                // 删除加载提示符
                $('.infinite-scroll-preloader').eq(0).hide();
                return;
            }

            common.ajax('GET', '/forum/news', {'id': url.id, 'per-page': pageSize, 'page': list}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(newsTpl, data);

                    loaded = false;

                    newsContent.append(html);

                    list++;
                }
            })
        }

        /**
         * 热线
         */
        $(document).on('click', '#tel', function() {
            var self = $(this),
                phone = self[0].getAttribute('data-tel');

            window.location.href = 'tel://' + phone;
        });

        loadTitle();
        loadNews();
        loadList();

        var pings = env.pings;
        pings();
    });

    $.init();
});
