require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    /**
     * 1 -> 正常流程 从 芝麻信用分页面进入 credit-index
     * 2 -> 活动报名页面进入 url.event_id
     */

    $(document).on("pageInit", "#credit-auth", function (e, id, page) {
        var url = common.getRequest();

        var status = true,
            transArr = ['name', 'idcard'],
            chineseArr = ['姓名', '身份证号'],
            params,
            error = false;

        $('#back').click(function () {
            location.href = common.ectouchUrl + '&c=user&a=index';
        });

        $(document).on('click', '#submit', function () {
            if (!status) return false;
            status = false;
            
            var self = $(this);
            var result = validateForm();
            if (result) {
                checkDetail(params, 'idcard');

                // 若从活动入口进来 修改重定向地址
                if (url.event_id && url.event_id != 0 && url.event_id != undefined) {
                    params['to_url'] = 'b';
                    params['event_id'] = url.event_id;
                } else {
                    params['to_url'] = 'a';
                }

                if (!error) {
                    self.find('span').html('授权中...');
                    self.css('backgroundColor', '#888 !important');
                    
                    common.ajax('GET', '/zhima/identify', params, true, function(rsp) {
                        if (rsp.data.code == 0) {
                            var data = rsp.data.info;
                            self.find('span').html('下一步');

                            location.href = data.url;
                        } else {
                            $.alert('很抱歉,提交失败,请重试!', '提交失败', function () {
                                status = true;
                            });
                            self.find('span').html('确认授权');
                            self.css('backgroundColor', '#009042 !important');
                        }
                    })
                }
            }
        });
        
        function validateForm() {
            var key;
            params = common.formToJson($('form').serialize());
            params = JSON.parse(decodeURIComponent(params));

            //检验参数是否为空
            for(var i in params) {
                if (params[i] == '' || params[i] == undefined) {
                    key = transArr.indexOf(i);
                    $.alert('很抱歉,' + chineseArr[key] + '不能为空!', '验证失败', function() {
                        status = true;
                    });
                    return false;
                }
            }
            return true;
        }

        function checkDetail(params, type) {
            var cur = $('input[name=' + type + ']');
            var text = $.trim(cur.val());

            switch(type) {
                case 'mobile':
                    var result = common.check(text, 2);
                    if (!result){
                        errAlert('手机号');
                    }
                    break;
                case 'age':
                    if (text < 0 || text > 100) {
                        errAlert('年龄');
                    }
                    break;
                case 'idcard':
                    var reg =  /(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/;
                    if (!reg.test(text)) {
                        errAlert('身份证号');
                    }
                    break;
            }
        }

        function loadData () {
            common.ajax('GET', '/zhima/index', {}, false, function (rsp) {
                if (rsp.data.code == 0) {
                    if (url.event_id && url.event_id != 0 && url.event_id != undefined) {
                        var path = '?event_id=' + url.event_id;

                        location.href = 'event-delievry-address.html' + path;
                    } else {
                        location.href = 'credit-index.html';
                    }
                }
            })
        }

        /** alert **/
        function errAlert(text) {
            error = true;
            $.alert('很抱歉,您填写的' + text + '不合法,请重新填写', '提交失败', function() {
                status = true;
            });
        }

        loadData();
        var pings = env.pings;pings();
    });
    $.init();
})
