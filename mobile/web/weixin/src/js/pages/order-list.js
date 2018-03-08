require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#order-list", function (e, id, page) {
        var url = common.getRequest();
        common.img();

        var host = location.host;
        //开发环境
        if (host.indexOf('8080') != -1) {
            host = 'www.afguanjia.com/';
        }
        host = 'http://' + host.replace('www', 'mall') + '/';

        var img = function (data) {
            if(data.substring(0,4) == 'data'){
                return host + data;
            }else{
                return 'http://pub.huilaila.net/'+data;
            }
        };
        juicer.register('img', img);

        var num = 2,
            pageSize = 10,
            loading = false,
            status = true,
            model = "<h3 style='text-align: center;margin-top: 4rem;'>暂无订单!</h3>",
            nums;

        var container = $('#container'),
            tpl = $('#tpl').html();

        $('.tab-link').eq(parseInt(url.classify) - 1).addClass('active');

        function loadData() {
            common.ajax('GET', '/order/list', {
                'type': url.classify,
                'page': 1,
                'per-page': pageSize
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    nums = data.pagination.pageCount;

                    if (data.pagination.total == 0) {
                        container.append(model);
                    }

                    var html = juicer(tpl, data);
                    container.append(html);

                    if (nums == 1) {
                        // 加载完毕，则注销无限加载事件，以防不必要的加载
                        $.detachInfiniteScroll($('.infinite-scroll'));
                        // 删除加载提示符
                        $('.infinite-scroll-preloader').remove();
                    }
                } else {
                    container.append(model);
                    $.detachInfiniteScroll($('.infinite-scroll'));
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

            if (num > nums) {
                // 加载完毕，则注销无限加载事件，以防不必要的加载
                $.detachInfiniteScroll($('.infinite-scroll'));
                // 删除加载提示符
                $('.infinite-scroll-preloader').remove();
                return;
            }

            common.ajax('GET', '/order/list', {
                'type': url.classify,
                'page': num,
                'per-page': pageSize
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    loading = false;

                    var result = rsp.data.info,
                        htm = juicer(tpl, result);

                    container.append(htm);

                    num++;
                }
            });

            $.refreshScroller();
        });

        /**
         * 跳转不同分类
         */
        $(document).on('click', '.tab-link', function () {
            var self = $(this),
                index = parseInt(self.index()) + 1;

            window.location.href = 'order-list.html?classify=' + index;
        });

        /**
         * 跳转详情
         */
        $(document).on('click', '.sm-margin', function () {
            var self = $(this),
                id = self.data('id');

            window.location.href = 'order-detail.html?classify=' + url.classify + '&id=' + id;
        });

        /**
         * 返回
         */
        $(document).on('click', '#back', function () {
            location.href = common.ectouchUrl + '&c=user&a=index';
        });

        /**
         * 确认收货
         */
        $('.to-delivery').live('click', function (e) {
            e.stopPropagation();
            var self = $(this),
                parents = self.parents('.sm-margin'),
                len = $('.sm-margin').size(),
                id = parents.data('id');

            if (!status) return;
            status = false;

            common.ajax('POST', '/order/operation', {'id': id, 'type': 2}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    $.alert('确认收货成功!', '操作成功', function () {
                        if (url.classify == 3) {
                            parents.remove();
                        } else {
                            var template = '<a class="button button-fill order-btn green to-comment">立即评价</a>';
                            self.replaceWith(template);
                            parents.find('.order-title-stat').html('待评价');
                        }

                        if (len == 1) {
                            container.append(model);
                        }
                    })
                } else {
                    $.alert('很抱歉,确认收货失败,请重试!', '操作失败');
                }
                status = true;
            });
        });

        /**
         * 立即评价
         */
        $('.to-comment').live('click', function (e) {
            e.stopPropagation();
            var self = $(this),
                parents = self.parents('.sm-margin'),
                id = parents.data('id');

            window.location.href = 'order-comment.html?id=' + id + '&refer=list&classify=' + url.classify;
        });

        /**
         * 去付款
         */
        $('.to-pay').live('click', function (e) {
            e.stopPropagation();
            var self = $(this),
                parents = self.parents('.sm-margin'),
                len = $('.sm-margin').size(),
                id = parents.data('id');

            if (!status) return;
            status = false;

            common.ajax('GET', '/order/wx-pay-params', {orderId: id}, false, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        jsApi = data['jsApi'],
                        return_url = data['url'];
                    
                    jsApi = JSON.parse(jsApi);

                    callpay(jsApi, return_url);

                }else {
                    $.alert('很抱歉,支付失败,请重试!');
                }
                status = true;
            })
        });

        function jsApiCall(event, jsApi, url) {
            WeixinJSBridge.invoke("getBrandWCPayRequest", jsApi , function (res) {
                if (res.err_msg == "get_brand_wcpay_request:ok") {
                    location.href = url + '&status=1';
                } else if (res.err_msg == 'fail') {
                    location.href = url + '&status=0';
                }
            });
        }

        function callpay(jsApi, url) {
            if (typeof WeixinJSBridge == "undefined") {
                if (document.addEventListener) {
                    document.addEventListener("WeixinJSBridgeReady", function(event){jsApiCall(event, jsApi, url);}, false);
                } else if (document.attachEvent) {
                    document.attachEvent("WeixinJSBridgeReady", function(event){jsApiCall(event, jsApi, url);});
                    document.attachEvent("onWeixinJSBridgeReady", function(event){jsApiCall(event, jsApi, url);});
                }
            } else {
                jsApiCall(event,jsApi, url);
            }
        }

        loadData();

        var pings = env.pings;
        pings();
    });

    $.init();
});
