layui.define(['table', 'element', 'form'], function (exports) {
	var $ = layui.$,
		admin = layui.admin,
		view = layui.view,
		element = layui.element,
		table = layui.table,
		form = layui.form;

	let $body = $('body');

	// 注册 data-sub-show 事件行为 (显示子集)
	$body.on('click', '[data-sub-show]', function () {
		$(this).hide().nextAll('[data-sub-hide]').show();
	});
	// 注册 data-sub-hide 事件行为 (隐藏子集)
	$body.on('click', '[data-sub-hide]', function () {
		$(this).hide().prevAll('[data-sub-show]').show();
	});

	// 设定表格默认参数
	table.set({
		elem: '#content-list-tree',
		where: {
			access_token: layui.data('layuiAdmin').access_token
		},
		toolbar: '#table-header-operation', // 开启表格头部工具栏区域
		page: false, // 开启分页
		text: '对不起，加载出现异常！'
	});

	// 监听头部工具栏事件
	table.on('toolbar(content-list-tree)', function(obj){
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
												layui.table.reload('content-list-tree');
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
		}
	});

	// 监听表格工具条
	table.on('tool(content-list-tree)', function (obj) {
		switch (obj.event) {
			case 'add': // 添加
				admin.req({
					url: layui.setter.controlUrl + '/add.html',
					data: {pid: obj.data.id},
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
											done: function (res) {
												layui.table.reload('content-list-tree');
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
												layui.table.reload('content-list-tree');
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
				layer.confirm('确定是否要删除ID为' + obj.data.id + '的数据吗？', function(index){
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
			case 'restore': // 还原
				layer.confirm('确定是否要还原ID为' + obj.data.id + '的数据吗？', function(index){
					admin.req({
						url: layui.setter.controlUrl + '/restore.html',
						type: 'post',
						data: {id : obj.data.id, field: 'restore'},
						done: function (res) {
							layui.table.reload('content-list');
						}
					});
					layer.close(index);
				});
				break;
			case 'thorough-del': // 删除
				layer.confirm('确定是否要彻底删除ID为' + obj.data.id + '的数据吗？', function(index){
					admin.req({
						url: layui.setter.controlUrl + '/thorough_del.html',
						type: 'post',
						data: {id : obj.data.id, field: 'restore'},
						done: function (res) {
							layui.table.reload('content-list');
						}
					});
					layer.close(index);
				});
				break;
		}
	});

	// 监听单元格编辑
	table.on('edit(content-list-tree)', function(obj){
		admin.req({
			url: layui.setter.controlUrl + '/' + obj.field + '.html',
			type: 'post',
			data: {id : obj.data.id, field: obj.field, value: obj.value},
			done: function (res) {
				layui.table.reload('content-list-tree');
			}
		});
	});

	// 监听状态操作
	form.on('switch(status)', function(obj){
		if (obj.elem.checked) { // 启用
			admin.req({
				url: layui.setter.controlUrl + '/enables.html',
				type: 'post',
				data: {id : this.value, field: 'status', value: 1},
			});
		} else { // 禁用
			admin.req({
				url: layui.setter.controlUrl + '/disables.html',
				type: 'post',
				data: {id : this.value, field: 'status', value: 0},
			});
		}
	});

	exports('list_tree', {})
});