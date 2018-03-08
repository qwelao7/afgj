require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#ecard-index", function (e, id, page) {
        var url = common.getRequest();

        var tpl = $('#tpl').html(),
            container = $('#container');
        
        var headimgUrl = '',
            name = '';

        common.img();

        $('#detail').live('click', function() {
            location.href = 'event-detail.html?id=48&type=1';
        });

        function loadData() {
            common.ajax('GET', '/events/graduate-card-info', {id: url.qr_code}, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var html = juicer(tpl, rsp.data);
                    container.append(html);

                    headimgUrl = rsp.data.info.headimgurl;
                    name = rsp.data.info.kidname;

                    /** renderPic **/
                    renderPic(rsp.data.info.pics);

                    $youziku.load("body", "780b5ad14ece49b1abede20a236a8be2", "DroidSans");
                    $youziku.load(".e_number", "4a34adb7a94348a39a91e62bf3497032", "HiraginoGBW3");
                    //    $youziku.load(".font-ordinary", "780b5ad14ece49b1abede20a236a8be2", "DroidSans");
                    $youziku.draw();

                    getConfig(headimgUrl, name);
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无数据信息!</h3>";
                    container.append(template);
                }
            })
        }

        function renderPic(pics) {
            $('.ecard_container').each(function(index, item) {
                var clip = parseFloat(pics[index]['width'] / pics[index]['height']),
                    klip = parseFloat(pics[index]['height'] / pics[index]['width']);

                var clipSmall = '?imageMogr2/thumbnail/!' + parseInt((clip * 100 * 405) / 100) +  'x405r/gravity/center/crop/530x405',
                    clipBig = '?imageMogr2/thumbnail/!540x' + parseInt((klip * 100 * 520) / 100) + 'r/gravity/center/crop/530x750';

                if ( clip >= 1.3) {
                    $(item).css('backgroundImage', 'url(' + common.QiniuDamain + pics[index]['path'] + clipSmall + ')')
                } else {
                    $(item).css('backgroundImage', 'url(' + common.QiniuDamain + pics[index]['path'] + clipBig +')')
                }
            })
        }

        /**  获取微信配置 **/
        function getConfig(headimgUrl, name) {
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
                        var title = '祝福' + name + '第一次毕业，我们为你骄傲！';
                        var desc = '回来啦社区';
                        var imgUrl = headimgUrl;
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
