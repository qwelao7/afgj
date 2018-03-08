require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#new-decoration", function (e, id, page) {
        //href
        var href = window.location.href;

        var tpl = $('#tpl').html(),
            first = $('.first');

        //参数
        var data = {},
            imgArr = '',
            crop = '?imageMogr2/auto-orient/thumbnail/!690x300r/gravity/center/crop/690x300';

        /** 加载 **/
        function loadData() {
            common.ajax('GET', '/decorate/create', {}, false, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data);

                    first.after(html);
                    getConfig();
                } else {
                   if(rsp.data.code == 108) {
                       $.modal({
                           title: '友情提示',
                           text: rsp.data.message + ', 请创建您的房产!',
                           buttons: [
                               {
                                   text: '知道了',
                                   onClick: function () {
                                       window.history.go(-1);
                                   }
                               },
                               {
                                   text: '立即前往',
                                   bold: true,
                                   onClick: function () {
                                       window.location.href = 'estate-manage.html';
                                   }
                               }
                           ]
                       })

                   }else {
                       $.alert(rsp.data.message);
                   }
                }
            })
        }

        loadData();

        //获取微信配置
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
            var self = $(this);
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
                                    self.hide();
                                    self.parent().append(template);
                                    imgArr = data + crop;
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
        $(page).on('click', '#delete', function () {
            imgArr = '';
            $('.box').remove();
            $('.icon-camera').show();
        });

        /** 提交按钮 **/
        $(page).on('click', '#submit', function () {
            var self = $(this);
            self.prop("disabled", true);

            data.title = $('#title').val();
            data.budget = $('#budget').val();
            data.address_id = $('#fang').val();
            data.company_id = $('#provider').val();
            data.thumbnailpic = imgArr;
            if (data.title == '' || data.title == undefined) {
                $.alert('标题不能为空');
                self.prop("disabled", false);
                return;
            }
            if (data.budget == '' || data.budget == undefined) {
                $.alert('预算不能为空');
                self.prop("disabled", false);
                return;
            }
            if (data.address_id == '' || data.address_id == undefined) {
                $.alert('房产未选择');
                self.prop("disabled", false);
                return;
            }
            if (data.company_id == '' || data.company_id == undefined) {
                $.alert('装修公司未选择');
                self.prop("disabled", false);
                return;
            }

            common.ajax('POST', '/decorate/create', {
                title: data.title, address_id: data.address_id, company_id: data.company_id,
                budget: data.budget, thumbnailpic: data.thumbnailpic
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    $.alert('装修项目创建成功', '创建成功', function () {
                        window.location.href = 'decoration-manage.html';
                    })
                } else {
                    $.alert('装修项目创建失败!失败原因:' + rsp.data.message, '创建失败', function () {
                        self.prop("disabled", false);
                    })
                }
            })
        });

        var pings = env.pings;pings();
    });

    $.init();
});
