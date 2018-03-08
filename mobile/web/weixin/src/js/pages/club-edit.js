require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function() {
    'use strict';

    /**
     * id -> community_id
     * bbs_id -> bbsId
     */
    $(document).on('pageInit', '#club-edit', function(e, id, page) {
        var url = common.getRequest();
    
        var tpl = $('#tpl').html(),
            container = $('#container');

        var thumbSize = '?imageMogr2/thumbnail/!175x100r/gravity/center/crop/175x100',
            template = "<div class='tips' style='text-align: center;height: 100%;'>很抱歉,数据错误!</div>";

        var status = true,
            transArr = ['name'],
            cnArr = ['社团名称'],
            key,
            imgUrl;

        var imgCover = function (data) {
            var qnDomain = common.QiniuDamain;
            return qnDomain + data + thumbSize;
        };
        juicer.register('imgCover', imgCover);
        
        function loadData() {
            common.ajax('GET', '/community/bbs-detail',{
                id: url.bbs_id
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data);

                    container.append(html);
                } else {
                    container.append(template);
                }
            })
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

        function valid(params) {
            for(var i in params) {
                if (params[i] == '' || params[i] == undefined) {
                    key = transArr.indexOf(i);
                    if (key != -1) {
                        $.alert('很抱歉,' + cnArr[key] + '不能为空!', '验证失败', function() {
                            status = true;
                        });
                        return false;
                    }
                }
            }
            return true;
        }

        function renderPic (picUrl, _this) {
            var thumbHtml =  "<div style='position: relative' class='JThumbnail'> " +
                "<img src='" + picUrl  + thumbSize + "'> " +
                "<i class='iconfont icon-cancel JCancelThumb' style='position: absolute;left: 8.2rem;top:-.8rem;color: red;z-index: 2;'></i>" +
                "</div>";

            var parent = _this.parent();

            _this.addClass('hide');
            parent.append(thumbHtml);

            //存储到input[type=hide]
            picUrl = picUrl.replace(common.QiniuDamain, '');
            parent.next().val(picUrl);
        }

        function renderNav(text, during) {
            var template = '<nav class="bar bar-tab" id="submit"><a class="tab-item external' + ((during) ? ' cancel' : '') + '"><span class="' + ((during) ? ' font-dark' : ' font-white') + '">' + text + '</span></a></nav>';

            $('#submit').replaceWith(template);
        }
        
        function editBbs (params) {
            params['id'] = url.bbs_id;
            
            common.ajax('POST', '/community/bbs-update', {
                'data': params
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    $.alert('社群编辑成功!', '编辑成功', function () {
                        location.href = 'club-list.html?id=' + url.id;
                    })
                } else {
                    $.alert('很抱歉,社群编辑失败,请重试!失败原因:' + rsp.data.message, '编辑失败', function () {
                        status = true;

                        renderNav('提交', false);
                    })
                }
            })
        } 

        $(document).on('click', '.icon-camera', function() {
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
                                imgUrl = rsp.data.info;
                                renderPic(imgUrl, self);
                            })
                        }
                    });
                }
            });
        });

        $(document).on('click', '.JCancelThumb', function() {
            var _this = $(this),
                parent = _this.parent(),
                root = _this.parents('.item-input');

            parent.remove();
            root.find('.icon-camera').removeClass('hide');
            root.next().val('');
        });

        $('#submit').on('click', function () {
            if (!status) return false;
            status = false;

            var params = common.formToJson($('form').serialize());
            params = JSON.parse(decodeURIComponent(params));

            if (valid(params)) {
                renderNav('提交中...', true);

                editBbs(params);
            }
        });

        loadData();
        getConfig();

        var pings = env.pings;pings();
    });

    $.init();
})