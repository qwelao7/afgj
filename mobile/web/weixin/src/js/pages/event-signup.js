require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#event-signup", function (e, id, page) {
        var url = common.getRequest();

        var container = $('#container'),
            header = $('header'),
            items = $('#items').html(),
            tpl = $('#tpl').html(),
            info = $('#info').html();

        var title, //活动标题
            events_time, //活动时间
            remarks,  //活动额外信息
            eventType,  //活动类型
            hasRemark = false, //是否有额外信息
            error = false,  //是否报错
            status = true,  //防止多次提交
            params = {},
            needCheck = false,
            sessionsId,  //场景id
            joinNum,  //报名人数
            canJoinNum,  //报名人数上限
            free,  //活动是否免费
            fee,   //单人报名费用
            points,  //用户友元总数 (x100)
            usedPoints = 0, //用户使用的友元总数(当总额小于友元总额,默认抵扣总额对应的友元数)
            amount = 0.00, //订单提交总额
            toPayAmount = 0.00; //订单应付金额

        var computed = function(data, args) {
            return parseInt(data) - parseInt(args);
        };
        var filter = function(data) {
            switch(data) {
                case 'range':
                case 'int':    return 'number';
                               break;
                case 'tel':
                case 'string': return 'text';
                               break;
                case 'email':  return 'email';
                               break;
                default:       return 'text';
                               break;
            }
        };
        juicer.register('computed', computed);
        juicer.register('filter', filter);

        /** 返回 **/
        $(document).on('click', '#back', function() {
            var path = (url.refer) ? '&refer=' + url.refer : '';
            window.location.href = 'event-detail.html?id=' + url.id + path + '&type=1';
        });
        
        /** 添加人数 **/
        $('.num_reduce').live('click', function() {
            var num = $.trim($('#join_num').text());

            if (num == 1) {
                $.alert('很抱歉,报名人数不能小于1', '数字提示')
            } else {
                //更新报名费用
                $('#join_num').text(--num);
                $('#join_amount').text(num * fee / 100);
            }

            joinNum = num;
            var state = $('input[name=points]').prop('checked');

            updateAmount(joinNum, fee, points, state);
        });

        $('.num_add').live('click', function () {
            var num = $.trim($('#join_num').text());

            if (num == canJoinNum) {
                $.alert('很抱歉,报名人数已达上限', '数字提示')
            } else {
                //更新报名费用
                $('#join_num').text(++num);
                $('#join_amount').text(num * fee / 100);
            }

            joinNum = num;
            var state = $('input[name=points]').prop('checked');

            updateAmount(joinNum, fee, points, state);
        });

        /** 切换友元选择 **/
        $('input[name=points]').live('change', function() {
            var self = $(this),
                checked = self.prop('checked');

            updateAmount(joinNum, fee, points, checked);
        });

        /**
         * 上传图片
         */
        $(document).on('click', '.icon-camera', function () {
            var self = $(this),
                parents = self.parents('.item-content'),
                label = parents.data('label');

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
                                    renderPics(data, label);
                                } else {
                                    $.alert('图片上传失败,请重试!', '上传失败');
                                }
                            })
                        }
                    });
                }
            });
        });

        /****
         * 删除图片
         * @type {module.exports.pings}
         */
        $(document).on('click', '.icon-cancel', function () {
            var self = $(this),
                box = self.parents('.box'),
                root = self.parents('.row').prev(),
                link = box.data('link');

            link = link.replace(common.QiniuDamain, '');

            var str = $.trim(root.val());
            str = str.split(',');

            var index = str.indexOf(link);

            str.splice(index, 1);

            root.val(str.join(','));

            box.remove();
        });

        /** 提交内容 **/
        $(document).on('click', '#submit-btn', function() {
            if (!status) return false;
            status = false;

            error = false;

            validate(hasRemark, remarks);
            if (!error) {
                if (eventType == 'sessions') {
                    submitSession();
                } else if (eventType == 'general') {
                    submitGeneral();
                }
            } else {
                status = true;
            }
        });

        /**
         * render pics
         */
        function renderPics(data, label) {
            var template = "<div class='col-33 box' data-link='" +  data + "' style='position: relative;padding: .3rem 0;'>" +
                "<img src='" + data +"' style='width: 4rem;height: auto;max-height: 4rem;'>" +
                "<i class='iconfont icon-cancel' style='position: absolute;left: 3.4rem;top:-.5rem;color: red;z-index: 2;'></i>" +
                "</div>";

            var $input = $('input[name=' + label + ']'),
                rows = $input.next(),
                value = $.trim($input.val());

            rows.append(template);

            data = data.replace(common.QiniuDamain, '');

            value = (value != '') ?  value + ',' + data : data;
            $input.val(value);
        }

        /** 加载数据 **/
        function loadData() {
            common.ajax('GET', '/events/apply-detail', {'id': url.id}, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(items, data),
                        htm = juicer(info, data);

                    container.append(html);
                    container.append(htm);
                    header.after(juicer(tpl, {}));

                    /** 初始化 **/
                    init(data);

                    //若有下拉列表 初始化下拉列表
                    initSelector();

                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,数据错误!</h3>";
                    container.append(template);
                }
            })
        }

        /** initSelector **/
        function initSelector() {
            $('.event_selector').each(function(index, item) {
                var _this = $(item),
                    selectors = _this.data('arr'),
                    label = _this.data('label');
                
                selectors = selectors.split(',');

                _this.picker({
                    toolbarTemplate: '<header class="bar bar-nav">\
                                <button class="button button-link pull-right close-picker">确定</button>\
                                <h1 class="title">请选择' + label +  '</h1>\
                                </header>',
                    cols: [
                        {
                            textAlign: 'center',
                            values: selectors
                        }
                    ],
                    onClose: function() {
                        var text = $.trim(_this.val());

                        _this.next().val(text);
                    }
                });
            })
        }

        /** 备注内容校验 **/
        function validate(hasRemark, remark) {
            if (hasRemark && remark) {
                for(var i in remark) {
                    if (i != 'num') {
                        isEmpty(remark, i);
                        if (error) return false;
                        checkDetail(remark, i);
                        if (error) return false;
                    }
                }
            }
        }

        /** 校验是非为空 **/
        function isEmpty(remark, type) {
            var val = $.trim($('input[name=' + type + ']').val());
            if (!val || val == '') {
                error = true;
                $.alert('很抱歉,' + remark[type].label + '不能为空,请填写!', '提交失败', function() {
                    status = true;
                });
            }
        }

        /** 校验具体内容 **/
        function checkDetail(remark, type) {
            var text = $.trim($('input[name=' + type + ']').val());

            var field = remark[type]['type'];

            switch(field) {
                case 'tel':
                    var result = common.check(text, 2);
                    if (!result){
                        errAlert(remark[type].label);
                    }
                    break;
                case 'age':
                    if (text < 0 || text > 100) {
                        errAlert(remark[type].label);
                    }
                    break;
                case 'idcard':
                    var reg =  /(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/;
                    if (!reg.test(text)) {
                        errAlert(remark[type].label);
                    }
                    break;
            }
        }

        /** 有场次校验 **/
        function validateSession() {
            var checked = $('input[name=my-radio]:checked');
            if (checked.length == 0) {
                error = true;
                $.alert('请选择活动场次!', '操作失败', function() {
                    status = true;
                });
                return false;
            }

            var parent = checked.parents('li'),
                count = parent.data('canjoin');
            sessionsId = parent.data('id');

            //校验人数
            if (remarks['num'] && free == 1) {
                joinNum = $.trim($('input[name=num]').val());
                if (joinNum == '') {
                    error = true;
                    $.alert('请填写报名人数!', '操作失败', function() {
                        status = true;
                    });
                    return false;
                }

                if (parseInt(joinNum) > parseInt(count)) {
                    error = true;
                    $.alert('您填写的报名人数已超上限,请重新填写', '提交失败', function() {
                        status = true;
                    });
                }
            }
        }

        /** 普通校验 **/
        function validateGeneral() {
            if (remarks && remarks.length > 0) {
                if (remarks['num'] && free == 1) {
                    joinNum = $.trim($('input[name=num]').val());
                    if (joinNum == '') {
                        error = true;
                        $.alert('请填写报名人数!', '操作失败', function() {
                            status = true;
                        });
                        return false;
                    }

                    if (parseInt(joinNum) > parseInt(remarks['num'].max)) {
                        error = true;
                        $.alert('您填写的报名人数已超上限,请重新填写', '提交失败', function() {
                            status = true;
                        });
                    }
                }
            }
        }

        /** alert **/
        function errAlert(text) {
            error = true;
            $.alert('很抱歉,您填写的' + text + '不合法,请重新填写', '提交失败', function() {
                status = true;
            });
        }

        /** 参数初始化 **/
        function init(data) {
            //参数初始化
            title = data.title;
            events_time = data.events_time;
            eventType = data.events_type;
            hasRemark = (data.ext_fields && !common.isEmptyObject(data.ext_fields)) ? true : false;
            remarks = data.ext_fields;
            canJoinNum = (data.events_num == 0) ? Infinity : parseInt(data.events_num - data.joined_num);
            fee = (data.free == 0) ? parseInt(data.fee * 100) : 0.00;
            points = (data.accept_point == 1) ? parseInt(data.point) : 0;
            needCheck = (data.apply_check == 1) ? true : false;
            joinNum = 1;
            free = data.free;

            //页面初始化 && 更新支付总金额
            updateAmount(joinNum, fee, points);

            /** 当活动收费是 去掉第一个人数报名 **/
            if (free == 0) {
                var ele = $('input[name=num]');
                ele.parents('.neighbor-detail-sharecom').hide();
            }
        }

        //更新支付总金额 used 是否使用友元
        function updateAmount(num, fee, points, used) {
            if(used == undefined) used = true;
            amount = parseInt(num * fee);
            
            if (used) {
                //使用友元
                if (parseInt(points) >= amount) {
                    usedPoints = amount;
                    toPayAmount = 0.00;
                } else {
                    usedPoints = points;
                    toPayAmount = parseFloat((amount - parseInt(usedPoints)) / 100);
                }
            } else {
                //不使用友元
                usedPoints = 0;
                toPayAmount = amount / 100;
            }

            //更新抵用友元数
           if (usedPoints == 0) {
               if (parseInt(points) >= amount) {
                   //随便写了,怎么简单怎么来
                   $('#usedPoints').prev().find('span').text(amount);
                   $('#usedPoints').text(amount / 100);
               } else {
                   $('#usedPoints').text(points / 100);
               }
           } else {
               //太乱了,能用就行
               $('#usedPoints').prev().find('span').text(usedPoints);
               $('#usedPoints').text(usedPoints / 100);
           }

            $('#total_amount').text(toPayAmount.toFixed(2));
        }

        /** 有场次提交 **/
        function submitSession() {
            //有场次
            validateSession();
            if (!error) {
                //上传参数
                params = $('#remark').serialize();
                if (params != '') {
                    params = common.formToJson(params);
                    params = JSON.parse(decodeURIComponent(params));
                } else {
                    params = {};
                }

                if (remarks['num'] && !common.isEmptyObject(remarks['num']) && free == 1) {
                    //报名人数转为int
                    params['num'] = parseInt(params['num']);
                }

                /** 缴费时 **/
                if (free == 0) {
                    params['num'] = parseInt($('#join_num').text());
                }

                // 活动缴费弹窗
                if (free == 0) {
                    var checkedRadio = $('input[name=my-radio]:checked'),
                        brother = checkedRadio.siblings('.item-inner'),
                        session = $.trim(brother.find('.item-title').text()),
                        session_content = $.trim(brother.find('.item-text').text());

                    var objs = {
                        'title': title,
                        'session': session,
                        'session_content': session_content,
                        'num': params.num,
                        'fee': parseFloat(amount / 100),
                        'points': usedPoints,
                        'pay': toPayAmount
                    };
                }

                // json 转 字符串
                params = JSON.stringify(params);

                // 弹窗
                if (free == 0) {
                    popup(objs, true, params);
                } else {
                    sessionApply(params);
                }
            }
        }
        
        /** 无场次提交 **/
        function submitGeneral() {
            //无场次
            validateGeneral();
            if (!error) {
                   params = $('#remark').serialize();
                   if (params != '') {
                       params = common.formToJson(params);
                       params = JSON.parse(decodeURIComponent(params));
                   } else {
                       params = {};
                   }

                if (remarks && remarks['num'] && !common.isEmptyObject(remarks['num']) && free == 1) {
                    //报名人数转为int
                    params['num'] = parseInt(params['num']);
                }

                /** 缴费时 **/
                if (free == 0) {
                    params['num'] = parseInt($('#join_num').text());
                }

                // 活动缴费弹窗
                if (free == 0) {
                    var objs = {
                        'title': title,
                        'events_time': events_time,
                        'num': params.num,
                        'fee': parseFloat(amount / 100),
                        'points': usedPoints,
                        'pay': toPayAmount
                    };
                }

                // json 转 字符串
                params = JSON.stringify(params);

                // 缴费弹窗
                if (free == 0) {
                    popup(objs, false, params);
                } else {
                    gengralApply(params);
                }
            }
        }
        
        /** 活动缴费 **/
        function payOrder(id) {
            if (toPayAmount == 0.00) {
                var path = (url.refer) ? '?id=' + url.refer : '';
                location.href = 'event-signup-success.html' + path;
            } else {
                common.ajax('GET', '/events/wx-pay-params', {
                    'applyId': id
                }, true, function(rsp) {
                    if (rsp.data.code == 0) {
                        var data = rsp.data.info,
                            jsApi = data['jsApi'];

                        jsApi = JSON.parse(jsApi);
                        callpay(jsApi, id);
                    } else {
                        status = true;
                    }
                })
            }
        }
        
        //支付相关
        function jsApiCall(event, jsApi, apply_id) {
            WeixinJSBridge.invoke("getBrandWCPayRequest", jsApi , function (res) {
                if (res.err_msg == "get_brand_wcpay_request:ok") {
                    $.alert('活动缴费成功!', '缴费成功', function() {
                        var path = (url.refer) ? '?id=' + url.refer : '';
                        location.href = 'event-signup-success.html' + path;
                    });
                } else {
                    payError(apply_id);
                }
            });
        }
        function callpay(jsApi, apply_id) {
            if (typeof WeixinJSBridge == "undefined") {
                if (document.addEventListener) {
                    document.addEventListener("WeixinJSBridgeReady", function(event){jsApiCall(event, jsApi, apply_id);}, false);
                } else if (document.attachEvent) {
                    document.attachEvent("WeixinJSBridgeReady", function(event){jsApiCall(event, jsApi, apply_id);});
                    document.attachEvent("onWeixinJSBridgeReady", function(event){jsApiCall(event, jsApi, apply_id);});
                }
            } else {
                jsApiCall(event,jsApi, apply_id);
            }
        }

        //微信配置
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

        //支付失败
        function payError(apply_id) {
            common.ajax('GET', '/events/pay-error', {
                'applyId': apply_id
            }, true, function(rsp) {
                if (rsp.data.code == 0) {
                    $.alert('很抱歉,活动缴费失败!', '缴费失败', function () {
                        location.href = 'event-detail.html?id=' + url.id + '&type=1';
                    });
                } else {
                    status = true;
                }
            });
        }

        //支付成功
        function paySuccess(apply_id) {
            common.ajax('GET', '/events/pay-success', {
                'applyId': apply_id
            }, true, function(rsp) {
                if (rsp.data.code == 0) {
                    if (!needCheck) {
                        var path = (url.refer) ? '?id=' + url.refer : '';
                        location.href = 'event-signup-success.html' + path;
                    } else {
                        authAlert();
                    }
                } else {
                    status = true;
                }
            });
        }

        // 活动缴费弹窗
        function popup (objs, isSessions, params) {
            var str = '<p class="vehicle-modal-text">' + objs.title + '</p>';

            if (!isSessions) {
                str += '<p class="vehicle-modal-text">' + objs.events_time + '</p>';
            } else {
                str += '<p class="vehicle-modal-text">' + objs.session + '</p>' +
                       '<p class="vehicle-modal-text">' + objs.session_content + '</p>';
            }

            str += '<p class="vehicle-modal-text">人数: ' + objs.num + '</p>' +
                '<p class="vehicle-modal-text">总费用: ' + objs.fee + '元</p>' +
                '<p class="vehicle-modal-text">友元抵扣: ' + parseFloat(objs.points/100) + '元</p>' +
                '<p class="vehicle-modal-text">实付: ' + objs.pay + '元</p>';

            $.modal({
                title: '支付确认',
                text: str,
                buttons: [
                    {
                        text: '取消',
                        onClick: function () {
                            $('#modal').removeClass('modal-overlay-visible');
                            status = true;
                        }
                    },
                    {
                        text: '确定',
                        bold: true,
                        onClick: function () {
                            if (!isSessions) {
                                gengralApply(params);
                            } else {
                                sessionApply(params);
                            }
                        }
                    }
                ]
            })
        }

        // 普通活动缴费
        function gengralApply(params) {
            common.ajax('POST', '/events/apply', {
                'id': url.id,
                'data': params,
                'point': parseFloat(usedPoints/100),
                'fee': parseFloat(amount / 100),
                'pay': toPayAmount
            }, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    //是否活动缴费
                    if (free == 1) {
                        if (!needCheck) {
                            $.alert('报名成功!', '报名成功', function() {
                                var path = (url.refer) ? '?id=' + url.refer : '';
                                window.location.href = 'event-signup-success.html' + path;
                            })
                        } else {
                            authAlert();
                        }
                    } else {
                        //支付流程
                        payOrder(data.id);
                    }
                }else {
                    $.alert(rsp.data.message, '报名失败', function() {
                        status = true;
                    })
                }
            })
        }

        // 场次活动缴费
        function sessionApply(params) {
            common.ajax('POST', '/events/apply', {
                'id': url.id,
                'data': params,
                'sessions_id': sessionsId,
                'point': parseFloat(usedPoints/100),
                'fee': parseFloat(amount / 100),
                'pay': toPayAmount
            }, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    //是否活动缴费
                    if (free == 1) {
                        if (!needCheck) {
                            $.alert('报名成功!', '报名成功', function() {
                                var path = (url.refer) ? '?id=' + url.refer : '';
                                window.location.href = 'event-signup-success.html' + path;
                            })
                        } else {
                            authAlert();
                        }
                    } else {
                        //支付流程
                        payOrder(data.id);
                    }
                }else {
                    $.alert(rsp.data.message, '报名失败', function() {
                        status = true;
                    })
                }
            })
        }

        // 审核弹窗
        function authAlert() {
            $.alert('报名信息提交成功。我们将在1个工作日内完成审核,届时您将收到审核结果通知,感谢您的耐心等待。', '提交成功', function() {
                var path = '?type=1&id=' + url.id;
                path += (url.refer) ? '&refer=' + url.refer : '';

                window.location.href = 'event-detail.html' + path;
            })
        }

        loadData();
        getConfig();
        var pings = env.pings;pings();
    });
    
    $.init();
});
