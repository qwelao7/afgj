require('../../css/style.css');
require('../../css/index.css');
var LuckyCard = require('../lib/lucky-card.js');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#square-tab-lottery", function (e, id, page) {
        var url = common.getRequest();

        var tpl = $('#tpl').html(),
            container = $('#container');

        function loadAuth () {
            common.ajax('GET', '/game-scratch/game-auth', {
                'id': url.id
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data);

                    container.append(html);

                    luckCardRender(data);
                } else {
                    var msg = {'message': rsp.data.message},
                        htm = juicer(tpl, msg);
                    
                    container.append(htm);
                }
            })
        }

        function luckCardRender (data) {
            LuckyCard.case({
                ratio: .5
            },function() {
                this.clearCover();
                // 执行回调
                callback(data);

                $('#card').css('opacity', 1);
            });
        }

        function callback(data) {

            common.ajax('get', '/game-scratch/game-start', {
                'id': data.scid,
                'card_id': data.id
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    $.alert('恭喜您成功获得' + data.point + '友元!', '恭喜获奖', function () {
                        window.location.reload();
                    });
                } else {
                    $.alert(rsp.data.message, '温馨提醒', function() {
                        window.location.reload();
                    });
                }
            })
        }
        
        loadAuth();

        var pings = env.pings;
        pings();
    });

    $.init();
});