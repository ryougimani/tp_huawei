layui.define('form', function (exports) {
	var $ = layui.$,
		layer = layui.layer,
		laytpl = layui.laytpl,
		setter = layui.setter,
		view = layui.view,
		admin = layui.admin,
		form = layui.form;

	var $body = $('body');

	// 更换图形验证码
	$body.on('click', '#get-captcha', function () {
		// var othis = $(this);
		this.src = '/captcha.html?t=' + new Date().getTime()
	});

	//自定义验证
	form.verify({
		captcha: function(value, item) {
			if(value.length !== 5){
				return '验证码为5位字符';
			}
		},
	});

	//对外暴露的接口
	exports('login', {});
});