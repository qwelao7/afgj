require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

/**
 * url 参数
 * type 1-创建 2-编辑
 * refer: freeride: 返回顺风车列表页
 *        vehicle: 返回设施详情页
 *
 */
$(function () {
    'use strict';

    $(document).on("pageInit", "#freeride-setcar", function (e, id, page) {
        var url = common.getRequest(),
            status = true;

        var container = $('#container'),
            tpl = $('#tpl').html(),
            nav = $('#nav').html();

        var params,
            key,
            transArr = ['color', 'car_num', 'now_km', 'buy_date'],
            skipArr = ['brand_id', 'series_id'],
            chineseArr = ['车辆颜色', '车牌号码', '当前公里', '购买时间'],
            data;

        var re = /^[京津沪渝冀豫云辽黑湘皖鲁新苏浙赣鄂桂甘晋蒙陕吉闽贵粤青藏川宁琼使领A-Z]{1}[A-Z]{1}([A-Z0-9]{4}|[A-z0-9]{5})[A-Z0-9挂学警港澳]{1}$/;

        function init() {
            var series = localStorage.getItem('series');
            if(series) {
                series = JSON.parse(series);
                $('input[name=series]').val($.trim(series.name));
                $('input[name=brand_id]').val($.trim(series.brand_id));
                $('input[name=series_id]').val($.trim(series.series_id));
            }
        }

        function loadData() {
            if (url.type == 1) {
                var html = juicer(tpl, {});
                container.append(html);

                $("input[name=buy_date]").calendar();
            } else if (url.type == 2) {
                common.ajax('GET', '/vehicle/car-info', {'id': url.refer_id}, false, function (rsp) {
                    if (rsp.data.code == 0) {
                        data = rsp.data.info;
                        var html = juicer(tpl, data);
                        container.append(html);

                        $("input[name=buy_date]").calendar({value: [data.buy_date]});
                    } else {
                        $.alert('很抱歉,' + rsp.data.message, '获取数据失败', function () {
                            history.back();
                        })
                    }
                })
            }

            setTimeout(function () {
                var htm = juicer(nav, {});
                container.after(nav);
                init();
            }, 500);
        }

        /**
         * 跳转车辆品牌界面
         */
        $(document).on('click', '#classify', function() {
            var path = '?type=' + url.type + '&refer=' + (url.refer ? url.refer : '');
            path = (url.refer_id) ? path + '&refer_id=' + url.refer_id : path;

            location.href = 'car-brand.html' + path;
        });

        $(document).on('click', '#submit', function() {
            if (!status) return false;
            status = false;

            params = common.formToJson($('form').serialize());
            params = JSON.parse(decodeURIComponent(params));

            var valid = validate(params);

            if (valid) {
                if (url.type == 2) {
                    params['car_id'] = url.refer_id;
                    params['car_num'] = params['car_num'].toUpperCase();
                    common.ajax('POST', '/ride-sharing/setting-car', {'data':params}, true, function(rsp) {
                        if(rsp.data.code == 0) {
                            $.alert('车辆信息编辑成功', '编辑成功', function() {
                                localStorage.removeItem('series');
                                location.href = 'vehicle-alert.html?id=' + url.refer_id;
                            })
                        }else {
                            status = true;
                            $.alert('很抱歉,车辆信息编辑失败,请重试!', '编辑失败');
                        }
                    });
                } else {
                    params['car_num'] = params['car_num'].toUpperCase();
                    common.ajax('POST', '/ride-sharing/setting-car', {'data':params}, true, function(rsp) {
                        if(rsp.data.code == 0) {
                            $.alert('车辆信息提交成功', '提交成功', function() {
                                localStorage.removeItem('series');
                                if (url.refer == 'freeride') {
                                    location.href = 'freeride-post.html';
                                } else {
                                    location.href = 'vehicle-manage.html';
                                }
                            })
                        }else {
                            status = true;
                            $.alert('很抱歉,车辆信息提交失败,请重试!', '提交失败');
                        }
                    });
                }
            }
        });

        function validate (params) {
            //检验车辆品牌是否设置
            if (params['brand_id'] == '' || params['series_id'] == '') {
                $.alert('很抱歉,请选择车型', '验证失败', function () {
                    status = true;
                });
                return false;
            }

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

            //检验车牌号是否正确
            if(!re.test(params['car_num'].toUpperCase())) {
                $.alert('很抱歉,车牌格式不正确,请重新填写!', '验证失败', function () {
                    status = true;
                });
                return false;
            }

            return true;
        }

        $(document).on('keyup', 'input[type=number]', function() {
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
         * 返回
         */
        $(document).on('click', '#back', function() {
            localStorage.removeItem('series');

            if (url.refer == 'freeride') {
                var loupanId = window.localStorage.getItem('loupanId');
                if(!loupanId) loupanId = 0;
                location.href = 'freeride-list.html?id=' + loupanId;
            } else if (url.refer == 'vehicle') {
                location.href = 'vehicle-alert.html?id=' + url.refer_id;
            } else {
                location.href = 'vehicle-manage.html';
            }
        });

        loadData();
        var pings = env.pings;pings();
    });

    $.init();
});
