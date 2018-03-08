require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#new-complaint", function (e, id, page) {
        //获取本次存储
        var storage = window.localStorage,
            pass = storage.getItem('pass');
        localStorage.removeItem('pass');

        //获取url参数
        var url = common.getRequest(),
            href = window.location.href;

        var tpl = $('#tpl').html(),
            container = $('#container'),
            content = $('#content');

        //上传图片数组
        var imgArr = [],
            imgs = $('#imgs');

        //模板自定义函数
        common.img();

        //获取用户信息
        common.ajax('GET', '/user/info', {userId: url.id}, true, function (rsp) {
            var data = rsp.data.info.list;
            data.address = pass;

            var html = juicer(tpl, data);
            container.prepend(html);

            getConfig();
        });

        //获取微信配置信息
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

        /** 点击上传图片 **/
        $(page).on('click', '#imgs', function () {
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
                            common.ajax('GET', '/wechat/upload', {mediaId: serverId}, true, function(rsp) {
                                if (rsp.data.code == 0) {
                                    var data = rsp.data.info;
                                    //添加图片
                                    var template = "<div class='col-33' style='position: relative;padding: .3rem 0;'>" +
                                        "<img src='" + data + "' style='width: 4rem;height: 4rem'>" +
                                        "<i class='iconfont icon-cancel delete' style='position: absolute;left: 3.4rem;top:-.5rem;color: red;z-index: 2;'></i>" +
                                        "</div>";
                                    $('.row').append(template);
                                    imgArr.push(data);
                                } else {
                                    $.alert('图片上传失败,请重试!');
                                }
                            })
                        }
                    });
                }
            })
        });

        /** 点击删除图片 **/
        $(page).on('click', '.delete', function () {
            var index = $(this).index();
            imgArr.splice(index, 1);
            $(this).parent().remove();
        })

        /** 提交投诉 **/
        $(page).on('click', '#submit', function () {
            var str = $('#complaint').val();
            if (str == '') {
                $.alert('请填写投诉信息!');
                return;
            }
            var picpath;
            if (imgArr.length > 0) {
                imgArr = common.imgUrl(imgArr);
                picpath = imgArr.join(",");
            } else {
                picpath = '';
            }

            common.ajax('POST', '/neighbour/appeal', {
                userId: url.id,
                content: str,
                picpath: picpath
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    $.alert('投诉已提交,感谢您的支持!');
                    setTimeout(function () {
                        window.history.go(-1);
                    }, 2000);
                } else {
                    $.alert('提交投诉信息失败,请重试!');
                }
            })
        })

        var pings = env.pings;pings();

    });

    $.init();
});
