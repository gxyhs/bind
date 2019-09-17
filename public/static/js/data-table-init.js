/**
 * Created by mosaddek on 3/4/15.
 */

function format(d) {
    // `d` is the original data object for the row
    return '<table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">' +
        '<tr>' +
        '<td>Full name:</td>' +
        '<td>' + d.name + '</td>' +
        '</tr>' +
        '<tr>' +
        '<td>Extension number:</td>' +
        '<td>' + d.extn + '</td>' +
        '</tr>' +
        '<tr>' +
        '<td>Extra info:</td>' +
        '<td>And any further details here (images etc)...</td>' +
        '</tr>' +
        '</table>';
}


function init_DataTables() {

    console.log('run_datatables');

    if( typeof ($.fn.DataTable) === 'undefined'){ return; }
    console.log('init_DataTables');


    $('#datatable').dataTable();
    $('.datatable-noorder').dataTable({
            ordering:false
        }
    );
    $('.datatable_mul').dataTable();
    $('.datatable_mul_page').dataTable({
        "aLengthMenu" :[50,25,100]
    });

    $('#datatable-keytable').DataTable({
        keys: true
    });

    function retrieveData(url, aoData, fnCallback) {
        $.ajax({
            url: url,//这个就是请求地址对应sAjaxSource
            data : {
                "aoData":JSON.stringify(aoData)
            },
            type: 'POST',
            dataType: 'json',
            async: true,
            success: function (result) {
                $('.check-all').attr("checked", false);
                fnCallback(result);//把返回的数据传给这个方法就可以了,datatable会自动绑定数据的
            },
            error:function(XMLHttpRequest, textStatus, errorThrown) {
                alert("status:"+XMLHttpRequest.status+",readyState:"+XMLHttpRequest.readyState+",textStatus:"+textStatus);

            }
        });
    }

    var ajaxDataTableFunction = function (object) {
        object.DataTable()
    } 

    var form_serialize = $('.form-search').serialize();
    $('.datatable-ajax').DataTable({
        "PaginationType": "bootstrap",
        dom: '<"tbl-top clearfix"lfr>,t,<"tbl-footer clearfix"<"tbl-info pull-left"i><"tbl-pagin pull-right"p>>',
        tableTools: {
            "sSwfPath": "swf/copy_csv_xls_pdf.swf"
        },
        "sPaginationType": "full_numbers",
        "aLengthMenu" :[20,50,75,100,500],
        "iDisplayLength": 20,  ///默认显示20行
        'language': {
            'emptyTable': 'empty table',
            'loadingRecords': 'loading...',
            'processing': 'processing...',   //加载提示
            'search': 'search:',   //是否开启搜索
            'lengthMenu': 'page _MENU_ pear',
            'zeroRecords': 'no data',
            //"bPaginate":false,  //是否使用分页器

            'paginate': {
                'first':      'first',
                'last':       'last',
                'next':       'next',
                'previous':   'previous'
            },
            'info': ' _PAGE_ page / total _PAGES_ page Article: _START_ - _END_ / _TOTAL_',
            'infoEmpty': 'infoEmpty',
            'infoFiltered': '(infoFiltered _MAX_ num)',
            "oAria": {
                "sSortAscending": ": sort",
                "sSortDescending": ": desc"
            }
        },
        //'order':[[0,'desc']],  //默认排序
        "searching":false,
        "bLengthChange": true, //关闭每页显示多少条的选择框
        "bPaginite": true,
        "renderer": "bootstrap", //渲染样式:Bootstrap和jquery-ui
        "bInfo": true,
        "bStateSave": true,// //保存状态到cookie ***** 很重要 ， 当搜索的时候页面一刷新会导致搜索的消失。使用这个属性就可避免了
        "bSort": false,
        "processing": false,
        "bServerSide": true,
		
        "sAjaxSource": "?"+form_serialize,//这个是请求的地址
        "fnServerData": retrieveData,// 获取数据的处理函数
    });

    $('#datatable-responsive').DataTable();

    $('#datatable-scroller').DataTable({
        ajax: "js/datatables/json/scroller-demo.json",
        deferRender: true,
        scrollY: 380,
        scrollCollapse: true,
        scroller: true
    });

    $('#datatable-fixed-header').DataTable({
        fixedHeader: true
    });

    var $datatable = $('#datatable-checkbox');

    $datatable.dataTable({
        'order': [[ 1, 'asc' ]],
        'columnDefs': [
            { orderable: false, targets: [0] }
        ]
    });
    $datatable.on('draw.7dt', function() {
        $('checkbox input').iCheck({
            checkboxClass: 'icheckbox_flat-green'
        });
    });

};
function init_validator () {

    if( typeof (validator) === 'undefined'){ return; }
    console.log('init_validator');

    // initialize the validator function
    validator.message.date = 'not a real date';

    // validate a field on "blur" event, a 'select' on 'change' event & a '.reuired' classed multifield on 'keyup':
    $('form')
        .on('blur', 'input[required], input.optional, select.required', validator.checkField)
        .on('change', 'select.required', validator.checkField)
        .on('keypress', 'input[required][pattern]', validator.keypress);

    $('.multi.required').on('keyup blur', 'input', function() {
        validator.checkField.apply($(this).siblings().last()[0]);
    });

    $('form').submit(function(e) {
        e.preventDefault();
        var submit = true;

        // evaluate the form using generic validaing
        if (!validator.checkAll($(this))) {
            submit = false;
        }

        if (submit)
            this.submit();

        return false;
    });

};


$('.colvis-data-table').DataTable({
    "PaginationType": "bootstrap",
    dom: '<"tbl-head clearfix"C>,<"tbl-top clearfix"lfr>,t,<"tbl-footer clearfix"<"tbl-info pull-left"i><"tbl-pagin pull-right"p>>'


});


$('.responsive-data-table').DataTable({
    "PaginationType": "bootstrap",
    responsive: true,
    dom: '<"tbl-top clearfix"lfr>,t,<"tbl-footer clearfix"<"tbl-info pull-left"i><"tbl-pagin pull-right"p>>'
});

//
//
//$('.scrolling-table').DataTable({
//    "PaginationType": "bootstrap",
//    "ajax": "data/2500.txt",
//    "scrollY": "200px",
//    dom: '<"tbl-top clearfix"fr>,t,<"tbl-footer clearfix"<"tbl-info-large pull-left"i><"tbl-pagin pull-right"S>>',
//
//    "deferRender": true
//
//
//});


$(function() {
    var table = $('.row-details-data-table').DataTable({
        "ajax": "data/objects.txt",
        dom: '<"tbl-top clearfix"lfr>,t,<"tbl-footer clearfix"<"tbl-info pull-left"i><"tbl-pagin pull-right"p>>',
        "columns": [{
            "class": 'details-control',
            "orderable": false,
            "data": null,
            "defaultContent": ''
        }, {
            "data": "name"
        }, {
            "data": "position"
        }, {
            "data": "office"
        }, {
            "data": "salary"
        }],
        "order": [
            [1, 'asc']
        ]
    });

    // Add event listener for opening and closing details
    $('.row-details-data-table tbody').on('click', 'td.details-control', function() {
        var tr = $(this).closest('tr');
        var row = table.row(tr);

        if (row.child.isShown()) {
            // This row is already open - close it
            row.child.hide();
            tr.removeClass('shown');
        } else {
            // Open this row
            row.child(format(row.data())).show();
            tr.addClass('shown');
        }
    });
});
$(document).ready(function() {
    init_DataTables();
    init_validator ();
})