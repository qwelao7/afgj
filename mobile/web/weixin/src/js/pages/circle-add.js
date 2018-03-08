require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#circle-add", function (e, id, page) {
        var url = common.getRequest();

        var names = [],
            types = [];

        var arr = ['bbs_name', 'join_way'],
            chineseArr = ['社团名称', '加入方式'],
            status = true,
            key;

        var picSize = '?imageMogr2/thumbnail/!80x80r/gravity/center/crop/80x80';

        $('.icon-camera').on('click',function () {
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
                                    var data = rsp.data.info;
                                    renderPic(data, self, parent);
                                } else {
                                    $.alert('图片上传失败,请重试!');
                                }
                            })
                        }
                    });
                }
            });
        });

        $('.icon-cancel').live('click', function () {
            var _self = $(this),
                parent = _self.parents('.box'),
                parents = _self.parents('.item-input'),
                camera = parent.prev();

            parent.remove();
            camera.removeClass('hide');

            //删除input赋值
            parents.find('input').val('');
        });

        $('#back').live('click', function () {
            location.href = 'address-list.html?id=' + url.id;
        });
        
        $('#submit').on('click', function() {
            if (!status) return false;
            status = false;

            var params = common.formToJson($('form').serialize());
            params = JSON.parse(decodeURIComponent(params));

            var result = valid(params);

            if (result) {
                params.loupan_id = url.id;
                common.ajax('POST', '/community/create-community-bbs', {'data': params}, true, function(rsp) {
                    if (rsp.data.code == 0) {
                        $.alert('创建成功', '社团创建成功!', function () {
                            location.href = 'circle-success.html?id=' + url.id;
                        })
                    } else {
                        $.alert('创建失败', '很抱歉,社团新建失败!失败原因:' + rsp.data.message, function() {
                            status = true;
                        })
                    }
                })
            }
        });

        function valid(params) {
            //验证是否为空
            for (var i in params) {
                key = arr.indexOf(i);
                if (key != -1) {
                    if (params[i] == '' || params[i] == undefined) {
                        $.alert('很抱歉,' + chineseArr[key] + '不能为空!', '验证失败', function () {
                            status = true;
                        });
                        return false;
                    }
                }
            }
            return true
        }

        function renderPic(data, self, parent) {
            var template = "<div class='box' style='overflow: visible;position: relative'> " +
                "<img src='" + data + "' style='width: 7rem;height: 7rem'> " +
                "<i class='iconfont icon-cancel' style='position: absolute;left: 6.4rem;top:-.8rem;color: red;z-index: 2;;'></i>" +
                "</div>";

            self.addClass('hide');
            parent.append(template);

            //input赋值
            data = data.replace(common.QiniuDamain, '');
            parent.find('input').val(data);
        }

        function loadData() {
            common.ajax('GET', '/community/create-community-bbs', {}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    names = rsp.data.info.name;
                    types = rsp.data.info.type;

                    init();
                    picker();
                }
            })
        }
        
        function picker() {
            $('#picker').picker({
                toolbarTemplate: '<header class="bar bar-nav">\
                                <button class="button button-link pull-right close-picker">确定</button>\
                                <h1 class="title">选择加入方式</h1>\
                                </header>',
                cols: [
                    {
                        textAlign: 'center',
                        values: names
                    }
                ],
                onClose: function() {
                }
            });
        }
        
        function init() {
            $('#picker').val(names[0]);
            $('input[name=join_way]').val(types[0]);
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

        loadData();
        getConfig();
        var pings = env.pings;pings();
    });

    $.init();
});
