layui.define(['table', 'element', 'form', 'tableFilter'], function (exports) {
	var $ = layui.$,
		admin = layui.admin,
		view = layui.view,
		laytpl = layui.laytpl,
		element = layui.element,
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
		text: '对不起，加载出现异常！',
		done: function (res, curr, count) {
			// 搜索模板
			if ($('#search_tpl').length !== 0) {
				laytpl(search_tpl.innerHTML).render(res, function (html) {
					$('#search_tpl').remove();
					$('.layui-card-header').html(html);
					form.render(null, 'list-search-form');
				})
			}
		}
	});

	// 监听排序事件
	table.on('sort(content-list)', function (obj) {
		table.reload('content-list', {
			initSort: obj,
			where: {
				field: obj.field,
				order: obj.type
			}
		});
	});

	// 监听头部工具栏事件
	table.on('toolbar(content-list)', function (obj) {
		let checkStatus = table.checkStatus(obj.config.id);
		if (obj.event === 'add') {
			admin.req({
				url: layui.setter.controlUrl + '/' + obj.event + '.html',
				done: function (res) {
					admin.popupRight({
						id: 'popupRight-add',
						area: layui.setter.popupRightArea,
						success: function (layero, index) {
							view(this.id).render(layui.setter.templateUrl + '/form', res).done(function () {
								$('#popupRight-' + obj.event + ' [template]').remove();
								form.render(null, 'form');
								form.on('submit(form-submit)', function (data) {
									admin.req({
										url: layui.setter.controlUrl + '/' + obj.event + '.html',
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
		} else if (obj.event === 'move'
			|| obj.event === 'audit') {
			// 移动 || 审核
			if (checkStatus.data.length > 0) {
				let id = (function () {
					let data = [];
					return layui.each(checkStatus.data, function (index, item) {
						data.push(item.id);
					}), data.join(',');
				}).call(this);
				admin.req({
					url: layui.setter.controlUrl + '/' + obj.event + '.html',
					data: {id: id},
					done: function (res) {
						admin.popup({
							title: res.lang[obj.event + '_title'],
							id: 'popup-' + obj.event,
							area: layui.setter.popupArea,
							success: function (layero, index) {
								view(this.id).render(layui.setter.templateUrl + '/' + obj.event, res).done(function () {
									$('#popup-' + obj.event + ' [template]').remove();
									form.render(null, 'form');
									form.on('submit(form-submit)', function (data) {
										admin.req({
											url: layui.setter.controlUrl + '/' + obj.event + '.html',
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
			}
		} else if (obj.event === 'del'
			|| obj.event === 'restore'
			|| obj.event === 'thorough_del') {
			// 删除 || 还原 || 彻底删除
			if (checkStatus.data.length > 0) {
				let msg = '';
				switch (obj.event) {
					case 'del': // 删除
						msg = '删除';
						break;
					case 'restore': // 还原
						msg = '还原';
						break;
					case 'thorough_del': // 删除
						msg = '彻底删除';
						break;
				}
				let id = (function () {
					let data = [];
					return layui.each(checkStatus.data, function (index, item) {
						data.push(item.id);
					}), data.join(',');
				}).call(this);
				layer.confirm('确定是否要' + msg + 'ID为（' + id + '）的数据吗？', function (index) {
					admin.req({
						url: layui.setter.controlUrl + '/' + obj.event + '.html',
						type: 'post',
						data: {id: id, field: obj.event},
						done: function (res) {
							layui.table.reload('content-list');
						}
					});
					layer.close(index);
				});
			}
		} else if (obj.event === 'empty_trash') {
			layer.confirm('确定是否要清空回收站的数据吗？', function (index) {
				admin.req({
					url: layui.setter.controlUrl + '/' + obj.event + '.html',
					type: 'post',
					done: function (res) {
						layui.table.reload('content-list');
					}
				});
				layer.close(index);
			});
		}
	});

	// 监听表格工具条
	table.on('tool(content-list)', function (obj) {
		if (obj.event === 'edit') {
			// 编辑
			admin.req({
				url: layui.setter.controlUrl + '/' + obj.event + '.html',
				data: {id: obj.data.id},
				done: function (res) {
					admin.popupRight({
						id: 'popupRight-' + obj.event,
						area: layui.setter.popupRightArea,
						success: function (layero, index) {
							view(this.id).render(layui.setter.templateUrl + '/form', res).done(function () {
								$('#popupRight-' + obj.event + ' [template]').remove();
								form.render(null, 'form');
								form.on('submit(form-submit)', function (data) {
									admin.req({
										url: layui.setter.controlUrl + '/' + obj.event + '.html',
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
		} else if (obj.event === 'move'
			|| obj.event === 'audit') {
			// 移动 || 审核
			admin.req({
				url: layui.setter.controlUrl + '/' + obj.event + '.html',
				data: {id: obj.data.id},
				done: function (res) {
					admin.popup({
						title: res.lang[obj.event + '_title'],
						id: 'popup-' + obj.event,
						area: layui.setter.popupArea,
						success: function (layero, index) {
							view(this.id).render(layui.setter.templateUrl + '/' + obj.event, res).done(function () {
								$('#popup-' + obj.event + ' [template]').remove();
								form.render(null, 'form');
								form.on('submit(form-submit)', function (data) {
									admin.req({
										url: layui.setter.controlUrl + '/' + obj.event + '.html',
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
		} else if (obj.event === 'del'
			|| obj.event === 'restore'
			|| obj.event === 'thorough_del') {
			// 删除 || 还原 || 彻底删除
			let msg = '';
			switch (obj.event) {
				case 'del': // 删除
					msg = '删除';
					break;
				case 'restore': // 还原
					msg = '还原';
					break;
				case 'thorough_del': // 删除
					msg = '彻底删除';
					break;
			}
			layer.confirm('确定是否要' + msg + 'ID为（' + obj.data.id + '）的数据吗？', function (index) {
				admin.req({
					url: layui.setter.controlUrl + '/' + obj.event + '.html',
					type: 'post',
					data: {id: obj.data.id, field: obj.event},
					done: function (res) {
						layui.table.reload('content-list');
					}
				});
				layer.close(index);
			});
		} else if (obj.event === 'password'
			|| obj.event === 'auth') {
			admin.req({
				url: layui.setter.controlUrl + '/' + obj.event + '.html',
				data: {id: obj.data.id},
				done: function (res) {
					admin.popup({
						title: res.lang[obj.event + '_title'],
						id: 'popup-' + obj.event,
						area: layui.setter.popupArea,
						success: function (layero, index) {
							view(this.id).render(layui.setter.templateUrl + '/' + obj.event, res).done(function () {
								$('#popup-' + obj.event + ' [template]').remove();
								form.render(null, 'form');
								form.on('submit(form-submit)', function (data) {
									admin.req({
										url: layui.setter.controlUrl + '/' + obj.event + '.html',
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
		}
	});

	// 监听单元格编辑
	table.on('edit(content-list)', function (obj) {
		admin.req({
			url: layui.setter.controlUrl + '/' + obj.field + '.html',
			type: 'post',
			data: {id: obj.data.id, field: obj.field, value: obj.value},
			done: function (res) {
				layui.table.reload('content-list');
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

	// 标签切换
	element.on('tab(content-header-tab)', function (elem) {
		let toolbar = '#table-header-operation';
		if ($(this).attr('lay-url') === '/recycle.html')
			toolbar = '#table-header-operation-recycle';
		table.reload('content-list', {
			url: layui.setter.controlUrl + $(this).attr('lay-url'),
			toolbar: toolbar
		});
	});

	// 渲染搜索表单
	layui.data.search_form = function (d) {
		form.render(null, 'list-search-form');
	};

	// 监听搜索
	form.on('submit(list-search)', function (data) {
		let field = data.field;
		// 执行重载
		table.reload('content-list', {
			where: field
		});
	});

	exports('list', {})
});