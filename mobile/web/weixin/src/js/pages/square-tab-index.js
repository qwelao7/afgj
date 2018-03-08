require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on('pageInit', '#square-tab-index', function (e, id, page) {
        var url = common.getRequest();

        var container = $('#container'),
            title = $('#title').html(),
            tpl = $('#tpl').html();

        var hasAuth = false,
            name = '';

        var defaultThumb = 'http://pub.huilaila.net/square-index-bg00.jpg';

        common.img();

        $('.to-detail').live('click', function() {
            var self = $(this),
                needInentify = self.data('needauth'),
                toUrl = self.data('url');

            if (!hasAuth && needInentify == 1) {
                renderModal();
            } else {
                location.href = toUrl;
            }
        });
        
        $('#back').on('click', function() {
            if (url.ref && url.ref != '') {
                location.href = url.ref;
            } else {
                location.href = '/square-tab-list.html'
            }
        });

        function loadData() {
            common.ajax('GET', '/official/detail', {id: url.id}, true, function(rsp) {
                if(rsp.data.code == 0) {
                    var data = rsp.data.info;
                    data['defaultThumb'] = defaultThumb;

                    name = data.name;
                    
                    var html = juicer(title, data),
                        htm = juicer(tpl, data);
                    
                    container.append(htm);
                    $('header').append(html);

                    if (data['end_date'] != '0') {
                        $youziku.load(".end_desc", "432b8be8112e466b92a3eb642724f109", "HanWangKaiMedium-Gb5");
                    } else {
                        $youziku.load("input[name=other]", "432b8be8112e466b92a3eb642724f109", "HanWangKaiMedium-Gb5");
                    }
                    $youziku.draw();

                    loadAuth();
                }else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>暂无相关数据!</h3>";
                    container.append(template);
                }
            })
        }

        function loadAuth() {
            common.ajax('GET', '/official/community-auth', {
                'id': url.id
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                    hasAuth = data.hasauth;
                }
            })
        }

        function renderModal() {
            $.modal({
                title: '温馨提示',
                text: '您暂未拥有该小区房产,请先添加房产!',
                buttons: [
                    {
                        text: '知道了'
                    },
                    {
                        text: '添加房产',
                        bold: true,
                        onClick: function () {
                            localStorage.setItem('community', '{"id":' +  url.id +   ',"name":"' + name + '"}');

                            window.location.href = 'estate-add.html?type=0';
                        }
                    }
                ]
            });
        }

        function pushHistory() {
            var state = {
                title: document.title,
                url: location.href
            };

            window.history.pushState(state,'', objs.url)
        }

        loadData();

        var pings = env.pings;
        pings();
    });

    $.init();
});