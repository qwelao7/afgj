require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#public-info-error", function (e, id, page) {
        var url = common.getRequest();

        var errs = [],
            errIds = [],
            reasons = [],
            reasonIds = [],
            status = true;

        function loadData() {
            common.ajax('GET', '/community/community-info-feedback', {id: url.id}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                    errs = data.info.name;
                    errIds = data.info.id;

                    reasons = data.feedback.value;
                    reasonIds = data.feedback.key;

                    init();
                    errPikcer();
                    reasonPicker();
                } else {
                    $.alert('很抱歉,数据加载失败,请重试!', '加载失败', function () {
                        location.reload();
                    })
                }
            })
        }
        
        function init() {
            $('#err-picker').val(errs[0]);
            $('#reason-picker').val(reasons[0]);
            
            $('input[name=cpi_id]').val(errIds[0]);
            $('input[name=feedback_reason]').val(reasonIds[0]);
        }

        function errPikcer() {
            $('#err-picker').picker({
                toolbarTemplate: '<header class="bar bar-nav">\
                                <button class="button button-link pull-right close-picker">确定</button>\
                                <h1 class="title">选择错误信息</h1>\
                                </header>',
                cols: [
                    {
                        textAlign: 'center',
                        values: errs
                    }
                ],
                onClose: function() {
                    var value = $.trim($('#err-picker').val()),
                        index = errs.indexOf(value);

                    $('input[name=cpi_id]').val(errIds[index]);
                }
            });
        }

        function reasonPicker() {
            $('#reason-picker').picker({
                toolbarTemplate: '<header class="bar bar-nav">\
                                <button class="button button-link pull-right close-picker">确定</button>\
                                <h1 class="title">选择错误原因</h1>\
                                </header>',
                cols: [
                    {
                        textAlign: 'center',
                        values: reasons
                    }
                ],
                onClose: function() {
                    var value = $.trim($('#reason-picker').val()),
                        index = reasons.indexOf(value);

                    $('input[name=feedback_reason]').val(reasonIds[index]);
                }
            });
        }

        $('#submit').on('click', function () {
            if (!status) return false;
            status = false;

            var params = common.formToJson($('form').serialize());
            params = JSON.parse(decodeURIComponent(params));

            common.ajax('POST', '/community/community-info-feedback', {'data': params}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    $.alert('上报信息错误成功!', '上报成功', function () {
                        location.href = 'public-info.html?id=' + url.id;
                    })
                } else {
                    $.alert('很抱歉,上报信息错误失败,失败原因:' + rsp.data.message, function () {
                        status = true;
                    })
                }
            })
        });
        
        loadData();

        var pings = env.pings;pings();
    });

    $.init();
});
