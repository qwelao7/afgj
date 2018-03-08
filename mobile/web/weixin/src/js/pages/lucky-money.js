require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#lucky-money", function (e, id, page) {

        var endTime;var beginTime;var isJoin=false;
        
        function loadData() {
            var storage = window.localStorage;

            common.ajax('GET', '/redenvelope/index', {}, true, function(rsp) {
                if(rsp.data.code == 0) {
                    var data = rsp.data.info;
                    endTime = parseInt(data.endTime)*1000;
                    beginTime = parseInt(data.startTime)*1000;
                    storage.setItem('reid',data.id);
                    setInterval(GetRTime, 1000);
                } else if(rsp.data.code == 2){
                    $('.btn-lucky-active').css('display', 'none');
                    $('.btn-lucky-disabled').css('display', 'block');
                    $('.btn-lucky-disabled').html(rsp.data.message);
                } else if(rsp.data.code == 3){
                    isJoin = true;
                    var data = rsp.data.info;
                    storage.setItem('reid',data.id);
                    $('.btn-lucky-active').css('display', 'block');
                    $('.btn-lucky-disabled').css('display', 'none');
                    $('.btn-lucky-active').html(rsp.data.message);
                }
            });
        }
        
        function GetRTime() {
            var nowTime = (new Date()).getTime();
            var t = beginTime - nowTime;
            var d = 0;
            var h = 0;
            var m = 0;
            var s = 0;
            if (t > 0) {
                d = Math.floor(t / 1000 / 60 / 60 / 24);
                if (d < 10) {
                    d = '0' + d;
                }
                h = Math.floor(t / 1000 / 60 / 60 % 24);
                if (h < 10) {
                    h = '0' + h;
                }
                m = Math.floor(t / 1000 / 60 % 60);
                if (m < 10) {
                    m = '0' + m;
                }
                s = Math.floor(t / 1000 % 60);
                if (s < 10) {
                    s = '0' + s;
                }
                if (parseInt(d) > 0) {
                    $('.clock-d').css('display', 'block');
                    $('.clock-h').css('display', 'none');
                    $('.clock-m').css('display', 'none');
                }
                else if (parseInt(d) == 0 && parseInt(h) > 0) {
                    $('.clock-d').css('display', 'none');
                    $('.clock-h').css('display', 'block');
                    $('.clock-m').css('display', 'none');
                } else if (parseInt(d) == 0 && parseInt(h) == 0) {
                    $('.clock-d').css('display', 'none');
                    $('.clock-h').css('display', 'none');
                    $('.clock-m').css('display', 'block');
                }
                document.getElementById("t_d1").innerHTML = d.toString().substr(0, 1);
                document.getElementById("t_d2").innerHTML = d.toString().substr(1, 1);
                document.getElementById("t_h1").innerHTML = h.toString().substr(0, 1);
                document.getElementById("t_h2").innerHTML = h.toString().substr(1, 1);
                document.getElementById("t_h11").innerHTML = h.toString().substr(0, 1);
                document.getElementById("t_h21").innerHTML = h.toString().substr(1, 1);
                document.getElementById("t_m1").innerHTML = m.toString().substr(0, 1);
                document.getElementById("t_m2").innerHTML = m.toString().substr(1, 1);
                document.getElementById("t_m11").innerHTML = m.toString().substr(0, 1);
                document.getElementById("t_m21").innerHTML = m.toString().substr(1, 1);
                document.getElementById("t_s1").innerHTML = s.toString().substr(0, 1);
                document.getElementById("t_s2").innerHTML = s.toString().substr(1, 1);
            } if(t <= 0) {
                if( endTime > nowTime) {
                    $('.btn-lucky-active').css('display', 'block');
                    $('.btn-lucky-disabled').css('display', 'none');
                }
                clearInterval(GetRTime);
            }

        }
        /** 绑定按钮点击 **/
        $(page).on('click', '#join-game', function () {
            if(isJoin) {
                window.location.href = "lucky-result.html";
                return;
            }
            $.ajax({
                type: 'GET',
                url: common.WEBSITE_API + '/redenvelope/join',
                dataType: 'json',
                beforeSend:function(xhr, settings){
                    $('#join-game').prop('disabled', true);
                },
                success: function (rsp) {
                    if (rsp.data.code == 0 ||rsp.data.code == 1 ) {
                        window.location.href = "lucky-result.html";
                    } else {
                        $.alert(rsp.data.message);
                        storage.setItem('join-game',0);
                    }
                },
                error: function (xhr, type) {
                    $.alert('很抱歉,服务器失去联系,请等待...');
                    $('#join-game').prop('disabled', false);
                }
            });


        });

        function preLoading() {
            common.ajax('GET', '/redenvelope/has-lucky', {}, false, function(rsp) {
                var data = rsp.data.info;

                if(data) {
                    loadData();
                }else {
                    window.location.href = 'lucky-break.html';
                }
            })
        }

        preLoading();

        var pings = env.pings;pings();
    });

    $.init();
});
