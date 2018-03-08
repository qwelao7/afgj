require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#bbs-vote-detail", function (e, id, page) {
        var url = common.getRequest();
        common.img();

        var detail = $('#detail').html(),
            navbar = $('#navbar').html(),
            content = $('#content');

        var status = true,
            checkedList = [],
            v_id,
            is_show;
        
        var thumbSize = '?imageMogr2/thumbnail/!700x392r/gravity/center/crop/700x392';

        function formatNum(data) {
            return parseInt(data) + 1;
        }

        juicer.register('formatNum', formatNum);

        function loadData() {
            common.ajax('GET', '/vote/info', {id: url.id}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                        //已过期且不显示结果
                    if (data.state.expired && data.vote.is_show == 0) {
                        window.location.href = 'vote-expire.html?m_id=' + url.msId;
                    }else if (data.vote.is_show == 1 && (data.expired || data.state.voted)) {
                        //已过期但显示结果 \ 未过期已投票显示结果
                        window.location.href = 'bbs-vote-result.html?m_id=' + url.msId + '&v_id=' + url.id;
                    }else if (data.state.voted && data.vote.is_show == 0) {
                        //已投票但不显示结果
                        window.location.href = 'vote-success.html?m_id=' + url.msId;
                    }else{
                        //未过期且未投票
                        v_id = data.vote.id;
                        is_show = data.vote.is_show;
                        var html = juicer(detail, data),
                            htm = juicer(navbar, {});

                        content.append(html);
                        $('header').after(htm);
                    }
                } else {
                    var template = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无投票详情!</h3>";
                    content.append(template);
                }
            })
        }

        $(document).on('click', '.to-vote', function () {
            var self = $(this),
                join = self.data('join');

        });

        $(document).on('click', '.vote-option-item', function (e) {
            e.stopPropagation();
            e.preventDefault();

            var self = $(this),
                id = self.data('id'),
                checked = self.find('input').prop('checked'),
                parents = self.parents('.media-list'),
                type = parents.data('votetype');


            if(checked) {
                self.find('input').prop('checked', false);
            }else {
                self.find('input').prop('checked', true);
            }

            //单选
            if (type == 1) {
                self.siblings().find('input').prop('checked', false);
                if (!checked) {
                    parents[0].setAttribute('data-checked', id);
                } else {
                    parents[0].setAttribute('data-checked', '');
                }
            } else if (type == 2) {
                if(!checked) {
                    checkedList.push(id);
                }else {
                    var index = checkedList.indexOf(id);
                    checkedList.splice(index, 1);
                }
                parents[0].setAttribute('data-checked',checkedList);
            }
        });

        $(document).on('click', '#to-vote', function () {
            if (!status) return;
            status = false;

            var self = $(this),
                voted = self.data('voted'),
                error = '';

            var arrs = $('.media-list').pluck('dataset'),
                arr = [];
            $.each(arrs, function(index,item) {
                if(!item.checked || item.checked == undefined) {
                    error = index + 1;
                    return false;
                }else {
                    var array = item.checked.split(',');
                    arr = arr.concat(array);
                }
            });
            if(error != '') {
                $.alert('很抱歉,问题' + error + '未选择!');
                status = true;
                return;
            }

            common.ajax('POST', '/vote/join', {'options': arr, 'v_id': v_id}, true, function(rsp) {
                if(rsp.data.code == 0) {
                    if(is_show == 1) {
                        window.location.href = 'bbs-vote-result.html?m_id'+ url.msId + '&v_id=' + url.id;
                    }else {
                        window.location.href = 'vote-success.html?m_id=' + url.msId;
                    }
                }else {
                    $.alert('很抱歉,投票未成功,请重试!');
                    status = true;
                }
            });
        });

        $(document).on('click', '#back', function() {
            history.go(-1);
        });

        loadData();

        var pings = env.pings;
        pings();

    });

    $.init();
});
