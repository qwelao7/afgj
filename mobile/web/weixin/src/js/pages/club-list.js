require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    /**
     * id -> community_id
     */
    $(document).on('pageInit', '#club-list', function (e, id, page) {
        var url = common.getRequest();

        var tpl = $('#tpl').html(),
            content = $('#content');

        var template = "<div class='tips' style='text-align: center;height: 100%;'>很抱歉,数据错误!</div>";

        common.img();
        var imgCover = function (data) {
            if (!data || data == '' || data == undefined) {
                data = 'circle-default-01.png';
            }

            var qnDomain = common.QiniuDamain;
            return qnDomain + data;
        };
        juicer.register('imgCover', imgCover);

        function loadData() {
            common.ajax('GET', '/community/bbs-list', {
                id: url.id
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data);
                    content.append(html);
                } else {
                    content.append(template);
                }
            });
        }

        function delBBs(bbsId, _content) {
            common.ajax('GET', '/community/delete-bbs', {
                id: bbsId
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    $.alert('该社群删除成功!', '删除成功', function () {
                        _content.remove();
                    });
                } else {
                    $.alert('很抱歉,社群删除失败,请重试!失败原因:' + rsp.data.message, '删除失败');
                }
            })
        }

        $('#back').on('click', function () {
            location.href = 'square-tab-index.html?id=' + url.id;
        });

        $('#add').on('click', function () {
            location.href = 'club-add.html?id=' + url.id;
        });

        $('.JPopup').live('click',function (e) {
            var self = $(this),
                level = self.data('level'),
                root = self.parents('.club-card'),
                bbsId = root.data('id'),
                groups = [];
            e.stopPropagation();

            var buttons1 = [
                {
                    text: '删除',
                    color: 'danger',
                    onClick: function () {
                        $.modal({
                            title: '<div class="font-red">' +
                            '删除' +
                            '</div>',
                            text: '确认删除该社群？',
                            buttons: [
                                {
                                    text: '<span>取消</span>'
                                },
                                {
                                    text: '<span class="font-red">确定</span>',
                                    bold: true,
                                    onClick: function () {
                                        delBBs(bbsId, root);
                                    }
                                }
                            ]
                        })
                    }
                },
                {
                    text: '编辑',
                    onClick: function () {
                        var path = '?id=' + url.id + '&bbs_id=' + bbsId;

                        location.href = 'club-edit.html' + path;
                    }
                }
            ];
            var buttons2 = [
                {
                    text: '取消'
                }
            ];

            //用户等级
            if (level == 1) {
                groups = [buttons1, buttons2];
            } else {
                groups = [buttons1.slice(0,1), buttons2];
            }
            $.actions(groups);
        });

        loadData();

        var pings = env.pings;
        pings();
    });

    $.init();
})