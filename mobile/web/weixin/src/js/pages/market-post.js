require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on('pageInit', '#market-post', function (e, id, page) {
        var url = common.getRequest();
        /**
         * 定义数组
         * @type {string[]}
         */
        var typeName = ['女装', '数码', '母婴', '美妆', '童装', '其他'];
        var typeNum = ['1', '2', '3', '4', '5', '6'];
        var href = window.location.href,
            community,
            type,
            data = {},
            imgArr = [],
            communityname,
            communityId;

        var tpl = $('#tpl').html(),
            communitys = $('#communitys').html(),
            classifyList = $('#classifyList'),
            communityList = $('#communityList');

        function loadData() {
            common.ajax('GET', '/ride-sharing/account-community', {}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var info = {},
                        index;

                    communityname = rsp.data.info.name;
                    communityId = rsp.data.info.id;

                    if (url.id == 0) {
                        index = 0;
                    } else {
                        index = communityId.indexOf(url.id)
                    }
                    if (url.classify == 0) {
                        info.classify = typeName[0];
                    } else {
                        info.classify = typeName[url.classify - 1];
                    }

                    info.name = communityname[index];

                    //community, type默认值
                    community = communityId[index];
                    if (url.classify == 0) url.classify = 1;
                    type = url.classify;

                    var html = juicer(tpl, info),
                        htm = juicer(communitys, info);

                    classifyList.prepend(html);
                    communityList.prepend(htm);

                    pick();
                    pickClassify();
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

        function pickClassify() {
            $("#picker1").picker({
                toolbarTemplate: '<header class="bar bar-nav">\
                               <button class="button button-link pull-right close-picker font-white">确定</button>\
                               <h1 class="title font-white">请选择物品分类</h1>\
                               </header>',
                cols: [
                    {
                        textAlign: 'center',
                        values: typeName
                    }
                ],
                onClose: function () {
                    var str = $('#picker1').val(),
                        index = typeName.indexOf(str);
                    type = typeNum[index];
                }
            });
        }

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

        /** 点击上传图片 **/
        $(page).on('click', '#imgs', function () {
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
                                        template = "<div class='col-33' style='position: relative;padding: .3rem 0;'>" +
                                            "<img src='" + data + "' style='width: 4rem;height: 4rem'>" +
                                            "<i class='iconfont icon-cancel delete' style='position: absolute;left: 3.4rem;top:-.5rem;color: red;z-index: 2;'></i>" +
                                            "</div>";
                                    $('#pic').append(template);
                                    data = data.replace(common.QiniuDamain, '');
                                    imgArr.push(data);
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
        $(document).on('click', '.delete', function () {
            var index = $(this).parent().index();
            imgArr.splice(index, 1);
            $(this).parent().remove();
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

            if(/^\d+$/.test($('#price').val()) == false)
            {
                self.prop("disabled", false);
                $.alert('很抱歉,您填写的价格格式有误,请填写数字金额!');
                return;
            }

            data.community_id = community;
            data.sell_item_type = $('#picker1').val();
            data.item_desc = $('#content').val();
            data.sell_item_price = parseFloat($('#price').val()).toFixed(2);
            data.item_pics = imgArr.join(',');

            tips(data.sell_item_type, '请选择工具类型', self, params);
            tips(data.community_id, '请填写你的房产', self, params);
            tips(data.sell_item_price, '请填写价格', self, params);
            tips(data.item_desc, '请填写物品描述', self, params);

            data.sell_item_type = typeName.indexOf(data.sell_item_type);
            data.sell_item_type = typeNum[data.sell_item_type];

            if (params.arr.indexOf('false') == -1) {
                common.ajax('POST', '/market/create', {'data': data}, true, function (rsp) {
                    if (rsp.data.code == 0) {
                        window.location.href = 'market-success.html?id=' + community + '&classify=' + type;
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