layui.define(['form', 'upload', 'laydate'], function (exports) {
	let $ = layui.$,
		admin = layui.admin,
		view = layui.view,
		upload = layui.upload,
		laydate = layui.laydate,
		form = layui.form;

	// 单图片上传
	let uploadOne = upload.render({
		elem: '#upload-images-one',
		url: '/admin/plugs/upload.html',
		accept: 'images',
		size: layui.setter.upload.size,
		before: function (obj) {
			// 预读本地文件示例，不支持ie8
			obj.preview(function (index, file, result) {
				$('#upload-images-one-img').attr('src', result); //图片链接（base64）
			});
		},
		done: function (res) {
			if (res.code === 1) {
				$('#upload-images-one-save').val(res.data.src);
				return layer.msg(res.msg || '上传成功');
			} else {
				return layer.msg(res.msg || '上传失败');
			}
		},
		error: function(){
			//演示失败状态，并实现重传
			let demoText = $('#upload-images-one-text');
			demoText.html('<span style="color: #FF5722;">上传失败</span> <a class="layui-btn layui-btn-mini demo-reload">重试</a>');
			demoText.find('.demo-reload').on('click', function(){
				uploadOne.upload();
			});
		}
	});

	// 单文件上传

	// 多图片上传

	// 多文件上传

	// 开始日期 - 结束日期
	laydate.render({
		elem: '#date_range',
		range: true,
		min: 0
	});

	// 结束时间
	laydate.render({
		elem: '#end_time'
	});

	//对外暴露的接口
	exports('data_form', {});
});