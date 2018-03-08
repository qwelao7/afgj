require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#bbs-vote-add", function (e, id, page) {
        var url = common.getRequest();
        //定义变量
        var length,
            question_num = 0,
            href = window.location.href,
            imgArr = {}, headUrls = {},
            arr = [],
            account_id = [],
            num = 2,
            thumbnail = '',
            nums,
            loading = false,
            genum = ['一', '二', '三', '四', '五', '六', '七', '八', '九', '十'],
            shinum = ['十', '二十', '三十', '四十', '五十', '六十', '七十', '八十', '九十'],
            add_option = $('#add-option').html(),
            smsList = $('#smsList').html(),
            smsInfo = $('#smsInfo').html(),
            add_question = $('#add-question').html(),
            sub = $('#sub').html(),
            userList = $('#userList'),
            headImg = $('#headImg').html(),
            checkedImgs = $('#checkedImgs');


        var state = true;

        var cropTh = '?imageMogr2/thumbnail/!175x100r/gravity/center/crop/175x100',
            cropVi = '?imageMogr2/thumbnail/!80x80r/gravity/center/crop/80x80';
        //添加选项
        $(document).on('click', '.add-option', function () {
            var self = $(this).parents('.option'),
                arr = self.siblings('.has-border-bottom').find('.vote_content').pluck('value');
            if ($.inArray("", arr) != -1) {
                $.alert("之前选项不能为空！");
            } else {
                length = self.siblings('.has-border-bottom').length;
                length++;
                $(this).next('.del-option').removeClass('hide');
                var shi = parseInt(length / 10),
                    ge = length % 10;
                if (shi == 0) {
                    shi = '';
                } else {
                    shi = shinum[shi - 1];
                }
                if (ge == 0) {
                    ge = '';
                } else {
                    ge = genum[ge - 1];
                }
                var option = '{"option":"' + shi + '' + ge + '"}',
                    data = JSON.parse(option),
                    html = juicer(add_option, data);
                self.before(html);
            }
        });
        //删除选项
        $(document).on('click', '.del-option', function () {
            var self = $(this).parent().parent();
            self.prev().remove();
            length = self.prevAll().length;
            if (length == 5) {
                $(this).addClass('hide');
            }
        });
        //添加问题
        $(document).on('click', '.add-question', function () {
            var prev = $(this).prev(),
                questionData = {};
            questionData.img = [];
            questionData.question_name = prev.find('.question_name').val();
            questionData.vote_type = prev.find('input[type="radio"]:checked').val();
            questionData.option = prev.find('.vote_content').pluck('value');
            var img = prev.find('li.has-border-bottom').pluck('dataset');
            $.each(img, function (i, val) {
                questionData.img.push(val['img']);
            });
            if (($.inArray("", questionData.option) == -1) && (questionData.question_name.length != 0)) {
                question_num++;
                var question = '{"question":"' + genum[question_num] + '"}',
                    data = JSON.parse(question),
                    html = juicer(add_question, data);
                $('.add-question').before(html);
                arr.push(questionData);
            } else {
                $.alert("请将前一个问题填写完整！")
            }
        });

        $("#signup-picker").datetimePicker({
            // value: ['2016', '12', '31', '0', '00']
        });

        function loadData() {
            common.ajax('GET', '/forum/user-list', {bbsId: url.id, page: 1, date: true}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(smsList, data),
                        htm = juicer(smsInfo, {});

                    nums = data.pagination.pageCount;

                    userList.append(html);
                    $('.popup-sms-list').append(htm);

                    if (nums == 1) {
                        // 加载完毕，则注销无限加载事件，以防不必要的加载
                        $.detachInfiniteScroll($('.infinite-scroll'));
                        // 删除加载提示符
                        $('.infinite-scroll-preloader').remove();
                    }
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无成员!</h3>";
                    userList.append(template);
                    $('.infinite-scroll-preloader').remove();
                }
            })
        }

        $(document).on('click', '#checkUser', function () {
            var checks = $('input[name="checklist"]:checked'),
                inners = checks.siblings('.check-item-inner'),
                imgs = inners.find('img.check-head-img');

            imgArr['imgs'] = imgs.pluck('src');
            imgArr['index'] = inners.pluck('tabIndex');
            var dataId = inners.pluck('dataset');
            $.each(dataId, function (i, val) {
                account_id.push(val['id']);
            });
            $.closeModal('.popup-sms-list');
            if (imgArr['imgs'].length > 0) {
                headUrls['imgs'] = imgArr['imgs'].slice(0, 5);
                headUrls['index'] = imgArr['index'].slice(0, 5);
                var template = juicer(headImg, headUrls);
                checkedImgs.show().empty().append(template);
                $('#toCheck').hide();
            } else {
                checkedImgs.empty().hide();
                $('#toCheck').show();
            }
        });

        $(document).on('infinite', '.infinite-scroll', function () {
            if (loading) return;
            loading = true;

            if (num > nums) {
                console.log(num);
                // 加载完毕，则注销无限加载事件，以防不必要的加载
                $.detachInfiniteScroll($('.infinite-scroll'));
                // 删除加载提示符
                $('.infinite-scroll-preloader').remove();
                return;
            }
            common.ajax('GET', '/forum/user-list', {bbsId: url.id, page: num, date: true}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    loading = false;
                    var data = rsp.data.info,
                        htm = juicer(sub, data);

                    userList.find('ul').append(htm);
                    num++;
                }
            });

            $.refreshScroller();
        });

        $(document).on('click', '#selectAll,#selectInner', function () {
            var check = $('input[name="checkname"]').prop('checked'),
                checkNum = $('#checkNum');
            $('input[name="checklist"]').prop('checked', !check);
            var len = $('input[name="checklist"]:checked').length;
            if (!check) {
                checkNum[0].innerHTML = len;
            } else {
                checkNum[0].innerHTML = 0;
            }
        });

        $(document).on('click', '.check-item, .check-item-inner', function () {
            var self = $(this),
                checkbox = self.siblings('input[name="checklist"]'),
                check = checkbox.prop('checked'),
                checkNum = $('#checkNum'),
                num = parseInt(checkNum.html());
            if (!check) {
                checkNum[0].innerHTML = num + 1;
            } else {
                checkNum[0].innerHTML = num - 1;
            }
        });

        $(document).on('click', '.open-sms-list', function () {
            $.popup('.popup-sms-list');
            $.initInfiniteScroll('.popup-sms-list');
        });

        $(document).on('click', '.open-sms-info', function () {
            $.popup('.popup-sms-info');
        });

        $(document).on('click', '.close-sms-list', function () {
            $.closeModal('.popup-sms-list');
        });

        $(document).on('click', '.close-sms-info', function () {
            $.closeModal('.popup-sms-info')
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
            })
        }

        /** 点击上传图片 **/
        $(page).on('click', '.img', function () {
            var self = $(this),
                type = self.data("type");

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
                                    var data = rsp.data.info;
                                    self.hide();
                                    if (type == 1) {
                                        var template = "<div class='col-33 box' style='position: relative;padding: .3rem 0;'>" +
                                            "<img src='" + data +cropTh+ "' style='width: 4rem;height: 4rem'>" +
                                            "<i class='iconfont icon-cancel delete-title' style='position: absolute;left: 3.4rem;top:-.5rem;color: red;z-index: 2;'></i>" +
                                            "</div>";
                                        data = data.replace(common.QiniuDamain, '');
                                        thumbnail = data;
                                        self.parent().append(template);
                                    }
                                    if (type == 2) {
                                        var template = "<div class='col-33 box' style='margin-left:33%;position: relative;padding: .3rem 0;'>" +
                                            "<img src='" + data + cropVi + "' style='width: 4rem;height: 4rem'>" +
                                            "<i class='iconfont icon-cancel delete' style='position: absolute;left: 3.4rem;top:-.5rem;color: red;z-index: 2;'></i>" +
                                            "</div>";
                                        data = data.replace(common.QiniuDamain, '');
                                        self.parents('li')[0].setAttribute('data-img', data);
                                        self.parents('li').append(template);
                                    }
                                } else {
                                    $.alert('图片上传失败,请重试!');
                                }
                            })
                        }
                    });
                }
            });
        });

        /** 删除选项图片 **/
        $(page).on('click', '.delete', function () {
            var self = $(this).parent();
            self.prev().find('.icon-camera-option').show();
            self.remove();
        });

        /** 删除封面图片 **/
        $(page).on('click', '.delete-title', function () {
            var self = $(this).parent();
            self.remove();
            $('.icon-camera-title').show();
        });

        //返回
        $(page).on('click', '#back', function () {
            history.go(-1);
        });

        //提交
        $(document).on('click', '#submit', function () {
            if (!state) return;
            state = false;

            var self = $(this),
                prev = $('.add-question').prev(),
                vote = {},
                params = {},
                questionData = {};
            questionData.img = [];
            params.arr = [];
            params.err = '';
            self.prop("disabled", true);
            questionData.question_name = prev.find('.question_name').val();
            questionData.vote_type = prev.find('input[type="radio"]:checked').val();
            questionData.option = prev.find('.vote_content').pluck('value');
            var img = prev.find('li.has-border-bottom').pluck('dataset');
            $.each(img, function (i, val) {
                questionData.img.push(val['img']);
            });

            vote.title = $('#vote_name').val();
            vote.thumbnail = thumbnail;
            vote.deadline = $('#signup-picker').val();
            vote.content = $('#content').val();

            if (($.inArray("", questionData.option) != -1) || (questionData.question_name.length == 0)) {
                params.arr.push('false');
                params.err = '请将问题填写完整！';
            }

            tips(vote.deadline, '请填写截止时间', self, params);
            tips(vote.title, '请填写投票标题', self, params);

            if (params.arr.indexOf('false') == -1) {
                arr.push(questionData);
                arr.push(vote);
                common.ajax('POST', '/forum/create-vote', {
                    'data': arr,
                    'bbsId': url.id,
                    'account_id': account_id
                }, true, function (rsg) {
                    if (rsg.data.code == 0) {
                        $.alert('创建成功!', function () {
                            window.location.href = 'bbs-create.html?id=' + url.id + '&v_id=' + rsg.data.info.id;
                        });
                    } else {
                        $.alert(rsg.data.message, function () {
                            state = true;
                        });
                    }
                });
            } else {
                params.arr = [];
                $.alert(params.err, function () {
                    state = true;
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
        getConfig();
        var pings = env.pings;
        pings();
    });

    $.init();
});
