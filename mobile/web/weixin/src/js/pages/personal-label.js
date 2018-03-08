require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function () {
    'use strict';

    $(document).on("pageInit", "#personal-label", function (e, id, page) {
        var tpl = $('#tpl').html(),
            container = $('#container');

        var status = true,
            tem = '<p id="label-none">您暂未添加标签</p>';

        $(document).on('click','#label-add', function () {
            $.prompt('自定义标签', function (value) {
                if(value.length>4){
                    $.alert('自定义标签最多四个字符！');
                    return;
                }else{
                    addSkill(value);
                }

            });
        });

        /**
         * 删除标签
         */
        $(document).on('click', '.label-selected', function() {
            if (!status) return false;
            status = false;

            var self = $(this),
                text = $.trim(self.find('span').text());

            common.ajax('POST', '/user/delete-skill', {'skill': text}, true, function(rsp) {
                if (rsp.data.code == 0) {
                    self.remove();
                    $('.label-selected').length == 0 && $('#selectedWrapper').append(tem);
                    cancel(text);
                    status = true;
                } else {
                    $.alert('很抱歉,删除个性标签失败,请重试!', '删除失败', function() {
                        status = true;
                    })
                }
            })
        });

        /**
         * 点击添加
         */
        $(document).on('click', '.label-choose', function() {
            var self = $(this),
                text = $.trim(self.find('span').text()),
                isselected = self.data('selected');

            !isselected && addSkill(text);
            self.removeClass('label-container-unselected');
            self.find('span').removeClass('label-unselected').addClass('label-active');
        });

        /**
         * 添加标签
         */
        function addSkill(skill) {
            if (!status) return false;
            status = false;

            if ($('.label-selected').length == 0 ){
                $('#label-none').remove();
            }

            common.ajax('POST', '/user/add-skill', { 'skill': skill }, true, function(rsp) {
                if (rsp.data.code == 0) {
                   var template = '<div class="sm-margin label-container label-selected">' +
                       '<span class="h3 font-white" style="padding: 0 .6rem">' + skill +
                       '<i class="iconfont icon-guanbi font-white pull-right" style="padding: 0;font-size: .4rem;padding-right: .3rem"></i>' +
                       '</span>' +
                       '</div>';
                    $('#selectedWrapper').prepend(template);
                    status = true;
                } else {
                    $.alert('很抱歉,创建个性标签失败,请重试!', '创建失败', function() {
                        status = true;
                    })
                }
            })
        }

        /**
         * 消除所有标签中的选中状态
         */
        function cancel(skill) {
            var result = $('.label-active'),
                item;
            $.each(result, function(index, item) {
                item = $(item);
                if ($.trim(item.text()) == skill) {
                    item.removeClass('label-active').addClass('label-unselected');
                    item.parent().addClass('label-container-unselected');
                }
            })

        }

        function loadData() {
            common.ajax('GET', '/user/skill', {}, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info,
                        html = juicer(tpl, data);

                    container.append(html);
                } else {
                    $.alert('很抱歉,数据获取失败,请重试!', function() {
                        location.reload();
                    })
                }
            })
        }

        loadData();
        var pings = env.pings;pings();
    });

    $.init();
});
