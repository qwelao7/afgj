require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#equip-add-img", function (e, id, page) {
        var url = common.getRequest();

        //自定义变量
        var data = {},
            info = {},
            imgArr = '',
            community,
            communityname,
            communityId,
            href = window.location.href,
            communitys = $('#communitys').html(),
            communityList = $('#communityList');
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

        /**
         * 加载数据
         */
        function loadData() {
            common.ajax('GET', '/hcho/auth-address', {}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    communityname = rsp.data.info.name;
                    communityId = rsp.data.info.id;
                    var index;

                    if (url.id == 0) {
                        index = 0;
                    } else {
                        index = communityId.indexOf(url.id)
                    }
                    info.name = communityname[index];
                    //community默认值
                    community = communityId[index];
                    var htm = juicer(communitys, info);
                    communityList.prepend(htm);
                    pick();
                    getConfig();
                } else if (rsp.data.code == 110) {
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
                } else {
                    $.alert(rsp.data.message);
                }
            });
        }

        /**
         * 获取小区列表
         */
        function pick() {
            $("#picker2").picker({
                toolbarTemplate: '<header class="bar bar-nav">\
                                   <button class="button button-link pull-right close-picker font-white">确定</button>\
                                   <h1 class="title font-white">请选择小区</h1>\
                                   </header>',
                cols: [
                    {
                        textAlign: 'center',
                        values: communityname
                    }
                ],
                onClose: function () {
                    var str = $('#picker2').val(),
                        index = communityname.indexOf(str);

                    community = communityId[index];
                }
            });
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
                                        template = "<div class='col-33 box' style='position: relative;padding: .3rem 0;'>" +
                                            "<img src='" + data + "' style='width: 4rem;height: 4rem'>" +
                                            "<i class='iconfont icon-cancel delete' style='position: absolute;left: 3.4rem;top:-.5rem;color: red;z-index: 2;'></i>" +
                                            "</div>";
                                    self.hide();
                                    self.parent().append(template);
                                    imgArr = data.replace(common.QiniuDamain, '');
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
        $(page).on('click', '.delete', function () {
            imgArr = '';
            $('.box').remove();
            $('.icon-camera').show();
        });

        /**
         * 跳转手动添加页面
         */
        $(page).on('click', '#add-self', function () {
            window.location.href = 'equip-add.html?id=' + url.id;
        });

        /**
         * 返回
         */
        $(page).on('click', '#back', function () {
            window.location.href = 'equip-list.html?id=' + url.id;
        });

        /**
         * 提交信息
         */
        $(page).on('click', '#submit', function (event) {
            event.preventDefault();
            var self = $(this),
                params = {};
            params.arr = [];
            params.err = '';
            self.prop("disabled", true);

            data.address_id = community;
            data.bill_pics = imgArr;

            tips(data.address_id, '请填写你的房产', self, params);
            tips(data.bill_pics, '请上传图片', self, params);

            if (params.arr.indexOf('false') == -1) {
                common.ajax('POST', '/facilities/create', {'data': data}, true, function (rsp) {
                    if (rsp.data.code == 0) {
                        window.location.href = 'equip-add-img-success.html?id=' + url.id;
                    } else {
                        $.alert('您的提交失败,请重试', function () {
                            self.prop("disabled", false);
                        });
                    }
                })
            } else {
                params.arr = [];
                $.alert(params.err, function () {
                    self.prop("disabled", false);
                });
            }
        });

        function tips(selecter, tips, self, params) {
            if (selecter == "" || selecter == undefined) {
                params.arr.push('false');
                params.err = tips;
                return params;
            }
        }

        loadData();
        var pings = env.pings;pings();
    });

    $.init();
});
