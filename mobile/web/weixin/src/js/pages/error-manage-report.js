require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    /**
     * case_id && work_id 正常工作流流程
     * log_id 修改log日志内容
     */
    $(document).on("pageInit", "#error-manage-report", function (e, id, page) {
        var url = common.getRequest();

        var status = true,
            imgUrl = '',
            imgArr = [];

        var tpl = $('#tpl').html(),
            container = $('#container'),
            title = $('#title').html(),
            imgs = $('#imgs').html(),
            checkArr = [];

        common.img();

        /**
         * 添加图片
         */
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

        /**
         * 删除详情图片
         */
        $(document).on('click', '.cancel-pics', function() {
            var _this = $(this),
                parent = _this.parent('.box'),
                index = parent.index();

            parent.remove();
            imgArr.splice(index, 1);
        });

        /**
         * 提交
         */
        $('#submit').on('click', function () {
            if (!status) return false;
            status = false;

            if (url.log_id && url.log_id != undefined) {
                editLog();
            } else {
                comSubmit();
            }
        });

        function comSubmit () {
            var comment = $('#comment').val();

            // 取CheckBox值
            var checked = $('input[name=person]:checked');
            checked.each(function() {
                var _this = $(this),
                    val = $.trim(_this.val());

                checkArr.push(val);
            });

            if ($('input[name=person]').length > 0 && checkArr.length == 0) {
                $.alert('很抱歉,请勾选下级处理人', '验证失败', function () {
                    status = false;
                });
                return false;
            }

            common.ajax('POST', '/feedback/maintain-operate', {
                'case_id': url.id,
                'comment': comment,
                'img': imgArr.join(','),
                'user_id': checkArr
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    $.alert('提交处理成功!', '提交成功', function () {
                        location.href = 'error-list.html?id=' + url.work_id;
                    })
                } else {
                    $.alert('很抱歉,提交失败!失败原因:' + rsp.data.message, '提交失败', function () {
                        status = true;
                    });
                }
            })
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
         * 添加图片
         * @param imgUrl
         * @param imgNum
         * @param params
         */
        function renderPics(imgUrl) {
            var picsHtml = "<div class='col-33 box' style='position: relative;padding: .3rem 0;'>" +
                "<img src='" + imgUrl + "' style='width: 4rem;height: auto;max-height: 4rem;'>" +
                "<i class='iconfont icon-cancel cancel-pics' style='position: absolute;left: 3.4rem;top:-.5rem;color: red;z-index: 2;'></i>" +
                "</div>";

            $('#rows').append(picsHtml);

            //存储到input[type=hide]
            imgUrl = imgUrl.replace(common.QiniuDamain, '');
            imgArr.push(imgUrl);
        }

        /**
         * 获取下个人员数据
         */
        function loadData() {
            common.ajax('GET', '/feedback/next-handler', {
                'work_id': url.work_id,
                'case_id': url.id
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                    // render checkbox
                    renderCheckBox(data);
                } else {
                    $.alert('数据获取失败,请重试!', '数据错误', function () {
                        history.go(-1);
                    })
                }
            })
        }

        function renderCheckBox(params) {
            if (url.log_id && url.log_id != undefined) {

            } else {
                var html = juicer(tpl, params);

                container.append(html);
            }

            var htm = juicer(title, {'url': url});
            $('header').prepend(htm);
        }

        function loadLog() {
            common.ajax('GET', '/feedback/get-log-info', {
                'logId': url.log_id
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        tem = juicer(imgs, data);

                    $('#rows').append(tem);
                    $('textarea').val(data.comment);
                    imgArr = data.img;

                    renderCheckBox();
                } else {
                    $.alert('数据获取失败,请重试!', '数据错误', function () {
                        history.go(-1);
                    })
                }
            });
        }

        function editLog () {
            var comment = $.trim($('#comment').val());
            var imgStr = imgArr.join(',');

            common.ajax('POST', '/feedback/edit-log', {
                'log_id': url.log_id,
                'comment': comment,
                'img': imgStr
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    $.alert('处理内容编辑成功', '编辑成功', function() {
                        history.go(-1);
                    })
                } else {
                    $.alert('很抱歉,编辑处理内容失败!失败原因:' + rsp.data.message, '编辑失败', function () {
                        status = true;
                    })
                }
            })
        }

        function init () {
            if (url.log_id && url.log_id != undefined) {
                loadLog();
            } else {
                loadData();
            }
        }

        init();
        getConfig();

        var pings = env.pings;
        pings();
    });

    $.init();
});