/**
 * Created by nancy on 2016/11/28.
 */
require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#lucky-list", function (e, id, page) {
        var url = common.getRequest();
        //定义变量
        var tpl = $('#tpl').html(),
            special = $('#special').html(),
            container = $('#container');
        function loadData(){
            common.ajax('GET','/redenvelope/detail',{'id':url.id},false, function (rsp){
                if(rsp.data.code == 0){
                    var data = rsp.data.info;
                    var htm = juicer(special, data);
                    container.append(htm);
                }else {
                    $.alert('很抱歉,服务器失去连接,请稍后...');
                }

            });
        }

        loadData();
        $(document).on('click', '#back', function() {
            window.location.href = 'lucky-list.html';
        });

        var pings = env.pings;pings();
    });

    $.init();
});
