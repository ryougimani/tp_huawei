layui.define('form', function (exports) {
	var $ = layui.$,
		layer = layui.layer,
		laytpl = layui.laytpl,
		setter = layui.setter,
		view = layui.view,
		admin = layui.admin,
		form = layui.form;

	// 更换图形验证码
	$body.on('click', '#get-captcha', function () {
		// var othis = $(this);
		this.src = '/captcha.html?t=' + new Date().getTime()
	});

	//对外暴露的接口
	exports('login', {});
});