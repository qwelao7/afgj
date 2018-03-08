require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';
    
    $(document).on("pageInit", "#vehicle-maintenance-add", function (e, id, page) {
        var url = common.getRequest();

        var ids = [],
            names = [],
            ajaxInfo = {},
            status = true,
            key;

        var arr = ['notification_name', 'notification_id', 'last_date', 'last_km'],
            chineseArr = ['养护项目', '养护项目', '维保日期', '维保里程'];

        function loadData() {
            common.ajax('GET', '/vehicle/get-notification-by-car', {id: url.car_id}, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                    ids = data.id;
                    names = data.notification_name;

                    picker();
                    initRender();
                } else {
                    $.alert('很抱歉,获取数据错误,请重试!', '数据错误', function () {
                        history.go(-1);
                    })
                }
            })
        }

        function loadInfo(id) {
            common.ajax('GET', '/vehicle/get-last-notification', {
                car_id: url.car_id,
                notification_id: id
            }, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                    /** init ajaxInfo **/
                    initAjaxInfo(data);

                    /** 更新 **/
                    $('input[name=last_date]').val(ajaxInfo.nowTime);
                    $('input[name=last_km]').val(ajaxInfo.nowKm);
                    $('input[name=exec_shop]').val(ajaxInfo.exec_shop);
                    updateKmMonth(ajaxInfo);
                }
            })
        }

        function picker() {
            $("#project-picker").picker({
                toolbarTemplate: '<header class="bar bar-nav">\
                                <button class="button button-link pull-right close-picker">确定</button>\
                                <h1 class="title">请选择维保项目</h1>\
                                </header>',
                cols: [
                    {
                        textAlign: 'center',
                        values: names  //维保项目名称
                    }
                ],
                onClose: function() {
                    var self = $('#project-picker'),
                        val = $.trim(self.val()),
                        index = names.indexOf(val);

                    if (ids[index] == 0) {
                        $('.project_item:nth-child(2)').removeClass('hide');
                        val = $.trim($('#other').val());
                    } else {
                        $('.project_item:nth-child(2)').addClass('hide');
                    }

                    initPickerVal(ids[index], val);
                    loadInfo(ids[index]);
                }
            });
        }

        function initRender() {
            var initId = (url.id) ? url.id : ids[0],
                initIndex = ids.indexOf(initId),
                initName = names[initIndex];

            $('#project-picker').val(initName);
            if (initId != 0) {
                $('.project_other').addClass('hide');
            }

            initPickerVal(initId, initName);
            loadInfo(initId);
        }

        function initPickerVal(id, name) {
            $('input[name=notification_name]').val(name);
            $('input[name=notification_id]').val(id);
        }

        function updateKmMonth(params) {
            $('input[name=next_km]').val(params.next_km);
            $('input[name=next_month]').val(params.next_month);
        }

        function initAjaxInfo(data) {
            ajaxInfo.nowTime = data.time.now_time;
            ajaxInfo.nowKm = data.time.now_km;
            ajaxInfo.next_km = (data.next_km) ? data.next_km : '';
            ajaxInfo.next_month = (data.next_month) ? data.next_month : '';
            ajaxInfo.record_date = (data.time.record_km_date) ? data.time.record_km_date : '';
            ajaxInfo.exec_shop = (data.exec_shop) ? data.exec_shop : '';
        }

        function valid(params) {
            //验证是否为空
            for(var i in params) {
                if (params[i] == '' || params[i] == undefined) {
                    key = arr.indexOf(i);
                    if (key != -1) {
                        $.alert('很抱歉,' + chineseArr[key] + '不能为空!', '验证失败', function() {
                            status = true;
                        });
                        return false;
                    }
                }
            }

            //间隔月份 || 间隔公里数
            if (params.next_month == '' && params.next_km == '') {
                $.alert('很抱歉, 下次维保间隔月份与间隔公里数,请至少选择一项填写!', '验证失败', function() {
                    status = true;
                });
                return false;
            }

            return true;
        }

        function back() {
            var path = '?car_id=' + url.car_id;

            if (url.id) {
                path = '?id=' + url.car_id;

                location.href = 'vehicle-alert.html' + path;
            } else {
                location.href = 'vehicle-maintenance-record.html' + path;
            }
        }

        $(document).on('keyup', 'input[name=exec_fee]', function() {
            var _this = $(this),
                reg = _this.val().match(/\d+\.?\d{0,2}/),
                txt = '';
            if (reg != null)
            {
                txt = reg[0];
            }
            _this.val(txt);
        });

        $('#other').on('blur', function() {
            var self = $(this),
                value = $.trim(self.val());

            $('input[name=notification_name]').val(value);
        });

        $('input[name=last_date]').on('change', function() {
            var self = $(this),
                value = $.trim(self.val());

            var cur = new Date(value.replace(/-/g,   "/")),
                record = new Date(ajaxInfo.record_date.replace(/-/g,   "/"));

            if (cur.getTime() < record.getTime()) {
                status = false;
                $.alert('很抱歉,维保日期不能晚于车辆更新里程日期 ( 即 ' + ajaxInfo.record_date + ' ) !', '日期错误', function() {
                    self.val(ajaxInfo.nowTime);
                    status = true;
                });
                return false;
            }


            ajaxInfo.nowTime = value;
        });

        $('input[name=last_km]').on('blur', function() {
            var self = $(this),
                val = $.trim(self.val());

            if (parseInt(val) < parseInt(ajaxInfo.nowKm)) {
                status = false;
                $.alert('很抱歉,维保里程不能小于车辆当前里程 ( 即 ' + ajaxInfo.nowKm + 'km ) !', '里程错误', function() {
                    self.val(ajaxInfo.nowKm);
                    status = true;
                });
            }
        });

        $('#back').on('click', function() {
            back();
        });

        $('#submit').on('click', function() {
            if (!status) return false;
            status = false;

            var params = common.formToJson($('form').serialize()),
                key;
            params = JSON.parse(decodeURIComponent(params));

            var result = valid(params);

            if (result) {
                params['car_id'] = url.car_id;
                common.ajax('POST', '/vehicle/add-notification', {
                    'data': params
                }, true, function(rsp) {
                    if (rsp.data.code == 0) {
                        $.alert('维保记录添加成功!', '添加成功', function() {
                            back();
                        })
                    } else {
                        $.alert('维保记录添加失败!失败原因:' + rsp.data.message,'添加失败', function() {
                            status = true;
                        })
                    }
                })
            }
        });

        loadData();

        var pings = env.pings;pings();
    });

    $.init();
});
