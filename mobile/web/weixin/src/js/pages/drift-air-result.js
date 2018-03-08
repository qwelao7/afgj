require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#drift-air-result", function (e, id, page) {
        var url = common.getRequest();

        var tpl     = $('#tpl').html(),
            content = $('#content');

        var headimgUrl;

        common.img();
        juicer.register('dateFormat', common.dateFormat);

        $('#back').live('click', function () {
           location.href = 'drift-air-index.html';
        });
        
        function loadData() {
            var refererHtml = document.referrer.split('/').pop().replace(/\.html/,'');
            common.ajax('GET', '/hcho/hcho-detail', {'id': url.id}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data);

                    content.prepend(html);

                    headimgUrl = data.user.headimgurl;

                    getConfig(headimgUrl);
                    if (refererHtml == 'drift-air-feedback') {
                        $.alert('请点击右上角...按钮,将测试结果分享到朋友圈，领取50元甲醛治理红包。');
                    }
                } else {
                    var template = "<h3 style='margin-top: 4rem;text-align: center;'>很抱歉,暂无数据</h3>";
                    content.prepend(template);
                }
            })
        }

        /**  获取微信配置 **/
        function getConfig(headimg) {
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
                        var title = '我亲自动手检测了家里的甲醛，简单又免费，你也可以试一下！';
                        var desc = '回来啦社区';
                        var imgUrl = headimg;
                        var link = location.href;
                        wx.onMenuShareTimeline({
                            title: title, // 分享标题
                            link: link, // 分享链接
                            imgUrl: imgUrl, // 分享图标
                            success: function () {
                                // 用户确认分享后执行的回调函数
                                common.ajax('GET', '/hcho/hcho-share', {}, true, function (rsp) {
                                    if (rsp.data.code == 0) {
                                        $.alert('感谢您的分享，红包已到账，请至“我家”-“我的钱包”-“红包”页面进行查看!');
                                    }
                                })
                            }
                        });
                    });
                } else {
                    $.alert('获取配置信息失败!');
                }
            })
        }

        loadData();

        var pings = env.pings;pings();
    });

    $.init();
});
