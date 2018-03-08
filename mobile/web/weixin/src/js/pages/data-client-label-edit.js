require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#data-client-label-edit", function (e, id, page) {
        var url = common.getRequest(),
            data = [],
            data1 = {},
            header = $('#header').html(),
            tpl = $('#tpl').html(),
            content = $('#content'),
            title = $('#title'),
            info = {};
        var token = '';
        token = url.token ? url.token : common.getCookie('openid');


        // 跨域ajax请求

        function loadTag() {
            $.ajax({
                url: env.ajax_data + "/pes/tag/" + url.tagid + "?token=" + token + "&customerCode=" + url.id,
                dataType: 'json',
                success: function (rsp) {
                    if (rsp.status == 200) {
                        $.each(rsp.data, function (index, item) {
                            data[index] = item;
                        })
                        data1.data = data;
                        data1.values = [];
                        console.log(data1);
                        $.each(data.values, function (index, item) {
                            data1.values[index] = item;
                        })
                        info.name = data.setting.tgname;
                        var html = juicer(header, info);
                        title.append(html);
                        var htm = juicer(tpl, data1);
                        content.append(htm);
                        var v = window.localStorage.getItem('tag_value');
                        if (v != 'null') {
                            $('#type_in').val(v);
                        }

                    }
                },
                error: function () {
                    window.location.href = 'data-input-auth.html';
                },
            });
        };

        $(document).on('click', '.label-container', function () {
            var self = $(this);
            if (data.setting.input_mode == 1) {
                if (self.hasClass('label-container-unselected')) {
                    self.removeClass('label-container-unselected');
                    self.children('span').removeClass('label-unselected').addClass('label-active');
                    data1.values.push(self.children('span').html().trim());
                    console.log(data1.values);
                } else {
                    self.addClass('label-container-unselected');
                    self.children('span').addClass('label-unselected').removeClass('label-active');
                    var n = data1.values.indexOf(self.children('span').html().trim());
                    data1.values.splice(n, 1);
                    console.log(data1.values);
                }
            } else if (data.setting.input_mode == 3) {
                if (self.hasClass('label-container-unselected')) {
                    self.parent().children('.label-container').addClass('label-container-unselected');
                    self.parent().children('.label-container').children('span').removeClass('label-active').addClass('label-unselected');
                    self.removeClass('label-container-unselected');
                    self.children('span').removeClass('label-unselected').addClass('label-active');
                    data1.values.splice(0, 1);
                    data1.values[0] = self.children('span').html().trim();
                    console.log(data1.values);
                }
            }

        });


        // 提交表单
        $(page).on('click', '#submit', function () {
            if (data.setting.input_mode == 1 && data.setting.input_extend == 1) {
                if ($('#extend').val() != '') {
                    data1.values.push($('#extend').val());
                }

            } else if (data.setting.input_mode == 5) {
                data1.values.length = 0;
                data1.values.push($('#type_in').val());
            }
            console.log(data1.values)
            var pushData = {};
            pushData.customerCode = url.id;
            pushData.tagValues = data1.values.join(',');
            console.log(pushData);

            if (data.setting.input_mode == 5) {
                if (data.setting.input_regexp) {
                    var reg=new RegExp(),
                        error = '',
                        mask='';
                    var input_regexp = JSON.parse(data.setting.input_regexp);
                    $.each(input_regexp, function (index, item) {
                        if (index == 'regexp') {
                            reg = eval(item);
                        } else if(index=='error') {
                            error = item;
                        }else{
                            mask=item;
                        }
                    })
                    var check=reg.test($('#type_in').val());
                    if(check){
                        $.ajax({
                                url: env.ajax_data + '/pes/tag/' + url.tagid + '?token=' + token,
                                type: 'PUT',
                                data: pushData,
                                dataType: 'json',
                                success: function (rsp) {
                                    if (rsp.code = 200) {
                                        $.alert('标签修改成功', function () {
                                            window.location.href = 'data-family-info.html?id=' + url.id + '&parentid=' + url.parentid;
                                        });
                                    } else {
                                        $.alert('标签提交修改失败！请重新提交');
                                    }

                                },
                                error: function () {
                                    $.alert('标签发送失败！');
                                }


                            }
                        )
                    }else{
                        $.alert(error+'。'+mask, '标签错误');
                    }
                }else{
                    $.ajax({
                            url: env.ajax_data + '/pes/tag/' + url.tagid + '?token=' + token,
                            type: 'PUT',
                            data: pushData,
                            dataType: 'json',
                            success: function (rsp) {
                                if (rsp.code = 200) {
                                    $.alert('标签修改成功', function () {
                                        window.location.href = 'data-family-info.html?id=' + url.id + '&parentid=' + url.parentid;
                                    });
                                } else {
                                    $.alert('标签提交修改失败！请重新提交');
                                }

                            },
                            error: function () {
                                $.alert('标签发送失败！');
                            }


                        }
                    )
                }
            }else{
                $.ajax({
                        url: env.ajax_data + '/pes/tag/' + url.tagid + '?token=' + token,
                        type: 'PUT',
                        data: pushData,
                        dataType: 'json',
                        success: function (rsp) {
                            if (rsp.code = 200) {
                                $.alert('标签修改成功', function () {
                                    window.location.href = 'data-family-info.html?id=' + url.id + '&parentid=' + url.parentid;
                                });
                            } else {
                                $.alert('标签提交修改失败！请重新提交');
                            }

                        },
                        error: function () {
                            $.alert('标签发送失败！');
                        }


                    }
                )
            }
        });


        //返回
        $(page).on('click', '#back', function () {
            history.go(-1);
        });

        loadTag();
        var pings = env.pings;
        pings();
    });

    $.init();
});
