require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#equip-care-add", function (e, id, page) {
        var url = common.getRequest();

        var tpl = $('#tpl').html(),
            container = $('#container');

        var arr = ['last_date', 'next_month'],
            chineseArr = ['养护时间', '下次养护间隔月份'];

        var status = true,
            key,
            serverTime,
            path,
            arrs = [],
            list = [];

        /**
         * 设置保养内容
         */
        $('input[name=custom_content]').live('blur', function () {
            var self = $(this),
                value = $.trim(self.val());

            if (list.indexOf(value) != -1) {
                $.alert('请填写与默认养护内容不一致的养护信息', '填写错误', function () {
                    self.val('');
                });
                return false;
            }

            updateContent(value, 2);
        });

        $('input[name=extra_content]').live('change', function () {
            var self = $(this),
                value = self.val(),
                index;
            if (self.prop('checked')) {
                arrs.push(value);
            } else {
                index = arrs.indexOf(value);
                arrs.splice(index, 1);
            }

            updateContent(arrs, 1);
        });

        /**
         * 提交内容
         */
        $('#add').live('click', function () {
            if (!status) return false;
            status = false;

            var params = common.formToJson($('form').serialize());
            params = JSON.parse(decodeURIComponent(params));

            var result = validate(params);

            if (params.content != '') {
                params.content = (params.content_name != '') ? params.content + ',' + params.content_name : params.content;
            } else {
                params.content = (params.content_name != '') ? params.content_name : params.content;
            }

            if (result) {
                common.ajax('POST', '/facilities/add-notification', {'data': params, 'id': url.id}, true, function (rsp) {
                    if (rsp.data.code == 0) {
                        $.alert('养护信息添加成功!', '添加成功', function () {
                            path = '?id=' + url.id + '&address=' + url.address;
                            path = (url.refer) ? path + '&refer=' + url.refer : path;
                            location.href = 'equip-care-list.html' + path;
                        })
                    } else {
                        $.alert('很抱歉,添加养护信息失败,失败原因:' + rsp.data.message, '添加失败', function () {
                            status = true;
                        });
                    }
                })
            }
        });

        /**
         * 特殊校验
         * 养护日期不能大于当前日期
         */
        $('input[name=last_date]').live('change', function () {
            var self = $(this),
                val = $.trim(self.val()),
                time = new Date(val);

            if (time > new Date(serverTime)) {
                $.alert('很抱歉,您填写的养护日期大于当前时间,请重新填写', '验证失败');
                self.val(serverTime);
            }
        });

        /**
         * 输入为金额 / 日期
         */
        $(document).on('keyup', 'input[name=fee]', function () {
            var _this = $(this),
                reg = _this.val().match(/\d+\.?\d{0,2}/),
                txt = '';
            if (reg != null) {
                txt = reg[0];
            }
            _this.val(txt);
        });

        $(document).on('keyup', 'input[name=next_month]', function () {
            var _this = $(this),
                reg = _this.val().match(/\d+/),
                txt = '';
            if (reg != null) {
                txt = reg[0];
            }
            _this.val(txt);
        });

        /**
         * 返回
         */
        $('#back').on('click', function () {
            path = '?id=' + url.id + '&address=' + url.address;
            path = (url.refer) ? path + '&refer=' + url.refer : path;

            location.href = 'equip-care-list.html' + path;
        });

        /**
         * 更新保养内容
         * type 1-选择 2-自定义
         * @param value type
         */
        function updateContent(value, type) {
            if (type == 1) {
                $('input[name=content]').val(value.join(','));
            } else if (type == 2) {
                $('input[name=content_name]').val(value);
            }
        }

        /**
         * 加载数据
         */
        function loadData() {
            common.ajax('GET', '/facilities/add-notification', {'id': url.id}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data);

                    list = data.list;

                    container.append(html);
                    init(data)
                }
            })
        }

        /**
         * 参数初始化
         */
        function init(data) {
            $('input[name=extra_content]').eq(0).prop('checked', true);
            $("input[name=last_date]").calendar({
                'value': [data.date]
            });
            $('input[name=last_date]').val(data.date);
            $('input[name=next_month]').val(data.month);

            var value = $.trim($('input[name=extra_content]').eq(0).val());
            arrs.push(value);
            updateContent(arrs, 1);

            serverTime = data.date;
        }

        /**
         * 检验呢内容
         */
        function validate(params) {
            //检验参数是否为空
            for (var i in params) {
                key = arr.indexOf(i);
                if (key != -1) {
                    if (params[i] == '' || params[i] == undefined) {
                        $.alert('很抱歉,' + chineseArr[key] + '不能为空!', '验证失败', function () {
                            status = true;
                        });
                        return false;
                    }
                }
            }

            /** 特殊校验 **/
            if (params.content == '' && params.content_name == '') {
                $.alert('很抱歉,养护内容不能为空!', '验证失败', function () {
                    status = true;
                });
                return false;
            }

            return true;
        }

        loadData();

        var pings = env.pings;
        pings();
    });

    $.init();
});
