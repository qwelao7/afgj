require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#bbs-vote-detail", function (e, id, page) {
        var url = common.getRequest();

        var tpl = $('#tpl').html(),
            container = $('#container');

        //模板自定义函数
        common.img();
        var formatNum = function (data) {
            return parseInt(data) + 1;
        };
        var cent = function(data, params) {
            return Math.round(data/params*100);
        };
        juicer.register('cent', cent);
        juicer.register('formatNum', formatNum);

        function loadData() {
            common.ajax('GET', '/vote/result',{id: url.v_id}, true, function(rsp) {
                if(rsp.data.code == 0) {
                    var data = rsp.data.info,
                        model = juicer(tpl, data);
                    container.append(model);
                }else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无投票结果信息!</h3>";
                    container.append(template);
                }
            });
        }

        $(document).on('click', '#back', function() {
            history.go(-2);
        });

        loadData();

        var pings = env.pings;
        pings();

    });

    $.init();
});
