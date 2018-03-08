require('../../css/style.css');
require('../../css/index.css');
var common = require('../lib/common.js');

$(function() {
    'use strict';
    
    //type == 1 活动工作人员加入确认
    //type == 2 活动审核页面 id --> apply_id unique_id => log唯一id
    // type == 3 活动退费审核页面  apply_id --> apply_id id-> refund_id
    // type == 4 加入房产确认 id -> address_id
    $(document).on('pageInit', '#confirm-tpl', function(e, id, page) {
        var url = common.getRequest();
        
        var container = $('#container'),
            nav = $('#nav').html(),
            tpl = $('#tpl').html(),
            refund = $('#refund').html(),
            title = $('#title').html();

        var status = true;

        var params = {
            user_id: '',
            events_id: ''
        };
        
        var getMoney = function (fee, point) {
            return parseFloat((fee * 100 + point) / 100).toFixed(2);
        };
        juicer.register('getMoney', getMoney);

        common.img();

        function init() {
            if (url.type) {
                if (url.type == 1) {
                    loadData();
                } else if (url.type == 2) {
                    loadEventAuth();
                } else if (url.type == 3) {
                    loadRefundAuth();
                } else if (url.type == 4) {
                    loadShareFang();
                }
            }
        }
        
        function loadData() {
            common.ajax('GET', '/events/worker-index', {
                id: url.id
            }, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    data['type'] = url.type;
                    
                    var html = juicer(tpl, data),
                        htm = juicer(nav, data),
                        tem = juicer(title, data);
                    container.append(html);
                    container.after(htm);
                    $('header').append(tem);
                } else {
                    var template = "<h3 class='grey' style='text-align: center;margin-top: 4rem;'>很抱歉,数据错误,请重新扫二维码!</h3>",
                        teml = juicer(title, '错误页面');

                    $('header').append(tem);
                    container.append(template);
                }
            })
        }

        function loadEventAuth() {
            common.ajax('GET', '/events/apply-check', {
                'id': url.id, //apply_id
            }, true, function(rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    data['type'] = url.type;

                    var html = juicer(tpl, data),
                        htm  = juicer(nav, data),
                        tem = juicer(title, data);

                    container.append(html);
                    container.after(htm);
                    $('header').append(tem);

                    renderParams(data);
                } else {
                    var template = "<h3 class='grey' style='text-align: center;margin-top: 4rem;'>很抱歉,数据错误,请重新扫二维码!</h3>",
                        teml = juicer(title, '错误页面');

                    container.append(template);
                    $('header').append(teml);
                }
            })
        }

        function loadRefundAuth () {
            common.ajax('GET', '/events/apply-cancel-detail', {
                'apply_id': url.apply_id,
                'refund_id': url.id
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    data['type'] = url.type;

                    var html = juicer(refund, data),
                        htm = juicer(nav, data),
                        tem = juicer(title, data);

                    container.append(html);
                    container.after(htm);
                    $('header').append(tem);
                } else {
                    var template = "<h3 class='grey' style='text-align: center;margin-top: 4rem;'>很抱歉,数据错误!</h3>",
                        teml = juicer(title, {'type': url.type});

                    container.append(template);
                    $('header').append(teml);
                }
            })
        }
        
        function loadShareFang() {
            common.ajax('GET', '/house/share-fang-confirm', {
                'addressId': url.id
            }, true, function (rsp) {
                if (rsp.data.code == 0) {
                    var data = rsp.data.info;
                    data['type'] = url.type;
                    
                    var html = juicer(tpl, data),
                        htm = juicer(nav, data),
                        tem = juicer(title, data);
                    container.append(html);
                    container.after(htm);
                    $('header').append(tem);
                } else {
                    var template = "<h3 class='grey' style='text-align: center;margin-top: 4rem;'>很抱歉,数据错误,请重新扫二维码!</h3>",
                        teml = juicer(title, '错误页面');

                    $('header').append(tem);
                    container.append(template);
                }
            })
        }

        function renderParams(objs) {
            params.user_id = objs.user_id;
            params.events_id = objs.events_id;
        }

        function submit(ele) {
            var self = $(ele),
                hasPass = self.data('pass');

            if (!hasPass) {
                if (!status) return false;
                status = false;

                common.ajax('GET', '/events/add-event-admin', {
                    id: url.id
                }, true, function(rsp) {
                    if (rsp.data.code == 0) {
                        $.alert('成功加入工作组!', '加入成功', function() {
                            status = true;

                            self[0].setAttribute('data-pass', true);
                            self.css('background','#ccc').find('span').text('已加入');
                        })
                    } else {
                        $.alert('很抱歉, 加入工作组失败!失败原因:' + rsp.data.message, function() {
                            status = true;
                        })
                    }
                })
            }
        }

        function  renderNav(text) {
            var template = '<nav class="bar bar-tab"><a class="tab-item external"><span class="font-white">' + text + '</span></a></nav>';

            container.after(template);
        }
        
        $('.error-img-container').live('click', function() {
            var self = $(this),
                href = self.data('link');

            location.href = href;
        });

        $('#submit').live('click', function() {
            var _this = this;

            if (url.type == 1) {
                submit(_this)
            }
        });

        $('.auth_success').live('click', function() {
            if (!status) return false;
            status = false;

            var self = $(this),
                parent = self.parents('.bar-tab');

            common.ajax('GET', '/events/apply-check-result', {
                'id': url.id,
                'unique_id': url.unique_id,
                'user_id': params.user_id,
                'events_id': params.events_id,
                'check_status': 1
            }, true, function(rsp) {
                if (rsp.data.code == 0) {
                    $.alert('该报名审核通过!', '审核通过', function() {
                        parent.remove();
                        status = true;

                        renderNav('已审核 | 通过');
                    })
                } else {
                    $.alert('很抱歉,审核失败!失败原因:' + rsp.data.message, '审核失败', function() {
                        status = true;
                    })
                }
            })
        });

        $('.auth_fail').live('click', function() {
            var self = $(this),
                parent = self.parents('.bar-tab');

            $.prompt('审核不通过理由:', function (value) {
                //submit

                common.ajax('GET', '/events/apply-check-result', {
                    'id': url.id,
                    'unique_id': url.unique_id,
                    'user_id': params.user_id,
                    'events_id': params.events_id,
                    'check_status': 2,
                    'fail_reason': value
                }, true, function(rsp) {
                    if (rsp.data.code == 0) {
                        $.alert('该报名未通过审核!', '提交成功', function() {
                            parent.remove();
                            status = true;

                            renderNav('已审核 | 未通过');
                        })
                    } else {
                        $.alert('很抱歉,提交失败!失败原因:' + rsp.data.message, '提交失败', function() {
                            status = true;
                        })
                    }
                })
            });
        });
        
        $('.con_fail').live('click', function () {
            var self = $(this),
                parent = self.parents('.bar-tab');
            
            $.prompt('审核不通过理由:', function (value) {
                //submit

                common.ajax('POST', '/events/apply-cancel-operate', {
                    'id': url.id,
                    'status': 3,
                    'reason': value
                }, true, function (rsp) {
                    if (rsp.data.code == 0) {
                        $.alert('退费申请被拒绝!', '提交成功', function() {
                            parent.remove();
                            status = true;

                            renderNav('退费申请被拒绝 | ' + value);
                        })
                    } else {
                        $.alert('很抱歉,提交失败!失败原因:' + rsp.data.message, '提交失败', function() {
                            status = true;
                        })
                    }
                });
            });
        });
        
        $('.con_success').live('click', function () {
            if (!status) return false;
            status = false;

            var self = $(this),
                parent = self.parents('.bar-tab');

            common.ajax('POST', '/events/apply-cancel-operate', {
                'id': url.id,
                'status': 2
            }, true, function(rsp) {
                if (rsp.data.code == 0) {
                    $.alert('退费申请通过!', '提交成功', function() {
                        parent.remove();
                        status = true;

                        renderNav('退费申请通过');
                    })
                } else {
                    $.alert('很抱歉,提交失败!失败原因:' + rsp.data.message, '提交失败', function() {
                        status = true;
                    })
                }
            })
        });

        $('#joinFang').live('click', function () {
            var _this = $(this),
                hasPass = _this.data('pass');

            if (!hasPass) {
                if (!status) return false;
                status = false;

                common.ajax('GET', '/house/qr-code-fang', {
                    addressId: url.id
                }, true, function(rsp) {
                    if (rsp.data.code == 0) {
                        $.alert('成功加入该房产!', '加入成功', function() {
                            status = true;

                            _this[0].setAttribute('data-pass', true);
                            _this.css('background','#ccc').find('span').text('已加入');
                        })
                    } else {
                        $.alert('很抱歉, 加入房产失败!失败原因:' + rsp.data.message, function() {
                            status = true;
                        })
                    }
                })
            }
        });
        
        init();
        
        var pings = env.pings;pings();
    });

    $.init();
});