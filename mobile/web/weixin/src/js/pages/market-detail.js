require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#market-detail", function (e, id, page) {
        var url = common.getRequest();

        var market = window.localStorage.getItem('market');
        market = JSON.parse(market);

        var tpl = $('#tpl').html(),
            ext = $('#ext').html(),
            tab = $('#tab').html(),
            swiper = $('#swiper').html(),
            comment = $('#comment'),
            content = $('#content'),
            pop = $('#pop'),
            item_type,
            config = {
                loop: true,
                autoHeight: true,
                visiblilityFullfit: true,
                autoplayDisableOnInteraction: false,
                pagination: '.swiper-pagination',
                paginationClickable: true
            };

        var state = true,
            status = true;

        /* 自定义模板 */
        common.img();
        var integer = function (data) {
            var string = parseFloat(data).toFixed(1);
            if (string == '0.0') {
                string = '免费'
            } else {
                string = '￥' + string;
            }
            return string;

        };
        juicer.register('integer', integer);
        var time = function (data) {
            data = data.replace(/\-/g, '/');
            var date = new Date(data),
                M = (date.getMonth() + 1 < 10 ? '0' + (date.getMonth() + 1) : date.getMonth() + 1) + '-',
                D = (date.getDate() < 10 ? '0' + date.getDate() : date.getDate()) + ' ',
                h = (date.getHours() < 10 ? '0' + date.getHours() : date.getHours()) + ':',
                m = date.getMinutes() < 10 ? '0' + date.getMinutes() : date.getMinutes();
            return M + D + h + m;
        };
        juicer.register('time', time);
        /**
         * 加载数据
         */
        function loadData() {
            common.ajax('GET', '/market/detail', {'id': url.id}, false, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                    item_type = data.desc.type;

                    var html = juicer(tpl, data);
                    content.append(html);

                    var htm = juicer(ext, data);
                    content.append(htm);

                    var htmlx = juicer(tab, data);
                    comment.after(htmlx);

                    swiperPics(data.desc.item_pics);
                } else {
                    $.alert('很抱歉,服务器失去连接,请稍后...');
                }
            });

        }

        /**
         * 私聊
         */
        $(document).on('click', '#to_talk', function () {
            if ($('.sm-margin').data('isowner')) {
                $.alert('很抱歉,您不能和自己对话!');
                return;
            }

            common.ajax('GET', '/borrowing/talk', {'id': url.id, 'type': 2}, false, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    window.location.href = "neighbor-chat.html?id=" + data + '&type=2&param=' + item_type;
                } else {
                    $.alert('很抱歉,服务器失去连接,请稍后...');
                }
            });
        });

        /**
         * 留言
         */
        $(document).on('click', '#comment', function () {
            $.prompt('请填写您的留言', function (value) {
                if (value == '') {
                    $.alert('很抱歉,留言不能为空!');
                    return;
                }
                value = $.trim(value);

                common.ajax('POST', '/borrowing/comment', {'id': url.id, 'content': value}, false, function (rsp) {
                    if (rsp.data.code == 0) {
                        var data = rsp.data.info;
                        var template = '<div class="user-item decoration-item lr-padding white">' +
                            '<div class = "user-item-img" ><img class= "head-img" src = "' + data.user.headimgurl + '" ></div>' +
                            '<div class="user-item-content">' +
                            '<h1 class = "item-two-line-title">' + data.user.nickname + '</h1>' +
                            '<h5 class = "item-two-line-detail" >' + data.comment_time + '</h5>' +
                            '</div>' +
                            '<br style = "clear: both">' +
                            '<h3 style = "padding-bottom: .4rem;margin-top:.4rem;margin-bottom: 0;padding-left: 14%">' + data.comment_content + '</h3>' +
                            '</div>';
                        $('.tips2').remove();
                        $('.tab-link').removeClass('active');
                        $('.tab-link').eq(0).addClass('active');
                        $('.tab').removeClass('active');
                        $('#tab2').addClass('active');
                        $('#tab2').prepend(template);
                    } else if (rsp.data.code == 110) {
                        $.alert('很抱歉,留言失败,请重试!');
                    }
                })
            });
        });

        /**
         * 点赞
         */
        $(document).on('click', '#toPraise', function (event) {
            if(!status) return;
            status = false;
            //点赞
            common.ajax('POST', '/borrowing/praise', {'id': url.id, 'type': 1}, false, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        template = '<div class="user-item lr-padding white to-praise" data-id="' + data.praise_id + '">' +
                            '<div class="user-item-img"><img class="head-img" src="' + data.user.headimgurl + '"></div>' +
                            '<div class="user-item-content">' +
                            '<h2 class="item-two-line-title">' + data.user.nickname + '</h2><h5 class="item-two-line-detail">' + data.praise_time + '</h5>' +
                            '</div> <br style="clear: both"> </div>';
                    $('#toPraise').replaceWith('<div class="button no-border font-dark" style="border-left: 1px solid #f6f6f9;height: 1rem;line-height: 1rem" id="praised"> <i class="iconfont icon-dianzanhll font-green" style="padding: 0;"></i>&nbsp;已赞</div>');
                    $('.tips3').hide();
                    $('.tab-link').removeClass('active');
                    $('.tab-link').eq(1).addClass('active');
                    $('.tab').removeClass('active');
                    $('#tab3').addClass('active');
                    $('#tab3').prepend(template);
                    $('.tab-zan')[0].setAttribute('data-ispraise', true);
                    status = true;
                } else {
                    $.alert('很抱歉,点赞失败,请重试!', function() {
                        status = true;
                    });
                }
            });
        });
        $(document).on('click', '#praised', function(event) {
            if(!state) return;
            state = false;
            //取消点赞
            common.ajax('POST', '/borrowing/praise', {'id': url.id, 'type': 2}, false, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = parseInt(rsp.data.info),
                        arr = [];
                    $('#praised').replaceWith('<div class="button no-border font-dark" style="border-left: 1px solid #f6f6f9;height: 1rem;line-height: 1rem" id="toPraise"> <i class="iconfont icon-zan1 font-dark" style="padding: 0;"></i>&nbsp;赞</div>');
                    $('.to-praise').each(function (index, item) {
                        arr.push($(item).data('id'));
                    });
                    var index = arr.indexOf(data);
                    if (index != -1) {
                        $('.to-praise').eq(index).remove();
                    }
                    if (index != -1 && arr.length == 1) {
                        $('.tips3').show();
                    }
                    $('.tab-link').removeClass('active');
                    $('.tab-link').eq(1).addClass('active');
                    $('.tab').removeClass('active');
                    $('#tab3').addClass('active');

                    $('.tab-zan')[0].setAttribute('data-ispraise', false);
                    state = true;
                } else {
                    $.alert('很抱歉,取消点赞失败,请重试!', function() {
                        state = true;
                    });
                }
            })
        });

        /**
         * 返回
         */
        $(document).on('click', '#back', function() {
            if(!market) {
                market = {};
                market.community = 0;market.classify = 0;
            }

            window.location.href = 'market-list.html?id=' + market.community + '&classify=' + market.classify;
        });

        /**
         * 图片浏览
         */
        function swiperPics(data) {
            var arr = [];
            $.each(data, function(index, item) {
                arr.push(common.QiniuDamain + item);
            });

            $(document).on('click','.pb-popup',function () {
                var myPhotoBrowserPopup = $.photoBrowser({
                    photos : arr,
                    type: 'popup',
                    theme: 'dark'
                });
                myPhotoBrowserPopup.open();
                $('.close-popup').removeClass('icon').addClass('iconfont font-white');
                $('nav.bar-tab').remove();
            });
        }

        loadData();
        var pings = env.pings;pings();
    });

    $.init();
});
