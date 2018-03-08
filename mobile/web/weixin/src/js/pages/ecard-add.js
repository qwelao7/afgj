require('../../css/style.css');
require('../../css/index.css');
require('../lib/awesomplete.min.js');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#ecard-add", function (e, id, page) {
        var url = common.getRequest();

        var status = true;
        
        var tpl = $('#tpl').html(),
            items = $('#items').html(),
            container = $('#container');

        var arr = ['kidname', 'kindergarten', 'class', 'blessing'],
            chineseArr = ['宝宝姓名', '幼儿园名称', '班级名称', '毕业祝福语'],
            imgUrl = '',
            imgArr = [],
            key,
            id = 0,
            awesomplete;

        var picSize = '?imageMogr2/thumbnail/!80x80r/gravity/center/crop/80x80';

        var submitType = 1; //1-创建 2-编辑

        common.img();

        /** 上传图片 **/
        $('.icon-camera').live('click',function () {
            wx.chooseImage({
                count: 9, // 默认9
                sizeType: ['compressed'], // 可以指定是原图还是压缩图，默认二者都有
                sourceType: ['album', 'camera'], // 可以指定来源是相册还是相机，默认二者都有
                success: function (res) {
                    var localIds = res.localIds; // 返回选定照片的本地ID列表，localId可以作为img标签的src属性显示图片
                    upload(localIds);
                }
            });
        });

        /** 删除图片 **/
        $('.cancel-pics').live('click', function() {
            var _this = $(this),
                parent = _this.parent(),
                index = parent.index();

            parent.remove();
            imgArr.splice(index, 1);
        });

        $('#submit').on('click', function () {
            if (!status) return false;
            status = false;

            var params = common.formToJson($('form').serialize());
            params = JSON.parse(decodeURIComponent(params));
            
            var result = valid(params);

            if (result) {
                params['pics'] = (imgArr.length > 0) ? imgArr.join(',') : '';

                if (submitType == 1) {
                   add(params);
                } else if (submitType == 2) {
                    edit(params);
                }
            }
        });
        
        /** 加载幼儿园列表 **/
        function loadList() {
            common.ajax('GET', '/events/school-list', {}, true, function(rsp) {
                if (rsp.data.code == 0) {
                    renderAwe(rsp.data);
                }
            })
        }
        
        /** 渲染自动补全 **/
        function renderAwe(data) {
            var input = document.getElementById("kindergarten");
            awesomplete = new Awesomplete(input);

            var htm = juicer(items, data);
            $('div.awesomplete ul').prop('hidden', true).append(htm);

            awesomplete.list = data.info;
            awesomplete.minChars = 1;
        }

        function loadData() {
            common.ajax('GET', '/events/search-card', {}, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data;

                    if (data.info.id) {
                        var html = juicer(tpl, data);
                    } else {
                        var html = juicer(tpl, {info: {}})
                    }

                    container.append(html);

                    init(data.info);
                } else {
                    $.alert('数据错误', '很抱歉,数据加载失败,请刷新!', function () {
                        location.reload();
                    })
                }
            })
        }
        
        function init(data) {
            //选择性性别初始化
            if (data.sex) {
                $('input[name=sex]').eq(data.sex - 1).attr('checked', true);
            } else {
                $('input[name=sex]').eq(0).attr('checked', true);
            }

            //编辑状态id参数赋值
            id = (data.id && data.id != 0) ? data.id : 0;
            submitType = (data.id && data.id != 0) ? 2 : 1;

            //图集数据初始化
            imgArr = (data.id && data.id != 0) ? data.pics : [];

            loadList();
        }

        function add(params) {
            common.ajax('POST', '/events/graduate-card', {
                'data': params
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    $.alert('创建成功', '毕业联系卡创建成功!', function () {
                        if (rsp.data.info.sex == 1) {
                            location.href = 'ecard-index-boy.html?qr_code=' + rsp.data.info.qr_code;
                        } else {
                            location.href = 'ecard-index-girl.html?qr_code=' + rsp.data.info.qr_code;
                        }
                    })
                } else {
                    $.alert('创建失败', '毕业联系卡创建失败!失败原因:' + rsp.data.message, function () {
                        status = true;
                    })
                }
            })
        }

        function edit(params) {
            params['id'] = id;

            common.ajax('POST', '/events/update-card', {'data': params}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    $.alert('编辑成功', '毕业联系卡编辑成功!', function () {
                        if (rsp.data.info.sex == 1) {
                            location.href = 'ecard-index-boy.html?qr_code=' + rsp.data.info.qr_code;
                        } else {
                            location.href = 'ecard-index-girl.html?qr_code=' + rsp.data.info.qr_code;
                        }
                    })
                } else {
                    $.alert('创建失败', '毕业联系卡创建失败!失败原因:' + rsp.data.message, function () {
                        status = true;
                    })
                }

            })
        }
        
        function upload(localIds) {
            var localId = localIds.shift();

            wx.uploadImage({
                localId: localId, // 需要上传的图片的本地ID，由chooseImage接口获得
                isShowProgressTips: 1,
                success: function (res) {
                    var serverId = res.serverId; // 返回图片的服务器端ID
                    common.ajax('GET', '/wechat/upload', {mediaId: serverId}, true, function (rsp) {
                        if (rsp.data.code == 0) {
                            var data = rsp.data.info;
                            renderPic(data);

                            //迭代
                            if(localIds.length > 0){
                                upload(localIds);
                            }
                        } else {
                            $.alert('图片上传失败,请重试!');
                        }
                    });
                }
            });
        }

        function valid(data) {
            //验证是否为空
            for (var i in data) {
                key = arr.indexOf(i);
                if (key != -1) {
                    if (data[i] == '' || data[i] == undefined) {
                        $.alert('很抱歉,' + chineseArr[key] + '不能为空!', '验证失败', function () {
                            status = true;
                        });
                        return false;
                    }
                }
            }

            if (data['mobilephone'] != '') {
                if (!common.check(data['mobilephone'], 2)) {
                    $.alert('很抱歉,请填写正确的手机号!', '验证失败', function() {
                        status = true;
                    });
                    return false;
                }
            }

            return true;
        }

        function renderPic(data) {
            var imgWrap = $('#imgs-row');

            var template = "<div class='col-33' style='position: relative;padding: .3rem 0;'>" +
                "<img src='" + data + picSize + "'>" +
                "<i class='iconfont icon-cancel cancel-pics' style='position: absolute;left: 3.4rem;top:-.5rem;color: red;z-index: 2;'></i>" +
                "</div>";

            imgWrap.append(template);

            imgUrl = data.replace(common.QiniuDamain, '');
            imgArr.push(imgUrl);
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
