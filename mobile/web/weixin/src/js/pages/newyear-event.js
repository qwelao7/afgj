require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#newyear-event", function (e, id, page) {
        var content = $('#container'),
            tpl = $('#tpl').html(),
            community = '';;

        function transNum(data) {
            var arr = ['一', '二', '三', '四', '五', '六', '七', '八', '九'],
                index = parseInt(data) - 1;
            return arr[index];
        }
        function transAward(data) {
            var arr = ['288', '88', '8'],
                index = parseInt(data) - 1;

            return arr[index];
        }

        juicer.register('transNum', transNum);
        juicer.register('transAward', transAward);


        function loadData() {
            common.ajax('GET', '/spring/index', {}, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data);

                    community = data.fang;

                    content.append(html);

                    $('.activity-item').each(function(index, item) {
                        if (data.cur[index + 1]) {
                            $(item).css({
                                'background': 'url("http://pub.huilaila.net/newyear-4.png") no-repeat 2rem 0',
                                'background-size':'contain'
                            })
                        }
                    });

                    getConfig();
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无活动信息!</h3>";
                    container.append(template);
                }
            })
        }

        /**  获取微信配置 **/
        function getConfig() {
            common.ajax('POST', '/wechat/config', {href: window.location.href}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    data = JSON.parse(data);
                    wx.config({
                        debug: false,
                        appId: data.appId,
                        timestamp: data.timestamp,
                        nonceStr: data.nonceStr,
                        signature: data.signature,
                        jsApiList: [
                            'checkJsApi',
                            'onMenuShareTimeline',
                            'onMenuShareAppMessage',
                        ]
                    });
                    wx.ready(function () {
                        var title = '快来参加回来啦社区新春特别活动“唯有家人最珍贵”，抢288元现金红包!',
                            desc = '回来啦社区',
                            imgUrl = common.QiniuDamain + '/spring.jpg',
                            link = window.location.href;
                        wx.onMenuShareAppMessage({
                            title: title, // 分享标题
                            desc: desc, // 分享描述
                            link: link, // 分享链接
                            imgUrl: imgUrl, // 分享图标
                            type: '', // 分享类型,music、video或link，不填默认为link
                            dataUrl: '', // 如果type是music或video，则要提供数据链接，默认为空
                        });
                        wx.onMenuShareTimeline({
                            title: title, // 分享标题
                            link: link, // 分享链接
                            imgUrl: imgUrl, // 分享图标
                        });
                    });
                } else {
                    $.alert('获取配置信息失败!');
                }
            })
        }

        $(document).on('click', '#home', function() {
            window.location.href = 'estate-manage.html';
        })

        $(document).on('click', '#neighbor', function() {
            if (community == '' || community == []) {
                $.alert('很抱歉，请先完成任务一!');
                return;
            }

            window.location.href = 'address-list.html?id=' + community.community_id;
        })

        $(document).on('click', '#forum', function() {
            if (community == '' || community == []) {
                $.alert('很抱歉，请先完成任务一!');
                return;
            }

            window.location.href = 'bbs-list.html?id=' + community.forum;
        })

        loadData();

        var pings = env.pings;pings();
    });

    $.init();
});
