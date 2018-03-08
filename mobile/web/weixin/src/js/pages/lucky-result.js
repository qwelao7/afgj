require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#lucky-result", function (e, id, page) {
        var headimgurl,remoney,nickname;
        function loadData() {
            var obj = common.getRequest();
            if(typeof(obj)!='undefined' && obj.hasOwnProperty('headimgurl') &&  obj.hasOwnProperty('remoney') &&  obj.hasOwnProperty('nickname')) {
                $('#headurl').prop('src', obj.headimgurl);
                $('#remoney').html(obj.remoney);
                headimgurl=obj.headimgurl;
                remoney=obj.remoney;
                nickname = obj.nickname;
                var title = '真走运，'+nickname+'抢到“回来啦社区”现金红包'+remoney+'元，你也来试试手气吧！ ^_^';
                $("title").html(title);
                $(".share_tips").css('display', 'none');
                return;
            }
            var storage = window.localStorage;
            var reid =  storage.getItem('reid');
            if(reid) {
                common.ajax('GET', '/redenvelope/result', {id: reid}, true, function (rsp) {
                    if (rsp.data.code == 0) {
                        var data = rsp.data.info;
                        headimgurl=data.headimgurl;
                        remoney=data.remoney;
                        nickname = data.nickname;
                        $('#headurl').prop('src', data.headimgurl);
                        $('#remoney').html(data.remoney);
                        getConfig();

                    } else {
                        $.alert(rsp.data.message);
                        window.location.href = "lucky-money.html";
                    }
                });

            }
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
                        var title = '真走运，我抢到“回来啦社区”现金红包'+remoney+'元，你也来试试手气吧！ ^_^';
                        var desc = '回来啦社区';
                        var imgUrl = common.QiniuDamain + '/rd1.jpg';
                        var link = window.location.href+"?headimgurl="+encodeURIComponent(headimgurl)+"&remoney="+encodeURIComponent(remoney)+"&nickname="+encodeURIComponent(nickname);
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
        loadData();
        var pings = env.pings;pings();
    });

    $.init();
});
