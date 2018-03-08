require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#personal-info", function (e, id, page) {
        /** 参数 **/
        var tpl = $('#tpl').html(),
            popup = $('#popup').html(),
            container = $('#container'),
            all = $('.popup-all');

        //配置参数
        var href = window.location.href,
            http = "http://"+ location.host + '/site',
            data = {
                code: 0
            };

        /** 模板自定义函数 **/
        var trans = function(data) {
            return data = (data=='1')?'男':'女';
        };
        var crop = function(data) {
            if(data.indexOf(common.QiniuDamain) > -1) {
                data += '?imageMogr2/auto-orient/thumbnail/90000@/gravity/center/crop/200x200';
            }
            return data;
        };
        juicer.register('trans', trans);
        juicer.register('crop', crop);
        
        common.ajax('GET', '/user/info', {}, false, function(rsp) {
            if(rsp.data.code == 0) {
                var platform = common.isWeixin();

                var data = rsp.data.info;
                data.platform = platform;
                var html = juicer(tpl, data);
                container.append(html);
            } else {
                $.alert('获取信息失败,请重试!');
            }
        });

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
            });
        }
        getConfig();

        /** 点击上传图片 **/
        $(page).on('click', '#headImg', function () {
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
                            common.ajax('GET', '/wechat/upload', {mediaId: serverId}, true, function(rsp) {
                                if (rsp.data.code == 0) {
                                    var data = rsp.data.info,
                                        template = "<img src ='" + data + '?imageMogr2/auto-orient/thumbnail/90000@/gravity/center/crop/200x200' + "'>";
                                    //更新用户头像
                                    common.ajax('POST', '/user/update', {headimgurl: data + '?imageMogr2/auto-orient/thumbnail/90000@/gravity/center/crop/200x200'}, true, function(rsp) {
                                        if(rsp.data.code == 0) {
                                            //替换图片
                                            self.replaceWith(template);
                                        }else {
                                            $.alert('头像更新失败');
                                        }
                                    });
                                } else {
                                    $.alert('头像上传失败,请重试!');
                                }
                            })
                        }
                    });
                }
            })
        });


        /** 退出登录 **/
        $(page).on('click', '#exit', function() {
            common.ajax('GET', '/user/exit',{},true, function(rsp) {
                if(rsp.data.code == 0) {
                    window.location.reload(true);
                }else {
                    $.alert('很抱歉,退出失败');
                }
            })
        });

        /** 跳转个人标签页 **/
        $(page).on('click', '#skills', function() {
            window.location.href = 'personal-label.html';
        });

        //更新用户信息
        function update(title, type, scope, param, self, val) {
            data.title = title;
            data.type = type;
            data.val = val;
            var modal = juicer(popup, data),
                id = '#update' + String(type) + data.code;
            all.empty().append(modal);
            $.popup('.popup-all');
            data.code++;

            $(page).on('click', id, function() {
                var params = {};
                if(type ==3) {
                    var str = $("input[type='radio']:checked").val();
                }else {
                    var str = $(scope).val();
                }

                if(str == undefined) {
                    $.alert('您未输入内容,请重试!');return;
                }
                if(type == 4) {
                    if(!common.check(str, 2)) {
                        $.alert('您输入的手机号码格式出错,请重试!');return;
                    }
                }
                if(type == 2) {
                    if(!common.check(str, 1)) {
                        $.alert('您输入的登录名格式错误,请重试!');return;
                    }
                }
                params[param] = str;
                common.ajax('POST', '/user/update', params, true, function(rsp) {
                    if(rsp.data.code == 0) {
                        $.closeModal('.popup-all');
                        if(type == 3) {
                            str = (str == 1)?'男': '女';
                        }
                        $(self).html(str);
                        setTimeout(function() {
                            all.empty();
                        },500);
                    }else {
                        $.alert(rsp.data.message + ', 请重新输入');
                    }
                });
            });
        }

        /** 弹出层 **/
        $(page).on('click', '.open-nick', function() {
            var val = $(this).find('h3').text();
            update('昵称', 1, '.up-nick', 'nickname', '.val-nick', val);
        });
        $(page).on('click', '.open-sex', function() {
            var val = $(this).find('h3').text();
            update('性别', 3, '.up-sex', 'sex', '.val-sex',val);
        });

        /** 返回 **/
        $(document).on('click','#back',function() {
            window.location.href = common.ectouchUrl + '&c=user&a=index&params';
        });

        /** 关闭弹窗 **/
        $(page).on('click', '#close', function() {
            $.closeModal('.popup-all');
            setTimeout(function() {
                all.empty();
            },500);
        });

        /** 跳转修改密码页面 **/
        $(page).on('click', '.open-pwd', function() {
            window.location.href = common.ectouchUrl + '&c=user&a=edit_password';
        })

        /**
         * 跳转手机绑定页面
         */
        $(page).on('click', '.open-mobile', function() {
            window.location.href = 'login.html';
        })

        var pings = env.pings;pings();
    });

    $.init();
});
