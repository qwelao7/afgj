require('../../css/style.css');
require('../../css/index.css');
require('../../css/fonts/iconfont.js');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#drift-lamp-feedback", function (e, id, page) {
        var item    = $('#tpl').html(),
            buttons = $('#buttons');

        var point       = [],
            curPoint    = 1,
            address_id  = 0,
            pics        = [],
            content     = '',
            imgUrl      = '',
            picsStr     = '',
            status      = true;

        var address = [],
            ids     = [];

        var imgsSize = '?imageMogr2/thumbnail/!80x80r/gravity/center/crop/80x80',
            template = '';

        var transArr    = ['name', 'cct', 'cqs', 'lux'],
            chineseArr  = ['品牌型号', '色温CCT', '色光品质CQS', '光照度lux'],
            key;

        /**
         * 新增台灯
         */
        $('.add-option').on('click', function () {
            var self    = $(this),
                brother = self.next();

            var result  = validate();
            if (result) {
                curPoint++;
                render();
                brother.removeClass('hide');
                point.push({name: '', cct: '', cqs: '', lux: ''});
            }
        });

        /**
         * 删除台灯
         */
        $('.del-option').on('click', function () {
            var self    = $(this),
                brother = self.prev();

            $('.lamp-option').eq(curPoint - 1).remove();
            curPoint--;
            point.pop();

            if (curPoint == 1) self.addClass('hide');
        });

        //赋值
        $('.lamp-item').live('blur', function () {
           var self = $(this),
               type = self.data('type'),
               val  = $.trim(self.val());

            point[curPoint - 1][type] = val;
        });

        /**
         * 返回
         */
        $('#back').on('click', function () {
            location.href = 'drift-lamp-index.html';
        });

        /**
         * 添加图片
         */
        $('.icon-camera').live('click', function() {
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
         * 删除详情图片
         */
        $('.cancel-pics').live('click', function() {
            var _this = $(this),
                parent = _this.parent(),
                index = parent.index();

            parent.remove();
            pics.splice(index, 1);
        });

        /**
         * 提交
         */
        $('#submit').live('click', function () {
            if (!status) return false;
            status = false;

            var error = [];

            if (address_id == 0) {
                $.alert('很抱歉,' + '检测房产不能为空!', '验证失败', function() {
                    status = true;
                });
                return false;
            }

            var result = validateEmpty(error);

            if (result.indexOf(false) == -1) {
                picsStr = (pics.length == 0) ? '' : pics.join(',');
                content = $.trim($('#content').val());

                //ajax
                common.ajax('POST', '/light/feedback',
                    {
                        'point':        point,
                        'address_id':   address_id,
                        'content':      content,
                        'pic':          picsStr
                    }, true, function (rsp) {
                        if (rsp.data.code == 0) {
                            $.alert('已帮您生成检测结果页面，您可点击右上角...按钮分享到朋友圈，让更多朋友重视家庭灯光的质量。', '反馈成功', function () {
                                location.href = 'drift-lamp-result.html?id=' + rsp.data.info;
                            })
                        } else {
                            $.alert('很抱歉,反馈失败!失败原因:' + rsp.data.message, '反馈失败', function () {
                                status = true;
                            })
                        }
                    })
            }
        });

        /**
         * 监听input[type=number]输入
         */
        $(document).on('keyup', 'input[type=number]', function() {
            var _this = $(this),
                reg = _this.val().match(/\d+/),
                txt = '';
            if (reg != null)
            {
                txt = reg[0];
            }
            _this.val(txt);
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
                    ids     = data.id;
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
                    var val     = $('#address-picker').val(),
                        index   = address.indexOf(val);

                    address_id = ids[index];
                }
            });
        }

        /**
         * 渲染template
         */
        function render() {
            var html =  juicer(item, {cur: curPoint});
            buttons.before(html);
        }

        /**
         * 检验台灯信息
         */
        function validate() {
            var cur = point[curPoint - 1];
            for(var i in cur) {
                if (cur[i] == '') {
                    key = transArr.indexOf(i);
                    $.alert('很抱歉,' + '台灯' + curPoint + chineseArr[key] + '不能为空!', '验证失败', function() {
                        status = true;
                    });
                    return false;
                }
            }
            return true;
        }

        /**
         * 校验全部信息
         */
        function validateEmpty (error) {
            point.forEach(function (item, index) {
                for(var i in item) {
                    if (item[i] == '') {
                        key = transArr.indexOf(i);
                        $.alert('很抱歉,' + '台灯' + (index + 1) + chineseArr[key] + '不能为空!', '验证失败', function() {
                            status = true;
                        });
                        error.push(false);
                        return false;
                    }
                }
            });
            return error;
        }

        loadData();
        getConfig();

        var pings = env.pings;pings();
        point.push({name: '', cct: '', cqs: '', lux: ''});
    });

    $.init();
});
