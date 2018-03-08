require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#error-rate", function (e, id, page) {
        var url = common.getRequest(),
            rateNum = 5;

        var status = true,
            commentText = $('#commentText');

        var imgUrl = '',
            imgArr = [];

        $(document).on('click', '.rate-item', function () {
            var self = $(this), key;
            key = self.index();

            $('.rate-item').map(function (index, item) {
                if (index <= key) {
                    $(item).replaceWith('<i class="iconfont icon-xx1-hll open-panel rate-item" style="color: #efb336"></i>');
                } else {
                    $(item).replaceWith('<i class="iconfont icon-xx2-hll open-panel rate-item"></i>');
                }
            });

            rateNum = key + 1;
        });

        $(document).on('click', '#submit', function () {
            if (!status) return false;
            status = false;

            common.ajax('POST', '/feedback/maintain-rate', {
                'case_id': url.id,
                'rate_comment': $.trim($('#commentText').val()),
                'rate_star': rateNum,
                'img': imgArr.join(',')
            }, true, function(rsp) {
                if (rsp.data.code == 0) {
                    $.alert('评价提交成功', '提交成功', function () {
                        location.href = common.ectouchPic;
                    })
                } else {
                    $.alert('很抱歉,提交评价失败!', '提交失败', function () {
                        status = true;
                    })
                }
            })

        });

        $('.delete').live('click', function () {
            var self = $(this),
                parent = self.parents('.box'),
                link = self.data('link'),
                index;

            index = imgArr.indexOf(link);
            imgArr.splice(index, 1);

            parent.remove();
        });

        $(document).on('click', '.icon-camera', function () {
            wx.chooseImage({
                count: 1, // 默认9
                sizeType: ['compressed'], // 可以指定是原图还是压缩图，默认二者都有
                sourceType: ['album', 'camera'], // 可以指定来源是相册还是相机，默认二者都有
                success: function (res) {
                    var localId = res.localIds[0]; // 返回选定照片的本地ID列表，localId可以作为img标签的src属性显示图片
                    wx.uploadImage({
                        localId: localId, // 需要上传的图片的本地ID，由chooseImage接口获得
                        success: function (res) {
                            var serverId = res.serverId; // 返回图片的服务器端ID
                            common.ajax('GET', '/wechat/upload', {mediaId: serverId}, true, function (rsp) {
                                imgUrl = rsp.data.info;
                                renderPics(imgUrl);
                            })
                        }
                    });
                }
            });
        });

        function renderPics(link) {
            var template = "<div class='col-33 box' style='position: relative;padding: .3rem 0;'>" +
                "<img src='" + link + "' style='width: 4rem;height: auto;max-height: 4rem;'>" +
                "<i class='iconfont icon-cancel delete' data-type='img' data-link='" + link + "' style='position: absolute;left: 3.4rem;top:-.5rem;color: red;z-index: 2;'></i>" +
                "</div>";

            $('#rows').append(template);
            link = link.replace(common.QiniuDamain, '');
            imgArr.push(link);
        }

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
                            'chooseImage',
                            'previewImage',
                            'uploadImage',
                            'downloadImage'
                        ]
                    });
                } else {
                    $.alert('获取配置信息失败!');
                }
            })
        }

        getConfig();

        var pings = env.pings;
        pings();
    });

    $.init();
});