require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#event-signin", function (e, id, page) {
        var url = common.getRequest();

        var header = $('header'),
            title = $('#title').html();

        var status = true;

        var arr = ['code', 'num'],
            transArr = ['签到数字码', '签到人数'],
            key;

        function loadData() {
            common.ajax('GET', '/events/admin-sign-in', {
                id: url.events_id
            }, true, function (rsp) {
                var data = '';
                if (rsp.data.code == 0) {
                    data = rsp.data.info;
                    data['event']['title'] += '签到';
                } else {
                    data = {'event': {'title': '活动签到'}}
                }

                var html = juicer(title, data);
                header.prepend(html);
            })
        }

        function init() {
            if (url.code && url.code != undefined) {
                $('input[name=code]').val(url.code);
            }
        }

        function valid(params) {
            for (var i in params) {
                if (params[i] == '' || params[i] == undefined) {
                    key = arr.indexOf(i);
                    $.alert('很抱歉,' + transArr[key] + '不能为空!', '验证失败', function () {
                        status = true;
                    });
                    return false;
                }
            }
            return true;
        }

        function renderAgain() {
            history.replaceState(null, document.title, location.href.split('&')[0]);
            $('form')[0].reset();
            url = common.getRequest();
        }
        
        function submit(params) {
            common.ajax('GET', '/events/sign-in', {
                events_id: url.events_id,
                code: params['code'],
                num: params['num']
            }, true, function(rsp) {
                if (rsp.data.code == 0) {
                    $.alert('该用户已成功签到!', '签到成功', function() {
                        status = true;

                        renderAgain();
                    })
                } else {
                    $.alert('很抱歉,签到失败!失败原因:' + rsp.data.message, '签到失败', function() {
                        status = true;
                    })
                }
            })
        }

        $('#back').on('click', function () {
            location.href = 'event-detail.html?id=' + url.events_id;
        });

        $(document).on('keyup', 'input[name=num]', function() {
            var _this = $(this),
                reg = _this.val().match(/\d+/),
                txt = '';
            if (reg != null)
            {
                txt = reg[0];
            }
            _this.val(txt);
        });


        $('#submit').on('click', function () {
            if (!status) return false;
            status = false;

            var params = common.formToJson($('form').serialize());
            params = JSON.parse(decodeURIComponent(params));
            
            if (valid(params)) {
                submit(params);
            }
        });

        init();
        loadData();

        var pings = env.pings;
        pings();
    });

    $.init();
});