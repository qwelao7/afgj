require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#drift-lamp-result", function (e, id, page) {
        var url = common.getRequest();

        var tpl     = $('#tpl').html(),
            content = $('#content');

        var headimgUrl;

        common.img();
        juicer.register('dateFormat', common.dateFormat);

        $('#back').live('click', function () {
            location.href = 'drift-lamp-index.html';
        });

        function loadData() {
            common.ajax('GET', '/light/light-detail', {'id': url.id}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data);

                    content.prepend(html);

                    headimgUrl = data.user.headimgurl;
                    
                    getConfig(headimgUrl);
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
                        var title = '我亲自动手检测了家里的灯光，简单又免费，你也可以试一下!';
                        var desc = '回来啦社区';
                        var imgUrl = headimg;
                        var link = location.href;
                        wx.onMenuShareAppMessage({
                            title: title, // 分享标题
                            desc: desc, // 分享描述
                            link: link, // 分享链接
                            imgUrl: imgUrl, // 分享图标
                            type: '', // 分享类型,music、video或link，不填默认为link
                            dataUrl: '' // 如果type是music或video，则要提供数据链接，默认为空
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

        loadData();

        var pings = env.pings;pings();

    });

    $.init();
});
