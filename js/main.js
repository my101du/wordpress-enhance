var company_fields = {
	'Name': ['Name', '名称', 4],
	'OperName': ['operName', '法人', 2],
	'Status': ['status', '状态', 3],
	'StartDate': ['regtime', '注册时间', 2]
};

var brand_fields = {
	'tmImg': ['tmImg', '商标图片', 2],
	'tmName': ['tmName', '商标名称',1],
	'applicantCn': ['applicantCn', '申请人', 1],
	'currentStatus': ['currentStatus', '状态', 1],
	'regNo': ['regNo', '注册号', 2],
	'intCls': ['intCls', '国际分类', 2],
	'appDate': ['appDate', '申请日期', 2]
};

jQuery(document).ready(function($) {

	// not used
	$("input[name='city']").focus(function(event) {
		$("#city-list").removeClass('hide');
	});

	// fill fields list
	function fillListToTable(dataType, data) {

		if (dataType == 'company-name') {
			var fields = company_fields;
		} else {
			var fields = brand_fields;
		}

		var doc = $("#result-list");
		doc.html('');

		var table = $('<div class="container" />');
		var tr = $('<div class="row first-th hidden-xs" />');

		// table head
		for (field in fields) {
			tr.append('<div class="' + field + ' col-xs-12 col-md-' + fields[field][2] + '"><b>' + fields[field][1] + '</b></div>');
		}
		tr.append('<div class="col-xs-12 col-md-' + fields[field][2] + '"></div>'); // detail cell
		table.append(tr);

		for (i = 0; i < data.length; i++) {
	
			table.append($('<hr />'));

			var record = data[i];

			var tr = $('<div class="row">');

			for (field in fields) {
				var labelString = '<label class="visible-xs-inline-block field-label">' + fields[field][1] + '</label>';

				// brand image or string
				if (field == 'tmImg') {
					tr.append('<div class="col-xs-12 col-md-' + fields[field][2] + '">'+labelString+'<img src="http://tmpic.tmkoo.com/' + record[field] + '-m" /></div>');
				} else {
					tr.append('<div class="col-xs-12 col-md-' + fields[field][2] + '">'+labelString + record[field] + '</div>');
				}
			}

			// detail button
			if (dataType == 'company-name') {
				var detail_td = $('<div class="col-xs-12 col-md-1"><label class="visible-xs-inline-block field-label">查看详情</label><a href="#" class="btn btn-primary" data-toggle="modal" data-target="#myModal">详情</a></div>').on(
					'click', { keyNo: record.KeyNo },
					getCompanyDetail
				);
			} else {
				var detail_td = $('<div class="col-xs-12 col-md-1"><label class="visible-xs-inline-block field-label">查看详情</label><a href="#" class="btn btn-primary" data-toggle="modal" data-target="#myModal">详情</a></div>').on(
					'click', { regNo: record.regNo, intCls: record.intCls },
					getBrandDetail
				);
			}

			//hide detail(modal window) now.
			tr.append(detail_td);

			table.append(tr);
		}

		doc.html(table);
	}

	/**
	 * Search Company & Brand
	 */
	$("#company-name-verify-form, #brand-verify-form").submit(function(e) {
		e.preventDefault();

		$('input[name="submit"]').val('查询中，请稍等...');

		$("#result-list").html('');

		var dataType = $('input[name="data-type"]').val();

		$.ajax({
			type: "POST",
			url: pageUrl,
			data: {
				'submit': 1,
				'data-type': dataType,
				'keyword': $('input[name="keyword"]').val()
			},
			success: function(res) {
				console.log(res);

				if (res.error) {
					$("#result-list").html('<div class="hide-result"><p><a class="btn btn-lg btn-danger" href="/wp-login.php">查看结果，请先登录</a></p></div>');
					return false;
				}

				if(dataType=='company-name'){
					dataList = res.Result;
				}else{
					dataList = res.result.data;
				}

				fillListToTable(dataType, dataList);

			},
			complete: function(data) {
				$('input[name="submit"]').val('查询');
			}
		});
	});

	/**
	 * Company Detail
	 */
	function getCompanyDetail(e) {

		keyNo = e.data.keyNo;

		$('#detail-container').html('');
		$('#detail-container').loading();

		$.ajax({
			type: "POST",
			url: pageUrl,
			data: {
				'submit': 1,
				'keyNo': keyNo
			},
			success: function(res) {
				console.log(res);

				var data = res.Result;

				// basic
				var innerHTML =
					'<tr><td>公司名</td><td>' + data.Name + '</td></tr>' +
					'<tr><td>信用代码</td><td>' + data.CreditCode + '</td></tr>' +
					'<tr><td>地址</td><td>' + data.Address + '</td></tr>' +
					'<tr><td>类型</td><td>' + data.EconKind + '</td></tr>' +
					'<tr><td>注册资金</td><td>' + data.RegistCapi + '</td></tr>' +
					'<tr><td>状态</td><td>' + data.Status + '</td></tr>' +
					'<tr><td>所属管局</td><td>' + data.BelongOrg + '</td></tr>' +
					'<tr><td>法人</td><td>' + data.OperName + '</td></tr>' +
					'<tr><td>注册时间</td><td>' + data.StartDate + '</td></tr>' +
					'<tr><td>经营范围</td><td>' + data.Scope + '</td></tr>';

				//industury
				innerHTML += '<tr><td>行业</td><td>' + data.Industry.Industry + data.Industry.SubIndustry + '</td></tr>';

				// employees
				innerHTML += '<tr><td>雇员</td><td>';
				for (var i in data.Employees) {
					employee = data.Employees[i];
					innerHTML += '<p>' + employee.Name + '(' + employee.Job + ')' + '</p>';
				}
				innerHTML += '</td></tr>';

				// change records
				innerHTML += '<tr><td>变更历史</td><td>';
				for (var i in data.ChangeRecords) {
					record = data.ChangeRecords[i];
					innerHTML += '<p><b>' + record.ProjectName + '</b>(' + record.BeforeContent + '~' + record.AfterContent + ', '+record.ChangeDate+')' + '</p>';
				}
				innerHTML += '</td></tr>';

				$('#detail-container').html('<table class="table table-striped table-condensed detail-table">' + innerHTML + "</table>");
			},
			complete: function(data) {
				$('#detail-container').loading('stop');
			}
		});
	}


	/**
	 * Brand Detail
	 */
	function getBrandDetail(e) {
		regNo = e.data.regNo;
		intCls = e.data.intCls;

		$('#detail-container').html('');
		$('#detail-container').loading();

		$.ajax({
			type: "POST",
			url: pageUrl,
			data: {
				'submit': 1,
				'regNo': regNo,
				'intCls': intCls
			},
			success: function(res) {
				console.log(res);

				var data = res.result.data;

				// basic
				var innerHTML =
					'<tr><td>注册号</td><td>' + data.regNo + '</td></tr>' +
					'<tr><td>商标名</td><td>' + data.tmName + '</td></tr>' +
					'<tr><td>申请日期</td><td>' + data.appDate + '</td></tr>'+
					'<tr><td>申请人</td><td>' + data.applicantCn + '</td></tr>' +
					'<tr><td>身份证</td><td>' + data.idCardNo + '</td></tr>' +
					'<tr><td>申请人地址</td><td>' + data.addressCn + '</td></tr>' +
					'<tr><td>代理人</td><td>' + data.agent + '</td></tr>' +
					'<tr><td>商标类型</td><td>' + data.category + '</td></tr>' +
					'';

				// goods
				innerHTML += '<tr><td>使用商品</td><td>';
				for (var i in data.goods) {
					good = data.goods[i];
					innerHTML += '<p>' + good.goodsName + '(' + good.goodsCode + ')' + '</p>';
				}
				innerHTML += '</td></tr>';

				// flow
				innerHTML += '<tr><td>商标流程</td><td>';
				for (var i in data.flow) {
					record = data.flow[i];
					innerHTML += '<p>' + record.flowName + '(' + record.flowDate + ')' + '</p>';
				}
				innerHTML += '</td></tr>';


				$('#detail-container').html('<table class="table table-striped table-condensed detail-table">' + innerHTML + '</table>');
			},
			complete: function(data) {
				$('#detail-container').loading('stop');
			}
		});
	};

});