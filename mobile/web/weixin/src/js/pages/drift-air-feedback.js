require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#drift-air-feedback", function (e, id, page) {
        var point = [],
            curPoint = 1,
            address_id = 0,
            pics = [],
            content = '',
            imgUrl = '',
            picsStr = '',
            status = true;

        var address = [],
            ids = [];

        var imgsSize = '?imageMogr2/thumbnail/!80x80r/gravity/center/crop/80x80',
            template = '';

        var transArr = ['name', 'number'],
            chineseArr = ['位置', '结果'],
            key;

        //添加选项
        $('#add_options').on('click', function () {
            var self = $(this),
                brother = self.next(),
                parent = self.parents('.option');

            var result = validate();
            if (result) {
                curPoint++;
                render();
                parent.before(template);
                brother.removeClass('hide');
                point.push({name: '', number: ''});
            }
        });

        //删除选项
        $('#delete_options').on('click', function () {
            var self = $(this);

            $('.air_option').eq(curPoint - 1).remove();
            curPoint--;
            point.pop();
            if (curPoint == 1) self.addClass('hide');
        });

        //赋值
        $('.air_result').live('blur', function () {
            var self = $(this),
                type = self.data('type'),
                value = $.trim(self.val());

            point[curPoint - 1][type] = value;
        });


        /**
         * 返回
         */
        $('#back').on('click', function () {
            location.href = 'drift-air-index.html';
        });

        /**
         * 添加图片
         */
        $('.icon-camera').live('click', function () {
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

        /**
         * 渲染图片
         */
        function renderPics(imgUrl) {
            var picsHtml = "<div class='col-33' style='position: relative;padding: .3rem 0;'>" +
                "<img src='" + imgUrl + imgsSize + "'>" +
                "<i class='iconfont icon-cancel cancel-pics' style='position: absolute;left: 3.4rem;top:-.5rem;color: red;z-index: 2;'></i>" +
                "</div>";

            $('#imgs-row').append(picsHtml);
            imgUrl = imgUrl.replace(common.QiniuDamain, '');
            pics.push(imgUrl);
        }

        /**
         * 删除详情图片
         */
        $('.cancel-pics').live('click', function () {
            var _this = $(this),
                parent = _this.parent(),
                index = parent.index();

            parent.remove();
            pics.splice(index, 1);
        });

        /****
         * 提交
         */
        $('#submit').live('click', function () {
            if (!status) return false;
            status = false;

            var error = [];

            if (address_id == 0) {
                $.alert('很抱歉,' + '检测房产不能为空!', '验证失败', function () {
                    status = true;
                });
                return false;
            }

            var result = validateEmpty(error);

            if (result.indexOf(false) == -1) {
                picsStr = (pics.length == 0) ? '' : pics.join(',');
                content = $.trim($('#content').val());

                common.ajax('POST', '/hcho/feedback',
                    {
                        'point': point,
                        'address_id': address_id,
                        'content': content,
                        'pic': picsStr
                    }, true, function (rsp) {
                        if (rsp.data.code == 0) {
                            location.href = 'drift-air-result.html?id=' + rsp.data.info;
                        } else {
                            $.alert('很抱歉,反馈失败!失败原因:' + rsp.data.message, '反馈失败', function () {
                                status = true;
                            })
                        }
                    })
            }
        });

        /**
         * input 输入浮点数
         */
        $(document).on('keyup', 'input[type=number]', function() {
            var _this = $(this),
                reg = _this.val().match(/\d+\.?\d{0,5}/),
                txt = '';
            if (reg != null)
            {
                txt = reg[0];
            }
            _this.val(txt);
        });


        /**
         * point 验证是否为空
         */
        function validateEmpty(error) {
            point.forEach(function (item, index) {
                for (var i in item) {
                    if (item[i] == '') {
                        key = transArr.indexOf(i);
                        $.alert('很抱歉,' + '检测点' + (index + 1) + chineseArr[key] + '不能为空!', '验证失败', function () {
                            status = true;
                        });
                        error.push(false);
                        return false;
                    }
                }
            });
            return error;
        }

        /***
         * 验证是否不全
         */
        function validate() {
            var cur = point[curPoint - 1];
            if (cur.name == '') {
                $.alert('请填写检测点' + curPoint + '的位置信息!', '校验失败');
                status = true;
                return false;
            }

            if (cur.number == '') {
                $.alert('请填写检测点' + curPoint + '的检测结果!', '检验失败');
                status = true;
                return false;
            }
            return true;
        }

        /**
         * 渲染template
         */
        function render() {
            template = '<li class="air_option" data-index="' + curPoint + '">' +
                '<div class="normal-list lr-padding has-border-bottom row" style="margin-bottom:0">' +
                '<div class="col-33 h3" style="color: #888;margin-left: 0">检测点' + curPoint + '</div>' +
                '<div class="col-66">' +
                '<input type="text" class="air_result" data-type="name" placeholder="例:客厅" style="font-size:.7rem;width: 100%;display: inline-block">' +
                '</div>' +
                '</div>' +
                '<div class="normal-list lr-padding has-border-bottom row" style="margin-bottom:0">' +
                '<div class="col-33 h3" style="color: #888;margin-left: 0">结果(ppm)</div>' +
                '<div class="col-66">' +
                '<input type="number" class="air_result" data-type="number" placeholder="例:0.02" style="font-size:.7rem;width: 100%;display: inline-block">' +
                '</div>' +
                '</div>' +
                '</li>';
        }

        /**
         * 获取微信配置
         */
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

        /**
         * 获取用户房产信息
         */
        function loadData() {
            common.ajax('GET', '/hcho/auth-address', {}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                    address = data.name;
                    ids = data.id;
                    renderPicker(address);
                } else {
                    $.modal({
                        text: '很抱歉,您还未认证房产!',
                        buttons: [
                            {
                                text: '知道了'
                            },
                            {
                                text: '前往认证',
                                bold: true,
                                onClick: function () {
                                    location.href = 'estate-manage.html';
                                }
                            }
                        ]
                    });
                }
            })
        }

        /**
         * 渲染picker
         */
        function renderPicker(data) {
            $("#address-picker").picker({
                toolbarTemplate: '<header class="bar bar-nav">\
                    <button class="button button-link pull-right close-picker">确定</button>\
                    <h1 class="title">选择房产</h1>\
                    </header>',
                cols: [
                    {
                        textAlign: 'center',
                        values: data
                    }
                ],
                onClose: function () {
                    var val = $('#address-picker').val(),
                        index = address.indexOf(val);

                    address_id = ids[index];
                }
            });
        }

        loadData();
        getConfig();

        var pings = env.pings;
        pings();
        point.push({name: '', number: ''});
    });

    $.init();
});
