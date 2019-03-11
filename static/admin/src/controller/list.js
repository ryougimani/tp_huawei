layui.define(['table', 'form', 'tableFilter'], function (exports) {
	var $ = layui.$,
		admin = layui.admin,
		view = layui.view,
		table = layui.table,
		tableFilter = layui.tableFilter,
		form = layui.form;

	// 设定表格默认参数
	table.set({
		elem: '#content-list',
		where: {
			access_token: layui.data('layuiAdmin').access_token
		},
		autoSort: false, // 自动处理排序
		toolbar: '#table-header-operation', // 开启表格头部工具栏区域
		page: true, // 开启分页
		limit: 30, // 每页显示的条数
		limits: [10, 20, 30, 40, 50, 60, 70, 80, 90, 100], // 每页条数的选择项
		text: '对不起，加载出现异常！'
	});

	// 监听头部工具栏事件
	table.on('toolbar(content-list)', function(obj){
		// var checkStatus = table.checkStatus(obj.config.id);
		switch(obj.event){
			case 'add': // 添加
				admin.req({
					url: layui.setter.controlUrl + '/add.html',
					done: function (res) {
						admin.popupRight({
							id: 'popupRight-add',
							area: layui.setter.popupRightArea,
							success: function (layero, index) {
								view(this.id).render(layui.setter.templateUrl + '/form', res.data).done(function () {
									form.render(null, 'form');
									form.on('submit(form-submit)', function (data) {
										admin.req({
											url: layui.setter.controlUrl + '/add.html',
											type: 'post',
											data: data.field,
											success: function (res) {
												layui.table.reload('content-list');
												layer.close(index);
											}
										});

									});
								});
							}
						});
					}
				});
				break;
			case 'del':

				break;
		}
	});

	// 监听表格工具条
	table.on('tool(content-list)', function (obj) {
		switch (obj.event) {
			case 'edit': // 编辑
				admin.req({
					url: layui.setter.controlUrl + '/edit.html',
					data: {id: obj.data.id},
					done: function (res) {
						admin.popupRight({
							id: 'popupRight-edit',
							area: layui.setter.popupRightArea,
							success: function (layero, index) {
								view(this.id).render(layui.setter.templateUrl + '/form', res.data).done(function () {
									form.render(null, 'form');
									form.on('submit(form-submit)', function (data) {
										admin.req({
											url: layui.setter.controlUrl + '/edit.html',
											type: 'post',
											data: data.field,
											done: function (res) {
												layui.table.reload('content-list');
												layer.close(index);
											}
										});

									});
								});
							}
						});
					}
				});
				break;
			case 'del': // 删除
				layer.confirm('确定要删除ID为' + obj.data.id + '的数据吗？', function(index){
					admin.req({
						url: layui.setter.controlUrl + '/del.html',
						type: 'post',
						data: {id : obj.data.id, field: 'delete'},
						done: function (res) {
							layui.table.reload('content-list');
						}
					});
					layer.close(index);
				});
				break;

		}
	});

	// 渲染搜索表单
	layui.data.search_form = function (d) {
		form.render(null, 'list-search-form');
	};

	// 监听搜索
	form.on('submit(list-search)', function (data) {
		var field = data.field;
		// 执行重载
		table.reload('content-list', {
			where: field
		});
	});

	exports('list', {})
});