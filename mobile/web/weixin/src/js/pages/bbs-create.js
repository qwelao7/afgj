require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#bbs-create", function (e, id, page) {
        var url = common.getRequest();
        common.img();
        
        var tpl = $('#tpl').html(),
            vote = $('#vote').html(),
            container = $('#container'),
            status = true,
            upload = {},
            num = 1,
            imgs = [];

        upload.attachment_type = 0;
        upload.attachment_content = '';
        upload.bbs_id = url.id;

        var picSize = '?imageMogr2/thumbnail/!80x80r/gravity/center/crop/80x80';

        function loadData() {
            var bbsContent = window.localStorage.getItem('bbsContent');
            $('textarea').text(bbsContent);

            if(url.a_id) {
                $('.create-icon').toggleClass('hide');
                common.ajax('GET', '/forum/create-attach', {type: 2, id: url.a_id}, true, function(rsp) {
                    if(rsp.data.code == 0) {
                        var data = rsp.data.info,
                            html = juicer(tpl, data);
                        container.append(html);
                    }else {
                        var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,没有找到创建的活动!</h3>";
                        container.append(template);
                        $.alert('很抱歉,没有找到创建的活动!','温馨提示', function() {
                            window.location.href = 'bbs-list.html?id=' + url.id;
                        })
                    }
                })
            }else if(url.v_id) {
                $('.create-icon').toggleClass('hide');
                common.ajax('GET', '/forum/create-attach', {type: 1, id: url.v_id}, true, function(rsp) {
                    if(rsp.data.code == 0) {
                        var data = rsp.data.info,
                            html = juicer(vote, data);
                        container.append(html);
                    }else {
                        var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,没有找到创建的投票!</h3>";
                        container.append(template);
                        $.alert('很抱歉,没有找到创建的投票!','温馨提示', function() {
                            window.location.href = 'bbs-list.html?id=' + url.id;
                        })
                    }
                })
            }
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

        $(document).on('click', '#uploadImgs', function() {
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
                                if(num == 1) {
                                    $('.create-icon').not('#uploadImgs').toggleClass('hide');
                                }
                                if(rsp.data.code == 0) {
                                var self = $(this);
                                var data = rsp.data.info,
                                    template = "<div class='col-33' style='position: relative;padding: .3rem 0;'>" +
                                        "<img src='" + data +  picSize +"'>" +
                                        "<i class='iconfont icon-cancel' style='position: absolute;left: 3.4rem;top:-.5rem;color: red;z-index: 2;'></i>" +
                                        "</div>";
                                $('.row').append(template);
                                data = data.replace(common.QiniuDamain, '');
                                imgs.push(data);
                                num++;
                                }else {
                                    $.alert('图片上传失败,请重试!', '上传失败');
                                }
                            })
                        }
                    });
                }
            });
        });

        $(document).on('click', '.delete-attach', function() {
            var self = $(this),
                parents = self.parents('.news-consult');
            parents.remove();
            if(url.a_id) {
                $('.create-icon').toggleClass('hide');
            }else if(url.v_id){
                $('.create-icon').toggleClass('hide');
            }
            upload.attachment_type = 0;
            upload.attachment_content = '';
        });

        $(document).on('click', '.icon-cancel', function() {
            var self = $(this),
                parent = self.parent(),
                index = parent.index();
            parent.remove();
            imgs.splice(index,1);
            if(imgs.length == 0) {
                $('.create-icon').not('#uploadImgs').toggleClass('hide');
            }
        });

        $(document).on('click', '#submit', function() {
            if(!status) return;
            status = false;

            if($('.col-33').length > 0) {
                upload.attachment_type = 1;
                upload.attachment_content = imgs.join(',');
            }

            if($('.news-consult').length > 0) {
                if(url.a_id) {
                    upload.attachment_type = 5;
                    upload.attachment_content = url.a_id;
                }
                if(url.v_id) {
                    upload.attachment_type = 4;
                    upload.attachment_content = url.v_id;
                }
            }

            upload.content = $('textarea').val();
            upload.content = $.trim(upload.content);

            if(upload.content == '') {
                $.alert('请输入新鲜事内容!', '温馨提示');
                status = true;
                return;
            }

            common.ajax('POST', '/forum/create', {data: upload}, true, function(rsp) {
                if(rsp.data.code == 0) {
                    localStorage.removeItem('bbsContent');
                    window.location.href = 'bbs-list.html?id=' + url.id;
                }else {
                    $.alert('很抱歉,新鲜事创建失败,请重试!','创建失败');
                    status = true;
                }
            })
        });

        $(document).on('click', '#uploadAct', function() {
            var val = $('textarea').val();
            val = $.trim(val);
            localStorage.setItem('bbsContent', val);
            window.location.href = 'bbs-event-add.html?id=' + url.id;
        });

        $(document).on('click', '#uploadVote', function() {
            var val = $('textarea').val();
            val = $.trim(val);
            localStorage.setItem('bbsContent', val);
            window.location.href = 'bbs-vote-add.html?id=' + url.id;
        });

        $(document).on('click', '#back', function() {
            var val = $('textarea').val();
            val = $.trim(val);
            localStorage.setItem('bbsContent', val);
            window.location.href = 'bbs-list.html?id=' + url.id;
        });

        $(document).ready(function() {
            loadData();
            getConfig();
        });

        var pings = env.pings;pings();
    });

    $.init();
});

