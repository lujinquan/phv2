/***** By http://www.mylucas.com.cn *****/
layui.define(['element', 'form', 'table', 'md5'], function(exports) {
    var $ = layui.jquery,element = layui.element, 
        layer = layui.layer, 
        form = layui.form, 
        table = layui.table,
        md5 = layui.md5;
    var checkBrowser = function() {
        var d = layui.device();
        d.ie && d.ie < 10 && layer.alert("IE" + d.ie + "下体验不佳，推荐使用：Chrome/Firefox/Edge/极速模式");
    }
    checkBrowser();

    var lockscreen = function() {
        document.oncontextmenu=new Function("event.returnValue=false;");
        document.onselectstart=new Function("event.returnValue=false;");
        layer.open({
            title: false,
            type: 1,
            content: '<div class="lock-screen"><input type="password" id="unlockedPwd" class="layui-input" placeholder="请输入登录密码解锁..." autocomplete="on"><button id="unlocked" class="layui-btn">解锁</button></div>',
            closeBtn: 0,
            shade: 0.95,
            offset: '350px'
        });

        $('#unlocked').click(function() {
            var pwd = $('#unlockedPwd').val();
            if (pwd == '') {
                return false;
            }
            $.post(ADMIN_PATH+'/system/publics/unlocked', {password:md5.exec(pwd)}, function(res) {
                if (res.code == 1) {
                    window.sessionStorage.setItem("lockscreen", false);
                    layer.closeAll();
                } else {
                    $('#unlockedPwd').attr('placeholder', res.msg).val('');
                }
            });
        });
    }
    /* 锁屏 */
    $('#lockScreen').click(function () {
        window.sessionStorage.setItem("lockscreen", true);
        lockscreen();
    });
    if(window.sessionStorage.getItem("lockscreen") == "true"){
        lockscreen();
    }
    
    /* 导航高亮标记 */
    $('.admin-nav-item').click(function() {
        window.localStorage.setItem("adminNavTag", $(this).attr('href'));
    });
    if (window.localStorage.getItem("adminNavTag")) {
        $('#switchNav a[href="'+window.localStorage.getItem("adminNavTag")+'"]').parent('dd').addClass('layui-this').parents('li').addClass('layui-nav-itemed').siblings('li').removeClass('layui-nav-itemed');
    }
    if (typeof(LAYUI_OFFSET) == 'undefined') {
        layer.config({offset:'50px'});
    } else {
        layer.config({offset:LAYUI_OFFSET});  
    }

    /* 打开/关闭左侧导航 */
    $('#foldSwitch').click(function(){
        var that = $(this);
        if (!that.hasClass('ai-zhankaicaidan')) {
            that.addClass('ai-zhankaicaidan').removeClass('ai-shouqicaidan');
            $('#switchNav').animate({width:'0'}, 100).addClass('close').hover(function() {
                if (that.hasClass('ai-zhankaicaidan')) {
                    $(this).animate({width:'200px'}, 300);
                    $('#switchNav .fold-mark').removeClass('fold-mark');
                    $('a[href="'+window.localStorage.getItem("adminNavTag")+'"]').parent('dd').addClass('layui-this').parents('li').addClass('layui-nav-itemed').siblings('li').removeClass('layui-nav-itemed');
                }
            },function() {
                if (that.hasClass('ai-zhankaicaidan')) {
                    $(this).animate({width:'0'}, 300);
                    $('#switchNav .layui-nav-item').addClass('fold-mark').removeClass('layui-nav-itemed');
                }
            });
            $('#switchBody,.footer').animate({left:'0'}, 100);
            $('#switchNav .layui-nav-item').addClass('fold-mark').removeClass('layui-nav-itemed');
        } else {
            $('a[href="'+window.localStorage.getItem("adminNavTag")+'"]').parent('dd').addClass('layui-this').parents('li').addClass('layui-nav-itemed').siblings('li').removeClass('layui-nav-itemed');
            that.removeClass('ai-zhankaicaidan').addClass('ai-shouqicaidan');
            $('#switchNav').animate({width:'200px'}, 100).removeClass('close');
            $('#switchBody,.footer').animate({left:'200px'}, 100);
            $('#switchNav .fold-mark').removeClass('fold-mark');
        }
    });

    /* 导航菜单切换 */
    $('.main-nav a').click(function () {
        var that = $(this), i = $('.main-nav a').index(this);
        $('.layui-nav-tree').hide().eq(i).show();
    });

    /* 操作提示 */
    $('.help-tips').click(function(){
        layer.tips($(this).attr('data-title'), this, {
            tips: [3, '#009688'],
            time: 5000
        });
        return false;
    });

    /* 全屏切换 */
    $('#fullscreen-btn').click(function(){
        var that = $(this);
        if (!that.hasClass('ai-quanping')) {
            $('#switchBody').css({'z-index':10000});
            $('#switchNav').css({'z-index':900});
            that.addClass('ai-quanping').removeClass('ai-quanping1').parents('.page-body').addClass('fullscreen');
            $('.page-tab-content').css({'min-height':($(window).height()-63)+'px'});
        } else {
            $('#switchBody').css({'z-index':998});
            $('#switchNav').css({'z-index':1000});
            that.addClass('ai-quanping1').removeClass('ai-quanping').parents('.page-body').removeClass('fullscreen');
            $('.page-tab-content').css({'min-height':'auto'});
        }
    });

    /* 静态表格全选 */
    form.on('checkbox(allChoose)', function(data) {
        var child = $(data.elem).parents('table').find('tbody input.checkbox-ids');
        child.each(function(index, item) {
            item.checked = data.elem.checked;
        });
        form.render('checkbox');
    });

    /* 后台主题设置 */
    $('#hisi-theme-setting').on('click', function() {
        var that = $(this);
        layer.open({
            type: 5,
            title: '主题方案',
            shade: 0.3,
            area: ['295px', '93%'],
            offset: 'rb',
            maxmin: true,
            shadeClose: true,
            closeBtn: false,
            anim: 2,
            content: $('#hisi-theme-tpl').html(),
            success: function(layero, index) {
                $('.hisi-themes li').on('click', function() {
                    var theme = $(this).attr('data-theme');
                    $.get(that.attr('href'), {theme : theme}, function(res) {
                        if (res.code == 0) {
                            layer.msg(res.msg);
                        } else {
                            $('body').prop('class', 'hisi-theme-'+theme);
                            $('.hisi-themes li').removeClass('active');
                            $('#hisi-theme-item-'+theme).addClass('active');
                        }
                    }, 'json');
                });
            }
        }); 
        return false;
    });

    /* 清空缓存 */
    $('#hisi-clear-cache').on('click', function() {
        var that = $(this);
        layer.open({
            type: 5,
            title: '删除缓存',
            shade: 0.3,
            area: ['205px', '93%'],
            offset: 'rb',
            maxmin: true,
            shadeClose: true,
            closeBtn: false,
            anim: 2,
            content: $('#hisi-clear-cache-tpl').html(),
            success: function(layero, index) {
                form.render('checkbox');
            }
        }); 
        return false;
    });

    /**
     * 删除快捷菜单
     * @attr data-href 请求地址
     */
    $('.j-del-menu,.hisi-del-menu').click(function(){
        var that = $(this);
        layer.confirm('删除之后无法恢复，您确定要删除吗？', {title:false, closeBtn:0}, function(index){

            $.post(that.attr('data-href'), function(res) {
                layer.msg(res.msg);
                if (res.code == 1) {
                    that.parents('dd').animate({left:'-1000px'},function(){
                        $(this).remove();
                    });
                }
            });
            layer.close(index);
        });

    });

    /**
     * iframe弹窗
     * @href 弹窗地址
     * @title 弹窗标题
     * @hisi-data {width: '弹窗宽度', height: '弹窗高度', idSync: '是否同步ID', table: '数据表ID(同步ID时必须)', type: '弹窗类型'}
     */
    $(document).on('click', '.j-iframe-pop,.hisi-iframe-pop', function() {
        var that = $(this), query = '';
        var def = {refresh: 1, width: '750px', height: '500px', idSync: false, table: 'dataTable', type: 2, url: that.attr('href'), title: that.attr('title')};
        var opt = new Function('return '+ that.attr('hisi-data'))() || {};

        opt.url     = opt.url || def.url;
        opt.refresh  = opt.refresh || def.refresh;
        opt.title   = opt.title || def.title;
        opt.width   = opt.width || def.width;
        opt.height  = opt.height || def.height;
        opt.type    = opt.type || def.type;
        opt.table   = opt.table || def.table;
        opt.idSync  = opt.idSync || def.idSync;

        if (!opt.url) {
            layer.msg('请设置href参数');
            return false;
        }

        if (opt.idSync) {// ID 同步
            if ($('.checkbox-ids:checked').length <= 0) {
                var checkStatus = table.checkStatus(opt.table);
                if (checkStatus.data.length <= 0) {
                    layer.msg('请选择要操作的数据');
                    return false;
                }

                for (var i in checkStatus.data) {
                    query += '&id[]=' + checkStatus.data[i].id;
                }
            } else {
                $('.checkbox-ids:checked').each(function() {
                    query += '&id[]=' + $(this).val();
                })
            }
        }

        if (opt.url.indexOf('?') >= 0) {
            opt.url += '&hisi_iframe=yes'+query;
        } else {
            opt.url += '?hisi_iframe=yes'+query;
        }

        layer.open({
            type: opt.type, 
            title: opt.title, 
            content: opt.url, 
            area: [opt.width, opt.height],
            cancel: function(index, layero){ 
              // if(confirm('确定要关闭么')){ //只有当点击confirm框的确定时，该层才会关闭
              //   layer.close(index);
              //   location.reload();
              // }
              layer.close(index);
              if(opt.refresh == 1){
                location.reload();
              }
              return false; 
            }  

        });
        return false;
    });
	
	/**
     * 通用状态设置开关
     * @attr data-href 请求地址
     */
    form.on('switch(switchStatus)', function(data) {
        var that = $(this), status = 0;
        if (!that.attr('data-href')) {
            layer.msg('请设置data-href参数');
            return false;
        }
        if (this.checked) {
            status = 1;
        }
        $.get(that.attr('data-href'), {val:status}, function(res) {
            layer.msg(res.msg);
            if (res.code == 0) {
                that.trigger('click');
                form.render('checkbox');
            }
        });
    });

    /**
     * 监听表单提交
     * @attr action 请求地址
     * 例如：<button type="submit" class="layui-btn upload_btn" lay-submit lay-filter="formSubmit">提交</button>
     * 
     * @attr data-form 表单DOM
     */
    form.on('submit(formSubmit)', function(data) {
        var _form = '', 
            that = $(this), //button标签
            text = that.text(), //return 提交
            options = {pop: false, refresh: true, jump: false, callback: null};
        //如果button属性data-form有值，则表单 = data-form值
        if ($(this).attr('data-form')) {
            _form = $(that.attr('data-form'));
        //如果不存在，则获取父元素的form标签
        } else {
            _form = that.parents('form');
        }
        
        if (that.attr('hisi-data')) {
            options = new Function('return '+ that.attr('hisi-data'))();
        } else if (that.attr('lay-data')) {
            options = new Function('return '+ that.attr('lay-data'))();
        }
        var formData = _form.serialize();
        var j_data = that.attr('j-data');
        if (j_data){
            j_data = (new Function("return " + j_data))();
            formData = $.param(j_data) + '&' + formData;
        }

        /* CKEditor专用 */
        if (typeof(CKEDITOR) != 'undefined') {
            for (instance in CKEDITOR.instances) {
                CKEDITOR.instances[instance].updateElement();
            }
        }
        that.prop('disabled', true).text('提交中...');
        $.ajax({
            type: "POST",
            url: _form.attr('action'),
            data: formData,
            success: function(res) {
                that.text(res.msg);
                if (res.code == 0) { //如果提价失败
                    that.removeClass('layui-btn-normal').addClass('layui-btn-danger');
                    setTimeout(function(){
                        that.prop('disabled', false);
                        that.removeClass('layui-btn-danger').addClass('layui-btn-normal').text(text);
                    }, 3000);
                } else { //如果提价成功
                    that.removeClass('layui-btn-normal').addClass('layui-bg-green');
                    setTimeout(function() {
                        that.text(text);
                        that.removeClass('layui-bg-green');
                        if (options.callback) {
                            options.callback(that, res);
                        }
                        console.log(options);
                        if (options.pop == true) {
                            console.log('关闭父级弹框');
                            if (options.refresh == true) {
                                parent.location.reload();
                            } else if (options.jump == true && res.url != '') {
                                parent.location.href = res.url;
                            }
                            parent.layui.layer.closeAll();
                        } else if (options.refresh == true) {
                            console.log('关闭当前弹框');
                            if (res.url != '') {
                                location.href = res.url;
                            } else {
                                location.reload();
                            }
                        }
                        //that.prop('disabled', false);  //防止多次提交
                    }, 3000);
                }
            }
        });
        return false;
    });

    /**
     * 通用TR数据行删除
     * @attr href或data-href 请求地址
     * @attr refresh 操作完成后是否自动刷新
     */
    // $(document).on('click', '.j-tr-del,.hisi-tr-del', function() {
    //     var that = $(this),
    //         href = !that.attr('data-href') ? that.attr('href') : that.attr('data-href');
    //         isReload = that.attr('isreload');
    //     layer.confirm('删除之后无法恢复，您确定要删除吗？', {title:false, closeBtn:0}, function(index){
    //         if (!href) {
    //             layer.msg('请设置data-href参数');
    //             return false;
    //         }
    //         var data_index = that.parents('tr').attr('data-index');
    //         console.log(that.parents('tr').attr('data-index'));
    //         $("tr[data-index='"+data_index + "']").remove();

    //         $.get(href, function(res) {
    //             console.log(res);
    //             if (res.code == 0) {
    //                 layer.msg(res.msg);
    //             } else {
    //                 if(isReload){
    //                     location.reload();
    //                 }else{
    //                     that.parents('tr').remove();
    //                 }
                    
    //             }
    //         });
    //         layer.close(index);
    //     });
    //     return false;
    // });

    $(document).on('click', '.j-tr-del,.hisi-tr-del', function() {
        var that = $(this),
            href = !that.attr('data-href') ? that.attr('href') : that.attr('data-href');
            isReload = that.attr('isreload');
        layer.confirm('删除之后无法恢复，您确定要删除吗？', {title:false, closeBtn:0}, function(index){
            if (!href) {
                layer.msg('请设置data-href参数');
                return false;
            }
            var data_index = that.parents('tr').attr('data-index');
            console.log(that.parents('tr').attr('data-index'));
            $("tr[data-index='"+data_index + "']").remove();

            $.get(href, function(res) {
                console.log(res);
                if (res.code == 0) {
                    layer.msg(res.msg);
                } else {
                    if(isReload){
                        location.reload();
                    }else{
                        that.parents('tr').remove();
                    }
                    
                }
            });
            layer.close(index);
        });
        return false;
    });

    table.on('tool(dataTable)', function(obj){ //注：tool 是工具条事件名，test 是 table 原始容器的属性 lay-filter="对应的值"
        
        var data = obj.data //获得当前行数据
        ,layEvent = obj.event; //获得 lay-event 对应的值

        var that = $(this),
            href = !that.attr('data-href') ? that.attr('href') : that.attr('data-href');
            isReload = that.attr('isreload');

        if (!href) {
            layer.msg('请设置data-href参数');
            return false;
        }
        if(layEvent === 'del'){
            layer.confirm('删除之后无法恢复，您确定要删除吗？', function(index){
            $.get(href, function(res) {
                //console.log(res);
                if (res.code == 0) {
                    layer.msg(res.msg);
                } else {
                    if(isReload){
                        location.reload();
                    }else{
                        obj.del(); //删除对应行（tr）的DOM结构
                    }
                    
                }
            });
            layer.close(index);
          });
        }
      });

    /**
     * ajax请求操作
     * @attr href或data-href 请求地址
     * @attr refresh 操作完成后是否自动刷新
     * @class confirm confirm提示内容
     */
    $(document).on('click', '.j-ajax,.hisi-ajax', function() {
        var that = $(this), 
            href = !that.attr('data-href') ? that.attr('href') : that.attr('data-href'),
            refresh = !that.attr('refresh') ? 'yes' : that.attr('refresh');
        if (!href) {
            layer.msg('请设置data-href参数');
            return false;
        }

        if (!that.attr('confirm')) {
            layer.msg('数据提交中...', {time:2000});
            $.get(href, {}, function(res) {
                layer.msg(res.msg, {time:5000}, function() {
                    if(res.data.refresh === undefined || res.data.refresh){
                        if (refresh == 'yes') {
                            if (typeof(res.url) != 'undefined' && res.url != null && res.url != '') {
                                location.href = res.url;
                            } else {
                                location.reload();
                            }
                        }
                    }
                });
            });
            layer.close();
        } else {
            layer.confirm(that.attr('confirm'), {title:false, closeBtn:0}, function(index){
                layer.msg('数据提交中...', {time:2000});
                $.get(href, {}, function(res) {
                    layer.msg(res.msg, {time:5000}, function() {
                        if(res.data.refresh === undefined || res.data.refresh){
                            if (refresh == 'yes') {
                                if (typeof(res.url) != 'undefined' && res.url != null && res.url != '') {
                                    location.href = res.url;
                                } else {
                                    location.reload();
                                }
                            }
                        }
                    });
                });
                layer.close(index);
            });
        }
        return false;
    });

    /**
     * 数据列表input编辑自动选中ids
     * @attr data-value 修改前的值
     */
    $('.j-auto-checked,hisi-auto-checked').blur(function(){
        var that = $(this);
        if(that.attr('data-value') != that.val()) {
            that.parents('tr').find('input[name="ids[]"]').attr("checked", true);
        }else{
            that.parents('tr').find('input[name="ids[]"]').attr("checked", false);
        };
        form.render('checkbox');
    });

    /**
     * input编辑更新
     * @attr data-value 修改前的值
     * @attr data-href 提交地址
     */
    $(document).on('focusout', '.j-ajax-input,.hisi-ajax-input',function(){
        var that = $(this), _val = that.val();
        if (_val == '') return false;
        if (that.attr('data-value') == _val) return false;
        if (!that.attr('data-href')) {
            layer.msg('请设置data-href参数');
            return false;
        }
        $.post(that.attr('data-href'), {val:_val}, function(res) {
            if (res.code == 1) {
                that.attr('data-value', _val);
            }
            layer.msg(res.msg);
        });
    });

    /**
     * 小提示
     */
    $('.tooltip').hover(function() {
        var that = $(this);
        that.find('i').show();
    }, function() {
        var that = $(this);
        that.find('i').hide();
    });

    /**
     * 列表页批量操作按钮组
     * @attr href 操作地址
     * @attr data-table table容器ID
     * @class confirm 类似系统confirm
     * @attr tips confirm提示内容
     */
    $(document).on('click', '.j-page-btns,.hisi-page-btns', function(){
        var that = $(this),
            query = '',
            code = function(that) {
                var href = that.attr('href') ? that.attr('href') : that.attr('data-href');
                var tableObj = that.attr('data-table') ? that.attr('data-table') : 'dataTable';
                //获取主键
                var witchid = that.attr('data-id') ? that.attr('data-id') : 'id';
                if (!href) {
                    layer.msg('请设置data-href参数');
                    return false;
                }
                console.log($('.checkbox-ids:checked'));
                if ($('.checkbox-ids:checked').length <= 0) {
                    var checkStatus = table.checkStatus(tableObj);
                    if (checkStatus.data.length <= 0) {
                        layer.msg('请选择要操作的数据');
                        return false;
                    }
                    for (var i in checkStatus.data) {
                        if (i > 0) {
                            query += '&';
                        }
                        query += 'id[]='+checkStatus.data[i][witchid];
                    }
                } else {
                    if (that.parents('form')[0]) {
                        query = that.parents('form').serialize();
                    } else {
                        query = $('#pageListForm').serialize();
                    }
                }

                layer.msg('数据提交中...');
                $.post(href, query, function(res) {
                    layer.msg(res.msg, {time:5000}, function(){
                        if (res.code != 0) {
                            location.reload();
                        } 
                    });
                });
            };
        if (that.hasClass('confirm')) {
            var tips = that.attr('tips') ? that.attr('tips') : '您确定要执行此操作吗？';
            layer.confirm(tips, {title:false, closeBtn:0}, function(index){
                code(that);
                layer.close(index);
            });
        } else {
           code(that); 
        }
        return false;
    });

    /**
     * 列表页批量excel导出操作按钮组
     * @attr href 操作地址
     * @attr data-table table容器ID
     * @class confirm 类似系统confirm
     * @attr tips confirm提示内容
     */
    $(document).on('click', '.j-page-excel-btns', function(){
        var that = $(this),
            query = '',
            code = function(that) {
                var href = that.attr('href') ? that.attr('href') : that.attr('data-href');
                var tableObj = that.attr('data-table') ? that.attr('data-table') : 'dataTable';
                //获取主键
                var witchid = that.attr('data-id') ? that.attr('data-id') : 'id';
                if (!href) {
                    layer.msg('请设置data-href参数');
                    return false;
                }
                //console.log($('.checkbox-ids:checked'));
                if ($('.checkbox-ids:checked').length <= 0) {
                    var checkStatus = table.checkStatus(tableObj);
                    if (checkStatus.data.length <= 0) {
                        layer.msg('请选择要操作的数据');
                        return false;
                    }
                    for (var i in checkStatus.data) {
                        if (i > 0) {
                            query += '&';
                        }
                        query += 'id[]='+checkStatus.data[i][witchid];
                    }
                } else {
                    if (that.parents('form')[0]) {
                        query = that.parents('form').serialize();
                    } else {
                        query = $('#pageListForm').serialize();
                    }
                }
that.prop('disabled', true);
that.addClass('layui-btn-disabled').text('导出中……');
                layer.msg('数据提交中...',{time:500000});
                $.post(href, query, function(output) {
                    layer.msg(output.msg, {}, function(){
                        if(output.code){ //成功则直接下载                      
                            document.location.href = output.data;
                        }
that.prop('disabled', false);
that.removeClass('layui-btn-disabled').html('<i class="layui-icon layui-icon-download-circle"></i>导出表结构');
                    });
                });
            };
        if (that.hasClass('confirm')) {
            var tips = that.attr('tips') ? that.attr('tips') : '您确定要执行此操作吗？';
            layer.confirm(tips, {title:false, closeBtn:0}, function(index){
                code(that);
                layer.close(index);

            });
        } else {
           code(that); 
        }

        return false;
    });

    /**
     * layui非静态table搜索渲染
     * @attr data-table table容器ID
     * @attr action 搜索请求地址
     */
    $('#hisiSearch,#hisi-table-search,#hisi-table-search-one,#hisi-table-search-two').submit(function() {
        var that = $(this), 
            arr = that.serializeArray(), 
            where = new Array(),
            dataTable = that.attr('data-table') ? that.attr('data-table') : 'dataTable';

        for(var i in arr) {
            where[arr[i].name] = arr[i].value;
        }
        // console.log(that.attr('data-table'));
        // console.log(that.attr('action'));
        table.reload(dataTable, {
            page: true,
            url: that.attr('action'),
            where: where
        });
        return false;
    });
    
    /**
     * layui非静态table过滤渲染
     * @attr data-table table容器ID
     * @attr href 过滤请求地址
     */
    $(document).on('click', '.hisi-table-a-filter', function() {
        var that = $(this), dataTable = that.attr('data-table') ? that.attr('data-table') : 'dataTable';
        table.reload(dataTable, {
          url: that.attr('href'),
          page: true
        });
        return false;
    });
    exports('global', {});
    /*楼层导航 S*/
     $(".j-floor-nav li:first").addClass("on");
     $(".layui-body").on("scroll",function(){
     		var scrollTop = $(document).scrollTop();
     		var floor = $('.floorWrap');
     		var nav = $('.navWrap').find('li');
     		var items = $('.floorWrap').find('.item');
            /* if(scrollTop>150){
    						$(".j-floor-nav").addClass("on");
    						console.log(1);
    					}
    					else{
    						$(".j-floor-nav").removeClass("on");
    						console.log(3);
    					}; */
     		items.each(function(){
     			var m=$(this);
     			var itemTop = m.offset().top;
     			if(scrollTop>itemTop-200){
     				/* console.log(scrollTop-itemTop); */
     				nav.eq(m.index()).addClass('current').siblings().removeClass('current');
     			}else{
     				return false;
     			}
     		})
     	})
    /*楼层导航 E*/
	//显示搜索框title属性值S
	$(".layui-input,.layui-unselect").mouseover(function(){
	  var titles = $(this).val();
	  $(this).attr("title",titles)
	});
	//显示搜索框title属性值E
	$(".j-page-btns").parents(".j-table-content").addClass("on");//表单新发样式
	//报表管理页面将值为0.00的替换为空
	$(".report tr").each(function(){
	        var td=$(this).find("td");
	        td.each(function(){
	            if($(this).text()=="0.00"){
	                $(this).text("");
	            }
	        });
	});
	//判断是否有滚动条，显示图片查看关闭按钮
	$(".j-viewer-img").on("click","img",function(){
	 var obj=document.getElementById("switchBody");
	 if(obj.scrollHeight>obj.clientHeight){
	 	$(".layui-row").addClass("on");
	 }
	 else
	 {
		$(".layui-row").removeClass("on");
	 }
	});

    //添加编辑附件上传图片查看
    $(".j-viewer-img").on("click",function(){
        $(this).viewer({
            url: 'data-original',
         });
         $(this).viewer('update');
    })
	//图片加载失败显示默认图片
    $("img").each(function() {
            var img = $(this);
            img.one("error", function(e){
                img.attr("src", "/static/system/image/add_img.png");
            });
        });
		
		
    //自定义验证规则
	//验证手机和座机
	var mobile = /^1[3|4|5|6|7|8]\d{9}$/,
	phone = /^(0[0-9]{2,3}\-)([2-9][0-9]{6,7})+(\-[0-9]{1,4})?$/;
	form.verify({
		tellphone: function(value){
			var flag = mobile.test(value) || phone.test(value);
			if(!flag){
				return '请输入正确座机号码或手机号';
			}
		}
	});

      //点击tr选中对应的checkbox
      $(document).on("click",".layui-table-body table.layui-table tbody tr", function () {
          var index = $(this).attr('data-index');
          var tableBox = $(this).parents('.layui-table-box');
          //存在固定列
          if (tableBox.find(".layui-table-fixed.layui-table-fixed-l").length>0) {
              tableDiv = tableBox.find(".layui-table-fixed.layui-table-fixed-l");
          } else {
              tableDiv = tableBox.find(".layui-table-body.layui-table-main");
          }
          var checkCell = tableDiv.find("tr[data-index=" + index + "]").find("td div.laytable-cell-checkbox div.layui-form-checkbox i");
          if (checkCell.length>0) {
              checkCell.click();
          }
      });
      
      $(document).on("click", "td div.laytable-cell-checkbox div.layui-form-checkbox", function (e) {
          e.stopPropagation();
      });  
  })

