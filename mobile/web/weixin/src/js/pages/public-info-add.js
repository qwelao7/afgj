require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#public-info-add", function (e, id, page) {
        var url = common.getRequest();

        var ids = [],
            names = [],
            status = true,
            key;

        var arr = ['name', 'phone'],
            chineseArr = ['信息名称', '联系电话'];
        
        function loadData() {
            common.ajax('GET', '/community/create-community-info', {}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                    names = data.name;
                    ids = data.id;
                    
                    init();
                    picker();
                } else {
                    $.alert('很抱歉,数据加载失败,请重试!', '加载失败', function () {
                        location.reload();
                    })
                }
            })
        }

        function init() {
            $('#picker').val(names[0]);
            $('input[name=type_id]').val(ids[0]);
            $('input[name=name]').val(names[0]);
        }

        function picker() {
            $('#picker').picker({
                toolbarTemplate: '<header class="bar bar-nav">\
                                <button class="button button-link pull-right close-picker">确定</button>\
                                <h1 class="title">选择信息名称</h1>\
                                </header>',
                cols: [
                    {
                        textAlign: 'center',
                        values: names
                    }
                ],
                onClose: function() {
                    var value = $.trim($('#picker').val()),
                        index = names.indexOf(value);
                    
                    if (index == 5) {
                        $('#others').removeClass('hide');
                        $('input[name=name]').val('');
                    } else {
                        $('#others').addClass('hide');
                        $('input[name=name]').val(value);
                    }

                    $('input[name=type_id]').val(ids[index]);
                }
            });
        }

        /** 校验 **/
        function valid(params) {
            //验证是否为空
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

            //校验手机号
            if (!common.check(params.phone, 2) && !common.check(params.phone, 3)) {
                $.alert('很抱歉,请填写正确的电话号码!', '验证失败', function() {
                    status = true;
                });
                return false;
            }

            return true;
        }

        /** 其他 **/
        $('input[name=other]').on('blur', function() {
           var self = $(this),
               val = $.trim(self.val());

            if (val == '') {
                $.alert('输入的信息名称不能为空!');
            } else {
                $('input[name=name]').val(val);
            }
        });

        $('#back').on('click', function () {
            location.href = 'address-list.html?id=' + url.id;
        });

        /** submit **/
        $('#submit').on('click', function() {
            if (!status) return false;
            status = false;

            var params = common.formToJson($('form').serialize());
            params = JSON.parse(decodeURIComponent(params));

            var result = valid(params);

            if (result) {
                params.community_id = url.id;

                common.ajax('POST', '/community/create-community-info', {data: params}, true, function (rsp) {
                    if (rsp.data.code == 0) {
                        $.alert('社区公共信息创建成功!', '创建成功', function () {
                            location.href = 'public-info.html?id=' + url.id;
                        })
                    } else {
                        $.alert('很抱歉,创建公共信息失败,失败原因:' + rsp.data.message, function () {
                            status = true;
                        })
                    }
                })
            }
        });

        loadData();

        var pings = env.pings;
        pings();
    });

    $.init();
});
