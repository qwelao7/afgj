require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#lucky-list", function (e, id, page) {
        //定义变量
        var  showLast = false,
            showHistory = true,
            reid = [];
        var tpl = $('#tpl').html(),
            special = $('#special').html(),
            history = $('#history').html(),
            container = $('#container');
        function loadData(){
            common.ajax('GET','/redenvelope/list',{},false, function (rsp){
                if(rsp.data.code == 0){
                    var data = rsp.data.info;
                    var html = juicer(tpl, data),
                        htm = juicer(special, data);
                    container.append(htm);
                    container.append(html);
                }else {
                    $.alert('很抱歉,服务器失去连接,请稍后...');
                }

            });
        }

        $(document).on('click','.detail',function(){
            var id = $(this).data("id"),
                self = $(this);
            $(this).parent().siblings().find('.red-detail').addClass("hide");
            $(this).nextAll().toggleClass("hide");
            if(reid.indexOf(id) == -1){
                common.ajax('GET','/redenvelope/detail',{'id':id},false, function (rsp){
                    if(rsp.data.code == 0){
                        reid.push(id);
                        var data = rsp.data.info;
                        var xhtml = juicer(history, data);
                        self.after(xhtml);
                    }else {
                        $.alert('很抱歉,服务器失去连接,请稍后...');
                    }
                });
            }
        });
        loadData();
        $(document).on('click', '#back', function() {
            window.location.href = 'lucky-money.html';
        });

        var pings = env.pings;pings();
    });

    $.init();
});
