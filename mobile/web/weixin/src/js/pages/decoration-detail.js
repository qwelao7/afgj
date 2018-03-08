require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#decoration-detail", function (e, id, page) {
        //url参数
        var url = common.getRequest(),
            time = new Date();

        //加载参数
        var loading = false,
            pageSize = 4,
            num = 2,
            nums,
            config = {
                loop: true,
                autoHeight: true,
                visiblilityFullfit: true,
                autoplayDisableOnInteraction: false,
                pagination: '.swiper-pagination',
                paginationClickable: true
            };

        var is_prototyperoom = 0;

        common.img();
        //获取storage
        var storage = window.localStorage,
            detailTitle = storage.getItem('decorateDetail');

        //容器
        var listTpl = $('#listTpl').html(),
            title = $('#title').html(),
            docTpl = $('#docTpl').html(),
            matTpl = $('#matTpl').html(),
            pop1Tpl = $('#pop1Tpl').html(),
            nav = $('#nav').html(),
            header = $('#header'),
            container = $('#container'),
            pop1 = $('#pop1'),
            pop2 = $('#pop2');

        //模板函数
        common.img();
        var trans = function (data) {
            switch (data) {
                case '1':
                    data = '房主';
                    break;
                case '2':
                    data = '项目经理';
                    break;
                case '3':
                    data = '客服';
                    break;
            }
            return data;
        };
        var split = function (data) {
            data = data.split(' ');
            var string = data[0];
            string = string.replace(/-/g, '.');
            return string;
        };
        juicer.register('trans', trans);
        juicer.register('split', split);

        /**
         * 标题
         */
        var titleTpl = juicer(title, {title: detailTitle});
        header.prepend(titleTpl);

        /**
         * 装修日志
         * @param params
         */
        function loadData(params) {
            common.ajax('GET', '/decorate/logs', {
                'id': url.id,
                'page': params,
                'per-page': pageSize
            }, false, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                    nums = data.pagination.pageCount;
                    loading = false;

                    var html = juicer(listTpl, data);
                    container.append(html);

                    if (params > 1) {
                        num++;
                    }

                    if (nums == 1) {
                        stopInfinite();
                    }
                } else {
                    container.append("<h3 style='text-align: center;margin-top: 4rem;'>无相关数据...</h3>");

                    stopInfinite();
                }
            })
        }

        /**
         * 装修档案
         */
        function loadDoc() {
            common.ajax('GET', '/decorate/archives', {'id': url.id}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    if (data.contact.length == 0 && data.pics === null) {
                        container.append("<h3 style='text-align: center;margin-top: 4rem;'>无相关数据...</h3>");
                    } else {
                        data.pics = JSON.parse(data.pics);
                        var html = juicer(docTpl, data);
                        container.append(html);

                        renderPics(data);
                    }
                } else {
                    container.append("<h3 style='text-align: center;margin-top: 4rem;'>无相关数据...</h3>");
                }
            });

        }

        /**
         * 装修材料
         */
        function loadMaterial() {
            common.ajax('GET', '/decorate/material', {'id': url.id}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data,
                        html = juicer(matTpl, data);

                    container.append(html);
                } else {
                    container.append("<h3 style='text-align: center;margin-top: 4rem;'>无相关数据...</h3>");
                }
            })
        }

        /**
         * 是否是样板放
         */
        function loadIsPrototyperoom() {
            common.ajax('GET', '/decorate/is-prototyperoom', {'id': url.id}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    is_prototyperoom = rsp.data.info.is_prototyperoom;

                    renderNav();
                }
            })
        }

        /**
         * render nav
         */
        function renderNav() {
            var htm = juicer(nav, {'is_prototyperoom': is_prototyperoom});
            $('#header').append(htm);
        }

        function init() {
            if (!url.type) url.type = 1;

            $('.tab-link').eq(url.type - 1).addClass('active');

            switch (url.type) {
                case '1':
                    loadIsPrototyperoom();
                    loadData(1);
                    break;
                case '2':
                    loadIsPrototyperoom();
                    stopInfinite();
                    loadDoc();
                    break;
                case '3':
                    loadIsPrototyperoom();
                    stopInfinite();
                    loadMaterial();
                    break;
            }
        }

        function stopInfinite() {
            // 加载完毕，则注销无限加载事件，以防不必要的加载
            $.detachInfiniteScroll($('.infinite-scroll'));
            // 删除加载提示符
            $('.infinite-scroll-preloader').remove();
        }

        function renderPics(data) {
            $(document).on('click', '.open-about', function () {
                //生成图集数据
                var index = $(this).data('index'),
                    result = {};
                result['pics'] = data.pics[index];

                var html = juicer(pop1Tpl, result);
                pop1.empty().append(html);

                $.popup('.popup-about');
                $('.swiper-container').swiper(config);
            });
        }

        //切换tab选项
        $('.tab-link').on('click', function () {
            var self = $(this),
                index = self.index();

            var path = '?id=' + url.id + '&address_id=' + url.address_id + '&type=' + (index + 1);
            location.href = 'decoration-detail.html' + path;
        });

        //加载
        $(document).on('infinite', '.infinite-scroll', function () {
            // 如果正在加载，则退出
            if (loading) return;

            loading = true;
            if (num > nums) {
                stopInfinite();
                return;
            } else {
                loadData(num);
            }

            $.refreshScroller();
        });

        /**
         * 一键报障(装修报障)
         */
        $(document).on('click', '.complain-route', function () {
            var self = $(this),
                parent = self.parents('.decoration-padding'),
                id = parent.data('id'),
                c_id = parent.data('cat_id');

            //d_id 装修id m_id 材料id c_id 材料分类id address_id 房产id
            var path = '?d_id=' + url.id + '&m_id=' + id + '&c_id=' + c_id + '&address_id=' + url.address_id;

            location.href = 'error-add.html' + path;
        });

        /**
         * 跳转编辑
         */
        $(document).on('click', '#to-edit', function () {
            window.location.href = 'decoration-edit.html?id=' + url.id + '&address_id=' + url.address_id;
        });

        init();

        var pings = env.pings;
        pings();

    });

    $.init();
});
