require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#event-edit", function (e, id, page) {
        var url = common.getRequest();

        var tpl = $('#tpl').html(),
            container = $('#container');

        var imgUrl = '',
            ranges,
            status = true,
            imgArr = [],
            checkArr = [],
            serverTime;
        
        var points = {
            name: [],
            id: []
        };

        var thumbSize = '?imageMogr2/thumbnail/!175x100r/gravity/center/crop/175x100',
            imgsSize = '?imageMogr2/thumbnail/!80x80r/gravity/center/crop/80x80';

        var transArr = ['title', 'address', 'events_begin', 'events_end', 'auth_way', 'tel'],
            chineseArr = ['标题', '活动地点', '活动开始时间', '活动结束时间', '活动范围', '发起人手机号'],
            skipArr = ['fee', 'events_num', 'content', 'img', 'accept_point', 'accept_point_community_id', 'thumbnail', 'free'];

        var defaultJson = {
            '_name': {
                'label': '姓名',
                'type': 'string',
                'size': 50
            },
            '_age': {
                'label': '年龄',
                'type': 'int'
            },
            '_fang': {
                'label': '房号',
                'type': 'int'
            },
            '_num': {
                'label': '人数',
                'type': 'range',
                'min': 1
            },
            '_mobile': {
                'label': '手机号',
                'type': 'tel'
            },
            '_idcard': {
                'label': '身份证号',
                'type': 'idcard'
            }
        };

        var imgBig = function (data) {
            return common.QiniuDamain + data + thumbSize;
        };
        var imgSmall = function (data) {
            return common.QiniuDamain + data + imgsSize;
        };
        var getContent = function (data) {
            if (data == '') return '';
            if ($(data).find('p').length > 0) {
                return $.trim($(data).find('p').text());
            } else {
                return '';
            }
        };
        juicer.register('imgBig', imgBig);
        juicer.register('imgSmall', imgSmall);
        juicer.register('getContent', getContent);

        /**
         * 编辑页面 展示数据初始化 (juicer 不方便展示的内容)
         */
        function juicerInit(data, points) {
            renderExt(data.events.ext_fields);
            renderContentImgs(data.events.content);

            //友元相关
            if (data.events.free == 1) {
                $('#points_use').addClass('hide');
            }

            if (data.events.accept_point == 0) {
                $('.points-area').addClass('hide');
            }

            checkArr = data.events.accept_point_community_id;
            updatePointArea();
        }

        /**
         * 备注内容初始化
         * @param params
         * @returns {boolean}
         */
        function renderExt(params) {
            if (params == '') return false;

            //备注信息展示
            params = JSON.parse(params);
            for(var i in params) {
                if (defaultJson.hasOwnProperty(i)) {
                    var path = '';
                    if (i.indexOf('_') != -1) {
                        path = '#input' + i
                    } else {
                        path = '#input+' + i;
                    }

                    $(path).attr('checked', true);
                } else {
                    var tem = '<div class="col-50">' +
                        '<label class="label-checkbox item-content">' +
                        '<input type="checkbox" name="extra" checked>' +
                        '<div class="item-media">' +
                        '<i class="icon icon-form-checkbox"></i>' +
                        '<span style="margin-left: .2rem">' + params[i]['label'] + '</span>' +
                        '</div>' +
                        '</label>' +
                        '</div>';
                    $('#extra-option').append(tem);
                }
            }
        }

        /**
         * 活动详情图片初始化
         */
        function renderContentImgs (imgs) {
            if (imgs == '') return false;

            var imgWrapper = $(imgs).find('img'),
                path,
                tem;
            if (imgWrapper.length == 0) {
                return false;
            } else {
                imgWrapper.each(function (index) {
                    path = $(this).attr('src');
                    tem = "<div class='col-33' style='position: relative;padding: .3rem 0;'>" +
                    "<img src='" + path + imgsSize + "'>" +
                    "<i class='iconfont icon-cancel cancel-pics' style='position: absolute;left: 3.4rem;top:-.5rem;color: red;z-index: 2;'></i>" +
                    "</div>";
                    $('#imgs-row').append(tem);

                    imgArr.push(path.replace(common.QiniuDamain, ''));
                });
            }
        }

        /**
         * 活动开始
         */
        function beginPicker () {
            $("#begin-picker").datetimePicker({
                onClose: function() {
                    pickerValidate('#begin-picker', 'input[name=events_begin]');
                }
            });
        }

        /**
         * 活动结束
         */
        function endPicker () {
            $("#end-picker").datetimePicker({
                onClose: function() {
                    pickerValidate('#end-picker', 'input[name=events_end]');
                }
            });
        }

        /**
         * 报名截止
         */
        function signPicker() {
            $('#signup-picker').datetimePicker({
                onClose: function() {
                    pickerValidate('#signup-picker', 'input[name=apply_end]');
                }
            })
        }

        /**
         * 扩展内容
         */
        $(document).on('click','#add-extra', function () {
            $.prompt('新增信息', function (value) {
                if (value == '') return false;

                var valueCut=(value.length>7)?value.substring(0,7)+'...':value;
                var option='<div class="col-50">'
                    +'<label class="label-checkbox item-content">'
                    +'<input type="checkbox" name="extra" checked>'
                    +'<div class="item-media">'
                    +'<i class="icon icon-form-checkbox" style="margin-top: -.4rem!important;"></i>'
                    +'<span style="margin-left: .2rem;">'+valueCut+'</span>'
                    +'</div>'
                    +'</label>'
                    +'</div>';
                $('#extra-option').append(option);
            });
        });

        /**
         * 添加图片
         */
        $(document).on('click', '.icon-camera', function() {
            var self = $(this),
                imgNum = self.data('nums');

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
                                renderPics(imgUrl, imgNum);
                            })
                        }
                    });
                }
            });
        });

        /**
         * 删除封面图
         */
        $(document).on('click', '.cancel-thumbnail', function() {
            var _this = $(this),
                parent = _this.parent();

            parent.remove();
            $('.add-pic-single').removeClass('hide');
            $('input[name=thumbnail]').val('');
        });

        /**
         * 删除详情图片
         */
        $(document).on('click', '.cancel-pics', function() {
            var _this = $(this),
                parent = _this.parent(),
                index = parent.index();

            parent.remove();
            imgArr.splice(index, 1);
        });

        /**
         * 切换费用显示
         */
        $(document).on('click', '.check-isfree', function() {
            var _this = $(this),
                radio = _this.find('input[name=free]'),
                value = radio.val();

            if (value == 1) {
                $('.item-fee').addClass('hide');
                $('input[name=fee]').val('');

                //清空友元相关
                emptyPoints();
            } else {
                $('.item-fee').removeClass('hide');

                //默认设置 (不开启接受友元)
                setDefaultPoints();
            }
        });

        /**
         * 切换友元是否使用
         */
        $(document).on('click', '.check-points', function() {
            var _this = $(this),
                radio = _this.find('input[name=accept_point]'),
                value = $.trim(radio.val());

            if (value == 1) {
                $('.points-area').removeClass('hide');
            } else {
                $('.points-area').addClass('hide');
            }

            //默认取消勾选
            checkArr = [];
            $('input[name=point_checkbox]').prop('checked', false);
            updatePointArea();
        });

        /***
         * 报名人数限制 数字
         */
        $(document).on('keyup', 'input[name=events_num]', function() {
            var _this = $(this),
                reg = _this.val().match(/\d+/),
                txt = '';
            if (reg != null)
            {
                txt = reg[0];
            }
            _this.val(txt);
        });

        /**
         * 提交
         */
        $(document).on('click', '#submit', function() {
            if (!status) return false;
            status = false;

            updatePointArea();
            //为input[name=imgs]赋值
            var str = (imgArr.length > 0) ? imgArr.join(',') : '';
            $('input[name=img]').val(str);

            var params = common.formToJson($('form').serialize()),
                key;
            params = JSON.parse(decodeURIComponent(params));
            params.events_num = (params.events_num == '无限制') ? 0 : params.events_num;
            
            //检验参数是否为空
            for(var i in params) {
                if (skipArr.indexOf(i) == -1) {
                    if (params[i] == '' || params[i] == undefined) {
                        key = transArr.indexOf(i);
                        $.alert('很抱歉,' + chineseArr[key] + '不能为空!', '验证失败', function() {
                            status = true;
                        });
                        return false;
                    }
                }
            }

            /**
             * 检验手机号
             */
            if (!common.check(params.tel, 2)) {
                $.alert('很抱歉,请填写正确的手机号!', '验证失败', function() {
                    status = true;
                });
                return false;
            }

            /**
             * 当收费时, 费用不能为空
             */
            if (params['free'] == 0 && params['fee'] == '') {
                $.alert('很抱歉,活动费用不能为空', '校验失败', function() {
                    status = true;
                });
                return false;
            }

            /**
             * 当选择使用友元时,小区不能为空
             */
            if (params['accept_point'] == 1 && params['accept_point_community_id'] == '') {
                $.alert('很抱歉,请选择哪些小区能够使用友元支付', '校验失败', function() {
                    status = true;
                });
                return false;
            }

            /**
             * 添加默认值
             */
            params['events_num'] = (params['events_num'] != '') ? params['events_num'] : 0;

            /** 扩展信息 **/
            params['ext_fields'] = mergeInfo();
            params['ext_fields'] = (common.isEmptyObject(params['ext_fields'])) ? '' : JSON.stringify(params['ext_fields']);
            params['events_id'] = url.id;

            /** 活动时间处理 **/
            params['events_begin'] = params['events_begin'].slice(0,16);
            params['events_end'] = params['events_end'].slice(0,16);
            params['apply_end'] = params['apply_end'].slice(0,16);

            /** 删除冗余字段 **/
            delete params.point_checkbox;

            /** ajax **/
            add(params);
        });

        //empty points
        function emptyPoints() {
            $('input[name=point_checkbox]').prop('checked', false);
            $('input[name=accept_point]').eq(1).prop('checked', true);

            checkArr = [];
            updatePointArea();
        }

        //set default
        function setDefaultPoints() {
            $('input[name=accept_point]').eq(1).prop('checked', true);
            $('.points-area').addClass('hide');
        }

        function add(params) {
            common.ajax('POST', '/events/create', params, true, function(rsp) {
                if (rsp.data.code == 0) {
                    $.alert('活动编辑成功!', '提交成功', function () {
                        history.back();
                    })
                } else {
                    $.alert('很抱歉,' + rsp.data.message + ',请重试!', '提交失败');
                    status = true;
                }
            })
        }

        /**
         * 更新友元适用小区选中
         */
        function updatePointArea() {
            checkArr = [];
            var checks = $('input[name=point_checkbox]:checked');

            $.each(checks, function(index, item) {
                var _this = $(item),
                    val = _this.val();

                checkArr.push(val);
            });

            $('input[name=accept_point_community_id]').val(checkArr.join(','));
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
         * 加载活动类别
         */
        function loadTypes() {
            common.ajax('GET', '/ride-sharing/account-community', {}, false, function (rsp) {
                if (rsp.data.code == 0) {
                    ranges = rsp.data.info;
                    ranges['name'].unshift('公开活动');
                    ranges['id'].unshift('0');

                    rangePicker();
                } else {
                    ranges = {};
                    ranges['name'] = ['公开活动'];
                    ranges['id'] = '0';

                    rangePicker();
                }
            });
        }

        /**
         * 加载活动数据
         */
        function loadData() {
            common.ajax('GET', '/events/point-community', {}, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    points.name = data.name;
                    points.id = data.id;

                    common.ajax('GET', '/events/info', {'id': url.id}, true, function (rsp) {
                        if (rsp.data.code == 0) {
                            var data = rsp.data.info,
                                html = juicer(tpl, {
                                    'info': data,
                                    'points': points
                                });

                            container.append(html);

                            loadTypes();
                            juicerInit(data, points);
                            beginPicker();
                            endPicker();
                            signPicker();
                        } else {
                            $.alert('很抱歉,数据获取失败,请重试!', function () {
                                location.reload();
                            })
                        }
                    });
                }
            })
        }

        /**
         * 获取服务器时间
         */
        function loadTime() {
            common.ajax('GET', '/site/get-current-time', {}, true, function(rsp) {
                if (rsp.data.code == 0) {
                    serverTime = rsp.data.info;
                    serverTime = new Date(parseInt(serverTime) * 1000);
                }
            })
        }

        /**
         * 是否公开以及是否选择小区
         */
        function rangePicker() {
            $("#range-picker").picker({
                toolbarTemplate: '<header class="bar bar-nav">\
                                <button class="button button-link pull-right close-picker">确定</button>\
                                <h1 class="title">请选择活动范围</h1>\
                                </header>',
                cols: [
                    {
                        textAlign: 'center',
                        values: ranges['name']  //公开或者小区
                    }
                ],
                onClose: function() {
                    var _self = $('#range-picker'),
                        text = $.trim(_self.val()),
                        index = ranges['name'].indexOf(text);
                    $('input[name=auth_way]').val(ranges['id'][index]);
                }
            });
        }

        /**
         * picker 日期转换
         */
        function pickerFormat(widget, selector) {
            var self = $(widget),
                text = $.trim(self.val());

            $(selector).val(text);
        }

        /**
         * picker 验证
         * @param curWidget
         * @param selector
         * @returns {boolean}
         */
        function pickerValidate(curWidget, selector) {
            var data = [
                {
                    msg: '活动开始时间',
                    selector: '#begin-picker'
                },
                {
                    msg: '活动结束时间',
                    selector: '#end-picker'
                },
                {
                    msg: '活动截止时间',
                    selector: '#signup-picker'
                }
            ];

            var self = $(curWidget),
                text = $.trim(self.val()),
                msg;

            for(var i in data) {
                if (data[i]['selector'] == curWidget) {
                    msg = data[i]['msg'];
                }
            }

            /***
             * 验证是否为空
             */
            if (text == '' || text ==  undefined) {
                $.alert('很抱歉,' + msg + '不能为空,请填写', '验证错误');
                return false;
            }

            timeCompare(curWidget, selector);
        }

        /**
         * 时间比较
         */
        function timeCompare(curWidget, selector) {
            var begin = '#begin-picker',
                end = '#end-picker',
                signup = '#signup-picker',
                curTime = $.trim($(curWidget).val()),
                beginTime = $.trim($(begin).val()),
                endTime = $.trim($(end).val()),
                signTime = $.trim($(signup).val());

            /**
             * 大于当前时间
             */
            if (common.timeStringToDate(curTime) < serverTime) {
                $.alert('很抱歉,您选择的时间不能小于当前时间', '日期选择错误', function() {
                    $(curWidget).val('');
                });
                return false;
            }

            /**
             * 活动时间 > 报名时间
             */
            if (curWidget == begin) {
                if (endTime != '') {
                    if (common.timeStringToDate(endTime) <= common.timeStringToDate(curTime)) {
                        $.alert('很抱歉,活动开始时间应该小于结束时间,请重新选择', '日期选择错误', function() {
                            $(curWidget).val('');
                        });
                        return false;
                    }
                }
            }

            /**
             * 活动结束时间 > 开始时间
             */
            if (curWidget == end) {
                if (beginTime != '') {
                    if (common.timeStringToDate(curTime) <= common.timeStringToDate(beginTime)) {
                        $.alert('很抱歉,活动结束时间应该大于开始时间,请重新选择', '日期选择错误', function() {
                            $(curWidget).val('');
                        });
                        return false;
                    }
                }

                if (signTime != '') {
                    if (common.timeStringToDate(signTime) >= common.timeStringToDate(curTime)) {
                        $.alert('很抱歉,活动结束时间应该大于报名截止时间,请重新选择', '日期选择错误', function() {
                            $(curWidget).val('');
                        });
                        return false;
                    }
                }
            }

            /**
             * 报名截止时间 < 结束时间
             */
            if (curWidget == signup) {
                if (endTime != '') {
                    if (common.timeStringToDate(curTime) >= common.timeStringToDate(endTime)) {
                        $.alert('很抱歉,报名截止时间应该小于结束时间,请重新选择', '日期选择错误', function() {
                            $(curWidget).val('');
                        });
                        return false;
                    }
                }
            }

            pickerFormat(curWidget, selector);
        }

        /**
         * 添加图片
         * @param imgUrl
         * @param imgNum
         * @param params
         */
        function renderPics(imgUrl, imgNum) {
            var icon = $('.add-pic-' + imgNum),
                parent = icon.parent();

            if (imgNum == 'single') {
                var thumbHtml =  "<div style='position: relative' id='thumbnail'> " +
                    "<img src='" + imgUrl  + thumbSize + "'> " +
                    "<i class='iconfont icon-cancel cancel-thumbnail' style='position: absolute;left: 8.2rem;top:-.8rem;color: red;z-index: 2;'></i>" +
                    "</div>";
                icon.addClass('hide');
                parent.append(thumbHtml);

                //存储到input[type=hide]
                imgUrl = imgUrl.replace(common.QiniuDamain, '');
                $('input[name=thumbnail]').val(imgUrl);
            } else {
                var picsHtml = "<div class='col-33' style='position: relative;padding: .3rem 0;'>" +
                    "<img src='" + imgUrl + imgsSize + "'>" +
                    "<i class='iconfont icon-cancel cancel-pics' style='position: absolute;left: 3.4rem;top:-.5rem;color: red;z-index: 2;'></i>" +
                    "</div>";
                var imgWrap = $('input[name=img]');
                $('#imgs-row').append(picsHtml);

                //存储到input[type=hide]
                imgUrl = imgUrl.replace(common.QiniuDamain, '');
                imgArr.push(imgUrl);
            }
        }

        /**
         * 组合报名信息的收集
         */
        function mergeInfo() {
            var checks = $('input[name=extra]:checked'),
                obj = {},
                _this,
                attrs,
                label;

            $.each(checks, function(index, item) {
                _this = $(item);
                attrs = _this.data('attr');
                label = $.trim(_this.parents('.item-content').find('span').text());

                if (defaultJson.hasOwnProperty(attrs)) {
                    obj[attrs] = defaultJson[attrs];
                } else {
                    obj['_extra'+index] = {
                        'label': label,
                        'type': 'string'
                    }
                }
            });
            return obj;
        }
        
        loadData();
        loadTime();
        getConfig();

        var pings = env.pings;pings();
    });

    $.init();
});