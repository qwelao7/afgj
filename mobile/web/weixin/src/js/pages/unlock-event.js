require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#event", function (e, id, page) {
        var status = true,
            content = $('#content'),
            tpl = $('#tpl').html();


        common.img();

        function loadData() {
            getConfig();
            common.ajax('GET', '/unlock/index', {}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var html = juicer(tpl, rsp.data);

                    content.append(html);
                } else {
                    $.confirm('很抱歉,数据请求失败,请重试!', '错误提示',
                        function () {
                            window.location.reload();
                        },
                        function () {
                            window.history.go(-1);
                        }
                    )
                }
            });
        }

        /**  获取微信配置 **/
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
                            'onMenuShareTimeline',
                            'onMenuShareAppMessage',
                        ]
                    });
                    wx.ready(function () {
                        var title = '我为小区添便利，借用物品大解锁--回来啦社区';
                        var desc = '回来啦社区';
                        var imgUrl = common.QiniuDamain + 'icon1001.jpg';
                        var link = window.location.href;
                        wx.onMenuShareAppMessage({
                            title: title, // 分享标题
                            desc: desc, // 分享描述
                            link: link, // 分享链接
                            imgUrl: imgUrl, // 分享图标
                            type: '', // 分享类型,music、video或link，不填默认为link
                            dataUrl: '', // 如果type是music或video，则要提供数据链接，默认为空
                        });
                        wx.onMenuShareTimeline({
                            title: title, // 分享标题
                            link: link, // 分享链接
                            imgUrl: imgUrl, // 分享图标
                        });
                    });
                } else {
                    $.alert('获取配置信息失败!');
                }
            })
        }

        /**
         * 我要借用
         */
        $(document).on('click', '.event-btn-borrow', function () {
            var self = $(this),
                index = self.parent().data('id');

            if (!status) return;
            status = false;

            common.ajax('GET', '/unlock/borrow', {'id': index}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var final = rsp.data.info;
                    window.location.href = 'borrow-detail.html?id=' + final.sharing_id;
                } else if (rsp.data.code == 101 || rsp.data.code == 102) {
                    var data = rsp.data.info;
                    $.modal({
                        title: '借用失败',
                        text: data,
                        buttons: [
                            {
                                text: '知道了',
                                onClick: function () {
                                    status = true;
                                }
                            },
                            {
                                text: '马上添加',
                                bold: true,
                                onClick: function () {
                                    window.location.href = 'estate-manage.html';
                                }
                            }
                        ]
                    });
                } else {
                    $.alert('很抱歉,借用失败,请重试!', function () {
                        status = true;
                    })
                }
            })
        });

        /**
         * 我要解锁
         */
        $(document).on('click', '.event-btn-unlock', function () {
            var self = $(this),
                good = self.parent().data('good'),
                id = self.parent().data('id'),
                index = self.parent().data('index');

            if (!status) return;
            status = false;

            common.ajax('POST', '/unlock/unlock', {'id': id, 'community': ''}, true, function (rsp) {
                switch (rsp.data.code) {
                    case 0:
                        var res = rsp.data.info;
                        $('.event-detail').eq(index)[0].innerHTML = res.now + '/' + res.total;
                        self.replaceWith('<div class="event-btn event-btn-receive"></div>');
                        status = true;
                        break;
                    case 100:
                        var data = rsp.data.info,
                            rest = data.total - data.now;
                        $.alert('恭喜您解锁"' + good + '"成功,还需要' + rest + '把钥匙就可以完成任务啦。多一人参与,少一天等待。现在就分享该页面到朋友圈,邀请小区的邻居们一起解锁吧。', '解锁成功', function () {
                            $('.event-detail').eq(index)[0].innerHTML = data.now + '/' + data.total;
                            status = true;
                        });
                        break;
                    case 101:
                        $.alert('您今天的钥匙已经用掉了哦,欢迎明天继续来解锁。多一人参与,少一天等待。现在就分享该页面到朋友圈,邀请小区的邻居们一起解锁吧。', '解锁失败', function () {
                            status = true;
                        });
                        break;
                    case 102:
                        $.alert('亲爱的业主朋友,感谢您对本活动的关注。您所在的小区暂未开通本活动,开通时我们会通过微信公众号通知您。', '解锁失败', function () {
                            status = true;
                        });
                        break;
                    case 103:
                        $.modal({
                            title: '解锁失败',
                            text: '亲爱的用户,本活动只有认证业主方能参加,请您先添加并认证房产,谢谢!',
                            buttons: [
                                {
                                    text: '知道了',
                                    onClick: function () {
                                        status = true;
                                    }
                                },
                                {
                                    text: '马上认证',
                                    bold: true,
                                    onClick: function () {
                                        window.location.href = 'estate-manage.html';
                                    }
                                }
                            ]
                        });
                        break;
                    default:
                        $.alert('很抱歉,解锁失败,请重试!', function () {
                            status = true;
                        })
                }
            })
        });

        /**
         * 我要领取
         */
        $(document).on('click', '.event-btn-receive', function () {
            var self = $(this),
                index = self.parent().data('id');

            if (!status) return;
            status = false;

            common.ajax('POST', '/unlock/claim', {'id': index}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    window.location.href = 'receive-ware.html?id=' + index;
                } else if (rsp.data.code == 102 || rsp.data.code == 101) {
                    var data = rsp.data.info;
                    $.modal({
                        title: '领取失败',
                        text: data,
                        buttons: [
                            {
                                text: '知道了',
                                onClick: function () {
                                    status = true;
                                }
                            },
                            {
                                text: '马上认证',
                                bold: true,
                                onClick: function () {
                                    window.location.href = 'estate-manage.html';
                                }
                            }
                        ]
                    });
                } else {
                    $.alert('很抱歉,领取失败,请重试!', function () {
                        status = true;
                    })
                }
            })
        });

        /**
         * 提交申请
         */
        $(document).on('click', '#submit', function () {
            var str = $('#request-add').val();

            $.trim(str);
            if (str == '' || str == undefined) {
                $.alert('很抱歉,提交内容不能为空!');
                return;
            }

            if (!status) return;
            status = false;

            common.ajax('POST', '/unlock/suggest', {'data': str, 'type': 2}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                    $.alert('恭喜您提交成功,' + str + '已被提交' + data + '次。您可分享该页面到朋友圈,邀请更多邻居来提交申请,以便尽早开通。', '提交成功', function () {
                        status = true;
                        $('#request-add').val('');
                    });
                } else {
                    $.alert('很抱歉,提交失败,请重试!', function () {
                        status = false;
                        $('#request-add').val('');
                    });
                }
            })
        });

        /**
         * 提交借用需求
         */
        $(document).on('click', '#more', function () {
            var str = $('#request-more').val();

            $.trim(str);
            if (str == '' || str == undefined) {
                $.alert('很抱歉,提交内容不能为空!');
                return;
            }

            if (!status) return;
            status = false;

            common.ajax('POST', '/unlock/suggest', {'data': str, 'type': 1}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    $.alert('恭喜您提交成功,感谢您的宝贵建议,我们会认真考虑,斟酌采用,感谢您对回来啦社区的关注与支持!', '提交成功', function () {
                        status = true;
                        $('#request-more').val('');
                    });
                } else {
                    $.alert('很抱歉,提交失败,请重试!', function () {
                        status = true;
                        $('#request-more').val('');
                    })
                }
            })

        });

        /**
         * 返回
         */
        $(document).on('click', '#back', function () {
            window.location.href = common.ectouchPic;
        })

        loadData();
        var pings = env.pings;pings();
    });

    $.init();
});
