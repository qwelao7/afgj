require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on('pageInit', '#points-share', function (e, id, page) {
        var url = common.getRequest();

        var status = true;

        var searchForm = $('#searchForm');

        var params = {
            'type': 1,
            'community_id': 0,
            'business_id': 0,
            'expire_time': ''
        };

        var tpl = $('#tpl').html(),
            items = $('#items').html(),
            result = $('#result'),
            points = $('#points'),
            list = $('#list');

        var num = 2,
            loading = false,
            uid = 0,
            nums;

        $('#search_submit').on('click', function (e) {
            var search = $.trim($('#search').val());

            if (!status) return false;
            status = false;

            if (search == '') {
                $.alert('请输入搜索内容!', '温馨提示', function () {
                    status = true;
                });
                return false;
            }

            searchUser(search);
        });

        $('#search').on('change', function () {
            var searchVal = $(this).val().trim();

            var isAndroid = common.isAndroid();

            searchVal = common.filterString(searchVal, isAndroid);

            if (searchVal == "") {
                result.empty();

                $('#hint').removeClass('hide');
                list.addClass('hide');

                $.attachInfiniteScroll($('.infinite-scroll'));
                $('.infinite-scroll-preloader').show();
                num = 2;
            }
        });

        function searchUser(str) {
            result.empty();

            common.ajax('GET', '/user/search-user', {
                'keyword': str,
                'page': 1
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;

                    nums = data.pagination.pageCount;

                    if (data.pagination.total == 0) {
                        var tem = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无搜索结果!</h3>";
                        result.append(tem);

                        removeInifite();
                    }

                    var html = juicer(tpl, data);

                    result.append(html);

                    $('#hint').addClass('hide');
                    list.removeClass('hide');

                    if (nums == 1) {
                        removeInifite();
                    }
                } else {
                    var tem = "<h3 style='text-align: center;margin-top: 4rem;'>很抱歉,暂无搜索结果!</h3>";
                    result.append(tem);

                    $('#hint').addClass('hide');
                    list.removeClass('hide');

                    removeInifite();
                }

                status = true;
            })
        }

        function removeInifite() {
            // 加载完毕，则注销无限加载事件，以防不必要的加载
            $.detachInfiniteScroll($('.infinite-scroll'));
            // 删除加载提示符
            $('.infinite-scroll-preloader').remove();
            return false;
        }

        function loadData() {
            common.ajax('GET', '/points/send-point-type', {}, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data,
                        html = juicer(items, data);

                    points.append(html);
                } else {
                    $.alert('很抱歉,数据获取失败,请重试!', '数据错误', function () {
                        history.go(-1);
                    })
                }
            })
        }

        function init() {
            list.addClass('hide');

            loadData();
        }

        function sharePoints(num) {
            renderNav('提交中...', true);

            common.ajax('GET', '/points/send-point', {
                'point_type': params.type,
                'to_user_id': uid,
                'community_id': params.community_id,
                'business_id': params.business_id,
                'expire_time': params.expire_time,
                'point': num
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    $.alert('友元分享成功!', '分享成功', function () {
                        renderNav('确认分享', false);
                    });
                    ajaxCallBack(num);
                } else {
                    $.alert('很抱歉,分享失败!失败原因:' + rsp.data.message, '分享失败', function () {
                        renderNav('确认分享', false);
                    });
                }
                status = true;
            });
        }

        function ajaxCallBack(num) {
            var checked = $('input[name=send_point]:checked'),
                checkParent = checked.parent(),
                grandParent = checked.parents('.grey'),
                rootParent = checked.parents('.expire_item').find('.total_can_use_points'),
                canUsePoints = checkParent.data('points');

            if (parseInt(num) < parseInt(canUsePoints)) {
                var leftPoints = parseInt(canUsePoints) - parseInt(num);

                checkParent.find('.can_use_points').text(leftPoints + '友元');
                checkParent[0].setAttribute('data-points', leftPoints);
                rootParent.text(parseInt(rootParent.text()) - parseInt(num));
            } else {
                if (grandParent.children().length == 1) {
                    grandParent.parents('.expire_item').remove();
                } else {
                    checkParent.remove();
                    rootParent.text(parseInt(rootParent.val()) - parseInt(num));
                }
            }
        }

        function renderNav(text, during) {
            var template = '<a class="tab-item external share_btn_inside' + ((during) ? ' cancel' : '') + '"><span class="' + ((during) ? ' font-dark' : ' font-white') + '">' + text + '</span></a>';

            $('.share_btn_inside').replaceWith(template);
        }

        $(document).on('infinite', '.infinite-scroll', function () {
            if (loading) return;
            loading = true;

            if (num > nums) {
                removeInifite();
            }

            common.ajax('GET', '/user/search-user', {
                'keyword': str,
                'page': num
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    loading = false;
                    var data = rsp.data.info,
                        html = juicer(tpl, data);
                    result.append(html);

                    num++;
                }
            });

            $.refreshScroller();
        });

        $('.user-item').live('click', function () {
            var self = $(this);

            uid = self.data('uid');

            $.popup('.popup-share');
        });

        $('#back').on('click', function () {
            window.location.href = 'points-index.html';
        });

        $('.normal-list').live('click', function () {
            var self = $(this),
                brother = self.next();

            brother.toggleClass('hide');
        });

        $('.share_btn').on('click', function () {
            var self = $(this);

            if (!status) return false;
            status = true;

            var checked = $('input[name=send_point]:checked');

            if (checked.length == 0) {
                $.alert('请选择要赠送的友元!', '温馨提示', function () {
                    status = true;
                });
            } else {
                params['type'] = checked.parent().data('type');
                params['community_id'] = params['type'] == 1 ? 0 : checked.parent().data('community');
                params['business_id'] = params['type'] == 1 ? 0 : checked.parent().data('business');
                params['expire_time'] = checked.parent().data('expire')

                $.modal({
                    title: '<span class="h2">请填写分享的友元数量</span>',
                    text: '<div>' +
                    '<p class="vehicle-modal-text"><span>友元总数</span><input type="number" name="numbers" value="' + checked.parent().data('points') + '"/></p>' +
                    '</div>',
                    buttons: [
                        {
                            text: '取消',
                            onClick: function () {
                                $('#modal').removeClass('modal-overlay-visible');

                                status = true;
                            }
                        },
                        {
                            text: '确定',
                            bold: true,
                            onClick: function () {
                                var num = $.trim($('input[name=numbers]').val());

                                if (num == "" || num == '0' || num == undefined) {
                                    $.alert('很抱歉,分享友元数不能为空!', '温馨提示', function () {
                                        status = true;
                                    })
                                } else {
                                    sharePoints(num);
                                    status = true;
                                }
                            }
                        }
                    ]
                });

            }
        });

        init();

        var pings = env.pings;
        pings();
    });

    $.init();
});