function bytesToSize(bytes) {  
　　if (bytes === 0) return '0 B';
　　var k = 1024;
　　sizes = ['B','K', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y'];
　　i = Math.floor(Math.log(bytes) / Math.log(k))　　
　　//return (bytes / Math.pow(k, i)) + ' ' + sizes[i];
　　return (bytes / Math.pow(k, i)).toPrecision(3) + ' ' + sizes[i];
　　//toPrecision(3) 后面保留两位小数，如1.00GB  
} 

function toFixed(num, s) {
    var times = Math.pow(10, s);
    var des = num * times + 0.5;
    des = parseInt(des, 10) / times;
    return des + '';
}

function formSubmit(type,url,data,index){
    $.ajax({
        type: type,
        url: url,
        data: data,
        success: function(res) {
            console.log(res);
            var that = $('.layui-layer-btn0');
            that.text(res.msg);
            if (res.code == 0) { 
                setTimeout(function(){
                    that.removeClass('disabled').text('确认');
                }, 2000);
            } else {
                setTimeout(function(){
                    parent.location.href = res.url;
                }, 2000);
            }
        }
    });
    return false;
}

/* $('.j-upload-from').bind("click",".j-viewer-img,.upload_img_list",function(){
    $(this).viewer({
     		url: 'data-original',
     	 });
     	 $(this).viewer('update');
  }); */