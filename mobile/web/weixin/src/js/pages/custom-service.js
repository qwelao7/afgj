var common = require('../lib/common.js');

var url = common.getRequest(),
    message = '',
    welMsg = '',
    tabTitle,
    group,
    touid;

//获取本地跳转存储
var storage = window.localStorage,
    skip = storage.getItem('skip'),
    decorateCompaint = storage.getItem('decorateCompaint');


//参数
if (url.type == undefined) {
    tabTitle = skip;
    group = '';
} else if (url.type == '1') {
    tabTitle = '一键报障';
    group = env.complainGroup;
} else if (url.type == '2') {
    tabTitle = '客户服务';
    group = env.customGroup;
} else if(url.type == '3') {
    tabTitle = '一键报障';
    group = env.complainGroup;
    message = '你好, ' + decorateCompaint + '有故障,我要报修。';
}


if (url.id != undefined) {
    touid = url.id;
} else {
    touid = env.customService;
}

if(tabTitle == '一键报障') {
    welMsg = '您好，欢迎使用一键报障服务！请用文字和图片描述您要申报的故障，我们将尽快为您分析故障原因并解决问题 ：)';
}else if(tabTitle == '客户服务') {
    welMsg = '您好，很高兴为您服务！有任何问题都可以找回来啦小管家帮忙哦 ：)';
}


//加载函数
function loadData() {
    common.ajax('GET', '/user/info', {}, false, function (rsp) {
        var data = rsp.data.info;
        wkitInit(data);
        storage.removeItem('skip');
    });
}

loadData();

//初始化聊天界面
function wkitInit(data) {
    WKIT.init({
        uid: data.list.user_id.toString(),
        avatar: data.list.headimgurl,
        appkey: env.appkey,
        credential: env.password,
        touid: touid, // E客服账号
        sendMsgToCustomService: true,
        groupId: group,
        imageZoom: true,
        titleBar: true,
        title: tabTitle,
        placeholder: '请输入您的内容',
        themeBgColor: '#009042',
        themeColor: '#fff',
        msgBgColor: '#9fe659',
        msgColor: '#fff',
        hideLoginSuccess: true, //隐藏默认登录文案
        autoMsg: message,
        autoMsgType:0,
        welcomeMsg: welMsg,
        onBack: function () {
            window.history.go(-1);
        },
        onUploaderError: function (error) {
            $.alert('很抱歉,上传图片失败,请重试!');
        }
    });
}

var pings = env.pings;pings();

