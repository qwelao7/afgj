var common = require('../lib/common.js');
//获取url参数 (type: 1: 借用, 2: 小市，3: 图片，4: 投票, 5: 活动;  param: 借用/小市物品类型)
//普通单聊 没有参数 打招呼
//模板消息进入  参数: straight (不打招呼) 默认为1
var url = common.getRequest(),
    userId = url.id;

var bool = (url.type)?true:false;
if(url.straight == 1 && url.straight) {
    bool = true;
}


common.ajax('GET', '/message/send', {userId: userId, type: bool}, true, function (rsp) {
    var data = rsp.data.info;
    wkitInit(data);
});

//小市,借用数据
var marketArr = ['女装', '数码', '母婴', '美妆', '童装', '其他'],
    borrowArr = ['工具', '卡券', '图书', '其他'];

var message = '';

if(url.type && url.param) {
    if(url.type == 1) {
        message = '你好, 我想借用您发布的' + borrowArr[url.param - 1];
    }else if(url.type == 2) {
        message = '你好, 我想购买您发布的' + marketArr[url.param - 1];
    }
}

//初始化聊天界面
function wkitInit(data) {
    if(url.type) {
        message = data.toNickname + ', ' + message;
    }else {
        message = '';
    }

    if(url.straight && url.straight == 1) {
        message = '';
    }
    
    WKIT.init({
        uid: data.uid,
        appkey: data.appkey,
        credential: data.password,
        touid: data.touid,
        avatar: data.avatar,
        toAvatar: data.toAvatar,
        onMsgReceived: function (data) {
        },
        onMsgSent: function (data) {
        },
        // beforeImageUploaderTrigger: function(event, uploadFn){
        //     // 打开native的文件选择器，请自己实现
        //     var file = Native.openFileSelector();
        //
        //     // 把文件转换成base64后的字符串，请自己实现
        //     var base64Img = Native.base64(file);
        //     // 获得文件的类型 png || jpg，请自己实现
        //     var ext = getFileExt(file);
        //     uploadFn({
        //         ext: ext,
        //         base64Img: base64Img
        //     });
        // },
        // onUploaderSuccess: function(url){
        //     console.log(url);
        // },
        // onUploaderError: function(error){
        //     console.log(error);
        // },
        titleBar: true,
        title: data.toNickname,
        placeholder: '说点什么吧',
        themeBgColor: '#009042',
        themeColor: '#fff',
        msgBgColor: '#9fe659',
        msgColor: '#fff',
        imageZoom: true,
        autoMsg: message,
        autoMsgType:0,
        hideLoginSuccess: true, //隐藏默认欢迎文案
        onBack: function () {
           if(window.history.length == 1) {
               window.location.href = 'home.html';
           }else {
               window.history.go(-1);
           }
        },
        onUploaderError: function (error) {
            alert('很抱歉,上传图片失败,请重试!');
        }
    });
}

var pings = env.pings;pings();
