require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#brand-rich-text", function (e, id, page) {
        //参数
        var container = $('#container'),
            wrap = $('#brand-rich-text'),
            richText = $('#richText'),
            content = $('#content'),
            loupan = $('#loupan'),
            title = $('#title').html(),
            fixed = $('#fixed').html(),
            info = $('#info').html(),
            rich = $('#rich').html();

        var url = common.getRequest(),
            config = {
                loop: true,
                autoHeight: true,
                visiblilityFullfit: true,
                autoplayDisableOnInteraction: false,
                pagination: '.swiper-pagination',
                paginationClickable: true
            };
        //加载介绍富文本信息(暂去)
        if (url.type == 1) {
            loadRichText();
        } else if (url.type == 2) {
            //加载楼盘富文本
            loadLoupan();
        }

        common.img();
        function int(data) {
            return parseInt(data);
        }

        juicer.register('int', int);

        function loadLoupan() {
            common.ajax('GET', '/buildings/view', {id: url.id}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    data.bannerpic = JSON.parse(data.bannerpic);
                    var template = "<h3 class='inline'>" + data.loupan_intro + "</h3>";

                    var tplT = juicer(title, data),
                        tplF = juicer(fixed, data),
                        tplI = juicer(info, data);

                    wrap.prepend(tplT);
                    wrap.append(tplF);
                    loupan.append(tplI);
                    $('li:last-child').append(template);
                    $(".swiper-container").swiper(config);
                } else {

                }
            })
        }

        function loadRichText() {

        }

        $('#code').live('click', function () {
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

        var pings = env.pings;pings();
    });

    $.init();
});