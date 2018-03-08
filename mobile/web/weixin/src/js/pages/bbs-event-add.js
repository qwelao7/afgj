require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#bbs-event-add", function (e, id, page) {
        var url = common.getRequest();

        var userList = $('#userList'),
            checkedImgs = $('#checkedImgs'),
            smsList = $('#smsList').html(),
            smsInfo = $('#smsInfo').html(),
            headImg = $('#headImg').html(),
            tpl = $('#tpl').html(),
            loading = false,
            status = true,
            num = 2,
            imgArr = {},
            headUrls = {},
            uploads = {},
            params = {},
            nums,
            date,
            remainder;
        //上传初始值
        uploads['pics'] = [];
        uploads['sms'] = [];
        uploads['thumbnail'] = '';
        params['errors'] = [];
        params['msgs'] = '';
        imgArr['users'] = [];

        var cropTh = '?imageMogr2/thumbnail/!175x100r/gravity/center/crop/175x100',
            cropVi = '?imageMogr2/thumbnail/!80x80r/gravity/center/crop/80x80';

        $("#signup-picker").datetimePicker({
            onClose: function() {
                var result = $('#signup-picker').val();
                result = result.replace(/\-/g, '/');
                result = Date.parse(new Date(result));
                if(result < date*1000) {
                    $.alert('很抱歉,报名截止时间不能小于当前时间!', '温馨提示', function() {
                        $('#signup-picker').val('');
                    })
                }
            }
        });

        $("#event-picker").datetimePicker({
            onClose: function() {
                var result = $('#event-picker').val();
                result = result.replace(/\-/g, '/');
                result = Date.parse(new Date(result));
                if(result < date*1000) {
                    $.alert('很抱歉,活动开始时间不能小于当前时间!', '温馨提示', function() {
                        $('#event-picker').val('');
                    });
                    return;
                }

                var val = $('#signup-picker').val();
                if(val != '') {
                    val = val.replace(/\-/g, '/');
                    val = Date.parse(new Date(val));
                    if(val > result) {
                        $.alert('很抱歉,活动开始时间不能小于报名截止时间!', '温馨提示', function() {
                            $('#event-picker').val('');
                        })
                    }
                }
            }
        });

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

        function loadData() {
            common.ajax('GET', '/forum/user-list', {bbsId: url.id, page: 1, date: true}, true, function(rsp) {
                if(rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(smsList, data),
                        htm = juicer(smsInfo, {});

                    nums = data.pagination.pageCount;
                    date = data.date;
                    remainder = data.remainder;

                    userList.append(html);
                    $('.popup-sms-list').append(htm);

                    if (nums == 1) {
                        // 加载完毕，则注销无限加载事件，以防不必要的加载
                        $.detachInfiniteScroll($('.infinite-scroll'));
                        // 删除加载提示符
                        $('.infinite-scroll-preloader').remove();
                    }
                }else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无成员!</h3>";
                    userList.append(template);
                    $('.infinite-scroll-preloader').remove();
                }
            })
        }

        $(document).on('click', '.icon-camera', function() {
            var self = $(this),
                dataNum = self.attr('id');

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
                                var data = rsp.data.info;
                                if(dataNum == 'single') {
                                    //添加封面
                                    var template = "<div style='position: relative' id='thumbnail'> " +
                                        "<img src='" + data  + cropTh + "'> " +
                                        "<i class='iconfont icon-cancel' style='position: absolute;left: 8.2rem;top:-.8rem;color: red;z-index: 2;' id='cancel-thumbnail'></i>" +
                                        "</div>";
                                    $('#single').hide().parent().append(template);
                                    //存储到uploads
                                    data = data.replace(common.QiniuDamain, '');
                                    uploads['thumbnail'] = data;
                                }else if(dataNum == 'more') {
                                    //添加图片
                                    var model = "<div class='col-33' style='position: relative;padding: .3rem 0;'>" +
                                        "<img src='" + data + cropVi + "'>" +
                                        "<i class='iconfont icon-cancel cancel-pics' style='position: absolute;left: 3.4rem;top:-.5rem;color: red;z-index: 2;'></i>" +
                                        "</div>";
                                    $('#imgs-row').append(model);
                                    //存储到uploads
                                    data = data.replace(common.QiniuDamain, '');
                                    uploads['pics'].push(data);
                                }

                            })
                        }
                    });
                }
            });
        });

        $(document).on('click', '#cancel-thumbnail', function() {
            $('#thumbnail').remove();
            $('#single').show();
            uploads['thumbnail'] = '';
        });

        $(document).on('click', '.cancel-pics', function() {
            var self = $(this),
                parent = self.parent(),
                index = parent.index();
            parent.remove();
            uploads['pics'].splice(index, 1);
        });

        $(document).on('infinite','.infinite-scroll', function() {
            if(loading) return;
            loading = true;

            if(num > nums) {
                // 加载完毕，则注销无限加载事件，以防不必要的加载
                $.detachInfiniteScroll($('.infinite-scroll'));
                // 删除加载提示符
                $('.infinite-scroll-preloader').remove();
                return;
            }

            common.ajax('GET', '/forum/user-list', {bbsId: url.id, page: num, date: true}, true, function(rsp) {
                if(rsp.data.code == 0) {
                    loading = false;
                    var data = rsp.data.info,
                        htm = juicer(tpl, data);

                    userList.find('ul').append(htm);
                    num++;
                }
            });

            $.refreshScroller();
        });

        $(document).on('click', '#selectAll,#selectInner', function() {
            var check = $('input[name="checkname"]').prop('checked'),
                checkNum = $('#checkNum');
            $('input[name="checklist"]').prop('checked', !check);
            var len = $('input[name="checklist"]:checked').length;

            if(remainder == 0) {
                $.alert('很抱歉,您本月短信配额已经用完,不能选择用户!', function() {
                    $('input[name="checklist"]').prop('checked', check);
                    $('input[name="checkname"]').prop('checked', false);
                    checkNum[0].innerHTML = 0;
                });
                return;
            }

            if(len > remainder) {
                $.alert('很抱歉,您本月短信配额不足', function() {
                    $('input[name="checklist"]').prop('checked', check);
                    $('input[name="checkname"]').prop('checked', false);
                    checkNum[0].innerHTML = 0;
                });
                return;
            }

            if(!check) {
                checkNum[0].innerHTML = len;
            }else {
                checkNum[0].innerHTML = 0;
            }
        });

        $(document).on('click', '.check-item, .check-item-inner', function() {
            var self = $(this),
                checkbox = self.siblings('input[name="checklist"]'),
                check = checkbox.prop('checked'),
                checkNum = $('#checkNum'),
                num = parseInt(checkNum.html());

            if(remainder == 0) {
                $.alert('很抱歉,您本月短信配额已经用完,不能选择用户!', function() {
                    $('input[name="checklist"]').prop('checked', check);
                });
                return;
            }

            if(num >= remainder && !check) {
                $.alert('很抱歉,您本月短信配额已经用完,不能选择用户!', function() {
                    checkbox.prop('checked', false);
                });
                return;
            }

            if(!check) {
                checkNum[0].innerHTML = num + 1;
            }else {
                checkNum[0].innerHTML = num - 1;
            }
        });

        $(document).on('click', '#checkUser', function() {
            var checks = $('input[name="checklist"]:checked'),
                inners = checks.siblings('.check-item-inner'),
                imgs = inners.find('img.check-head-img'),
                arrs = inners.pluck('dataset');

            imgArr['users'] = [];

            imgArr['imgs'] = imgs.pluck('src');
            $.each(arrs, function(index, item) {
               imgArr['users'].push(item['uid']);
            });

            $.closeModal('.popup-sms-list');
            if(imgArr['imgs'].length > 0) {
                headUrls['imgs'] = imgArr['imgs'].slice(0,5);
                var template = juicer(headImg, headUrls);
                checkedImgs.show().empty().append(template);
                $('#toCheck').hide();
            }else {
                checkedImgs.empty().hide();
                $('#toCheck').show();
            }
        });

        $(document).on('click','.open-sms-list', function () {
            $.popup('.popup-sms-list');
            $.initInfiniteScroll('.popup-sms-list');
        });

        $(document).on('click','.open-sms-info', function () {
            $.popup('.popup-sms-info');
        });

        $(document).on('click', '.close-sms-list', function() {
            $.closeModal('.popup-sms-list');
        });

        $(document).on('click', '.close-sms-info', function() {
            $.closeModal('.popup-sms-info')
        });

        $(document).on('click', '#submit', function() {
            if(!status) return;
            status = false;

            uploads['name'] = $('#title').val();
            uploads['signup_end'] = $('#signup-picker').val();
            uploads['begin'] = $('#event-picker').val();
            uploads['address'] = $('#address').val();
            uploads['person_num'] = ($('#person-num').val() != '')?$('#person-num').val():'无限制';
            uploads['fee'] = ($('#fee').val() != '')?$('#fee').val():'无限制';
            uploads['pics'] = (uploads['pics'].length > 0)?uploads['pics'].join(','):'';
            uploads['content'] = $('textarea').val();
            uploads['sms'] = (imgArr['users'].length > 0) ?imgArr['users']: '';
            uploads['loupan_id'] = url.id;

            validate(uploads['address'], '很抱歉,活动地点不能为空!', params);
            validate(uploads['begin'], '很抱歉,活动开始时间不能为空!', params);
            validate(uploads['signup_end'], '很抱歉,报名截止时间不能为空!', params);
            validate(uploads['name'], '很抱歉,活动标题不能为空!', params);
            console.log(uploads);
            if(params.errors.indexOf(false) == -1) {
                common.ajax('POST', '/forum/create-act', {data: uploads}, true, function(rsp) {
                    if(rsp.data.code == 0) {
                        window.location.href = 'bbs-create.html?id=' + url.id + '&a_id=' + rsp.data.info;
                    }else {
                        status = true;
                        $.alert('很抱歉,创建失败,请重试!');
                    }
                });
            }else {
                $.alert(params.msgs, '创建失败');
                status = true;
                params.errors = [];
            }
        });

        $(document).on('click', '#back', function() {
            history.go(-1);
        })

        function validate(val, msg, params) {
            if (val == "" || val == undefined) {
                params.errors.push(false);
                params.msgs = msg;
                return params;
            }
        }

        loadData();
        getConfig();
        var pings = env.pings;pings();

    });

    $.init();
});
