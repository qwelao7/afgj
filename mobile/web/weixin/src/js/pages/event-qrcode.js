require('../../css/style.css');
require('../../css/index.css');
var QRCode = require('../lib/qrcode.js');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#event-qrcode", function (e, id, page) {
        var url = common.getRequest();
        
        var tpl = $('#tpl').html(),
            content = $('#content');
        
        var logo = 'http://pub.huilaila.net/defaultpic/huilailalogo.jpg';

        var time = function(data) {
            data = data.replace(/\-/g, '/');
            var date = new Date(data);

            return common.formatDate(date.getTime() / 1000);
        };
        var splitStr = function (data, num) {
            var char = '地址:';
            if (data.indexOf('地址') == -1) {
                if (num == 0) {
                    return data;
                } else {
                    return '';
                }
            } else {
                var arr = data.split(char);
                return arr[num];
            }
        };
        var parse = function(data) {
            var str = data.replace(/\s/g, '<br/>');
            return str;
        };
        juicer.register('parse', parse);
        juicer.register('time', time);
        juicer.register('splitStr', splitStr);

        $('#back').on('click', function() {
            location.href = 'event-detail.html?id='+ url.id + '&type=1';
        });
        
        function renderCode(data) {
            new QRCode('qrcode', {
                text: 'http://' + location.host + '/event-signin.html?events_id=' + data.event.id + '%26code=' + data.sign_in_code,
                width: 152,
                height: 152,
                colorDark : '#000000',
                colorLight : '#ffffff',
                correctLevel : QRCode.CorrectLevel.H
            });
        }

        function loadData() {
            common.ajax('GET', '/events/share-qrcode', {
                id: url.id
            }, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data);
                    content.append(html);

                    renderCode(data);
                    
                    getConfig(data);
                } else {
                    var template = "<h3 style='margin-top: 4rem;text-align: center;'>很抱歉,数据错误!</h3>";
                    content.append(template);
                }
            })
        }

        /**  获取微信配置 **/
        function getConfig(info) {
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
                            'onMenuShareAppMessage'
                        ]
                    });
                    wx.ready(function () {
                        var title = info.title + '签到码';
                        var desc = '回来啦社区';
                        var imgUrl = logo;
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
                            imgUrl: imgUrl // 分享图标
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
