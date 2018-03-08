require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#decoration-edit", function (e, id, page) {
        var url = common.getRequest(),
            href = window.location.href;

        var tpl = $('#tpl').html(),
            content = $('#container');

        common.img();

        var imgArr = [],
            crop = '?imageMogr2/auto-orient/thumbnail/!690x300r/gravity/center/crop/690x300',
            isSingle = true,
            status = true;

        function loadData() {
            common.ajax('GET', '/decorate/decorate-detail', {'id': url.id}, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data);
                    content.append(html);

                    imgArr.push(data.thumbnailpic);
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>无相关数据...</h3>";
                    content.append(template);
                }
            })
        }

        /** 获取微信配置 **/
        function getConfig() {
            common.ajax('POST', '/wechat/config', {href: href}, true, function (rsp) {
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

        /** 上传图片 **/
        $(document).on('click', '.icon-camera', function () {
            var self = $(this),
                parent = self.parents('.item-input');

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
                                if (rsp.data.code == 0) {
                                    var data = rsp.data.info,
                                        template = "<div class='box' style='overflow: visible;position: relative'> " +
                                            "<img src='" + data + "' style='width: 7rem;height: 7rem'> " +
                                            "<i class='iconfont icon-cancel' style='position: absolute;left: 6.4rem;top:-.8rem;color: red;z-index: 2;;' id='delete'></i>" +
                                            "</div>";

                                        if (isSingle) self.addClass('hide');
                                        parent.prepend(template);
                                        imgArr.push(data.replace(common.QiniuDamain, '') + crop);
                                } else {
                                    $.alert('图片上传失败,请重试!');
                                }
                            })
                        }
                    });
                }
            });
        });

        /** 删除图片 **/
        $(document).on('click', '.icon-cancel', function () {
            var _self = $(this),
                parent = _self.parents('.box'),
                camera = parent.next(),
                index = parent.index();

            imgArr.splice(index, 1);

            parent.remove();
            camera.removeClass('hide');
        });

        /** 提交 **/
        $(document).on('click', '#submit', function () {
            if (!status) return;
            status = false;

            var params = {};
            params.id = url.id;
            params.title = $.trim($('#decorate-title').val());
            params.budget = $.trim($('#decorate-budget').val());
            params.thumbnailpic = (imgArr.length == 0) ? '' : $.trim(imgArr.join(','));

            if (!params.title || params.title == '') {
                $.alert('很抱歉,提交失败,请填写装修标题!', '提交失败');
                status = true;
                return;
            }

            if (!params.budget || params.budget == '') {
                $.alert('很抱歉,提交失败,请填写装修预算!', '提交失败');
                status = true;
                return;
            }

            common.ajax('POST', '/decorate/decorate-edit', params, true, function(rsp) {
                if (rsp.data.code == 0) {
                    $.alert('装修项目编辑成功!', '编辑成功', function() {
                        var path = '?id=' + url.id + '&address_id=' + url.address_id + '&type=1';

                        window.localStorage.setItem('decorateDetail', params.title);
                        window.location.href = 'decoration-detail.html' + path;
                    })
                } else {
                    $.alert('很抱歉,装修项目编辑失败,请重试!', '编辑失败');
                }
                status = true;
            })
        });

        $('#back').live('click', function() {
            var path = '?id=' + url.id + '&address_id=' + url.address_id + '&type=1';

            window.location.href = 'decoration-detail.html' + path;
        });

        loadData();
        getConfig();

        var pings = env.pings;pings();
    });

    $.init();
});
