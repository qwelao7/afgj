require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#incoming-letter", function (e, id, page) {
        var tpl = $('#tpl').html(),
            chat = $('#chat').html(),
            container = $('#container');

        //默认头像
        var head = env.defaultHeadImg;

        /* http */
        var http = "http://" + location.host + '/site';
        var user = localStorage.getItem('user');

        //时间戳
        var timestamp = new Date().getTime();

        /* 自定义模板 */
        var bbsImg = function(data) {
            var qiniu = common.QiniuDamain;
            if(data == null || data == '') {
                data = env.defaultCommunityImg;
            }
            return qiniu + data;
        };
        var format = function (data) {
            return common.formatTime(data);
        };
        var trans = function (data) {
            if (data.indexOf('http://') != -1) data = '[图片]';
            if (data.length > 20) data = data.slice(0, 20) + '...';
            return data;
        };
        var img = function (data) {
            if (data == null) return data = head;
            else return data;
        };
        juicer.register('format', format);
        juicer.register('trans', trans);
        juicer.register('img', img);
        juicer.register('bbsImg', bbsImg);

        /* ajax */
        function loadData() {
            common.ajax('GET', '/message/index', {}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    var html = juicer(tpl, data);
                    container.prepend(html);

                    if (user == null) {
                        localStorage.setItem('user', data.user.user_id);
                        user = localStorage.getItem('user');
                        im(user);
                    }

                } else {
                    $.alert('获取信息失败,请重试!');
                }
            });
        };

        /* 云旺 */
        function im(uid) {
            window.__WSDK__POSTMESSAGE__DEBUG__ = true;
        
            var sdk = new WSDK();
        
            /** 请求次数 **/
            var limit = 1;
            var callback = function () {
                sdk.Base.login({
                    uid: uid,
                    appkey: env.appkey,
                    credential: env.password,
                    timeout: 4000,
                    success: function (data) {
                        getMessage();
                    },
                    error: function (error) {
                        if(limit < 3) {
                            callback();
                            limit++;
                        }else {
                            console.log(error);
                        }
                    }
                });
            };
        
            var getMessage = function () {
                sdk.Base.getRecentContact({
                    count: 30,
                    success: function (rsp) {
                        var data = rsp.data;
                        $.each(data.cnts, function (index, item) {
                            if(item.nickname == null) {
                                if (item.uid == env.homeMember) {
                                    item.nickname = '一键报障';
                                } else {
                                    item.nickname = '客户服务';
                                }
                            }
                        });
        
                        var result = juicer(chat, data);
                        container.append(result);
                    },
                    error: function (error) {
                        console.log('获取最近联系人及最后一条消息内容失败', error);
                    }
                });
            };
            callback();
        }

        //有本地存储走本地存储
        if (user == null) {
            loadData();
        } else {
            im(user);
            loadData();
        }

        /* 跳转楼盘资讯页面 */
        $(page).on('click', '#news', function () {
            window.location.href = 'news-loupan.html';
        });

        /* 跳转对话消息页面 */
        $(page).on('click', '.chat', function () {
            var userId = $(this).data('id'),
                nick = $(this).data('nick');
            if (userId.indexOf('cntaobao') > -1) {
                userId = userId.split('cntaobao');
                //设置本地跳转存储
                common.saveStorage(nick);
                window.location.href = 'custom-service.html?id=' + userId[1] + '&time=' + timestamp;
            } else {
                userId = userId.slice(8);
                window.location.href = 'neighbor-chat.html?id=' + userId + '&time=' + timestamp;
            }
        });

        /* 跳转社群新鲜事页面 */
        $(page).on('click', '#msg', function () {
            var id = $(this).data('id');
            window.location.href = 'bbs-list.html?id=' + id;
        });
        
        //返回
        $('#back').on('click', function() {
            location.href = 'square-index.html';
        })
        
        var pings = env.pings;pings();
    });

    $.init();
});
