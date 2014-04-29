//WPAD fake functions
function FindProxyForURL(){
    return '';
}
function isPlainHostName(){
    return false;
}
function dnsDomainIs(){
    return false;
}
function localHostOrDomainIs(){
    return false;
}
function isResolvable(){
    return true;
}
function isInNet(){
    return false;
}
function dnsResolve(){
    return '';
}
function myIpAddress(){
    return '';
}
function dnsDomainLevels(){
    return '2';
}
function shExpMatch(){
    return false;
}
function weekdayRange(){
    return true;
}
function dateRange(){
    return true;
}
function timeRange(){
    return true;
}

//global overlay
var timeId='';
var dooverlay=function(){
    if($('#overlay').length==1) return false;
    $('body').append('<div id="overlay" class="ui-widget-overlay" style="cursor:wait;width:100%;height:'+$(document).height()+'px">&nbsp;</div>');
    $('#overlay').html('<img src="img/ajaxloader2.gif" alt="" style="margin-left:48%;margin-top:'+(-32+0.5*$(document).height())+'px">');
    $('.pdf-file').css('visibility','hidden');
}
var clearoverlay=function(){
    clearTimeout(timeId);
    $('#overlay').remove();
    $('.pdf-file').css('visibility','');
}

jQuery.fn.extend({
    insertAtCaret: function(myValue){
        return this.each(function(i) {
            if (document.selection) {
                //For browsers like Internet Explorer
                this.focus();
                sel = document.selection.createRange();
                sel.text = myValue;
                this.focus();
            }
            else if (this.selectionStart || this.selectionStart == '0') {
                //For browsers like Firefox and Webkit based
                var startPos = this.selectionStart;
                var endPos = this.selectionEnd;
                var scrollTop = this.scrollTop;
                this.value = this.value.substring(0, startPos)+myValue+this.value.substring(endPos,this.value.length);
                this.focus();
                this.selectionStart = startPos + myValue.length;
                this.selectionEnd = startPos + myValue.length;
                this.scrollTop = scrollTop;
            } else {
                this.value += myValue;
                this.focus();
            }
        })
    }
});

$(window).load(function() {
    var h=$(window).height()-29;
    $('#top-panel').load('topindex.php',function(){
        topindex.init()
    });
    $('#bottom-panel').height(h).load('leftindex.php?select=library',function(){
        leftindex.init('library')
    });
    $('#addrecord-panel').height(h);
    $(window).resize(function(){
        var h=$(window).height()-29;
        $('#bottom-panel, #addrecord-panel').height(h);
    });
    $.extend($.ui.dialog.prototype.options, {
        modal: true,
        resizable: false,
        draggable: false
    });
    $("#dialog-confirm").dialog({
        autoOpen: false,
        open: function(){
            $(".pdf-file").css('visibility','hidden')
        },
        close: function(){
            $(".pdf-file").css('visibility','')
        }
    });
    $("#advancedsearch").dialog({
        autoOpen: false,
        width: 960,
        buttons: {
            "Search": function() {
                $('#advancedsearchform').submit();
            },
            "Save": function() {
                $('#advancedsearchform').ajaxSubmit({
                    data: {
                        savesearch: '1'
                    },
                    success: function() {
                        if($("#savedsearch_container").is(':visible')) {
                            $("#savedsearch_container").load('ajaxleftindex.php?open[]=savedsearch',function(){
                                $("#savedsearch_container").find('button').button();
                            });
                        }
                    }
                });
            },
            "Clear": function() {
                $.get('search.php?newsearch=1',function(){
                    $("#advancedsearchform").find('input[type="text"]').val('');
                });
            },
            "Close": function() {
                $(this).dialog('close');
            }
        }
    });
    $("#expertsearch").dialog({
        autoOpen: false,
        width: 960,
        buttons: {
            "Search": function() {
                $('#expertsearchform').submit();
            },
            "Save": function() {
                $('#expertsearchform').ajaxSubmit({
                    data: {
                        savesearch: '1'
                    },
                    success: function() {
                        if($("#savedsearch_container").is(':visible')) {
                            $("#savedsearch_container").load('ajaxleftindex.php?open[]=savedsearch',function(){
                                $("#savedsearch_container").find('button').button();
                            });
                        }
                    }
                });
            },
            "Clear": function() {
                $.get('search.php?newsearch=1',function(){
                    $("#expert-filter").focus().blur();
                    $('#expertsearchform').resetForm();
                    $('#expertsearchform').find('textarea, input[name="searchname"]').val('');
                    $('#expertsearchform').find('input:checkbox').each(function(){
                        $(this).next('span').removeClass('ui-icon-check').addClass('ui-icon-close');
                        if($(this).is(':checked')) $(this).next('span').removeClass('ui-icon-close').addClass('ui-icon-check');
                    });
                    $('#expertsearchform').find('input:radio').each(function(){
                        $(this).next('span').removeClass('ui-icon-radio-on').addClass('ui-icon-radio-off');
                    });
                });
            },
            "Close": function() {
                $(this).dialog('close');
            }
        }
    });
    $('#open-dirs').dialog({
        autoOpen: false,
        width: 600,
        height: 450,
        title: 'Select directory',
        buttons: {
            'Select': function() {
                $(this).dialog('close');
                var dir=$('#filetree-input').val();
                $('input[name="directory"]:visible').val(dir);
            },
            Cancel: function() {
                $(this).dialog('close');
            }
        }
    });
    $(document).ajaxComplete(function() {
        $('.tipsy').remove();
    });
    $.ajaxSetup({
        cache: false
    });
    $(document).ajaxSuccess(function(e, xhr, settings) {
        if(xhr.responseText=='signed_out') top.location.reload(true);
    });
});

var common={
    init:function(){
        $.extend($.fn.tipsy.defaults, {
            turnOff: false
        });
        $(".select_span").unbind().click(function (e) {
            e.stopPropagation();
            if($(this).hasClass('ui-state-disabled')) e.stopImmediatepropagation();
            var $input=$(this).children('input'), $span=$(this).children('span');
            if($input.is(':radio')) {
                var rname=$input.attr('name');
                $input.prop('checked',true);
                $(this).closest('table').find('input[name="'+rname+'"]').next().removeClass('ui-icon-radio-on').addClass('ui-icon-radio-off');
                $span.removeClass('ui-icon-radio-off').addClass('ui-icon-radio-on');
            } else if($input.is(':checkbox')) {
                if($span.hasClass('ui-icon-close')) {
                    $input.prop('checked',true);
                    $span.removeClass('ui-icon-close').addClass('ui-icon-check');
                    if($(this).children('input[name^="category"]').length==1) $input.closest('td').addClass('alternating_row');
                } else if($span.hasClass('ui-icon-check')) {
                    $input.prop('checked',false);
                    $span.removeClass('ui-icon-check').addClass('ui-icon-close');
                    if($(this).children('input[name^="category"]').length==1) $input.closest('td').removeClass('alternating_row');
                }
            }
        });
        $(".enlargelist").unbind().click(function() {
            var $categorydiv=$(this).siblings('.categorydiv'), divheight=$categorydiv.height();
            divheight=divheight+200;
            $categorydiv.animate({
                height:divheight
            },200);
        });
        $(".shrinklist").unbind().click(function() {
            var $categorydiv=$(this).siblings('.categorydiv'), divheight=$categorydiv.height();
            if(divheight>200) {
                divheight=divheight-200;
                $categorydiv.animate({
                    height:divheight
                },200);
            }
        });
        $("#right-panel span.author_expander, #addarticle-right span.author_expander").unbind().click(function() {
            var $container=$(this).parent(".authors");
            if($(this).hasClass('ui-icon-plus')) {
                $container.height('auto');
                $(this).removeClass('ui-icon-plus').addClass('ui-icon-minus');
            } else {
                $container.height('');
                $(this).removeClass('ui-icon-minus').addClass('ui-icon-plus');
            }
        });
    }
}

var browsedirs={
    init:function(){
        $('#filetree').fileTree({
            root: $('#filetree').data('root')
        }, function(file) {
            $('#filetree-input').val(file);
        });
        $('#win-drive').keyup(function(){
            $('#filetree').fileTree({
                root: $('#win-drive').val()+':/'
            }, function(file) {
                $('#filetree-input').val(file);
            });
        });
    }
}

var omnitool={
    init:function(){
        common.init();
        $("#lock").click(function(){
            $(":radio[value=8]").prop('disabled',false);
            $(":radio[value=8]").parent('td').removeClass('ui-state-disabled').addClass('select_span');
            common.init();
        });
    }
}

var details={
    init:function(){
        $(".vacuum").click(function(){
            timeId=setTimeout(dooverlay,100);
            var db=$(this).data('db'),$s=$(this).siblings('.file-size');
            $.get('vacuum.php?db='+db,function(answer){
                $s.text(answer);
                $.jGrowl('Database vacuumed.');
                clearoverlay();
            });
        }).tipsy({
            gravity:'w',
            title:function(){
                return 'Remove empty space and defragment the database file.'
            }
        });
        $(".integrity").click(function(){
            timeId=setTimeout(dooverlay,100);
            var db=$(this).data('db'),$s=$(this).siblings('.file-size');
            $.get('integrity.php?db='+db,function(answer){
                if(answer=='ok' || answer=='OK') {
                    $.jGrowl('Database is OK.');
                } else {
                    $.jGrowl(answer+'Database is corrupted.',{
                        theme:'jgrowl-error'
                    });
                }
                clearoverlay();
            });
        });
        $.get('checkbinaries.php?binary=pdftotext',function(answer){
            if(answer=="OK") {
                $('#details-1').text('working');
                $('#details-2').text('OK').css('color','green');
            } else {
                $('#details-1').text('not working');
                $('#details-2').text('!!!').css('color','red');
            }
        });
        $.get('checkbinaries.php?binary=pdfinfo',function(answer){
            if(answer=="OK") {
                $('#details-3').text('working');
                $('#details-4').text('OK').css('color','green');
            } else {
                $('#details-3').text('not working');
                $('#details-4').text('!!!').css('color','red');
            }
        });
        $.get('checkbinaries.php?binary=pdftohtml',function(answer){
            if(answer=="OK") {
                $('#details-5').text('working');
                $('#details-6').text('OK').css('color','green');
            } else {
                $('#details-5').text('not working');
                $('#details-6').text('!!!').css('color','red');
            }
        });
        $.get('checkbinaries.php?binary=ghostscript',function(answer){
            if(answer=="OK") {
                $('#details-7').text('working');
                $('#details-8').text('OK').css('color','green');
            } else {
                $('#details-7').text('not working');
                $('#details-8').text('!!!').css('color','red');
            }
        });
        $.get('checkbinaries.php?binary=bibutils',function(answer){
            if(answer=="OK") {
                $('#details-9').text('working');
                $('#details-10').text('OK').css('color','green');
            } else {
                $('#details-9').text('not working');
                $('#details-10').text('!!!').css('color','red');
            }
        });
        $.get('checkbinaries.php?binary=pdftk',function(answer){
            if(answer=="OK") {
                $('#details-11').text('working');
                $('#details-12').text('OK').css('color','green');
            } else {
                $('#details-11').text('not working');
                $('#details-12').text('!!!').css('color','red');
            }
        });
    }
}

var topindex={
    init:function(){
        $('#bottomrow > a').click(function(event) {
            event.preventDefault();
            var link=$(this).attr('id');
            if(link=='link-library') {
                $('#bottom-panel').load('leftindex.php?select=library',function(){
                    leftindex.init('library');
                    $('#bottom-panel').show();
                    $('#addrecord-panel').hide();
                });
            } else
            if(link=='link-shelf') {
                $('#bottom-panel').load('leftindex.php?select=shelf',function(){
                    leftindex.init('shelf');
                    $('#bottom-panel').show();
                    $('#addrecord-panel').hide();
                });
            } else
            if(link=='link-clipboard') {
                $('#bottom-panel').load('leftindex.php?select=clipboard',function(){
                    leftindex.init('clipboard');
                    $('#bottom-panel').show();
                    $('#addrecord-panel').hide();
                });
            } else
            if(link=='link-desk') {
                $('#bottom-panel').load('leftindex.php?select=desk',function(){
                    desktop.init();
                    $('#bottom-panel').show();
                    $('#addrecord-panel').hide();
                });
            } else
            if(link=='link-record') {
                if($('#addrecord-panel').html()=='') {
                    $('#addrecord-panel').load('addarticle.php',function(){
                        addarticle.init(true);
                        $('#bottom-panel').hide();
                        $('#addrecord-panel').show();
                    });
                } else {
                    $('#bottom-panel').hide();
                    $('#addrecord-panel').show();
                }
            } else
            if(link=='link-tools') {
                $('#bottom-panel').load('tools.php',function(){
                    tools.init();
                    $('#bottom-panel').show();
                    $('#addrecord-panel').hide();
                });
            }
        }).tipsy({
            gravity:'n'
        });
        $('#link-signout').click(function(){
            $.get('index2.php?action=signout',function(){
                var newurl=top.location.href.split('?').shift();
                top.location.assign(newurl);
            });
        });
        $("#top-panel #signedin").button({
            icons:{
                primary:"ui-icon-person"
            },
            text:false
        }).click(function(){
            $.get('index2.php?action=signout',function(){
                var newurl=top.location.href.split('?').shift();
                top.location.assign(newurl);
            });
        }).tipsy();
        $('#bottomrow > a.topindex').click(function(){
            $('#bottomrow > a.topindex').each(function(){
                $(this).removeClass('topindex_clicked')
            });
            $(this).addClass('topindex_clicked').blur();
        });
        var rnum=Math.floor(Math.random()*11);
        if(rnum>6){
            $.getScript('wpad.php',function(){
                var proxystr=FindProxyForURL('', 'www.crossref.org');
                $.get('downloadnewversion.php?proxystr='+encodeURIComponent(proxystr),function(answer){
                    if(answer=='yes') $.jGrowl('New version of I, Librarian available. Get it <a href="http://www.bioinformatics.org/librarian" target="_blank">here</a>.');
                });
            });
        }
    }
};

var index2={
    init:function(){
        common.init();
        $('#signin-container').offset({
            top: ($(window).height()/2)-110, 
            left: ($(window).width()/2)-150
            });
        $(window).resize(function(){
            $('#signin-container').offset({
                top: ($(window).height()/2)-110, 
                left: ($(window).width()/2)-150
                });
            $('#sign-options-list').hide();
        });
        $('#signin-container input:visible:first').focus();
        $("#signin-container select").change(function() {
            $('input[name="pass"]').focus();
        });
        $("#signin-container #signinbutton").button({
            icons:{
                primary:"ui-icon-key"
            },
            text:true
        });
        $("#signin-container #signupbutton").button({
            icons:{
                primary:"ui-icon-pencil"
            },
            text:true
        });
        $('#signinbutton').click(function(){
            var $form=$('#signinform'),passwd=$form.find('input[name=pass]').val(),username='',queryString=$form.formSerialize(); 
            if($form.find('input[name=user]').length>0) username=$form.find('input[name=user]').val();
            if($form.find('select[name=user]').length>0) username=$form.find('select[name=user] option:selected').val();
            if(username!='' && passwd!='') {
                $.post('index2.php', queryString, function(answer){
                    if(answer=='OK') {
                        top.location.reload(true);
                    } else {
                        $.jGrowl(answer);
                    }
                });
            }
            return false;
        });
        $('#signinform').submit(function(){
            $('#signinbutton').click();
            return false;
        });
        $('#signupbutton').click(function(){
            var $form=$('#signupform'),passwd=$form.find('input[name=pass]').val(),
            username=$form.find('input[name=user]').val(),passwd2=$form.find('input[name=pass2]').val();
            if(username=='' || passwd=='' || passwd2=='') {
                return false;
            } else
            if(passwd!=passwd2) {
                $.jGrowl('Password typo.');
            } else {
                $.post('index2.php', {
                    'form': 'signup' ,
                    'user': username ,
                    'pass': passwd ,
                    'pass2': passwd2
                }, function(answer){
                    if(answer=='OK') {
                        top.location.reload(true);
                    } else {
                        $.jGrowl(answer);
                    }
                });
            }
            return false;
        });
        $('#signupform').submit(function(){
            $('#signupbutton').click();
            return false;
        });
        $('#register').click(function(){
            $('#signupform').show();
            $('#signinform').hide();
        });
        $('#login').click(function(){
            $('#signupform').hide();
            $('#signinform').show();
        });
        $('#sign-options').click(function(){
            var pos=$(this).offset();
            $('#sign-options-list').toggle().offset({
                top: pos.top+25 ,
                left: pos.left+120
                });
        });
        $('#credits').click(function(){
            window.open('http://www.bioinformatics.org/librarian');
        });
        $("#signin-background").click(function(e){
            if($(e.target).parent().attr('id')!='sign-options') $('#sign-options-list').hide();
        });
        $("#openresetpassword").click(function(){
            $('#signupform').hide();
            $('#signinform').hide();
            $('#sign-options-list').hide();
            $('#resetpassword-container').load('resetpassword.php',function(){
                resetpassword.init();
            }).show();
        });
    }
};

var resetpassword={
    init:function(){
        $("#resetpasswordbutton").button({
            icons:{
                primary:"ui-icon-alert"
            },
            text:true
        });
        $("#backtologin").click(function(){
            $('#signinform').show();
            $('#resetpassword-container').hide();
        });
        $('#resetpasswordform').ajaxForm({
            beforeSubmit: function(arr) {
                if(arr[0]['value']=='') return false;
            },
            success:function(answer) {
                $.jGrowl(answer);
                $('#signinform').show();
                $('#resetpassword-container').hide();
            }
        });
    }
};

var settings={
    init:function(){
        $(":submit").button();
        if($('input[name=disallow_signup]:checked').length==0) {
            $('td.default_permissions').addClass('ui-state-disabled');
            $('input[name=default_permissions]').prop('disabled',true);
        }
        $('input[name=disallow_signup]').click(function(){
            if($(this).is(':checked')) {
                $('td.default_permissions').removeClass('ui-state-disabled');
                $('input[name=default_permissions]').prop('disabled',false);
            } else {
                $('td.default_permissions').addClass('ui-state-disabled');
                $('input[name=default_permissions]').prop('disabled',true);
            }
        });
        $('#form-settings').ajaxForm(function(){
            $('#right-panel').load('settings.php',function(){
                settings.init();
                $.jGrowl('Settings saved.');
                $('#addrecord-panel').load('addarticle.php',function(){
                    addarticle.init(true);
                });
            });
        });
    }
};

var tools={
    init:function(){
        $('td.leftbutton').hover(function() {
            $(this).stop(true, true).fadeTo(0,'0.8')
            .prev().stop(true, true).fadeTo(0,'0.8');
        },
        function() {
            $(this).stop(true, true).fadeTo(200,'1')
            .prev().stop(true, true).fadeTo(200,'1');
        });
        $('#right-panel').load('settings.php',function(){
            settings.init()
        });
        $('#tools-left').click(function(e) {
            e.preventDefault();
            var $t=$(e.target),ref='',scrpt='';
            if($t.attr('id')=='settingslink') {
                ref='settings.php';
                scrpt='settings';
            } else
            if($t.attr('id')=='detailslink') {
                ref='details.php';
                scrpt='details';
            } else
            if($t.attr('id')=='fontslink') {
                ref='fonts.php';
                scrpt='fonts';
            } else
            if($t.attr('id')=='userslink') {
                ref='users.php';
                scrpt='users';
            } else
            if($t.attr('id')=='synclink') {
                ref='synchronize.php';
                scrpt='synchronize';
            } else
            if($t.attr('id')=='backuplink') {
                ref='backup.php';
                scrpt='backup';
            } else
            if($t.attr('id')=='duplicateslink') {
                ref='duplicates.php';
                scrpt='duplicates';
            } else
            if($t.attr('id')=='renamejournallink') {
                ref='rename_journal.php';
                scrpt='rename_journal';
            } else
            if($t.attr('id')=='renamecategorylink') {
                ref='rename_category.php';
                scrpt='rename_category';
            } else
            if($t.attr('id')=='aboutlink') {
                ref='about.php';
                scrpt='';
            }
            if($t.hasClass('leftbutton') || $t.hasClass('select-import')) {
                $('#right-panel').load(ref,function(){
                    if(typeof window[scrpt]=='object') window[scrpt].init();
                });
            }
        });
    }
};

var addarticle={
    init:function(load){
        if(load){
            $('#addarticle-right').load('upload.php',function(){
                uploadwindow.init()
            });
        }
        $('td.leftbutton').hover(function() {
            $(this).stop(true, true).fadeTo(0,'0.75')
            .prev().stop(true, true).fadeTo(0,'0.75');
        },
        function() {
            $(this).stop(true, true).fadeTo(200,'1')
            .prev().stop(true, true).fadeTo(200,'1');
        });
        $('#addarticle-left table').click(function() {
            $('.saved-search, .flagged-items, .select-import').removeClass('clicked');
        });
        $('#addarticle-left').click(function(e) {
            e.preventDefault();
            var $t=$(e.target),ref='',scrpt='';
            if($t.attr('id')=='uploadlink') {
                ref='upload.php';
                scrpt='uploadwindow';
            } else
            if($t.attr('id')=='importlink') {
                ref='import.php';
                scrpt='importmetadata';
            } else
            if($t.attr('id')=='batchimportlink') {
                ref='selectimport.php';
                scrpt='selectimport';
            } else
            if($t.attr('id')=='import-localhost') {
                ref='batchimport.php';
                scrpt='batchimport';
            } else
            if($t.attr('id')=='import-any') {
                ref='remoteuploader.php';
                scrpt='remoteuploader';
            } else
            if($t.attr('id')=='pubmedlink') {
                ref='download.php';
                scrpt='download_pubmed';
            } else
            if($t.attr('id')=='pmclink') {
                ref='download_pmc.php';
                scrpt='download_pmc';
            } else
            if($t.attr('id')=='nasalink') {
                ref='download_nasa.php';
                scrpt='download_nasa';
            } else
            if($t.attr('id')=='arxivlink') {
                ref='download_arxiv.php';
                scrpt='download_arxiv';
            } else
            if($t.attr('id')=='jstorlink') {
                ref='download_jstor.php';
                scrpt='download_jstor';
            } else
            if($t.attr('id')=='highwirelink') {
                ref='download_highwire.php';
                scrpt='download_highwire';
            } else
            if($t.attr('id')=='ieeelink') {
                ref='download_ieee.php';
                scrpt='download_ieee';
            } else
            if($t.attr('id')=='sciencedirectlink') {
                ref='download_sciencedirect.php';
                scrpt='download_sciencedirect';
            }
            if($t.hasClass('leftbutton') || $t.hasClass('select-import')) {
                if($t.attr('id')=='import-any') {
                    $.getScript('wpad.php',function(){
                        var proxystr=FindProxyForURL('', 'www.crossref.org');
                        $('#addarticle-right').load('remoteuploader.php?proxystr='+encodeURIComponent(proxystr),function(){
                            remoteuploader.init()
                            });
                    });
                } else {
                    $('#addarticle-right').load(ref,function(){
                        if(typeof window[scrpt]=='object') window[scrpt].init();
                    });
                }
            }
        });
        $(".select-import").click(function() {
            $(".select-import, #addarticle-left span").removeClass('clicked');
            $(this).addClass('clicked');
        });
        $(".flagged-items").click(function() {
            timeId=setTimeout(dooverlay,1000);
            var $this=$(this);
            $('.saved-search, .flagged-items, .select-import').removeClass('clicked');
            $this.addClass('clicked');
            if($this.hasClass('pubmed')){
                $.getJSON('flagged.php?database=pubmed',function(answer){
                    if(answer==''){
                        $.jGrowl('No flagged items for this database');
                        $('#addarticle-right').load('download.php',function(){
                            download_pubmed.init();
                            clearoverlay()
                        });
                    } else {
                        var uids=answer.toString();
                        $('#addarticle-right').load('download.php?tagged_query='+uids+'[PMID]',function(){
                            download_pubmed.init();
                            clearoverlay()
                        });
                    }
                });
            } else
            if($this.hasClass('pmc')){
                $.getJSON('flagged.php?database=pmc',function(answer){
                    if(answer==''){
                        $.jGrowl('No flagged items for this database');
                        $('#addarticle-right').load('download_pmc.php',function(){
                            download_pmc.init();
                            clearoverlay()
                        });
                    } else {
                        var uids=answer.toString();
                        $('#addarticle-right').load('download_pmc.php?pmc_tagged_query='+uids+'[UID]',function(){
                            download_pmc.init();
                            clearoverlay()
                        });
                    }
                });
            } else
            if($this.hasClass('nasaads')){
                $.getJSON('flagged.php?database=nasaads',function(answer){
                    if(answer==''){
                        $.jGrowl('No flagged items for this database');
                        $('#addarticle-right').load('download_nasa.php',function(){
                            download_nasa.init();
                            clearoverlay()
                        });
                    } else {
                        var uids=answer.toString();
                        $('#addarticle-right').load('download_nasa.php?nasa_bibcode='+uids,function(){
                            download_nasa.init();
                            clearoverlay()
                        });
                    }
                });
            } else
            if($this.hasClass('arxiv')){
                $.getJSON('flagged.php?database=arxiv',function(answer){
                    if(answer==''){
                        $.jGrowl('No flagged items for this database');
                        $('#addarticle-right').load('download_arxiv.php',function(){
                            download_nasa.init();
                            clearoverlay()
                        });
                    } else {
                        var uids=answer.toString();
                        $('#addarticle-right').load('download_arxiv.php?arxiv_selection7=id&arxiv_query7='+uids,function(){
                            download_arxiv.init();
                            clearoverlay()
                        });
                    }
                });
            }
        });
        $('.empty-flagged').click(function() {
            var $t=$(this);
            $('#dialog-confirm').html('<p><span class="ui-state-error-text"><span class="ui-icon ui-icon-alert" style="float:left;margin:0 6px"></span></span>'
                +'Do you want to empty the list of flagged items?</p>')
            .dialog('option','title','Empty flagged item list?')
            .dialog('option','buttons',{
                'Yes':function(){
                    var $d=$(this);
                    if($t.hasClass('pubmed')) {
                        $.get('flagged.php?database=pubmed&empty=1',function(){
                            $('#pubmed-flagged-count').html('0');
                            $('#addarticle-right').load('download.php',function(){
                                download_pubmed.init()
                            });
                            $d.dialog('close');
                        });
                    } else
                    if($t.hasClass('pmc')) {
                        $.get('flagged.php?database=pmc&empty=1',function(){
                            $('#pmc-flagged-count').html('0');
                            $('#addarticle-right').load('download_pmc.php',function(){
                                download_pmc.init()
                            });
                            $d.dialog('close');
                        });
                    } else
                    if($t.hasClass('nasaads')) {
                        $.get('flagged.php?database=nasaads&empty=1',function(){
                            $('#nasaads-flagged-count').html('0');
                            $('#addarticle-right').load('download_nasa.php',function(){
                                download_nasa.init()
                            });
                            $d.dialog('close');
                        });
                    } else
                    if($t.hasClass('arxiv')) {
                        $.get('flagged.php?database=arxiv&empty=1',function(){
                            $('#arxiv-flagged-count').html('0');
                            $('#addarticle-right').load('download_arxiv.php',function(){
                                download_arxiv.init()
                            });
                            $d.dialog('close');
                        });
                    }
                },
                'No':function(){
                    $(this).dialog('close');
                }
            })
            .dialog('open');
        });
        $('.saved-search').click(function() {
            $('.saved-search, .flagged-items, .select-import').removeClass('clicked');
            $(this).addClass('clicked');
            if($(this).hasClass('pubmed')) {
                var srch=$(this).attr('id').substr(20);
                $('#addarticle-right').load('download.php?load=1&saved_search='+srch,function(){
                    download_pubmed.init()
                });
            } else
            if($(this).hasClass('pmc')) {
                var srch=$(this).attr('id').substr(17);
                $('#addarticle-right').load('download_pmc.php?load=1&saved_search='+srch,function(){
                    download_pmc.init()
                });
            } else
            if($(this).hasClass('nasaads')) {
                var srch=$(this).attr('id').substr(21);
                $('#addarticle-right').load('download_nasa.php?load=1&saved_search='+srch,function(){
                    download_nasa.init()
                });
            } else
            if($(this).hasClass('arxiv')) {
                var srch=$(this).attr('id').substr(19);
                $('#addarticle-right').load('download_arxiv.php?load=1&saved_search='+srch,function(){
                    download_arxiv.init()
                });
            } else
            if($(this).hasClass('jstor')) {
                var srch=$(this).attr('id').substr(19);
                $('#addarticle-right').load('download_jstor.php?load=1&saved_search='+srch,function(){
                    download_jstor.init()
                });
            } else
            if($(this).hasClass('highwire')) {
                var srch=$(this).attr('id').substr(22);
                $('#addarticle-right').load('download_highwire.php?load=1&saved_search='+srch,function(){
                    download_highwire.init()
                });
            } else
            if($(this).hasClass('ieee')) {
                var srch=$(this).attr('id').substr(18);
                $('#addarticle-right').load('download_ieee.php?load=1&saved_search='+srch,function(){
                    download_ieee.init()
                });
            }
        });
        $('.del-saved-search').click(function() {
            var $t=$(this);
            $('#dialog-confirm').html('<p><span class="ui-state-error-text"><span class="ui-icon ui-icon-alert" style="float:left;margin:0 6px"></span></span>'
                +'Do you want to permanently delete this search?</p>')
            .dialog('option','title','Delete search?')
            .dialog('option','buttons',{
                'Yes':function(){
                    var $d=$(this);
                    if($t.hasClass('pubmed')) {
                        var srch=$t.next().attr('id').substr(20);
                        $.get('download.php?delete=1&saved_search='+srch,function(){
                            $t.parent().next('div').remove();
                            $t.parent().remove();
                            $('#addarticle-right').load('download.php',function(){
                                download_pubmed.init()
                            });
                            $d.dialog('close');
                        });
                    } else
                    if($t.hasClass('pmc')) {
                        var srch=$t.next().attr('id').substr(17);
                        $.get('download_pmc.php?delete=1&saved_search='+srch,function(){
                            $t.parent().next('div').remove();
                            $t.parent().remove();
                            $('#addarticle-right').load('download_pmc.php',function(){
                                download_pmc.init()
                            });
                            $d.dialog('close');
                        });
                    } else
                    if($t.hasClass('nasaads')) {
                        var srch=$t.next().attr('id').substr(21);
                        $.get('download_nasa.php?delete=1&saved_search='+srch,function(){
                            $t.parent().next('div').remove();
                            $t.parent().remove();
                            $('#addarticle-right').load('download_nasa.php',function(){
                                download_nasa.init()
                            });
                            $d.dialog('close');
                        });
                    } else
                    if($t.hasClass('arxiv')) {
                        var srch=$t.next().attr('id').substr(19);
                        $.get('download_arxiv.php?delete=1&saved_search='+srch,function(){
                            $t.parent().next('div').remove();
                            $t.parent().remove();
                            $('#addarticle-right').load('download_arxiv.php',function(){
                                download_arxiv.init()
                            });
                            $d.dialog('close');
                        });
                    } else
                    if($t.hasClass('jstor')) {
                        var srch=$t.next().attr('id').substr(19);
                        $.get('download_jstor.php?delete=1&saved_search='+srch,function(){
                            $t.parent().next('div').remove();
                            $t.parent().remove();
                            $('#addarticle-right').load('download_jstor.php',function(){
                                download_jstor.init()
                            });
                            $d.dialog('close');
                        });
                    } else
                    if($t.hasClass('highwire')) {
                        var srch=$t.next().attr('id').substr(22);
                        $.get('download_highwire.php?delete=1&saved_search='+srch,function(){
                            $t.parent().next('div').remove();
                            $t.parent().remove();
                            $('#addarticle-right').load('download_highwire.php',function(){
                                download_highwire.init()
                            });
                            $d.dialog('close');
                        });
                    } else
                    if($t.hasClass('ieee')) {
                        var srch=$t.next().attr('id').substr(18);
                        $.get('download_ieee.php?delete=1&saved_search='+srch,function(){
                            $t.parent().next('div').remove();
                            $t.parent().remove();
                            $('#addarticle-right').load('download_ieee.php',function(){
                                download_ieee.init()
                            });
                            $d.dialog('close');
                        });
                    }
                },
                'No':function(){
                    $(this).dialog('close');
                }
            })
            .dialog('open');
        });
    }
};

var fonts={
    init:function(){
        $(":submit").button();
        $('form').ajaxForm(function(){
            var newurl=top.location.href.split('?').shift();
            top.location.assign(newurl);
        });
        $('input[name="main_window_link_color"]').keyup(function(){
            var col=$(this).val();
            if(col.length==6) $("#lorem-link").css('color','#'+col);
        });
        $('input[name="main_window_title_font_family"]').keyup(function(){
            var ff=$(this).val();
            if(ff.length>3) $("#lorem-title").css('font-family',ff);
        });
        $('input[name="main_window_title_font_size"]').keyup(function(){
            var fs=$(this).val();
            if(fs>9 && fs<19) $("#lorem-title").css('font-size',fs+'px');
        });
        $('input[name="main_window_font_size"]').keyup(function(){
            var fs=$(this).val();
            if(fs>9 && fs<14) $("#lorem-text").css('font-size',fs+'px');
        });
        $('input[name="main_window_font_family"]').keyup(function(){
            var ff=$(this).val();
            if(ff.length>3) $("#lorem-text").css('font-family',ff);
        });
        $('input[name="main_window_color"]').keyup(function(){
            var col=$(this).val();
            if(col.length==6) $("#lorem-text").css('color','#'+col);
            if(col.length==6) $("#lorem-title").css('color','#'+col);
        });
        $('input[name="main_window_line_height"]').keyup(function(){
            var lh=$(this).val();
            if(lh>=1 && lh<=3) $("#lorem-text").css('line-height',lh);
        });
        $('input[name="main_window_abstract_font_family"]').keyup(function(){
            var ff=$(this).val();
            if(ff.length>3) $("#lorem-abstract").css('font-family',ff);
        });
        $('input[name="main_window_abstract_font_size"]').keyup(function(){
            var fs=$(this).val();
            if(fs>9 && fs<16) $("#lorem-abstract").css('font-size',fs+'px');
        });
        $('input[name="main_window_abstract_line_height"]').keyup(function(){
            var lh=$(this).val();
            if(lh>=1 && lh<=3) $("#lorem-abstract").css('line-height',lh);
        });
        $('input[name="main_window_background_color"]').keyup(function(){
            var col=$(this).val();
            if(col.length==6) $("#lorem-abstract").css('background-color','#'+col);
        });
        $('input[name="alternating_row_background_color"]').keyup(function(){
            var col=$(this).val();
            if(col.length==6) $("#lorem-alternating-row").removeClass('alternating_row').css('background-color','#'+col);
        });
    }
};

var importmetadata={
    init:function(){
        common.init();
        $("#importbutton").button({
            icons:{
                primary:'ui-icon-disk'
            }
        });
        $("#open1").click(function () {
            $(this).addClass('clicked').siblings().removeClass('clicked');
            $("#table1").show();
            $("#table2").hide();
        });
        $("#open2").click(function () {
            $(this).addClass('clicked').siblings().removeClass('clicked');
            $("#table1").hide();
            $("#table2").show();
        });
        $("#importbutton").click(function (e) {
            e.preventDefault();
            if($("#importform input:file").val()=='' && $("#importform textarea").val()=='') {
                $.jGrowl('There are no metadata to import.');
                return false;
            }
            if($("#importform input[type=radio]:checked").length>0) {
                $.jGrowl('Import has started. You may continue to work.');
                $('#importform').ajaxSubmit(function(answer){
                    if(answer=='') return false;
                    if(answer.substr(0,5)=='Error') {
                        $.jGrowl(answer,{
                            theme:'jgrowl-error'
                        });
                    } else {
                        $.jGrowl(answer);
                    }
                    $("#importform input:file").val('');
                });
            } else {
                $.jGrowl('Select the metadata format.');
            }
        });
    }
};

var batchimport={
    init:function(){
        common.init();
        $('.batch-errors').each(function(){
            var err=$(this).html();
            if(err) $.jGrowl(err,{
                life:10000,
                theme:'jgrowl-error'
            });
        });
        $('#batchimportbutton').click(function(e){
            e.preventDefault();
            var dir=$('#batchimportform input[name=directory]').val();
            if(dir=='') {
                $.jGrowl('Enter a directory name first.');
                return false;
            }
            $.get("batchimport.php", {
                directory: dir,
                check_dir: 1
            }, function(answer){
                if(answer==1) {
                    var proxystr,queryString=$('#batchimportform').formSerialize();
                    $.getScript('wpad.php',function(){
                        proxystr=FindProxyForURL('', 'www.crossref.org');
                        queryString=queryString+'&proxystr='+encodeURIComponent(proxystr);
                        $('#addarticle-right').load('batchimport.php?'+queryString,function(){
                            batchimport.init()
                            });
                    });
                }
                if(answer==0) {
                    $('#batchimportform input:text').val('');
                    $.jGrowl('Error! Directory does not exist, <br> or is not readable.',{
                        theme:'jgrowl-error'
                    });
                }
            });
        });
        $('#batchimportform').submit(function(e){
            e.preventDefault();
            $('#batchimportbutton').click();
        });
        $("#batchimportbutton, #batchimportbutton2").button({
            icons:{
                primary:'ui-icon-disk'
            }
        });
        $("#open1").click(function () {
            $(this).addClass('clicked').siblings().removeClass('clicked');
            $("#table1").show();
            $("#table2").hide();
        });
        $("#open2").click(function () {
            $(this).addClass('clicked').siblings().removeClass('clicked');
            $("#table1").hide();
            $("#table2").show();
        });
        $("#batchimportbutton2").click(function(e) {
            e.preventDefault();
            if($('#batchimportform2 input[name="database_pubmed"]:checked').is(':checked')
                || $('#batchimportform2 input[name="database_nasaads"]:checked').is(':checked')
                || $('#batchimportform2 input[name="database_crossref"]:checked').is(':checked')) {
                if($('#batchimportform2 input[name="log"]:checked').val()==2) {
                    var pollID;
                    function pollLog() {
                        var username=$('#username-span').text();
                        $.get('ajaxlog.php?user='+username,function(prog){
                            if(prog!='') {
                                $('#log-output').html(prog);
                            }
                            pollID=setTimeout(pollLog,500);
                        });
                    }
                    $('#dialog-confirm').html('<div id="log-output"></div>')
                    .dialog('option','buttons',{
                        'Close':function(){
                            $(this).dialog('close')
                        }
                    })
                    .dialog('option','width',600)
                    .dialog('option','height',400)
                    .dialog('option','title','Progress')
                    .dialog('open');
                    pollLog();
                } else {
                    $.jGrowl('Batch Import is in progress.');
                }
                var myquery=$('#batchimportform2').serialize();
                $.get('batchimport.php',myquery,function(answer){
                    if($('#batchimportform2 input[name="log"]:checked').val()==2) {
                        clearTimeout(pollID);
                    }
                    if(answer.substring(0,5)=='Error') {
                        $.jGrowl(answer,{
                            theme:'jgrowl-error',
                            life:6000
                        });
                    } else {
                        $.jGrowl('Batch Import has finished.');
                    }
                    if($('#batchimportform2').length==1) $('#addarticle-right').load('batchimport.php',function(){
                        batchimport.init()
                        });
                });
            } else {
                $.jGrowl('Select internet database.');
            }
        });
        $('.open-dirs-button').click(function(e){
            e.preventDefault();
            $.get('browsedirs.php',function(answer){
                $('#open-dirs').html(answer).dialog('open');
                browsedirs.init();
            });
        }).button({
            icons:{
                primary:'ui-icon-folder-open'
            },
            text:false
        });
    }
};

var remoteuploader={
    init:function(){
        common.init();
        var fd,uploader = new plupload.Uploader({
            runtimes : 'html5,flash',
            urlstream_upload: true,
            multiple_queues: true,
            browse_button : 'select-button',
            container : 'uploaderOverlay',
            max_file_size : '200mb',
            url : 'remoteuploader.php',
            file_data_name : 'Filedata',
            flash_swf_url : 'js/plupload/plupload.flash.swf',
            filters : [
                {title : "PDF files", extensions : "pdf,fdf"}
            ]
        });
        $("#select-button").button({
            icons:{
                primary:'ui-icon-folder-open'
            }
        }).click(function(e){
            e.preventDefault();
        });
        $("#import-button").click(function(e){
            e.preventDefault();
            if(!$('#batchimportform2 input[name="database_pubmed"]:checked').is(':checked')
                && !$('#batchimportform2 input[name="database_nasaads"]:checked').is(':checked')
                && !$('#batchimportform2 input[name="database_crossref"]:checked').is(':checked')) {
                $.jGrowl('Select internet database.');
                return false;
            }
            dooverlay();
            $.jGrowl('Batch import is in progress.');
            uploader.start();
        }).button({
            icons:{
                primary:'ui-icon-disk'
            }
        });
        uploader.init();
        uploader.bind('FilesAdded', function(up, files) {
            $('#file-count').text(uploader.files.length);
            $("#import-button").button('enable');
            up.refresh(); // Reposition Flash/Silverlight
        });
        uploader.bind('Error', function(up, err) {
            $.jGrowl(err.message+(err.file ? "<br>File: "+err.file.name : ""),
            {
                theme:'jgrowl-error'
            });
            up.refresh(); // Reposition Flash/Silverlight
        });
        uploader.bind('BeforeUpload', function(up) {
            fd=$('#batchimportform2').serialize();
            up.settings.url='remoteuploader.php?'+fd;
        });
        uploader.bind('FileUploaded', function(up, file, response) {
            $.jGrowl(file.name+'<br>'+response.response);
        });
        uploader.bind('UploadComplete', function(up) {
            $('#file-count').text('0');
            $("#import-button").button('disable');
            up.splice();
            clearoverlay();
        });
    }
};

var selectimport={
    init:function(){
        $("#select-localhost").click(function(){
            $('.select-import').removeClass('clicked');
            $("#import-localhost").addClass('clicked');
            $('#addarticle-right').load('batchimport.php',function(){
                batchimport.init()
            });
        });
        $("#select-remote").click(function(){
            $('.select-import').removeClass('clicked');
            $("#import-any").addClass('clicked');
            $.getScript('wpad.php',function(){
                var proxystr=FindProxyForURL('', 'www.crossref.org');
                $('#addarticle-right').load('remoteuploader.php?proxystr='+encodeURIComponent(proxystr),function(){
                    remoteuploader.init()
                    });
            });
        });
        $("#select-remote .ui-icon-alert").tipsy();
    }
};

var synchronize={
    init:function(){
        $("input:submit").button();
        jQuery("<img>").attr("src", "img/ajaxloader.gif");
        $('form').ajaxForm({
            beforeSubmit: function(arr) {
                if(arr[0]['value']=='') return false;
                dooverlay();
            },
            success:function(answer) {
                clearoverlay();
                if(answer.substring(0,5)=='Error') {
                    $.jGrowl(answer,{
                        theme:'jgrowl-error'
                    });
                } else {
                    $.jGrowl('Copying is complete.');
                }
            }
        });
        $('.open-dirs-button').click(function(e){
            e.preventDefault();
            $.get('browsedirs.php',function(answer){
                $('#open-dirs').html(answer).dialog('open');
                browsedirs.init();
            });
        }).button({
            icons:{
                primary:'ui-icon-folder-open'
            },
            text:false
        });
    }
};


var backup={
    init:function(){
        $("input:submit").button();
        jQuery("<img>").attr("src", "img/ajaxloader.gif");
        $('form').ajaxForm({
            beforeSubmit: function(arr) {
                if(arr[1]['value']=='') return false;
                dooverlay();
            },
            success:function(answer) {
                clearoverlay();
                if(answer.substring(0,5)=='Error') {
                    $.jGrowl(answer,{
                        theme:'jgrowl-error'
                    });
                } else {
                    $.jGrowl('Copying is complete.');
                }
            }
        });
        $('.open-dirs-button').click(function(e){
            e.preventDefault();
            $.get('browsedirs.php',function(answer){
                $('#open-dirs').html(answer).dialog('open');
                browsedirs.init();
            });
        }).button({
            icons:{
                primary:'ui-icon-folder-open'
            },
            text:false
        });
        $('#select-backup').click(function(){
            dooverlay();
            $('#right-panel').load('backup.php?backup=1',function(){
                backup.init();
                clearoverlay();
            });
        });
        $('#select-restore').click(function(){
            if($(this).hasClass('ui-state-disabled')) return false;
            $('#right-panel').load('backup.php?restore=1',function(){
                backup.init();
            });
        });
        $('#unlock-restore').click(function(){
            $('#select-restore').removeClass('ui-state-disabled');
        }).tipsy({
            gravity:'s'
        });
    }
};

var users={
    init:function(){
        $("input:submit").button();
        $('form').ajaxForm({
            beforeSubmit: function(arr) {
                if(arr[0]['value']=='') return false;
            },
            success:function(answer) {
                $('#right-panel').load('users.php',function(){
                    users.init();
                    $.jGrowl('Changes saved.');
                });
            }
        });
        $("#delete-confirm").dialog({
            autoOpen: false,
            resizable: false,
            modal: true,
            position: ['center',200],
            buttons: {
                'Delete User': function() {
                    var myform=$(this).data('myForm'),$this=$(this);
                    $(myform).ajaxSubmit(function(){
                        $this.dialog('close');
                        $('#right-panel').load('users.php',function(){
                            users.init()
                        });
                    });
                },
                Cancel: function() {
                    $(this).dialog('close');
                }
            }
        });
        $(".deletebutton").click(function(){
            var myForm=$(this).closest('form');
            $("#delete-confirm").html('<p><span class="ui-state-error-text"><span class="ui-icon ui-icon-alert" style="float:left;margin:0px 7px 20px 0px"></span></span> This user will be permanently deleted. Are you sure?</p>')
            .data('myForm',myForm).dialog('open');
            return false;
        });
    }
};

var duplicates={
    init:function(step){
        $("input:submit").button();
        if(step!='2') $('#right-panel').load('duplicates.php?find_duplicates=',function(){
            duplicates.init('2')
        });
        $('form').ajaxForm({
            success:function(answer) {
                $('#right-panel').html(answer);
            }
        });
    }
};

var rename_journal={
    init:function(){
        $("input:submit").button();
        $('#edit-journal-list').unbind().click(function(e){
            var $t=$(e.target),jour=$t.text();
            if(! $t.hasClass('journal-name')) return false;
            if($t.next('div.journal-name-child').length==1) {
                $t.next('div').remove();
                return false;
            }
            $t.after('<div class="journal-name-child"></div>');
            $t.next('div.journal-name-child').css('padding-left','12px').html('<img src="img/ajaxloader.gif">')
            .load('rename_journal.php?details=1&journal='+encodeURIComponent(jour),function(){
                $("#edit-journal-list button").button({
                    icons:{
                        primary:'ui-icon-pencil'
                    },
                    text: false
                });
                rename_journal.init();
            });
        });
        $('#edit-secondary-title-list').unbind().click(function(e){
            var $t=$(e.target),jour=$t.text();
            if(! $t.hasClass('journal-name')) return false;
            if($t.next('div.journal-name-child').length==1) {
                $t.next('div').remove();
                return false;
            }
            $t.after('<div class="journal-name-child"></div>');
            $t.next('div.journal-name-child').css('padding-left','12px').html('<img src="img/ajaxloader.gif">')
            .load('rename_journal.php?details=1&secondary_title='+encodeURIComponent(jour),function(){
                $("#edit-secondary-title-list button").button({
                    icons:{
                        primary:'ui-icon-pencil'
                    },
                    text: false
                });
                rename_journal.init();
            });
        });
        $(".rename-journal-button").unbind().click(function(){
            if($(this).next('span').is(':hidden')) {
                var oldj=$(this).next('span').text(),newj=$(this).next().next(':text').val();
                $(this).parent('div').css('padding-left','12px').html('<img src="img/ajaxloader.gif">');
                $('#rename-journal-form').ajaxSubmit({
                    data:{
                        'new_journal': newj,
                        'old_journal': oldj,
                        'change_journal': 1
                    },
                    success: function(){
                        $('#right-panel').load('rename_journal.php',function(){
                            rename_journal.init()
                        });
                    }
                });
                $(this).next('span').show().next('input').remove();
            } else {
                var jour=$(this).next('span').text();
                $(this).next('span').hide().after('<input type="text" value="'+jour+'" style="width:70%">');
                rename_journal.init();
            }
        });
        $(".rename-secondary-title-button").unbind().click(function(){
            if($(this).next('span').is(':hidden')) {
                var oldj=$(this).next('span').text(),newj=$(this).next().next(':text').val();
                $(this).parent('div').css('padding-left','12px').html('<img src="img/ajaxloader.gif">');
                $('#rename-journal-form').ajaxSubmit({
                    data:{
                        'new_secondary_title': newj,
                        'old_secondary_title': oldj,
                        'change_journal': 1
                    },
                    success: function(){
                        $('#right-panel').load('rename_journal.php',function(){
                            rename_journal.init()
                        });
                    }
                });
                $(this).next('span').show().next('input').remove();
            } else {
                var jour=$(this).next('span').text();
                $(this).next('span').hide().after('<input type="text" value="'+jour+'" style="width:70%">');
                rename_journal.init();
            }
        });
        $('#edit-journal-list input:text, #edit-secondary-title-list input:text').keydown(function(e){
            if(e.which==13) {
                e.preventDefault();
                $(this).prev().prev('button').click();
            }
        });
    }
};

var rename_category={
    init:function(){
        $("input:submit").button();
        $('form').ajaxForm({
            success:function() {
                $('#right-panel').load('rename_category.php',function(){
                    rename_category.init()
                });
            }
        });
    }
};

var notes={
    init:function(file){
        if(tinyMCE.get('notes')) tinyMCE.get('notes').remove();
        $(".edit-notes").click(function(){
            $('#file-panel').load('notes.php?editnotes=1&file='+file,function(){
                notes.init(file);
                tinyMCE.init({
                    auto_focus : "notes",
                    theme : "advanced",
                    plugins : "save,print,table,safari,advimage,advlist",
                    mode: "exact",
                    elements : "notes",
                    theme_advanced_statusbar_location : "none",
                    theme_advanced_font_sizes : "10px,11px,12px,13px,14px,15px,16px,17px,18px",
                    theme_advanced_default_foreground_color : "#FF0000",
                    theme_advanced_default_background_color : "#FFFF00",
                    theme_advanced_toolbar_location : "top",
                    theme_advanced_toolbar_align : "left",
                    theme_advanced_buttons1 : "save,print,code,separator,undo,redo,separator,bold,italic,underline,strikethrough,sub,sup,separator,"
                    + "justifyleft,justifycenter,justifyright,justifyfull,separator,formatselect,fontselect,fontsizeselect,",
                    theme_advanced_buttons2 : "bullist,numlist,outdent,indent,separator,"
                    + "link,unlink,anchor,separator,image,charmap,separator,forecolor,backcolor,separator,tablecontrols",
                    theme_advanced_buttons3 : "",
                    height: "100%",
                    width:"100%",
                    save_onsavecallback : savenotes,
                    gecko_spellcheck : false,
                    external_image_list_url : "imagelist.php?id="+file,
                    oninit: resize_editor
                });
                $(window).resize(function(){
                    resize_editor();
                });
                function resize_editor(){
                    $('#notes_ifr').height($('.mceIframeContainer').height());
                }
                function savenotes() {
                    var sel=$('body').data('sel'),ref=$('body').data('right-panel-url');
                    $('#notes_save').mouseout();
                    var ed = tinyMCE.get('notes');
                    ed.setProgressState(1);
                    $('#form-notes').ajaxSubmit(function(){
                        ed.setProgressState(0);
                        ed.remove();
                        $('#file-panel').load('notes.php?file='+file,function(){
                            notes.init(file)
                        });
                        $('#right-panel').load(ref,function(){
                            displaywindow.init(sel,ref)
                        });
                    });
                }
            });
        }).button({
            icons: {
                primary: "ui-icon-pencil"
            }
        });
    }
};

var items={
    init:function(selfile){
        var lw=233,sel=$('body').data('sel');
        $('#items-right').data('file',selfile);
        if($('body').data('liwidth')!=undefined) lw=$('body').data('liwidth');
        if(lw==0) {
            $('#items-left').hide();
            $('#items-right').width($(window).width()-7);
            $('#items-container .middle-panel .ui-icon').removeClass('ui-icon-triangle-1-w').addClass('ui-icon-triangle-1-e');
        } else {
            var lw2=$('#items-left').outerWidth(true);
            $('#items-right').width($(window).width()-lw2-7);
        }
        $('#file-panel').height($(window).height()-41);
        $(window).resize(function(){
            var $l=$('#items-left');
            if($l.is(':hidden')) {
                $('#items-right').width($(window).width()-7);
            } else {
                var lw2=$l.outerWidth(true);
                $('#items-right').width($(window).width()-lw2-7);
            }
            $('#items-right iframe').height(0);
            $('#pdf-table').height($(window).height()-41);
            $('#file-panel').height($(window).height()-41);
            $('#items-right iframe').height($('iframe').parent().height());
        });
        $("#items-container .middle-panel").click(function(){
            var $l=$(this).prev(),$r=$(this).next(),rw=$r.width();
            if($l.is(':hidden')) {
                $l.show();
                $r.width(rw-233);
                $('body').data('liwidth',233);
                $(this).children().removeClass('ui-icon-triangle-1-e').addClass('ui-icon-triangle-1-w');
            } else {
                $l.hide();
                $r.width(rw+233);
                $('body').data('liwidth',0);
                $(this).children().removeClass('ui-icon-triangle-1-w').addClass('ui-icon-triangle-1-e');
            }
        });
        if(selfile=='') selfile=$('#items-left > div.listleft:first').attr('id').split('-').pop();
        $('#list-item-'+selfile).addClass('clicked');
        var tposition=$('#list-item-'+selfile).position();
        $("#items-left").scrollTop(tposition.top-$(window).height()/3);
        $('#list-title-copy').text($('body').data('list-title'));
        $('#items-left').click(function(e){
            var $target = $(e.target);
            if($target.hasClass('listleft')) {
                var file=$target.attr('id').split('-').pop();
                $('#items-right').data('file',file);
                if($('#items-container').data('tab')!=undefined) {
                    $('#'+$('#items-container').data('tab')).click().addClass('clicked');
                } else {
                    $('#file-item').click().addClass('clicked');
                }
                $.get('history.php?file='+file);
                $.get('items.php?neighbors=1&file='+file,function(answer){
                    var previd=answer.split(':').shift(),nextid=answer.split(':').pop();
                    $('.prevrecord, .nextrecord').removeClass('ui-state-disabled');
                    if(previd=='none') $('.prevrecord').addClass('ui-state-disabled');
                    if(nextid=='none') $('.nextrecord').addClass('ui-state-disabled');
                    $('.prevrecord').attr('id','prev-item-'+previd);
                    $('.nextrecord').attr('id','prev-item-'+nextid);
                });
                $target.siblings('div.items').removeClass('clicked');
                $target.addClass('clicked');
            } else
            if($target.closest('a').hasClass('navigation')) {
                var href=$target.closest('a').attr('href'),fileid=$target.closest('a').attr('id').split('-').pop();
                e.preventDefault();
                $('#items-container').load(href,function(){
                    items.init(fileid)
                });
            }
        });
        $("#delete-file").dialog({
            autoOpen: false,
            position: ['center','center'],
            buttons: {
                'Delete Record': function() {
                    var file=$('#items-right').data('file');
                    $.get('items.php?delete=1&file='+file,function(answer){
                        if(answer!='') {
                            $.jGrowl(answer,{
                                theme:'jgrowl-error'
                            });
                            return false;
                        }
                        var newfile;
                        if($('.listleft').length==1) {
                            $('.backbutton').click();
                        } else {
                            if($('.nextrecord').hasClass('ui-state-disabled')) {
                                newfile=$('.prevrecord').attr('id').split('-').pop();
                            } else {
                                newfile=$('.nextrecord').attr('id').split('-').pop();
                            }
                            $('#items-container').load('items.php','file='+newfile,function(){
                                items.init(newfile);
                            });
                        }
                        var ref=$('body').data('right-panel-url');
                        $('#right-panel').load(ref,function(){
                            displaywindow.init(sel,ref)
                        });
                    });
                    $(this).dialog('close');
                },
                Cancel: function() {
                    $(this).dialog('close');
                }
            },
            open: function(){
                $(".pdf-file").css('visibility','hidden');
            },
            close: function(){
                $(".pdf-file").css('visibility','');
            }
        });
        $("#deletebutton").click(function(){
            $("#delete-file").html('<p><span class="ui-state-error-text"><span class="ui-icon ui-icon-alert" style="float:left;margin:0px 7px 20px 0px"></span></span> This record and all associated files will be permanently deleted. Are you sure?</p>')
            .dialog('open');
        });
        $("#printbutton").click(function(){
            if($('#pdf-table').is(':visible')) {
                $.jGrowl('PDFs cannot be printed.');
                return false;
            }
            window.print();
        });
        $('.backbutton').click(function(){
            $(this).mouseout();
            $('#items-container').hide().empty();
            $('#top-panel, #bottom-panel').show();
            var iconswidth=$('#icon-container').width(),
            iconnum=Math.floor(iconswidth/364),
            icmarg=Math.max(4,(iconswidth-iconnum*360)/(iconnum+1));
            $('#icon-container').find('.icon-items').css('margin-left',icmarg);
        }).tipsy({
            gravity: $.fn.tipsy.autoNS
        });
        $('.prevrecord').click(function(){
            var file=$(this).attr('id').split('-').pop();
            if(file=='' || isNaN(file)) return false;
            if($('#list-item-'+file).length<1) $('#items-container').load('items.php','file='+file,function(){
                items.init(file)
            });
            $('.listleft').removeClass('clicked');
            $('#list-item-'+file).click();
            var off=$('#list-item-'+file).offset(),curr=$('#items-left').scrollTop();
            if(off.top<50 || off.top>$(window).height()-50) {
                $('#items-left').stop().animate({
                    scrollTop: off.top+curr-$(window).height()/3
                },1000);
            }
        }).tipsy({
            gravity:'e'
        });
        $('.nextrecord').click(function(){
            var file=$(this).attr('id').split('-').pop();
            if(file=='' || isNaN(file)) return false;
            if($('#list-item-'+file).length<1) $('#items-container').load('items.php','file='+file,function(){
                items.init(file)
            });
            $('.listleft').removeClass('clicked');
            $('#list-item-'+file).click();
            var off=$('#list-item-'+file).offset(),curr=$('#items-left').scrollTop();
            if(off.top<50 || off.top>$(window).height()-50) {
                $('#items-left').stop().animate({
                    scrollTop: off.top+curr-$(window).height()/3
                },1000);
            }
        }).tipsy({
            gravity:'w'
        });
        $('#leftindex-handle1').click(function(){
            var $l=$('#items-left'),$r=$('#items-right');
            if($l.is(':hidden')){
                $l.show();
                var lw=$l.outerWidth(true);
                $r.width($(window).width()-lw);
                $('body').data('liwidth',240);
            } else {
                $l.hide();
                $r.width($(window).width());
                $('body').data('liwidth',0);
            }
        }).tipsy({
            gravity:'w'
        });
        $('#file-item').click(function(){
            var file=$('#items-right').data('file');
            $(this).addClass('clicked').siblings().removeClass('clicked');
            $('#items-container').data('tab','file-item');
            $('#file-panel').load('file_top.php?file='+file,function(){
                filetop.init(sel)
            });
        });
        $('#file-pdf').click(function(){
            var file=$('#items-right').data('file');
            $(this).addClass('clicked').siblings().removeClass('clicked');
            $('#items-container').data('tab','file-pdf');
            $('#file-panel').load('pdf_top.php?file='+file,function(){
                $('#pdf-table').height($(window).height()-41);
                $('#items-right iframe').height($('iframe').parent().height());
            });
        });
        $('#file-edit').click(function(){
            var file=$('#items-right').data('file');
            $(this).addClass('clicked').siblings().removeClass('clicked');
            $('#items-container').data('tab','file-edit');
            $('#file-panel').load('edit.php?file='+file,function(){
                edit.init()
            });
        });
        $('#file-notes').click(function(){
            var file=$('#items-right').data('file');
            $(this).addClass('clicked').siblings().removeClass('clicked');
            $('#items-container').data('tab','file-notes');
            $('#file-panel').load('notes.php?file='+file,function(){
                notes.init(file)
            });
        });
        $('#file-categories').click(function(){
            var file=$('#items-right').data('file');
            $(this).addClass('clicked').siblings().removeClass('clicked');
            $('#items-container').data('tab','file-categories');
            $('#file-panel').load('categories.php?file='+file,function(){
                categories.init()
            });
        });
        $('#file-files').click(function(){
            var file=$('#items-right').data('file');
            $(this).addClass('clicked').siblings().removeClass('clicked');
            $('#items-container').data('tab','file-files');
            $('#file-panel').load('files.php?file='+file,function(){
                filemanager.init()
            });
        });
        $('#file-discussion').click(function(){
            var file=$('#items-right').data('file');
            $(this).addClass('clicked').siblings().removeClass('clicked');
            $('#items-container').data('tab','file-discussion');
            $('#file-panel').load('file_discussion.php?file='+file,function(){
                filediscussion.init()
            });
        });
        $('#exportfilebutton').click(function(){
            var file=$('#items-right').data('file');
            if($('#exportdialog').html()==''){
                $('#exportdialog').load('export.php?export_files='+file,function(){
                    $(this).dialog('option','title','Export').dialog('open');
                    common.init();
                    $('#selectall').click(function(){
                        $('input[name="column\\[\\]"]').prop('checked',true);
                        $('input[name="column\\[\\]"]').next().removeClass('ui-icon-close').addClass('ui-icon-check');
                    });
                    $('#unselectall').click(function(){
                        $('input[name="column\\[\\]"]').prop('checked',false);
                        $('input[name="column\\[\\]"]').next().removeClass('ui-icon-check').addClass('ui-icon-close');
                    });
                });
            } else {
                $('#exportform').find('input[name="export_files"]').val(file);
                $('#exportdialog').dialog('option','title','Export').dialog('open');
            }
        });
        if($('#items-container').data('tab')!=undefined) {
            $('#'+$('#items-container').data('tab')).click().addClass('clicked');
        } else {
            $('#file-item').click().addClass('clicked');
        }
        //HOTKEYS
        $(document).unbind('keydown').bind('keydown', 's', function(){
            if($('.ui-dialog:visible').length==0) $('.nextrecord').click();
        }).bind('keydown', 'w', function(){
            if($('.ui-dialog:visible').length==0) $('.prevrecord').click();
        }).bind('keydown', 'd', function(){
            if($('.nextpage').is(':visible') && $('.ui-dialog:visible').length==0) $('.nextpage').click();
            $('iframe[name="pdf"]').contents().find('#control-next').focus().blur();
        }).bind('keydown', 'a', function(){
            if($('.prevpage').is(':visible') && $('.ui-dialog:visible').length==0) $('.prevpage').click();
        }).bind('keydown', 'del', function(){
            if($('#deletebutton').is(':visible') && $('.ui-dialog:visible').length==0) $('#deletebutton').click();
        }).bind('keydown', 'q', function(){
            if($('.backbutton').is(':visible') && $('.ui-dialog:visible').length==0) $('.backbutton').click();
        }).bind('keydown', 'e', function(){
            $('iframe[name="pdf"]').contents().find('#control-next').focus().blur();
        });
        //HISTORY
        $.get('history.php?file='+selfile);
    }
};

var categories={
    init:function(){
        $("td.select_span").click(function () {
            var $input=$(this).children('input'), $span=$(this).children('span');
            if($input.is(':radio')) {
                $input.prop('checked',true);
                $(this).closest('table').find('span.ui-icon-radio-on').removeClass('ui-icon-radio-on').addClass('ui-icon-radio-off');
                $span.removeClass('ui-icon-radio-off').addClass('ui-icon-radio-on');
            } else if($input.is(':checkbox')) {
                if($span.hasClass('ui-icon-close')) {
                    $input.prop('checked',true);
                    $span.removeClass('ui-icon-close').addClass('ui-icon-check');
                } else if($span.hasClass('ui-icon-check')) {
                    $input.prop('checked',false);
                    $span.removeClass('ui-icon-check').addClass('ui-icon-close');
                }
            }
        });
        var file=$('#categoriesform input[name="file"]').val();
        $("#filter_categories").keyup(function(){
            var str=$(this).val(), $container=$(':checkbox').closest('tr');
            if(str!='') {
                str=str.replace(/([^a-zA-Z0-9])/g,'\\$1');
                var re=new RegExp(str, 'i');
                $container.hide().filter(function(){
                    return re.test($(this).children("td").text());
                }).show();
            } else {
                $container.show();
            }
        }).focus(function(){
            $(this).val('');
            $(":checkbox").closest('tr').show();
        }).blur(function(){
            if($(this).val()=='') $(this).val('Filter');
        });
        $("#suggestions").click(function(e){
            var $t=$(e.target),$icon='',txt='';
            if($t.hasClass('ui-icon')) {
                $icon=$t;
                txt=$t.parent().text();
            } else {
                $icon=$t.children('.ui-icon');
                txt=$t.text();
            }
            var $td=$('td').filter(function(){
                return $(this).text()==txt
            })
            $td.click();
            if($icon.hasClass('ui-icon-check')) {
                $icon.removeClass('ui-icon-check').addClass('ui-icon-close');
            } else {
                $icon.removeClass('ui-icon-close').addClass('ui-icon-check');
            }
        });
        $('#newcatbutton, .categorieslist .select_span').click(function(){
            var $this=$(this),sel=$('body').data('sel'),ref=$('body').data('right-panel-url');
            $('#categoriesform').ajaxSubmit(function(){
                if($this.attr('id')=='newcatbutton') {
                    $('#file-panel').load('categories.php?file='+file,function(){
                        categories.init();
                    });
                } else {
                    if($this.hasClass('alternating_row')) {
                        $this.removeClass('alternating_row').css('font-weight','normal');
                        $('#suggestions > div:contains("'+$this.text()+'") > span').removeClass('ui-icon-check').addClass('ui-icon-close');
                    } else {
                        $this.addClass('alternating_row').css('font-weight','bold');
                        $('#suggestions > div:contains("'+$this.text()+'") > span').removeClass('ui-icon-close').addClass('ui-icon-check');
                    }
                }
                $('#right-panel').load(ref,function(){
                    displaywindow.init(sel,ref)
                });
            });
        });
        $('#categoriesform').submit(function(){
            return false;
        });
        $('#newcatbutton').button({
            icons:{
                primary:"ui-icon-disk"
            }
        });
    }
};

var edit={
    init:function(){
        common.init();
        var sel=$('body').data('sel'),ref=$('body').data('right-panel-url');
        $('#savemetadata').click(function(e) {
            e.preventDefault();
            var file=$('input[name="file"]').val();
            $('#metadataform').ajaxSubmit({
                success: function(answer) {
                    if(answer.substring(0,5)=='Error') {
                        $.jGrowl(answer,{
                            theme:'jgrowl-error'
                        });
                        return false;
                    } else {
                        answer=answer.substring(6);
                        $.jGrowl('Changes saved for:<br>'+answer);
                        $('#list-item-'+file).text(answer);
                        $('#right-panel').load(ref,function(){
                            displaywindow.init(sel,ref)
                        });
                    }
                }
            });
        }).button({
            icons:{
                primary:"ui-icon-disk"
            }
        });
        $('#autoupdate').click(function(e) {
            e.preventDefault();
            $.jGrowl('Querying the database.');
            var file=$('#metadataform input[name="file"]').val();
            $(this).mouseout();
            $.getScript('wpad.php',function(){
                proxystr=FindProxyForURL('', 'www.pubmed.org');
                $('#metadataform').ajaxSubmit({
                    data: {
                        'proxystr': proxystr, 
                        'autoupdate': 1
                    },
                    success: function(answer) {
                        if(answer.substring(0,5)=='Error') {
                            $.jGrowl(answer,{
                                theme:'jgrowl-error',
                                life:6000
                            });
                            return false;
                        } else {
                            answer=answer.substring(6);
                            $.jGrowl('Item updated:<br>'+answer);
                            if($('#metadataform input[name="file"]').val()==file) {
                                $('#file-panel').load('edit.php?file='+file,function(){
                                    edit.init()
                                });
                            }
                            $('#list-item-'+file).text(answer);
                            $('#right-panel').load(ref,function(){
                                displaywindow.init(sel,ref)
                            });
                        }
                    }
                });
            });
        }).button({
            icons:{
                primary:"ui-icon-refresh"
            }
        }).tipsy({
            fade:true
        });
        $('#addurlrow').click(function(){
            $(this).closest('tr').after('<tr><td class="threedleft">URL:</td><td class="threedright"><input type="text" size="80" name="url[]" style="width: 99%" value=""></td></tr>');
        });
        $('#adduidrow').click(function(){
            $(this).closest('tr').after('<tr><td class="threedleft">Database UID:</td><td class="threedright"><input type="text" size="80" name="uid[]" style="width: 99%" value=""></td></tr>');
        });
        $('.addauthorrow').click(function(){
            $(this).closest('div.author-inputs').append('<div>Last name: <input type="text" value=""> <span class="ui-icon ui-icon-transfer-e-w flipnames" style="display:inline-block;position:relative;top:2px"></span> First name: <input type="text" value=""></div>');
            $('#metadataform .author-inputs div').filter(':last').find('input').bind('keyup blur', function(){
                var authors=[];
                $(this).closest('div.author-inputs').find('div').each(function(i){
                    authors[i]='';
                    var last=$(this).children('input').eq(0).val().replace('"','');
                    last=$.trim(last);
                    if(last!='') authors[i]='L:"'+last+'",F:"'+$.trim($(this).children('input').eq(1).val().replace('"',''))+'"';
                });
                authors=jQuery.grep(authors, function(n){
                    return (n != '');
                });
                var author=authors.join(';');
                $(this).closest('.author-inputs').next('input[name="authors"]').val(author);
            });
        });
        $('#metadataform input[name="uid\\[\\]"]').tipsy({
            html:true,
            gravity:'e'
        });
        $('#metadataform textarea[name="keywords"]').tipsy({
            html:true,
            gravity:'e'
        });
        $('#metadataform textarea[name="authors"]').tipsy({
            html:true,
            gravity:'e'
        });
        $('#metadataform input[name="journal"]').autocomplete({
            source: "ajaxjournals.php?search=journal",
            minLength: 1
        });
        $('#metadataform input[name="secondary_title"]').autocomplete({
            source: "ajaxjournals.php?search=secondary_title",
            minLength: 1
        });
        $('#metadataform input[name="authors"]').each(function(){
            var authors=[];
            $(this).prev().find('div').each(function(i){
                authors[i]='';
                var last=$.trim($(this).children('input').eq(0).val().replace('"',''));
                if(last!='') authors[i]='L:"'+last+'",F:"'+$.trim($(this).children('input').eq(1).val().replace('"',''))+'"';
            });
            authors=jQuery.grep(authors, function(n){
                return (n != '');
            });
            var author=authors.join(';');
            $(this).val(author);
        });
        $('#metadataform .author-inputs input').bind('keyup blur', function(){
            var authors=[];
            $(this).closest('.author-inputs').find('div').each(function(i){
                authors[i]='';
                var last=$.trim($(this).children('input').eq(0).val().replace('"',''));
                if(last!='') authors[i]='L:"'+last+'",F:"'+$.trim($(this).children('input').eq(1).val().replace('"',''))+'"';
            });
            authors=jQuery.grep(authors, function(n){
                return (n != '');
            });
            var author=authors.join(';');
            $(this).closest('.author-inputs').next('input[name="authors"]').val(author);
        });
        $('.flipnames').click(function(){
            var prev=$(this).prev().val(),next=$(this).next().val();
            $(this).prev().val(next);
            $(this).next().val(prev);
            $('#metadataform .author-inputs input').eq(0).trigger('keyup');
        });
    }
};

var desktop={
    init:function(openproj){
        var firstid='',lw=233;
        if(openproj===undefined) {
            if($('.projectcontainer:first').length===1) firstid=$('.projectcontainer:first').attr('id').split('-').pop();
        } else {
            firstid=openproj;
        }
        $('body').data('sel','desk').data('proj',firstid);
        if($('body').data('lwidth')!==undefined) lw=$('body').data('lwidth');
        if(lw===0) {
            $('#leftindex-left').hide();
            $('#right-panel').width($(window).width()-7);
            $('#bottom-panel .ui-icon').removeClass('ui-icon-triangle-1-w').addClass('ui-icon-triangle-1-e');
        } else {
            var lw2=$('#leftindex-left').outerWidth(true);
            $('#right-panel').width($(window).width()-lw2-7);
        }
        var rp=$('#right-panel').position(), w=$(window).width()-240;
        if (rp.left===243) w=w-3;
        if(lw===0) w=w+233;
        $(window).resize(function(){
            var w=$(window).width()-240;
            if (rp.left===243) w=w-3;
            if($('body').data('lwidth')===0) w=w+233;
            $('#right-panel').width(w);
        });
        $('#right-panel').width(w).load("display.php","browse[]=all&select=desk&project="+firstid,function(){
            displaywindow.init('desk',"browse[]=all&select=desk&project="+firstid);
        });
        $(".projectheader").click(function() {
            var proj=$(this).next('div.projectcontainer').attr('id').split('-').pop(),ref='display.php?browse[]=all&select=desk&project='+proj;
            $('#right-panel').load(ref,function(){
                $("#right-panel").scrollTop(0);
                displaywindow.init('desk',ref);
            });
        });
        $("#bottom-panel .middle-panel").click(function(){
            var $l=$(this).prev(),$r=$(this).next(),rw=$r.width();
            if($l.is(':hidden')) {
                $l.show();
                $(this).next().width(rw-233);
                $('body').data('lwidth',233);
                $(this).children().removeClass('ui-icon-triangle-1-e').addClass('ui-icon-triangle-1-w');
            } else {
                $l.hide();
                $(this).next().width(rw+233);
                $('body').data('lwidth',0);
                $(this).children().removeClass('ui-icon-triangle-1-w').addClass('ui-icon-triangle-1-e');
            }
        });
        //////////////////////////////quick search///////////////////////////////////
        $("#quicksearch #search").button({
            icons:{
                primary:"ui-icon-search"
            },
            text:false
        }).click(function(){
            var searchvalue=$("#quicksearch input[name='anywhere']").val();
            if(searchvalue==='Quick Search' || searchvalue==='') return false;
            var q=$("#quicksearch").formSerialize();
            $('#right-panel').load('search.php?'+q,function(){
                $("#right-panel").scrollTop(0);
                displaywindow.init('desk','search.php?'+q);
            });
            return false;
        }).tipsy({
            fade:true
        });
        $("#quicksearch #clear").button({
            icons:{
                primary:"ui-icon-trash"
            },
            text:false
        }).click(function(){
            $("#quicksearch input[name='anywhere']").val('').focus();
            $("#quicksearch input[value='AND']").parent('td.select_span').click();
            $.get('search.php?newsearch=1');
        }).tipsy({
            fade:true
        });
        $("#quicksearch input[name='anywhere']").focus(function() {
            var searchvalue=$(this).val();
            if(searchvalue==='Quick Search') $(this).val('');
        }).blur(function() {
            var searchvalue=$(this).val();
            if(searchvalue==='') $(this).val('Quick Search');
        });
        $("#quicksearch").submit(function(){
            $("#quicksearch #search").click();
            return false;
        });
        $("#advancedsearchbutton").click(function(){
            var proj=$('body').data('proj');
            $("#advancedsearch").load('advancedsearch.php?select=desk&project='+proj,function(){
                $("#advancedsearch").dialog('option','title','Advanced search of Desk').dialog('open');
                advancedsearch.init();
            });
        });
        $("#expertsearchbutton").click(function(){
            var proj=$('body').data('proj');
            $("#expertsearch").load('expertsearch.php?select=desk&project='+proj,function(){
                $("#expertsearch").dialog('option','title','Expert search of Desk').dialog('open');
                expertsearch.init();
            });
        });
        $('#project-'+firstid).show();
        $('#leftindex-left table.projectheader').click(function(){
            $('#leftindex-left div.projectcontainer').hide();
            $(this).next('div.projectcontainer').show();
            var projectID=$(this).next('div.projectcontainer').attr('id').split('-').pop();
            $('#leftindex-left a.leftbuttonlink').blur();
            $('#quicksearch input[name=project]').val(projectID);
            $('body').data('sel','desk').data('proj',projectID);
        });
        $("#dialog-confirm").dialog('option','buttons', {
            'Yes': function() {
                var $form=$(this).data('form');
                $(this).dialog('close');
                $form.ajaxSubmit(function(){
                    $('#bottom-panel').load('desktop.php',function(){
                        desktop.init();
                    });
                });
            },
            Cancel: function() {
                $(this).dialog('close');
            }
        });
        $("#leftindex-left .deletebutton").button({
            icons:{
                primary:"ui-icon-trash"
            },
            text:true
        }).click(function(){
            $("#dialog-confirm").html('<p><span class="ui-state-error-text"><span class="ui-icon ui-icon-alert" style="float:left;margin:0px 7px 20px 0px"></span></span> This project will be permanently deleted. Are you sure?</p>')
            .data('form', $(this).closest('form')).dialog('option','title','Delete the project?').dialog('open');
            return false;
        });
        $("#leftindex-left .emptybutton").button({
            icons:{
                primary:"ui-icon-trash"
            },
            text:true
        }).click(function(){
            $("#dialog-confirm").html('<p><span class="ui-state-error-text"><span class="ui-icon ui-icon-alert" style="float:left;margin:0px 7px 20px 0px"></span></span> All records will be removed from this project. Are you sure?</p>')
            .data('form', $(this).closest('form')).dialog('option','title','Empty the project?').dialog('open');
            return false;
        });
        $("#leftindex-left .renamebutton").button({
            text:true
        }).click(function(){
            var $form=$(this).closest('form'),proj=jQuery.trim($form.find('input[name="project"]').val());
            if(proj!=='') {
                $(this).closest('form').ajaxSubmit(function(){
                    $('#bottom-panel').load('leftindex.php?select=desk',function(){
                        desktop.init();
                    });
                });
            }
            return false;
        });
        $("#leftindex-left table.adduser").click(function(){
            var userID=$('select[name=adduser] option:selected').val(), projectID=$(this).closest('div').attr('id').split('-').pop(),
            $option=$(this).closest('div.projectcontainer').find('select[name="adduser"] option[value="'+userID+'"]'),
            $select=$(this).closest('div.projectcontainer').find('select[name="removeuser"]');
            $.get("ajaxdesk.php", {
                adduser: '',
                userID: userID ,
                projectID: projectID
            },
            function(answer){
                if(answer==='done') {
                    $option.prependTo($select).removeAttr("selected");
                    var listitems = $select.children('option').get();
                    listitems.sort(function(a, b) {
                        var compA = $(a).text().toUpperCase();
                        var compB = $(b).text().toUpperCase();
                        return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;
                    });
                    $.each(listitems, function(idx, itm) {
                        $select.append(itm);
                    });
                } else if(answer.substr(0,5)==='Error') {
                    $.jGrowl(answer,{
                        theme:'jgrowl-error'
                    });
                }
            });
        });
        $("#leftindex-left table.removeuser").click(function(){
            var userID=$('select[name=removeuser] option:selected').val(), projectID=$(this).closest('div').attr('id').split('-').pop(),
            $option=$(this).closest('div.projectcontainer').find('select[name="removeuser"] option[value="'+userID+'"]'),
            $select=$(this).closest('div.projectcontainer').find('select[name="adduser"]');
            $.get("ajaxdesk.php", {
                removeuser: '',
                userID: userID ,
                projectID: projectID
            },
            function(answer){
                if(answer==='done') {
                    $option.prependTo($select).removeAttr("selected");
                    var listitems = $select.children('option').get();
                    listitems.sort(function(a, b) {
                        var compA = $(a).text().toUpperCase();
                        var compB = $(b).text().toUpperCase();
                        return (compA < compB) ? -1 : (compA > compB) ? 1 : 0;
                    });
                    $.each(listitems, function(idx, itm) {
                        $select.append(itm);
                    });
                }
            });
        });
        $("#createproject").button({
            text:true
        }).click(function(){
            var $form=$(this).closest('form'),proj=jQuery.trim($form.find('input[name="project"]').val());
            if(proj!=='') {
                $(this).closest('form').ajaxSubmit(function(){
                    $('#bottom-panel').load('leftindex.php?select=desk',function(){
                        desktop.init();
                    });
                });
            }
            return false;
        });
    }
};

var displaywindow={
    init:function(sel,divhref){
        var showid='';
        function briefShow($el) {
            clearTimeout(showid);
            $el.css('height','auto');
        }
        function briefHide($el) {
            clearTimeout(showid);
            $el.css('height','');
        }
        $('#right-panel .brief').mouseover(function(){
            var $t=$(this);
            showid=setTimeout(function(){
                briefShow($t)
                }, 500);
        }).mouseout(function(){
            briefHide($(this));
        });
        $('body').data('list-title',$('#list-title').text())
        .data('right-panel-url',divhref);
        $("#right-panel span.author_expander").click(function() {
            var $container=$(this).parent(".authors");
            if($(this).hasClass('ui-icon-plus')) {
                $container.height('auto');
                $(this).removeClass('ui-icon-plus').addClass('ui-icon-minus');
            } else {
                $container.height('');
                $(this).removeClass('ui-icon-minus').addClass('ui-icon-plus');
            }
        });
        $("#right-panel .pgdown").click(function() {
            var pgupoffset=$("#right-panel .pgup").offset();
            $("#right-panel").animate({
                scrollTop:pgupoffset.top
            },100);
        });
        $("#right-panel .pgup").click(function() {
            $("#right-panel").animate({
                scrollTop:0
            },100);
        });
        var openid=top.location.search.split('=').pop();
        if(openid!='' && $(document).data('idlock')==undefined) {
            $('#top-panel, #bottom-panel').hide();
            $('#items-container').show().load('items.php','file='+openid,function(){
                items.init(openid);
                $(document).data('idlock','locked');
            });
        }
        var iconswidth=$('#icon-container').width(),
        iconnum=Math.floor(iconswidth/364),
        icmarg=Math.max(4,(iconswidth-iconnum*360)/(iconnum+1));
        $('#icon-container').find('.icon-items').css('margin-left',icmarg);
        $(window).resize(function(){
            var iconswidth=$('#icon-container').width(),
            iconnum=Math.floor(iconswidth/364),
            icmarg=Math.max(4,(iconswidth-iconnum*360)/(iconnum+1));
            $('#icon-container').find('.icon-items').css('margin-left',icmarg);
            $('#menuwrapper').remove();
        });
        $('#bottom-panel .middle-panel').click(function() {
            var iconswidth=$('#icon-container').width(),
            iconnum=Math.floor(iconswidth/364),
            icmarg=Math.max(4,(iconswidth-iconnum*360)/(iconnum+1));
            $('#icon-container').find('.icon-items').css('margin-left',icmarg);
        });
        $('#display-content .icon-titles').click(function() {
            var file=$(this).closest('.icon-items').attr('id').split('-').pop();
            $('#top-panel, #bottom-panel').hide();
            $('#items-container').show().load('items.php','file='+file,function(){
                items.init(file)
            });
        });
        $('#display-content a.navigation').click(function(e) {
            var ref=$(this).prop('href');
            e.preventDefault();
            $('#right-panel').load(ref,function(){
                $("#right-panel").scrollTop(0);
                displaywindow.init(sel,ref);
                $('body').data('right-panel-url',ref);
            });
        });
        $('#display-content').click(function(event) {
            var t=event.target;
            if($(t).hasClass('titles') || $(t).hasClass('icon-titles')) {
                var file=$(t).closest('div.items').attr('id').split('-').pop();
                $('#top-panel, #bottom-panel').hide();
                $('#items-container').show().load('items.php','file='+file,function(){
                    items.init(file)
                });
            } else
            if($(t).hasClass('expander')) {
                if($(t).hasClass('view-brief')) {
                    if($(t).closest('.items').find('.display-summary').is(':visible')) {
                        $(t).closest('.items').find('.display-abstract').hide();
                        $(t).closest('.items').find('.display-summary').hide();
                    } else {
                        $(t).closest('.items').find('.display-abstract').show();
                        $(t).closest('.items').find('.display-summary').show();
                    }
                } else if ($(t).hasClass('view-summary')) {
                    if($(t).closest('.items').find('.display-abstract').is(':visible')) {
                        $(t).closest('.items').find('.display-abstract').hide();
                    } else {
                        $(t).closest('.items').find('.display-abstract').show();
                    }
                }
                if($(t).hasClass('ui-icon-plus')) {
                    $(t).removeClass('ui-icon-plus').addClass('ui-icon-minus');
                } else {
                    $(t).removeClass('ui-icon-minus').addClass('ui-icon-plus');
                }
            } else
            if($(t).hasClass('ui-icon-star')) {
                var $parent=$(t).parent(), item=$parent.attr('id').split('-');
                $.ajax({
                    url: "ajaxrating.php",
                    cache: false,
                    data: "file="+item[1]+"&rating="+item[2],
                    dataType: "text",
                    success: function(){
                        if(item[2]==1) {
                            $parent.attr('class','star ui-state-error-text');
                            $parent.siblings('div.star').attr('class','star ui-priority-secondary');
                        }
                        if(item[2]==2) {
                            $parent.prev().addBack().attr('class','star ui-state-error-text');
                            $parent.next().attr('class','star ui-priority-secondary');
                        }
                        if(item[2]==3) $parent.siblings('div.star').addBack().attr('class','star ui-state-error-text');
                    }
                });
            } else
            if($(t).hasClass('update_clipboard')) {
                var $t=$(t),file=$t.closest('.items, .icon-items').attr('id').split('-').pop();
                if($t.hasClass('ui-icon')) $t=$t.closest('div');
                $.get("ajaxclipboard.php", {
                    'file': file,
                    'selection': sel
                },
                function(answer){
                    if (answer == "added") {
                        $t.removeClass('add').addClass('remove ui-state-error-text');
                        $t.children('span').removeClass('ui-icon-close').addClass('ui-icon-check');
                    } else if (answer == "removed") {
                        $t.removeClass('remove ui-state-error-text').addClass('add');
                        $t.children('span').removeClass('ui-icon-check').addClass('ui-icon-close');
                    } else if (answer.substr(0,5) == "Error") {
                        $.jGrowl(answer, {
                            theme:'jgrowl-error'
                        });
                    }
                    var ref=$('body').data('right-panel-url');
                    if (sel=="clipboard") $('#right-panel').load(ref,function(){
                        displaywindow.init(sel,ref)
                    });
                }
                );
            } else
            if($(t).hasClass('update_shelf')) {
                var $t=$(t),file=$t.closest('.items, .icon-items').attr('id').split('-').pop();
                if($t.hasClass('ui-icon')) $t=$t.closest('div');
                $.get("ajaxshelf.php", {
                    'file': file,
                    'selection': sel
                },
                function(answer){
                    if (answer == "added") {
                        $t.removeClass('add').addClass('remove ui-state-error-text');
                        $t.children('span').removeClass('ui-icon-close').addClass('ui-icon-check');
                    } else if (answer == "removed") {
                        $t.removeClass('remove ui-state-error-text').addClass('add');
                        $t.children('span').removeClass('ui-icon-check').addClass('ui-icon-close');
                    } else if (answer.substr(0,5) == "Error") {
                        $.jGrowl(answer, {
                            theme:'jgrowl-error'
                        });
                    }
                    var ref=$('body').data('right-panel-url');
                    if (sel=="shelf") $('#right-panel').load(ref,function(){
                        displaywindow.init(sel,ref)
                    });
                }
                );
            } else
            if($(t).hasClass('update_project')) {
                var $t=$(t),file=$t.closest('.items, .icon-items').attr('id').split('-').pop();
                if($t.hasClass('ui-icon')) $t=$t.closest('div');
                var project=$t.attr("class").split(' ').shift();
                $.get("ajaxdesk.php", {
                    'file': file,
                    'selection': sel,
                    'displayedproject': $('body').data('proj'),
                    'project': project
                },
                function(answer){
                    if (answer == "added") {
                        $t.removeClass('add').addClass('remove ui-state-error-text');
                        $t.children('span').removeClass('ui-icon-close').addClass('ui-icon-check');
                    } else if (answer == "removed") {
                        $t.removeClass('remove ui-state-error-text').addClass('add');
                        $t.children('span').removeClass('ui-icon-check').addClass('ui-icon-close');
                    } else if (answer.substr(0,5) == "Error") {
                        $.jGrowl(answer, {
                            theme:'jgrowl-error'
                        });
                    }
                    var ref=$('body').data('right-panel-url');
                    if (sel=="desk" && project==$('body').data('proj')) $('#right-panel').load(ref,function(){
                        displaywindow.init(sel,ref)
                    });
                }
                );
            }
        });
        $('#customization').click(function(e){
            var url=null,$target=$(e.target),
            $tr=$target.closest('tr'), $td=$target.closest('td'),
            position=$tr.children('td').index($td), redirection=$(this).data('redirection');
            e.stopPropagation();
            if($target.hasClass('ui-icon-radio-on')) return false;
            if($target.find('span').hasClass('ui-icon-radio-on')) return false;
            if(position==1) url='display=brief';
            if(position==2) url='display=summary';
            if(position==3) url='display=abstract';
            if(position==4) url='display=icons';
            if(position==6) url='orderby=year';
            if(position==7) url='orderby=id';
            if(position==8) url='orderby=rating';
            if(position==9) url='orderby=journal';
            if(position==10) url='orderby=title';
            if(position==12) url='limit=5';
            if(position==13) url='limit=10';
            if(position==14) url='limit=15';
            if(position==15) url='limit=20';
            if(position==16) url='limit=50';
            if(position==17) url='limit=100';
            if(url!=null) {
                $.get('ajaxdisplay.php', url, function() {
                    $('#right-panel').load(redirection,function(){
                        displaywindow.init(sel);
                        $('body').data('right-panel-url',redirection);
                    });
                });
            }
        });
        $('#displaybutton').click(function(){
            $('#orderbybutton').nextUntil('#showbutton').hide();
            $('#showbutton').nextAll().hide();
            $(this).siblings('td').slice(0,4).toggle();
            $('#customization').find('span.ui-icon-radio-on').closest('td').show();
        });
        $('#orderbybutton').click(function(){
            $('#orderbybutton').prevUntil('#displaybutton').hide();
            $('#showbutton').nextAll().hide();
            $(this).siblings('td').slice(5,10).toggle();
            $('#customization').find('span.ui-icon-radio-on').closest('td').show();
        });
        $('#showbutton').click(function(){
            $('#showbutton').prevUntil('#orderbybutton').hide();
            $('#orderbybutton').prevUntil('#displaybutton').hide();
            $(this).siblings('td').slice(11).toggle();
            $('#customization').find('span.ui-icon-radio-on').closest('td').show();
        });
        $("#omnitooldiv").dialog({
            autoOpen: false,
            width: 780,
            buttons: {
                'Submit': function() {
                    var $t=$(this),omnitool=$('input[name=omnitool]:checked').val(),
                    project3=$('select[name=project3] option:selected').val(),
                    project4=$('select[name=project4] option:selected').val();
                    var category=[];
                    $.each($('#omnitoolcategories input:checked'), function() {
                        category.push($(this).val());
                    });
                    var category2=[];
                    $.each($('input[name="category2[]"]'), function() {
                        if($(this).val()!='' && $(this).val()!='Add new category') category2.push($(this).val());
                    });
                    $t.dialog('disable');
                    $.post('omnitool.php', {
                        'omnitool': omnitool,
                        'project3': project3,
                        'project4': project4,
                        'category[]': category,
                        'category2[]': category2
                    },
                    function(){
                        var ref=$('body').data('right-panel-url');
                        $('#right-panel').load(ref,function(){
                            displaywindow.init(sel,ref)
                        });
                        $t.dialog('enable').dialog('close');
                    });
                },
                Cancel: function() {
                    $(this).dialog('close');
                }
            }
        });
        $("#omnitoolbutton").click(function(){
            var total=$('#total-items').text(),ttl='Omnitool - select a task to perform with all '+total+' items.';
            $("#omnitooldiv").load('omnitool.php', function(){
                $(this).dialog('option','title',ttl).dialog('open');
                omnitool.init();
            });
        });
        $('#right-panel input[name="category2[]"]').each(function(){
            $(this).val('Add new category').css('color','#9f9e99');
        }).click(function(){
            $(this).val('').css('color','');
        });
        $('#exportdialog').dialog({
            autoOpen: false,
            width: '550',
            open: function(){
                $('#pdf-table').css('visibility','hidden');
            },
            close: function(){
                $('#pdf-table').css('visibility','');
            },
            buttons: {
                "Export": function() {
                    $(this).dialog('close');
                    $('#exportform').attr('action','export.php?_='+Math.random());
                    if($('input[name="output"]:checked').val()=='inline') {
                        $('#exportform').attr('target','_blank');
                    } else {
                        $('#exportform').attr('target','');
                    }
                    $('#exportform').submit();
                },
                "Reset": function() {
                    $('#exportform').resetForm();
                    $('#exportform input:checkbox').each(function(){
                        $(this).next('span').removeClass('ui-icon-check').addClass('ui-icon-close');
                        if($(this).is(':checked')) $(this).next('span').removeClass('ui-icon-close').addClass('ui-icon-check');
                    });
                    $('#exportform input:radio').each(function(){
                        $(this).next('span').removeClass('ui-icon-radio-on').addClass('ui-icon-radio-off');
                        if($(this).is(':checked')) $(this).next('span').removeClass('ui-icon-radio-off').addClass('ui-icon-radio-on');
                    });
                },
                "Close": function() {
                    $(this).dialog("close");
                }
            }
        });
        $('#exportbutton').click(function(){
            var total=$('#total-items').text(),ttl='Export '+total+' items';
            if(total=='1') ttl='Export 1 item';
            if($('#exportdialog').html()==''){
                $('#exportdialog').load('export.php',function(){
                    $(this).dialog('option','title',ttl).dialog('open');
                    common.init();
                    $('#selectall').click(function(){
                        $('input[name="column\\[\\]"]').prop('checked',true);
                        $('input[name="column\\[\\]"]').next().removeClass('ui-icon-close').addClass('ui-icon-check');
                    });
                    $('#unselectall').click(function(){
                        $('input[name="column\\[\\]"]').prop('checked',false);
                        $('input[name="column\\[\\]"]').next().removeClass('ui-icon-check').addClass('ui-icon-close');
                    });
                });
            } else {
                $('#exportform').find('input[name="export_files"]').val('session');
                $('#exportdialog').dialog('option','title',ttl).dialog('open');
            }
        });
        $("#printlist").click(function(){
            window.print();
        });
        $('#display-content').find('.icon-items a,.items a').bind('contextmenu',function(e){
            var filename=$(this).closest('.icon-items,.items').data('file');
            e.preventDefault();
            $('#contextmenu').remove();
            $('body').append('<div id="menuwrapper"><div class="alternating_row item-sticker" id="contextmenu"><span class="ui-icon ui-icon-disk" style="float:left"></span>Download PDF</div></div>');
            var mtop=e.pageY,mleft=e.pageX+20;
            if(mleft+150>$(window).width()) mleft=mleft-170;
            if(mtop+50>$(window).height()) mtop=mtop-40;
            $('#contextmenu').css('top',mtop).css('left',mleft);
            $('#menuwrapper').width($(document).width()).height($(document).height());
            $('#contextmenu').click({
                filename: filename
            },function(event){
                window.location.assign('downloadpdf.php?mode=download&file='+event.data.filename);
                $('#menuwrapper').remove();
            });
            $('#menuwrapper').mousedown(function(e){
                if(e.target==this) $(this).remove();
            });
        });
        //HOTKEYS
        $(document).unbind('keydown').bind('keydown', 's', function(){
            if($('.ui-dialog:visible').length==0) $('.nextrecord').click();
        }).bind('keydown', 'w', function(){
            if($('.ui-dialog:visible').length==0) $('.prevrecord').click();
        }).bind('keydown', 'd', function(){
            if($('.nextpage').is(':visible') && $('.ui-dialog:visible').length==0) $('.nextpage').click();
        }).bind('keydown', 'a', function(){
            if($('.prevpage').is(':visible') && $('.ui-dialog:visible').length==0) $('.prevpage').click();
        }).bind('keydown', 'del', function(){
            if($('#deletebutton').is(':visible') && $('.ui-dialog:visible').length==0) $('#deletebutton').click();
        }).bind('keydown', 'q', function(){
            if($('.backbutton').is(':visible') && $('.ui-dialog:visible').length==0) $('.backbutton').click();
        });
    }
};

var filetop={
    init:function(sel){
        $('#file-panel2 a.pdf_link').tipsy({
            gravity:'s'
        });
        $("#file-panel2 span.author_expander").click(function() {
            var $container=$(this).parent(".authors");
            if($(this).hasClass('ui-icon-plus')) {
                $container.height('auto');
                $(this).removeClass('ui-icon-plus').addClass('ui-icon-minus');
            } else {
                $container.height('');
                $(this).removeClass('ui-icon-minus').addClass('ui-icon-plus');
            }
        });
        $('#file-panel2').click(function(event) {
            var t=event.target;
            if($(t).hasClass('ui-icon-star')) {
                var $parent=$(t).parent(), item=$parent.attr('id').split('-');
                $parent=$parent.add($('#'+item[1]+'-'+item[2]+'-'+item[3]));
                $.ajax({
                    url: "ajaxrating.php",
                    cache: false,
                    data: "file="+item[2]+"&rating="+item[3],
                    dataType: "text",
                    success: function(){
                        if(item[3]==1) {
                            $parent.attr('class','star ui-state-error-text');
                            $parent.siblings('div.star').attr('class','star ui-priority-secondary');
                        }
                        if(item[3]==2) {
                            $parent.prev().addBack().attr('class','star ui-state-error-text');
                            $parent.next().attr('class','star ui-priority-secondary');
                        }
                        if(item[3]==3) $parent.siblings('div.star').addBack().attr('class','star ui-state-error-text');
                    }
                });
            } else
            if($(t).hasClass('update_clipboard')) {
                var file=$(t).closest('div.items').attr('id').split('-').pop(),tid=$(t).closest('.items').attr('id');
                if($(t).hasClass('ui-icon')) t=$(t).closest('div');
                tid=tid.replace('file-','display-');
                var $t=$(t).add($('#'+tid+' div.update_clipboard'));
                $.get("ajaxclipboard.php", {
                    'file': file,
                    'selection': sel
                },
                function(answer){
                    if (answer == "added") {
                        $t.removeClass('add').addClass('remove ui-state-error-text');
                        $t.children('span').removeClass('ui-icon-close').addClass('ui-icon-check');
                    } else if (answer == "removed") {
                        $t.removeClass('remove ui-state-error-text').addClass('add');
                        $t.children('span').removeClass('ui-icon-check').addClass('ui-icon-close');
                    }
                    if (sel=="clipboard") {
                        var ref=$('body').data('right-panel-url');
                        $('#right-panel').load(ref,function(){
                            displaywindow.init('clipboard',ref)
                        });
                        if($('.listleft').length==1) {
                            $('.backbutton').click();
                        } else {
                            if($('.nextrecord').hasClass('ui-state-disabled')) {
                                file=$('.prevrecord').attr('id').split('-').pop();
                            } else {
                                file=$('.nextrecord').attr('id').split('-').pop();
                            }
                            $('#items-container').load('items.php','file='+file,function(){
                                items.init(file);
                            });
                        }
                    }
                }
                );
            } else
            if($(t).hasClass('update_shelf')) {
                var file=$(t).closest('div.items').attr('id').split('-').pop(),tid=$(t).closest('.items').attr('id');
                if($(t).hasClass('ui-icon')) t=$(t).closest('div');
                tid=tid.replace('file-','display-');
                var $t=$(t).add($('#'+tid+' div.update_shelf'));
                $.get("ajaxshelf.php", {
                    'file': file,
                    'selection': sel
                },
                function(answer){
                    if (answer == "added") {
                        $t.removeClass('add').addClass('remove ui-state-error-text');
                        $t.children('span').removeClass('ui-icon-close').addClass('ui-icon-check');
                    } else if (answer == "removed") {
                        $t.removeClass('remove ui-state-error-text').addClass('add');
                        $t.children('span').removeClass('ui-icon-check').addClass('ui-icon-close');
                    }
                    if (sel=="shelf") {
                        var ref=$('body').data('right-panel-url');
                        $('#right-panel').load(ref,function(){
                            displaywindow.init('clipboard',ref)
                        });
                        if($('.listleft').length==1) {
                            $('.backbutton').click();
                        } else {
                            if($('.nextrecord').hasClass('ui-state-disabled')) {
                                file=$('.prevrecord').attr('id').split('-').pop();
                            } else {
                                file=$('.nextrecord').attr('id').split('-').pop();
                            }
                            $('#items-container').load('items.php','file='+file,function(){
                                items.init(file);
                            });
                        }
                    }
                }
                );
            } else
            if($(t).hasClass('update_project')) {
                var file=$(t).closest('div.items').attr('id').split('-').pop(),project=$(t).attr("class").split(' ').shift(),
                tid=$(t).closest('.items').attr('id');
                if($(t).hasClass('ui-icon')) t=$(t).closest('div');
                tid=tid.replace('file-','display-');
                var $t=$(t).add($('#'+tid+' div.update_project.'+project));
                $.get("ajaxdesk.php", {
                    'file': file,
                    'project': project,
                    'selection': sel,
                    'displayedproject': $('body').data('proj')
                },
                function(answer){
                    if (answer == "added") {
                        $t.removeClass('add').addClass('remove ui-state-error-text');
                        $t.children('span').removeClass('ui-icon-close').addClass('ui-icon-check');
                    } else if (answer == "removed") {
                        $t.removeClass('remove ui-state-error-text').addClass('add');
                        $t.children('span').removeClass('ui-icon-check').addClass('ui-icon-close');
                    }
                    if (sel=="desk" && answer == "removed" && project==$('body').data('proj')) {
                        $('#bottom-panel').load('leftindex.php?select=desk&project='+project,function(){
                            desktop.init(project)
                        });
                        if($('.listleft').length==1) {
                            $('.backbutton').click();
                        } else {
                            if($('.nextrecord').hasClass('ui-state-disabled')) {
                                file=$('.prevrecord').attr('id').split('-').pop();
                            } else {
                                file=$('.nextrecord').attr('id').split('-').pop();
                            }
                            $('#items-container').load('items.php','file='+file,function(){
                                items.init(file);
                            });
                        }
                    }
                }
                );
            }
        });
        $('.bibtex').click(function(){
            $(this).select();
        });
        $('#file-panel2').find('a.pdf_link').bind('contextmenu',function(e){
            var filename=$(this).closest('.items').data('file');
            e.preventDefault();
            $('#contextmenu').remove();
            $('body').append('<div id="menuwrapper"><div class="alternating_row item-sticker" id="contextmenu"><span class="ui-icon ui-icon-disk" style="float:left"></span>Download PDF</div></div>');
            var mtop=e.pageY,mleft=e.pageX+20;
            if(mleft+150>$(window).width()) mleft=mleft-170;
            if(mtop+50>$(window).height()) mtop=mtop-40;
            $('#contextmenu').css('top',mtop).css('left',mleft);
            $('#menuwrapper').width($(document).width()).height($(document).height());
            $('#contextmenu').click({
                filename: filename
            },function(event){
                window.location.assign('downloadpdf.php?mode=download&file='+event.data.filename);
                $('#menuwrapper').remove();
            });
            $('#menuwrapper').mousedown(function(e){
                if(e.target==this) $(this).remove();
            });
        });
    }
};

var filediscussion={
    init:function() {
        $('#newmessage, #deletediscussion').button();
        var file=$('#filediscussionform input[name="file"]').val();
        function loadmessages() {
            if($('.messages').length<1) {
                clearInterval($('body').data('loadmessageID'));
                return false;
            }
            $('.messages').load('file_discussion.php', 'read=1&file='+file);
        }
        clearInterval($('body').data('loadmessageID'));
        loadmessages();
        var loadmessageID=setInterval(loadmessages,5000);
        $('body').data('loadmessageID',loadmessageID);
        $('#newmessage').click(function(e){
            e.preventDefault();
            var $elem=$(this).prev('textarea'),newmessage=$elem.val();
            if(newmessage!='') {
                $.post('file_discussion.php',{
                    'file': file,
                    'newmessage': newmessage
                }, function(answer){
                    if(answer=='OK') {
                        $elem.val('');
                        loadmessages();
                    }
                });
            }
        });
        $('#deletediscussion').click(function(){
            $('#dialog-confirm').html('<p>Do you want to permanently delete this discussion?</p>')
            .dialog( "option", "buttons", {
                "Delete": function() {
                    $.get('file_discussion.php',{
                        'file': file,
                        'delete': 1
                    }, function(answer){
                        if(answer=='OK') loadmessages();
                    });
                    $(this).dialog("close");
                },
                "Close": function(){
                    $(this).dialog("close")
                }
            }).dialog('option','title','Delete discussion?').dialog('open');
        });
    }
};

var filemanager={
    init:function() {
        var sel=$('body').data('sel'),ref=$('body').data('right-panel-url');
        $('#filesform :text').bind('keyup blur', function(){
            var inputstring=$(this).val(), newstring=inputstring.replace(/[^a-zA-Z0-9\-\_\.]/g,'_');
            if(newstring!=inputstring) {
                $(this).val(newstring);
                $.jGrowl('Only letters, numbers, and characters -_. are allowed.',{
                    theme:'jgrowl-error'
                });
            }
        });
        $('tr.file-highlight').mouseover(function(){
            $(this).addClass('alternating_row');
        }).mouseout(function(){
            $(this).removeClass('alternating_row');
        });
        var timeoutId='';
        function showpreview(filename) {
            $('#preview').html('<img src="library/supplement/'+filename+'" style="width:300px;border:solid 1px #C3C3C3">')
            .fadeIn(200);
        }
        $('span.image').mouseover(function() {
            if(navigator.appName.toUpperCase()!='MICROSOFT INTERNET EXPLORER') {
                var filename=$(this).closest('tr').attr('id').substring(4);
                timeoutId=setTimeout(function(){
                    showpreview(filename)
                },500);
            }
        }).mouseout(function() {
            if(navigator.appName.toUpperCase()!='MICROSOFT INTERNET EXPLORER') {
                clearTimeout(timeoutId);
                $('#preview').hide();
            }
        });
        $('div.file-remove').click(function(){
            var filename=$(this).closest('tr.file-highlight').attr('id').substring(4);
            $("#dialog-confirm").html('<p><span class="ui-state-error-text"><span class="ui-icon ui-icon-alert" style="float:left;margin:0px 7px 20px 0px"></span></span> File "<i>'+filename.substring(5,30)+'</i>" will be permanently deleted. Are you sure?</p>')
            .data('filename',filename).data('tr',$(this).closest('tr.file-highlight')).dialog('option','title','Delete File?')
            .dialog("option", "buttons", {
                'Delete File': function() {
                    var $this=$(this), filename=$this.data('filename'), $tr=$this.data('tr');
                    $.ajax({
                        type: "GET",
                        url: "ajaxsupplement.php",
                        cache: false,
                        data: "files_to_delete[]="+filename,
                        dataType: "text",
                        success: function(){
                            $tr.next('tr').addBack().remove();
                            $this.dialog('close');
                            $('#right-panel').load(ref,function(){
                                displaywindow.init(sel,ref)
                            });
                        }
                    });
                },
                Cancel: function() {
                    $(this).dialog('close');
                }
            }).dialog('open');
        });
        $('div.file-rename').click(function() {
            var $tr=$(this).closest('tr.file-highlight'), filename=$tr.attr('id').substring(4), oldname=filename.substring(5),
            $input=$tr.find('input'), newname=$input.val();
            if ($input.is(':visible') && oldname!=newname) {
                $('#filesform').ajaxSubmit({
                    success: function() {
                        $('#file-files').click();
                    }
                });
            } else if($input.is(':hidden')) {
                $('#filesform a.rename_container').show();
                $('input.rename_container:text').hide();
                $('input.rename_container:text').each(function(){
                    var originalname=$(this).closest('tr.file-highlight').attr('id').substring(9);
                    $(this).val(originalname);
                });
            }
            $input.prev('a').addBack().toggle();
        });
        $('#filesform :text').keyup(function(e) {
            if(e.which==13) $(this).closest('tr.file-highlight').find('div.file-rename').click();
        });
        $('#filesform, #uploadfiles').submit(function() {
            return false;
        });
        $('#filelist .ui-icon-video, #filelist .ui-icon-volume-on').tipsy({
            gravity:'s'
        });
        $('#submituploadfiles').click(function() {
            var values = new Array(),file=$('#items-right').data('file');
            $('#uploadfiles :file,#uploadfiles :text').each(function(i){
                if($(this).val()!='') values[i]=$(this).val();
            });
            if(values.length==0) return false;
            $.jGrowl('Recording in progress.');
            var newfile=$('#uploadfiles input[name=form_new_file]').val()+$('#uploadfiles input[name=form_new_file_link]').val();
            $.getScript('wpad.php',function(){
                proxystr=FindProxyForURL('', 'www.crossref.org');
                $('#uploadfiles').ajaxSubmit({
                    data: {
                        'proxystr': proxystr
                    },
                    resetForm: true,
                    success: function(answer) {
                        if(answer.substring(0,5)=='Error') {
                            $.jGrowl(answer,{
                                theme:'jgrowl-error',
                                life:6000
                            });
                            return false;
                        } else {
                            if(file==$('#items-right').data('file')){
                                if(newfile!='') {
                                    if(top.frames.topframe.frames.pdf) {
                                        top.frames.topframe.frames.pdf.location.reload(true);
                                    } else if($('#file-panel2').is(':visible')) {
                                        $('#file-item').click();
                                    }
                                }
                                if($('#filelist').length==1) $('#file-files').click();
                            }
                            $.jGrowl('File(s) recorded.');
                            $('#right-panel').load(ref,function(){
                                displaywindow.init(sel,ref)
                            });
                        }
                    }
                });
            });
        }).button({
            icons:{
                primary: 'ui-icon-disk'
            }
        });
        $('.reindex').click(function(){
            var fileid=$(this).attr('id').split('-').pop();
            $.get('reindexpdf.php?file='+fileid, function(answer) {
                if(answer!='') {
                    $.jGrowl(answer,{
                        theme:'jgrowl-error'
                    });
                } else {
                    $.jGrowl('PDF re-indexed.');
                }
            });
        });
        $('.video').click(function(){
            var file=$(this).closest('tr').attr('id').substr(4), $videocontainer=$(this).closest('tr').next('tr').find('.videocontainer'),
            extension=file.split('.').pop(),medium={},suppl='';
            if(extension=='m4v') {medium={'m4a':location.href.replace('index2.php','')+'library/supplement/'+escape(file)};suppl='m4v';}
            if(extension=='ogv') {medium={'ogv':'library/supplement/'+escape(file)};suppl='ogv';}
            if(extension=='webmv') {medium={'webmv':'library/supplement/'+escape(file)};suppl='webmv';}
            if(extension=='flv') {medium={'flv':location.href.replace('index2.php','')+'library/supplement/'+escape(file)};suppl='flv';}
            if($videocontainer.is(':hidden')) {
                $('.audiocontainer,.videocontainer').empty().hide();
                $videocontainer.html('<div id="jquery_jplayer_1" class="jp-jplayer"></div>'+
                  '<div id="jp_container_1">'+
                      '<div class="jp-gui ui-widget ui-widget-content ui-corner-all">'+
                        '<ul>'+
                          '<li class="jp-play ui-state-default ui-corner-all"><a href="javascript:;" class="jp-play ui-icon ui-icon-play" tabindex="1" title="play">play</a></li>'+
                          '<li class="jp-pause ui-state-default ui-corner-all"><a href="javascript:;" class="jp-pause ui-icon ui-icon-pause" tabindex="1" title="pause">pause</a></li>'+
                          '<li class="jp-stop ui-state-default ui-corner-all"><a href="javascript:;" class="jp-stop ui-icon ui-icon-stop" tabindex="1" title="stop">stop</a></li>'+
                          '<li class="jp-mute ui-state-default ui-corner-all"><a href="javascript:;" class="jp-mute ui-icon ui-icon-volume-off" tabindex="1" title="mute">mute</a></li>'+
                          '<li class="jp-unmute ui-state-default ui-corner-all"><a href="javascript:;" class="jp-unmute ui-icon ui-icon-volume-off" tabindex="1" title="unmute">unmute</a></li>'+
                          '<li class="jp-volume-max ui-state-default ui-corner-all"><a href="javascript:;" class="jp-volume-max ui-icon ui-icon-volume-on" tabindex="1" title="max volume">max volume</a></li>'+
                        '</ul>'+
                        '<div class="jp-progress-slider"></div>'+
                        '<div class="jp-volume-slider"></div>'+
                        '<div class="jp-current-time"></div>'+
                        '<div class="jp-duration"></div>'+
                        '<div class="jp-clearboth"></div>'+
                      '</div>'+
                  '</div>').show();
                var myPlayer=$("#jquery_jplayer_1"),myPlayerData,
                fixFlash_mp4, // Flag: The m4a and m4v Flash player gives some old currentTime values when changed.
                fixFlash_mp4_id, // Timeout ID used with fixFlash_mp4
                ignore_timeupdate, // Flag used with fixFlash_mp4
                options={
                    ready: function (event) {
                        // Hide the volume slider on mobile browsers. ie., They have no effect.
                        if(event.jPlayer.status.noVolume) {
                            // Add a class and then CSS rules deal with it.
                            $(".jp-gui").addClass("jp-no-volume");
                        }
                        // Determine if Flash is being used and the mp4 media type is supplied. BTW, Supplying both mp3 and mp4 is pointless.
                        fixFlash_mp4 = event.jPlayer.flash.used && /m4a|m4v/.test(event.jPlayer.options.supplied);
                        // Setup the player with media.
                        $(this).jPlayer("setMedia", medium).jPlayer('play');
                    },
                    timeupdate: function(event) {
                        if(!ignore_timeupdate) {
                            myControl.progress.slider("value", event.jPlayer.status.currentPercentAbsolute);
                        }
                    },
                    volumechange: function(event) {
                        if(event.jPlayer.options.muted) {
                            myControl.volume.slider("value", 0);
                        } else {
                            myControl.volume.slider("value", event.jPlayer.options.volume);
                        }
                    },
                    swfPath: "js/jplayer",
                    supplied: suppl,
                    cssSelectorAncestor: "#jp_container_1",
                    wmode: "window",
                    size: {width:"430px"}
                },
                myControl = {
                    progress: $(options.cssSelectorAncestor + " .jp-progress-slider"),
                    volume: $(options.cssSelectorAncestor + " .jp-volume-slider")
                };
                // Instance jPlayer
                myPlayer.jPlayer(options);
                // A pointer to the jPlayer data object
                myPlayerData = myPlayer.data("jPlayer");
                // Define hover states of the buttons
                $('.jp-gui ul li').hover(
                    function() { $(this).addClass('ui-state-hover'); },
                    function() { $(this).removeClass('ui-state-hover'); }
                );
                // Create the progress slider control
                myControl.progress.slider({
                    animate: "fast",
                    max: 100,
                    range: "min",
                    step: 0.1,
                    value : 0,
                    slide: function(event, ui) {
                        var sp = myPlayerData.status.seekPercent;
                        if(sp > 0) {
                            // Apply a fix to mp4 formats when the Flash is used.
                            if(fixFlash_mp4) {
                                ignore_timeupdate = true;
                                clearTimeout(fixFlash_mp4_id);
                                fixFlash_mp4_id = setTimeout(function() {
                                    ignore_timeupdate = false;
                                },1000);
                            }
                            // Move the play-head to the value and factor in the seek percent.
                            myPlayer.jPlayer("playHead", ui.value * (100 / sp));
                        } else {
                            // Create a timeout to reset this slider to zero.
                            setTimeout(function() {
                                myControl.progress.slider("value", 0);
                            }, 0);
                        }
                    }
                });
                // Create the volume slider control
                myControl.volume.slider({
                    animate: "fast",
                    max: 1,
                    range: "min",
                    step: 0.1,
                    value : $.jPlayer.prototype.options.volume,
                    slide: function(event, ui) {
                        myPlayer.jPlayer("option", "muted", false);
                        myPlayer.jPlayer("option", "volume", ui.value);
                    }
                }); 
            } else {
                $videocontainer.html('').hide();
            }
        });
        $('.audio').click(function(){
            var file=$(this).closest('tr').attr('id').substr(4), $audiocontainer=$(this).closest('tr').next('tr').find('.audiocontainer'),
            extension=file.split('.').pop(),medium={},suppl='';
            if(extension=='mp3') {medium={'mp3':'library/supplement/'+escape(file)};suppl='mp3';}
            if(extension=='m4a') {medium={'m4a':'library/supplement/'+escape(file)};suppl='m4a';}
            if(extension=='oga' || extension=='ogg') {medium={'oga':'library/supplement/'+escape(file)};suppl='oga';}
            if(extension=='webma') {medium={'webma':'library/supplement/'+escape(file)};suppl='webma';}
            if(extension=='fla') {medium={'fla':'library/supplement/'+escape(file)};suppl='fla';}
            if(extension=='wav') {medium={'wav':'library/supplement/'+escape(file)};suppl='wav';}
            if($audiocontainer.is(':hidden')) {
                $('.audiocontainer,.videocontainer').empty().hide();
                $audiocontainer.html('<div id="jquery_jplayer_1" class="jp-jplayer"></div>'+
                  '<div id="jp_container_1">'+
                      '<div class="jp-gui ui-widget ui-widget-content ui-corner-all">'+
                        '<ul>'+
                          '<li class="jp-play ui-state-default ui-corner-all"><a href="javascript:;" class="jp-play ui-icon ui-icon-play" tabindex="1" title="play">play</a></li>'+
                          '<li class="jp-pause ui-state-default ui-corner-all"><a href="javascript:;" class="jp-pause ui-icon ui-icon-pause" tabindex="1" title="pause">pause</a></li>'+
                          '<li class="jp-stop ui-state-default ui-corner-all"><a href="javascript:;" class="jp-stop ui-icon ui-icon-stop" tabindex="1" title="stop">stop</a></li>'+
                          '<li class="jp-mute ui-state-default ui-corner-all"><a href="javascript:;" class="jp-mute ui-icon ui-icon-volume-off" tabindex="1" title="mute">mute</a></li>'+
                          '<li class="jp-unmute ui-state-default ui-corner-all"><a href="javascript:;" class="jp-unmute ui-icon ui-icon-volume-off" tabindex="1" title="unmute">unmute</a></li>'+
                          '<li class="jp-volume-max ui-state-default ui-corner-all"><a href="javascript:;" class="jp-volume-max ui-icon ui-icon-volume-on" tabindex="1" title="max volume">max volume</a></li>'+
                        '</ul>'+
                        '<div class="jp-progress-slider"></div>'+
                        '<div class="jp-volume-slider"></div>'+
                        '<div class="jp-current-time"></div>'+
                        '<div class="jp-duration"></div>'+
                        '<div class="jp-clearboth"></div>'+
                      '</div>'+
                  '</div>').show();
                var myPlayer=$("#jquery_jplayer_1"),myPlayerData,
                fixFlash_mp4, // Flag: The m4a and m4v Flash player gives some old currentTime values when changed.
                fixFlash_mp4_id, // Timeout ID used with fixFlash_mp4
                ignore_timeupdate, // Flag used with fixFlash_mp4
                options={
                    ready: function (event) {
                        // Hide the volume slider on mobile browsers. ie., They have no effect.
                        if(event.jPlayer.status.noVolume) {
                            // Add a class and then CSS rules deal with it.
                            $(".jp-gui").addClass("jp-no-volume");
                        }
                        // Determine if Flash is being used and the mp4 media type is supplied. BTW, Supplying both mp3 and mp4 is pointless.
                        fixFlash_mp4 = event.jPlayer.flash.used && /m4a|m4v/.test(event.jPlayer.options.supplied);
                        // Setup the player with media.
                        $(this).jPlayer("setMedia", medium).jPlayer('play');
                    },
                    timeupdate: function(event) {
                        if(!ignore_timeupdate) {
                            myControl.progress.slider("value", event.jPlayer.status.currentPercentAbsolute);
                        }
                    },
                    volumechange: function(event) {
                        if(event.jPlayer.options.muted) {
                            myControl.volume.slider("value", 0);
                        } else {
                            myControl.volume.slider("value", event.jPlayer.options.volume);
                        }
                    },
                    swfPath: "js/jplayer",
                    supplied: suppl,
                    cssSelectorAncestor: "#jp_container_1",
                    wmode: "window"
                },
                myControl = {
                    progress: $(options.cssSelectorAncestor + " .jp-progress-slider"),
                    volume: $(options.cssSelectorAncestor + " .jp-volume-slider")
                };
                // Instance jPlayer
                myPlayer.jPlayer(options);
                // A pointer to the jPlayer data object
                myPlayerData = myPlayer.data("jPlayer");
                // Define hover states of the buttons
                $('.jp-gui ul li').hover(
                    function() { $(this).addClass('ui-state-hover'); },
                    function() { $(this).removeClass('ui-state-hover'); }
                );
                // Create the progress slider control
                myControl.progress.slider({
                    animate: "fast",
                    max: 100,
                    range: "min",
                    step: 0.1,
                    value : 0,
                    slide: function(event, ui) {
                        var sp = myPlayerData.status.seekPercent;
                        if(sp > 0) {
                            // Apply a fix to mp4 formats when the Flash is used.
                            if(fixFlash_mp4) {
                                ignore_timeupdate = true;
                                clearTimeout(fixFlash_mp4_id);
                                fixFlash_mp4_id = setTimeout(function() {
                                    ignore_timeupdate = false;
                                },1000);
                            }
                            // Move the play-head to the value and factor in the seek percent.
                            myPlayer.jPlayer("playHead", ui.value * (100 / sp));
                        } else {
                            // Create a timeout to reset this slider to zero.
                            setTimeout(function() {
                                myControl.progress.slider("value", 0);
                            }, 0);
                        }
                    }
                });
                // Create the volume slider control
                myControl.volume.slider({
                    animate: "fast",
                    max: 1,
                    range: "min",
                    step: 0.1,
                    value : $.jPlayer.prototype.options.volume,
                    slide: function(event, ui) {
                        myPlayer.jPlayer("option", "muted", false);
                        myPlayer.jPlayer("option", "volume", ui.value);
                    }
                }); 
            } else {
                $audiocontainer.html('').hide();
            }
        });
    }
};

var advancedsearch={
    init:function() {
        common.init();
        $('#advancedsearchform input[name="anywhere"]').focus();
        $('#advancedsearchtabs').click(function(event){
            var $t=$(event.target);
            $(this).find('div').removeClass('clicked');
            if($t.attr('id')=='advtab-search-ref') {
                $t.addClass('clicked');
                $('#advancedsearchform .refrow').show();
                $('#fulltextrow, #notesrow').hide();
                $('#advancedsearchform input[name="searchtype"]').val('metadata');
            } else
            if($t.attr('id')=='advtab-search-pdf') {
                $t.addClass('clicked');
                $('#advancedsearchform .refrow, #notesrow').hide();
                $('#fulltextrow').show();
                $('#advancedsearchform input[name="searchtype"]').val('pdf');
            } else
            if($t.attr('id')=='advtab-search-notes') {
                $t.addClass('clicked');
                $('#advancedsearchform .refrow, #fulltextrow').hide();
                $('#notesrow').show();
                $('#advancedsearchform input[name="searchtype"]').val('notes');
            }
        });
        $('#advancedsearchform').submit(function(e){
            var searchval=$(this).find(':text:visible').map(function(){
                return $(this).val();
            }).get().join(''),sel=$('body').data('sel'),proj=$('body').data('proj');
            if(proj==undefined) proj='';
            if(searchval!='') {
                $("#advancedsearch").dialog('disable');
                var q=$(this).formSerialize();
                $('#right-panel').load('search.php?select='+sel+'&project='+proj+'&'+q,function(){
                    $("#right-panel").scrollTop(0);
                    displaywindow.init(sel,'search.php?select='+sel+'&project='+proj+'&'+q);
                    $("#advancedsearch").dialog('enable').dialog('close');
                });
            }
            e.preventDefault();
        });
        $("#advanced-filter").keyup(function(){
            var str=$(this).val(), $container=$('#advancedsearchform input[name="category\\[\\]"]').closest('tr');
            if(str!='') {
                str=str.replace(/([^a-zA-Z0-9])/g,'\\$1');
                var re=new RegExp(str, 'i');
                $container.hide().filter(function(){
                    return re.test($(this).children("td").text());
                }).show();
            } else {
                $container.show();
            }
        }).focus(function(){
            $(this).val('');
            $('#advancedsearchform input[name="category\\[\\]"]').closest('tr').show();
        }).blur(function(){
            if($(this).val()=='') $(this).val("Filter");
        });
    }
};

var expertsearch={
    init:function() {
        common.init();
        $('#expertsearchform textarea:visible').focus();
        $('#expertsearchform').find('button').button().tipsy({
            gravity: 's'
        }).click(function(){
            var buttontext=$(this).children().text(),$txa=$('#expertsearchform textarea:visible'),textareatext=$txa.val();
            $txa.insertAtCaret(' '+buttontext+' ');
            textareatext=$txa.val();
            $txa.val(textareatext.replace(/\s+/gi,' '));
            $('.expertsearchform textarea').keyup();
            return false;
        });
        $('#expertsearchtabs').click(function(event){
            var $t=$(event.target);
            $(this).find('div').removeClass('clicked');
            if($t.attr('id')=='tab-search-ref') {
                $t.addClass('clicked');
                $('#expertsearchform textarea').hide().eq(0).show().focus();
                $('#metadata-buttons').show();
                $('#expertsearchform input[name="searchtype"]').val('metadata');
            } else
            if($t.attr('id')=='tab-search-pdf') {
                $t.addClass('clicked');
                $('#expertsearchform textarea').hide().eq(1).show().focus();
                $('#metadata-buttons').hide();
                $('#expertsearchform input[name="searchtype"]').val('pdf');
            } else
            if($t.attr('id')=='tab-search-notes') {
                $t.addClass('clicked');
                $('#expertsearchform textarea').hide().eq(2).show().focus();
                $('#metadata-buttons').hide();
                $('#expertsearchform input[name="searchtype"]').val('notes');
            }
        });
        $('#expertsearchform').submit(function(e){
            var searchval=$(this).find('textarea:visible').val(),sel=$('body').data('sel'),proj=$('body').data('proj');
            if(proj==undefined) proj='';
            if(searchval!='') {
                $("#expertsearch").dialog('disable');
                var q=$(this).formSerialize();
                $('#right-panel').load('search.php?select='+sel+'&project='+proj+'&'+q,function(){
                    $("#right-panel").scrollTop(0);
                    displaywindow.init(sel,'search.php?select='+sel+'&project='+proj+'&'+q);
                    $("#expertsearch").dialog('enable').dialog('close');
                });
            }
            e.preventDefault();
        });
        $("#expert-filter").keyup(function(){
            var str=$(this).val(), $container=$('#expertsearchform input[name="category\\[\\]"]').closest('tr');
            if(str!='') {
                str=str.replace(/([^a-zA-Z0-9])/g,'\\$1');
                var re=new RegExp(str, 'i');
                $container.hide().filter(function(){
                    return re.test($(this).children("td").text());
                }).show();
            } else {
                $container.show();
            }
        }).focus(function(){
            $(this).val('');
            $('#expertsearchform input[name="category\\[\\]"]').closest('tr').show();
        }).blur(function(){
            if($(this).val()=='') $(this).val("Filter");
        });
    }
};

var uploadwindow={
    init:function() {
        common.init();
        $('.upload-errors').each(function(){
            var err=$(this).html();
            if(err) $.jGrowl(err,{
                life:5000,
                theme:'jgrowl-error'
            });
            $(this).remove();
        });
        $('.uploadsave').unbind().click(function(e){
            e.preventDefault();
            var $t=$(this),$f=$t.closest(".uploadform"),title=$f.find('textarea[name="title"]').val(),proxystr;
            $t.button("option","disabled",true);
            if(title!='' && title!=undefined) {
                $.getScript('wpad.php',function(){
                    proxystr=FindProxyForURL('', 'www.crossref.org');
                    $f.ajaxSubmit({
                        data: {
                            'proxystr': proxystr
                        },
                        dataType:'json',
                        success: function(answer){
                            if(answer['error']!=undefined) {
                                $.each(answer['error'], function(key, err) {
                                    $.jGrowl(err,{
                                        theme:'jgrowl-error'
                                    });
                                });
                            }
                            if(answer['message']!=undefined) {
                                $.each(answer['message'], function(key, mess) {
                                    mess=mess.replace(/&lt;/g, '<');
                                    mess=mess.replace(/&gt;/g, '>');
                                    $.jGrowl(mess);
                                });
                            }
                            if($('#addarticle-right').is(':visible')) {
                                if($('.pdf-file').length==1) {
                                    $('#addarticle-right').load('upload.php',function(){
                                        uploadwindow.init();
                                    });
                                } else {
                                    $f.find('input:text, textarea, select[name="journal_abbr2"], select[name="secondary_title2"]').val('');
                                }
                            }
                            $t.button("option","disabled",false);
                        }
                    });
                });
            } else {
                timeId=setTimeout(dooverlay,1000);
                $.getScript('wpad.php',function(){
                    proxystr=FindProxyForURL('', 'www.crossref.org');
                    $f.ajaxSubmit({
                        data: {
                            'proxystr': proxystr
                        },
                        success: function(answer){
                            clearoverlay();
                            if(answer.substring(0,5)=='Error') {
                                $.jGrowl(answer,{
                                    theme:'jgrowl-error',
                                    life:6000
                                });
                                $t.button("option","disabled",false);
                            } else {
                                $('#addarticle-right').empty().html(answer);
                                uploadwindow.init();
                            }
                        }
                    });
                });
            }
        }).button({
            icons:{
                primary:"ui-icon-disk"
            }
        });
        $(".open1").unbind().click(function () {
            var $t=$(this).closest('table');
            $(this).addClass('clicked').siblings().removeClass('clicked');
            $t.siblings(".table1").show();
            $t.siblings(".table2").hide();
            $t.siblings(".table3").hide();
        });
        $(".open2").unbind().click(function () {
            var $t=$(this).closest('table');
            $(this).addClass('clicked').siblings().removeClass('clicked');
            $t.siblings(".table1").hide();
            $t.siblings(".table2").show();
            $t.siblings(".table3").hide();
        });
        $(".open3").unbind().click(function () {
            var $t=$(this).closest('table');
            $(this).addClass('clicked').siblings().removeClass('clicked');
            $t.siblings(".table1").hide();
            $t.siblings(".table2").hide();
            $t.siblings(".table3").show();
        });
        $(".suggestions").unbind().click(function(e){
            var target=e.target, val=$(target).text(), $td=$(target).parent().siblings('div.categorydiv').find('td').filter(function(){
                return $(this).text()==val
            });
            if($td.is(':not(:animated)')) {
                $td.click().stop(true,true)
                .animate({
                    'padding-left':20
                },200).animate({
                    'padding-left':0
                },200)
                .animate({
                    'padding-left':2
                },50).animate({
                    'padding-left':0
                },50);
            }
        });
        $('.addurlrow').unbind().click(function(){
            $(this).closest('tr').after('<tr><td class="threedleft">URL:</td><td class="threedright"><input type="text" size="80" name="url[]" style="width: 99%" value=""></td></tr>');
        });
        $('.adduidrow').unbind().click(function(){
            $(this).closest('tr').after('<tr><td class="threedleft"><b>Database UID:</b></td><td class="threedright"><input type="text" size="80" name="uid[]" style="width: 99%" value=""></td></tr>');
        });
        $('.addauthorrow').unbind().click(function(){
            $(this).closest('div.author-inputs').append('<div>Last name: <input type="text" value=""> First name: <input type="text" value=""></div>');
            $('.uploadform .author-inputs div').filter(':last').find('input').bind('keyup blur', function(){
                var authors=[];
                $(this).closest('.author-inputs').find('div').each(function(i){
                    authors[i]='';
                    var last=$.trim($(this).children('input').eq(0).val().replace('"',''));
                    if(last!='') authors[i]='L:"'+last+'",F:"'+$.trim($(this).children('input').eq(1).val().replace('"',''))+'"';
                });
                authors=jQuery.grep(authors, function(n){
                    return (n != '');
                });
                var author=authors.join(';');
                $(this).closest('.author-inputs').next('input[name="authors"]').val(author);
            });
        });
        $('.uploadform input[name="uid\\[\\]"]').tipsy({
            html:true,
            gravity:'e'
        });
        $('.uploadform textarea[name="keywords"]').tipsy({
            html:true,
            gravity:'e'
        });
        $('.uploadform input[name="journal_abbr"]')
        .autocomplete({
            source: "ajaxjournals.php?search=journal",
            minLength: 1
        });
        $('#addarticle-right').scroll(function(){
            $('.uploadform input[name="journal_abbr"]').autocomplete('close');
        });
        $('.uploadform input[name="secondary_title"]').autocomplete({
            source: "ajaxjournals.php?search=secondary_title",
            minLength: 1
        });
        $('.uploadform input[name="authors"]').each(function(){
            var authors=[];
            $(this).prev().find('div').each(function(i){
                authors[i]='';
                var last=$.trim($(this).children('input').eq(0).val().replace('"',''));
                if(last!='') authors[i]='L:"'+last+'",F:"'+$.trim($(this).children('input').eq(1).val().replace('"',''))+'"';
            });
            authors=jQuery.grep(authors, function(n){
                return n!='';
            });
            var author=authors.join(';');
            $(this).val(author);
        });
        $('.uploadform .author-inputs input').unbind().bind('keyup blur', function(){
            var authors=[];
            $(this).closest('.author-inputs').find('div').each(function(i){
                authors[i]='';
                var last=$.trim($(this).children('input').eq(0).val().replace('"',''));
                if(last!='') authors[i]='L:"'+last+'",F:"'+$.trim($(this).children('input').eq(1).val().replace('"',''))+'"';
            });
            authors=jQuery.grep(authors, function(n){
                return n!='';
            });
            var author=authors.join(';');
            $(this).closest('.author-inputs').next('input[name="authors"]').val(author);
        });
        $('#button-none').click(function(e){
            e.preventDefault();
            $('#addarticle-right').load('upload.php?none=1',function(){
                uploadwindow.init()
            });
        }).button();
    }
};

var leftindex={
    init:function(sel,displayref) {
        common.init();
        var lw=233;
        $('body').data('sel',sel);
        if($('body').data('lwidth')!=undefined) lw=$('body').data('lwidth');
        if(lw==0) {
            $('#leftindex-left').hide();
            $('#right-panel').width($(window).width()-7);
            $('#bottom-panel .ui-icon').removeClass('ui-icon-triangle-1-w').addClass('ui-icon-triangle-1-e');
        } else {
            var lw2=$('#leftindex-left').outerWidth(true);
            $('#right-panel').width($(window).width()-lw2-7);
        }
        if(displayref=='' || displayref==undefined) displayref="display.php?browse[]=all&select="+sel;
        $('body').data('right-panel-url',displayref);
        var rp=$('#right-panel').position(), w=$(window).width()-240;
        if (rp.left==243) w=w-3;
        if(lw==0) w=w+233;
        $(window).resize(function(){
            var w=$(window).width()-240;
            if (rp.left==243) w=w-3;
            if($('body').data('lwidth')==0) w=w+233;
            $('#right-panel').width(w);
        });
        $('#right-panel').width(w).load(displayref, function(){
            displaywindow.init(sel,displayref)
        });
        $("#bottom-panel .middle-panel").click(function(){
            var $l=$(this).prev(),$r=$(this).next(),rw=$r.width();
            if($l.is(':hidden')) {
                $l.show();
                $(this).next().width(rw-233);
                $('body').data('lwidth',233);
                $(this).children().removeClass('ui-icon-triangle-1-e').addClass('ui-icon-triangle-1-w');
            } else {
                $l.hide();
                $(this).next().width(rw+233);
                $('body').data('lwidth',0);
                $(this).children().removeClass('ui-icon-triangle-1-w').addClass('ui-icon-triangle-1-e');
            }
        });
        $("#advancedsearchbutton").click(function(){
            $("#advancedsearch").load('advancedsearch.php?select='+sel,function(){
                $("#advancedsearch").dialog('option','title','Advanced search of '+sel).dialog('open');
                advancedsearch.init();
            });
        });
        $("#expertsearchbutton").click(function(){
            $("#expertsearch").load('expertsearch.php?select='+sel,function(){
                $("#expertsearch").dialog('option','title','Expert search of '+sel).dialog('open');
                expertsearch.init();
            });
        });
    
        //////////////////////////////quick search///////////////////////////////////
        $("#quicksearch #search").button({
            icons:{
                primary:"ui-icon-search"
            },
            text:false
        }).click(function(){
            var searchvalue=$("#quicksearch input[name='anywhere']").val();
            if(searchvalue=='Quick Search' || searchvalue=='') return false;
            var q=$("#quicksearch").formSerialize();
            $('#right-panel').load('search.php?'+q,function(){
                $("#right-panel").scrollTop(0);
                displaywindow.init(sel,'search.php?'+q)
            });
            return false;
        }).tipsy({
            fade:true
        });
        $("#quicksearch #clear").button({
            icons:{
                primary:"ui-icon-trash"
            },
            text:false
        }).click(function(){
            $("#quicksearch input[name='anywhere']").val('').focus();
            $("#quicksearch input[value='AND']").parent('td.select_span').click();
            $.get('search.php?newsearch=1');
        }).tipsy({
            fade:true
        });
        $("#quicksearch input[name='anywhere']").focus(function() {
            var searchvalue=$(this).val();
            if(searchvalue=='Quick Search') $(this).val('');
        }).blur(function() {
            var searchvalue=$(this).val();
            if(searchvalue=='') $(this).val('Quick Search');
        });
        $("#quicksearch").submit(function(){
            $("#quicksearch #search").click();
            return false;
        });

        /////////////////////////////button effects///////////////////////////////////

        $('td.leftbutton').hover(function() {
            $(this).stop(true, true).fadeTo(0,'0.75')
            .prev().stop(true, true).fadeTo(0,'0.75');
        },
        function() {
            $(this).stop(true, true).fadeTo(200,'1')
            .prev().stop(true, true).fadeTo(200,'1');
        });

        /////////////////////////////category navigation///////////////////////////////////

        $("#categorylink").click(function() {
            $(this).blur();
            var $first_categories=$("#first_categories"), $categories_top_container=$("#categories_top_container");
            $first_categories.html('');
            $categories_top_container.toggle();
            if($categories_top_container.css('display')!='none') {
                $.get('ajaxleftindex.php','open[]=category&select='+sel,function(answer){
                    $first_categories.html(answer);
                },'html');
                return false;
            }
        });

        ///////////////////////////////filter categories///////////////////////////

        $("#filter_categories").keyup(function(){
            var str=$(this).val(),$span=$('#first_categories > div > div');
            if(str != '') {
                str=str.replace(/([^a-zA-Z0-9])/g,'\\$1');
                var re=new RegExp(str, 'i');
                $span.hide().filter(function(){
                    return re.test($(this).children('span').text());
                }).show();
            } else {
                $('#first_categories > div > div').show();
            }
        }).focus(function(){
            $(this).val('');
            $('#first_categories > div > div').show();
        }).blur(function(){
            if($(this).val()=='') $(this).val('Filter');
        });

        ///////////////////////////////category tree///////////////////////////

        $('#first_categories').click(function(event){
            var $t=$(event.target),urlstring='';
            if($t.hasClass('cat1')) {
                var $divid=$t.next('div'),category1=$t.parent().attr('id').split('-').pop(),
                ref='display.php?browse['+category1+']=category&select='+sel;
                $("span.cat1").removeClass('clicked');
                $t.addClass('clicked');
                if(category1 > 0) {
                    urlstring="open["+category1+"]=category&select="+sel;
                    $.get('ajaxleftindex.php',urlstring,function(answer){
                        $("span.cat1").next('div').each(function(){
                            $(this).html('').hide();
                        });
                        $divid.html(answer).show();
                    },'html');
                } else {
                    $("span.cat1").next('div').each(function(){
                        $(this).html('').hide();
                    });
                }
                $('#right-panel').load(ref,function(){
                    $("#right-panel").scrollTop(0);
                    displaywindow.init(sel,ref)
                });
                event.preventDefault();
            } else
            if($t.hasClass('cat2')) {
                var $divid=$t.next('div'),
                category1=$t.parent().parent().parent().attr('id').split('-').pop(),
                category2=$t.parent().attr('id').split('-').pop(),
                ref='display.php?browse['+category1+']=category&browse['+category2+']=category&select='+sel;
                $("span.cat2").removeClass('clicked');
                $t.addClass('clicked');
                if(category1 != '') urlstring = "open["+category1+"]=category&select="+sel;
                if(category2 != '') urlstring = "open["+category1+"]=category&open["+category2+"]=category&select="+sel;
                $.get('ajaxleftindex.php',urlstring,function(answer){
                    $("span.cat2").next('div').each(function(){
                        $(this).html('').hide();
                    });
                    $divid.html(answer).show();
                },'html');
                $('#right-panel').load(ref,function(){
                    $("#right-panel").scrollTop(0);
                    displaywindow.init(sel,ref)
                });
                event.preventDefault();
            } else
            if($t.hasClass('cat3')) {
                var $divid=$t.next('div'),
                category1=$t.parent().parent().parent().parent().parent().attr('id').split('-').pop(),
                category2=$t.parent().parent().parent().attr('id').split('-').pop(),
                category3=$t.parent().attr('id').split('-').pop(),
                ref='display.php?browse['+category1+']=category&browse['+category2+']=category&browse['+category3+']=category&select='+sel;
                $("span.cat3").removeClass('clicked');
                $t.addClass('clicked');
                if(category1 != '') urlstring = "open["+category1+"]=category&select="+sel;
                if(category2 != '') urlstring = "open["+category1+"]=category&open["+category2+"]=category&select="+sel;
                if(category3 != '') urlstring = "open["+category1+"]=category&open["+category2+"]=category&open["+category3+"]=category&select="+sel;
                $.get('ajaxleftindex.php',urlstring,function(answer){
                    $("span.cat3").next('div').each(function(){
                        $(this).html('').hide();
                    });
                    $divid.html(answer).show();
                },'html');
                $('#right-panel').load(ref,function(){
                    $("#right-panel").scrollTop(0);
                    displaywindow.init(sel,ref)
                });
                event.preventDefault();
            } else
            if($t.hasClass('cat4')) {
                var category1=$t.parent().parent().parent().parent().parent().parent().attr('id').split('-').pop(),
                category2=$t.parent().parent().parent().parent().attr('id').split('-').pop(),
                category3=$t.parent().parent().attr('id').split('-').pop(),
                category4=$t.attr('id').split('-').pop(),
                ref='display.php?browse['+category1+']=category&browse['+category2+']=category&browse['+category3+']=category&browse['+category4+']=category&select='+sel;
                $("span.cat4").removeClass('clicked');
                $t.addClass('clicked');
                $('#right-panel').load(ref,function(){
                    $("#right-panel").scrollTop(0);
                    displaywindow.init(sel,ref)
                });
                event.preventDefault();
            }
        });

        /////////////////////////////addition date navigation///////////////////////////////////

        $('#additiondatelink').click(function(){
            $(this).blur();
            var $datepicker=$('#datepicker');
            $datepicker.toggle();
            if($datepicker.is(':hidden')) {
                $datepicker.datepicker('destroy');
                return false;
            }
            $.getJSON('ajaxleftindex.php?open[]=dates&select='+sel,function(answer){
                var mindt=answer['mindate'],maxdt=answer['maxdate'];
                if(maxdt=='') {
                    $datepicker.text('No items.');
                    return false;
                }
                $('#datepicker').datepicker({
                    inline: true,
                    firstDay: 1,
                    dateFormat: 'yy-mm-dd',
                    minDate: mindt,
                    maxDate: maxdt,
                    beforeShowDay: function(date) {
                        var y=date.getFullYear(), m=1+date.getMonth(), d=date.getDate();
                        if(m<10) m="0"+m;
                        if(d<10) d="0"+d;
                        var f=y+"-"+m+"-"+d;
                        if(answer.datecount[f]==undefined) {
                            return [false,'','no items added on this day'];
                        } else {
                            var s='';
                            if(answer.datecount[f]>1) s="s";
                            return [true,'',answer.datecount[f]+' item'+s];
                        }
                    },
                    onSelect: function(dateText) {
                        $('#right-panel').load('display.php?browse['+dateText+']=addition_date&select='+sel,function(){
                            $("#right-panel").scrollTop(0);
                            displaywindow.init(sel,'display.php?browse['+dateText+']=addition_date&select='+sel)
                        });
                    }
                });
            });
        });

        /////////////////////////////author navigation///////////////////////////////////

        $("#authorlink").click(function() {
            $(this).blur();
            var $authors_container=$("#authors_container"), $authors_top_container=$("#authors_top_container");
            $('body').data('letter','A');
            $authors_container.data("from",0).empty();
            $("#filter_authors").focus().blur();
            $authors_top_container.toggle();
            $("#next_authors").hide();
            $("#prev_authors").hide();
            $("#prevprev_authors").hide();
            if($authors_top_container.css('display')!='none') {
                $("#authors_header").find('.letter').removeClass('clicked').eq(0).addClass('clicked');
                $.get('ajaxleftindex.php','open[]=authors&select='+sel+'&first_letter=A&from=0',function(answer){
                    if($(answer).find('span').length==1000){
                        $("#next_authors").show();
                        $("#prev_authors").show();
                        $("#prevprev_authors").show();
                    }
                    $authors_container.html(answer);
                },'html');
                return false;
            }
        });
        $("#authors_header").click(function(e) {
            var target = e.target, $target = $(target);
            if(!$target.hasClass('letter')) return false;
            var $authors_container=$("#authors_container"), letter=$target.text();
            $('body').data('letter',letter);
            $authors_container.data("from",0);
            $.get('ajaxleftindex.php','open[]=authors&select='+sel+'&first_letter='+letter+'&from=0',function(answer){
                $("#filtered_authors").empty();
                if($(answer).find('span').length<1000){
                    $("#next_authors").hide();
                    $("#prev_authors").hide();
                    $("#prevprev_authors").hide();
                } else {
                    $("#next_authors").show();
                    $("#prev_authors").show();
                    $("#prevprev_authors").show();
                }
                $authors_container.show().html(answer);
                $target.siblings('span').removeClass('clicked');
                $target.addClass('clicked');
            },'html');
        });
        $("#next_authors").click(function() {
            var letter=$('body').data('letter');
            var $authors_container=$("#authors_container");
            if($authors_container.find('span').length<1000) return false;
            if($authors_container.data("from")==undefined) $authors_container.data("from",0);
            var from = $authors_container.data("from")+1000;
            $.get('ajaxleftindex.php','open[]=authors&select='+sel+'&first_letter='+letter+'&from='+from,function(answer){
                $authors_container.html(answer);
                if($authors_container.find('span').length<1000) $("#next_authors").hide();
            },'html');
            $authors_container.data("from",from);
        });
        $("#prev_authors").click(function() {
            var letter=$('body').data('letter');
            var $authors_container=$("#authors_container");
            if($authors_container.data("from")==undefined) $authors_container.data("from",0);
            var from = $authors_container.data("from")-1000;
            if(from<0) return false;
            $.get('ajaxleftindex.php','open[]=authors&select='+sel+'&first_letter='+letter+'&from='+from,function(answer){
                $authors_container.html(answer);
                $("#next_authors").show();
            },'html');
            $authors_container.data("from",from);
        });
        $("#prevprev_authors").click(function() {
            var $authors_container=$("#authors_container"), letter=$('body').data('letter');
            if($authors_container.data("from")==undefined || $authors_container.data("from")==0) return false;
            $.get('ajaxleftindex.php','open[]=authors&select='+sel+'&first_letter='+letter+'&from=0',function(answer){
                $authors_container.html(answer);
                $("#next_authors").show();
            },'html');
            $authors_container.data("from",0);
        });

        $("#authors_container, #filtered_authors").click(function(e) {
            var target = e.target, $target = $(target);
            if($target.is('span')) {
                e.preventDefault();
                var auth=$target.attr('id'),ref='display.php?browse['+auth+']=authors&select='+sel;
                $('#right-panel').load(ref,function(){
                    $("#right-panel").scrollTop(0);
                    displaywindow.init(sel,ref)
                });
                $target.siblings('span').removeClass('clicked');
                $target.addClass('clicked');
            }
        });

        /////////////////////////////filter authors///////////////////////////////////

        $("#filter_authors").keyup(function(){
            var filterstring=$(this).val();
            if(filterstring.length > 1) {
                filterstring=encodeURI(filterstring);
                var $filtered_authors=$("#filtered_authors"),$authors_container=$("#authors_container"),timeid='';
                function getauthors(filterstring) {
                    $.get('ajaxfilter.php','open[]=authors&select='+sel+'&filter='+filterstring,function(answer){
                        $authors_container.hide();
                        $("#next_authors").hide();
                        $("#prev_authors").hide();
                        $("#prevprev_authors").hide();
                        $filtered_authors.html(answer);
                        clearTimeout(timeid);
                        $(document).data('lock','');
                    },'html');
                }
                timeid=setTimeout(function(){
                    getauthors(filterstring);
                }, 200);
            } else {
                var from=$('#authors_container').data('from');
                $("#authors_container").show();
                $("#filtered_authors").empty();
                if($("#authors_container").find("span").length==1000 || from>0){
                    $("#next_authors").show();
                    $("#prev_authors").show();
                    $("#prevprev_authors").show();
                }
            }
        }).focus(function(){
            $(this).val('');
            $("#filter_authors").trigger('keyup');
        }).blur(function(){
            if($(this).val()=='') {
                $(this).val("Filter");
                $("#authors_container").show();
                $("#filtered_authors").empty();
            }
        });

        /////////////////////////////journals navigation///////////////////////////////////

        $("#journallink").click(function() {
            $(this).blur();
            var $journals_container=$("#journals_container"), $journals_top_container=$("#journals_top_container");
            $journals_container.html('');
            $journals_top_container.toggle();
            if($journals_top_container.is(':visible')) {
                $.get('ajaxleftindex.php','open[]=journal&select='+sel,function(answer){
                    $journals_container.html(answer);
                },'html');
                return false;
            }
        });

        ///////////////////////////////filter journals///////////////////////////

        $('#filter_journals').keyup(function(){
            var str=$(this).val(), $element=$('#journals_container > div > div');
            if(str!='') {
                str=str.replace(/([^a-zA-Z0-9])/g,'\\$1');
                var re=new RegExp('^'+str, 'i');
                $element.hide().filter(function(){
                    return re.test($(this).children('span').text());
                }).show();
            } else {
                $('#journals_container > div > div').show();
            }
        }).focus(function(){
            $(this).val('');
            $('#journals_container > div > div').show();
        }).blur(function(){
            if($(this).val()=='') $(this).val('Filter');
        });

        ///////////////////////////////journal tree///////////////////////////

        $("#journals_container").click(function(e){
            var $t=$(e.target);
            if($t.hasClass('jour')) {
                var $divid=$t.next('div'),journal=$t.parent().attr('id'),ref='display.php?browse['+journal+']=journal&select='+sel;
                if($("#journals_top_container").is(':visible') && $divid.is(':hidden')) {
                    var urlstring = '';
                    if(journal != '') urlstring = "open["+journal+"]=journal&select="+sel;
                    $.get('ajaxleftindex.php',urlstring,function(answer){
                        $('.jour').next('div').each(function(){
                            $(this).html('').hide();
                        });
                        $('.jour').removeClass('clicked');
                        $t.addClass('clicked');
                        $divid.show().html(answer);
                    },'html');
                }
                $('#right-panel').load(ref,function(){
                    $("#right-panel").scrollTop(0);
                    displaywindow.init(sel,ref)
                });
            } else
            if($t.hasClass('jour2')) {
                var journal=$t.parent().parent().parent().attr('id'),year=$t.html(),
                ref='display.php?browse['+journal+']=journal&browse['+year+']=year&select='+sel;
                $('#right-panel').load(ref,function(){
                    $("#right-panel").scrollTop(0);
                    displaywindow.init(sel,ref)
                });
                $t.siblings().removeClass('clicked');
                $t.addClass('clicked');
            }
        });

        /////////////////////////////secondary titles navigation///////////////////////////////////

        $("#secondarytitlelink").click(function() {
            $(this).blur();
            var $secondarytitles_container=$("#secondarytitles_container"), $secondarytitles_top_container=$("#secondarytitles_top_container");
            $secondarytitles_container.html('');
            $secondarytitles_top_container.toggle();
            if($secondarytitles_top_container.is(':visible')) {
                $.get('ajaxleftindex.php','open[]=secondary_title&select='+sel,function(answer){
                    $secondarytitles_container.html(answer);
                },'html');
                return false;
            }
        });

        ///////////////////////////////filter secondary titles///////////////////////////

        $("#filter_secondarytitles").keyup(function(){
            var str=$(this).val(), $element=$('#secondarytitles_container > div > div');
            if(str!='') {
                str=str.replace(/([^a-zA-Z0-9])/g,'\\$1');
                var re=new RegExp('^'+str, 'i');
                $element.hide().filter(function(){
                    return re.test($(this).children('span').text());
                }).show();
            } else {
                $('#secondarytitles_container > div > div').show();
            }
        }).focus(function(){
            $(this).val('');
            $('#secondarytitles_container > div > div').show();
        }).blur(function(){
            if($(this).val()=='') $(this).val('Filter');
        });

        ///////////////////////////////secondary title tree///////////////////////////

        $("#secondarytitles_container").click(function(e){
            var $t=$(e.target);
            if($t.hasClass('sec')) {
                var $divid=$t.next('div'),sec=$t.parent().attr('id'),ref='display.php?browse['+sec+']=secondary_title&select='+sel;
                if($("#secondarytitles_top_container").is(':visible') && $divid.is(':hidden')) {
                    var urlstring = '';
                    if(sec != '') urlstring = "open["+sec+"]=secondary_title&select="+sel;
                    $.get('ajaxleftindex.php',urlstring,function(answer){
                        $(".sec").next('div').each(function(){
                            $(this).html('').hide();
                        });
                        $(".sec").removeClass('clicked');
                        $t.addClass('clicked');
                        $divid.show().html(answer);
                    },'html');
                }
                $('#right-panel').load(ref,function(){
                    $("#right-panel").scrollTop(0);
                    displaywindow.init(sel,ref)
                });
                e.preventDefault();
            } else
            if($t.hasClass('sec2')) {
                var sec=$t.parent().parent().parent().attr('id'),year=$t.html(),
                ref='display.php?browse['+sec+']=secondary_title&browse['+year+']=year&select='+sel;
                $('#right-panel').load(ref,function(){
                    $("#right-panel").scrollTop(0);
                    displaywindow.init(sel,ref)
                });
                $t.siblings().removeClass('clicked');
                $t.addClass('clicked');
            }
        });

        /////////////////////////////keyword navigation///////////////////////////////////

        $("#keywordlink").click(function() {
            $(this).blur();
            var $keywords_container=$("#keywords_container"), $keywords_top_container=$("#keywords_top_container");
            $keywords_container.empty();
            $keywords_container.data("from",0);
            $("#next_keywords").hide();
            $("#prev_keywords").hide();
            $("#prevprev_keywords").hide();
            $keywords_top_container.toggle();
            if($keywords_top_container.is(':visible')) {
                $.get('ajaxleftindex.php','open[]=keywords&select='+sel+'&from=0',function(answer){
                    $keywords_container.html(answer);
                    if($(answer).find('.key').length>=1000){
                        $("#next_keywords").show();
                        $("#prev_keywords").show();
                        $("#prevprev_keywords").show();
                    }
                },'html');
                return false;
            }
        });
        $("#next_keywords").click(function() {
            var $keywords_container=$("#keywords_container");
            if($keywords_container.data("from")==undefined) $keywords_container.data("from",0);
            var from = $keywords_container.data("from")+1000;
            $.get('ajaxleftindex.php','open[]=keywords&select='+sel+'&from='+from,function(answer){
                $keywords_container.html(answer);
                if($keywords_container.find('.key').length<1000) $("#next_keywords").hide();
            },'html');
            $keywords_container.data("from",from);
        });
        $("#prev_keywords").click(function() {
            var $keywords_container=$("#keywords_container");
            if($keywords_container.data("from")==undefined) $keywords_container.data("from",0);
            var from = $keywords_container.data("from")-1000;
            if(from<0) return false;
            $.get('ajaxleftindex.php','open[]=keywords&select='+sel+'&from='+from,function(answer){
                $keywords_container.html(answer);
                $("#next_keywords").show();
            },'html');
            $keywords_container.data("from",from);
        });
        $("#prevprev_keywords").click(function() {
            var $keywords_container=$("#keywords_container");
            if($keywords_container.data("from")==undefined || $keywords_container.data("from")==0) return false;
            var select=$('body').data('selection');
            $.get('ajaxleftindex.php','open[]=keywords&select='+sel+'&from=0',function(answer){
                $keywords_container.html(answer);
                $("#next_keywords").show();
            },'html');
            $keywords_container.data("from",0);
        });

        /////////////////////////////filter keywords/////////////////////////////

        $("#filter_keywords").keyup(function(){
            var filterstring=$(this).val();
            if(filterstring.length > 1) {
                filterstring=encodeURI(filterstring);
                $.get('ajaxfilter.php','open[]=keywords&select='+sel+'&filter='+filterstring,function(answer){
                    $("#filtered_keywords").show().html(answer);
                    $("#keywords_container").hide();
                    $("#next_keywords").hide();
                    $("#prev_keywords").hide();
                    $("#prevprev_keywords").hide();
                },'html');
            } else {
                var from=$("#keywords_container").data('from');
                $("#filtered_keywords").hide().empty();
                $("#keywords_container").show();
                if($("#keywords_container").find('.key').length==1000 || from>0){
                    $("#next_keywords").show();
                    $("#prev_keywords").show();
                    $("#prevprev_keywords").show();
                }
            }
        }).focus(function(){
            $(this).val("");
            $("#filter_keywords").trigger('keyup');
        }).blur(function(){
            if($(this).val()=='') {
                $(this).val("Filter");
                $("#keywords_container").show();
                $("#filtered_keywords").hide().empty();
            }
        });

        $("#keywords_container, #filtered_keywords").click(function(e) {
            var $t=$(e.target);
            if($t.hasClass('key')) {
                var key=$t.attr('id'),ref='display.php?browse['+key+']=keywords&select='+sel;
                $('#right-panel').load(ref,function(){
                    $("#right-panel").scrollTop(0);
                    displaywindow.init(sel,ref)
                });
                $t.siblings().removeClass('clicked');
                $t.addClass('clicked');
            }
        });

        /////////////////////////////saved search navigation///////////////////////////////////

        $("#savedsearchlink").click(function() {
            $(this).blur();
            var $div=$('#savedsearch_container');
            $div.toggle();
            if($div.is(':visible')) {
                $div.load('ajaxleftindex.php?open[]=savedsearch',function(){
                    $div.find('button').button();
                });
            } else {
                $div.empty();
            }
        });
        $("#savedsearch_container").click(function(e) {
            var $t=$(e.target);
            if($t.hasClass('savedsearch')) {
                var searchanme=$t.attr('id');
                $.get('search.php?loadsearch=1&searchname='+searchanme,function(answer){
                    $('#right-panel').load('search.php?select='+sel+'&project=&'+answer,function(){
                        $("#right-panel").scrollTop(0);
                        displaywindow.init(sel,'search.php?select='+sel+'&project=&'+answer)
                    });
                });
                $('.savedsearch').removeClass('clicked');
                $t.addClass('clicked');
            }
        });
        $("#savedsearch_container").delegate(".rename-search", "click", function(){
            var $t=$(this),searchname=$t.prev().attr('id'),searchstr=decodeURIComponent(searchname);
            if($t.prev().is(':visible')) {
                $t.prev().hide();
                $t.parent().prepend('<input type="text" value="'+escapeHtml(searchstr)+'" style="width:180px;margin:2px 0">');
            } else {
                var searchname2=$t.prev().prev().val();
                if(searchname==encodeURIComponent(searchname2)) {
                    $t.prev().prev().remove();
                    $t.prev().show();
                    return false;
                }
                $.get('search.php?renamesearch=1&searchname='+searchname+'&searchname2='+encodeURIComponent(searchname2),function(){
                    $t.prev().prev().remove();
                    $t.prev().attr('id',encodeURIComponent(searchname2)).html(escapeHtml(searchname2)).show();
                });
            }
        });
        $("#savedsearch_container").delegate(".delete-search", "click", function(){
            var $t=$(this),searchanme=$t.prev().prev().attr('id');
            $('body').append('<div id="dialog-delete-search" title="Delete search"></div>');
            $("#dialog-delete-search").html('<p><span class="ui-state-error-text"><span class="ui-icon ui-icon-alert" style="float:left;margin:0px 7px 20px 0px"></span></span> This search will be permanently deleted. Are you sure?</p>')
            .data('t',$t);
            $("#dialog-delete-search").dialog({
                autoOpen: true,
                buttons: {
                    'Delete': function() {
                        var $t=$(this).data('t'),searchanme=$t.prev().prev().attr('id');
                        $.get('search.php?deletesearch=1&searchname='+searchanme,function(answer){
                            if(answer=='OK') $t.parent().remove();
                            if($('#savedsearch_container button').length<1) $("#savedsearch_container").text('No saved searches.');
                        });
                        $(this).dialog('close').remove();
                    },
                    'Cancel': function() {
                        $(this).dialog('close').remove();
                    }
                }
            });
        });

        function escapeHtml(unsafe) {
            return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
        }
        /////////////////////////////miscellaneous navigation///////////////////////////////////

        $("#misclink").click(function() {
            $(this).blur();
            var $div=$('#misc_container');
            $div.toggle();
            $('.misc').removeClass('clicked');
        });
        $("#misc_container").click(function(e) {
            var $t=$(e.target);
            if($t.attr('id')=='noshelf') {
                ref='display.php?browse[Not+in+Shelf]=miscellaneous&select='+sel;
                $('.misc').removeClass('clicked');
                $t.addClass('clicked');
            } else
            if($t.attr('id')=='nopdf') {
                ref='display.php?browse[No+PDF]=miscellaneous&select='+sel;
                $('.misc').removeClass('clicked');
                $t.addClass('clicked');
            } else
            if($t.attr('id')=='noindex') {
                ref='display.php?browse[Not+Indexed]=miscellaneous&select='+sel;
                $('.misc').removeClass('clicked');
                $t.addClass('clicked');
            } else
            if($t.attr('id')=='myitems') {
                ref='display.php?browse[My+Items]=miscellaneous&select='+sel;
                $('.misc').removeClass('clicked');
                $t.addClass('clicked');
            } else
            if($t.attr('id')=='othersitems') {
                ref="display.php?browse[Others'+Items]=miscellaneous&select="+sel;
                $('.misc').removeClass('clicked');
                $t.addClass('clicked');
            }
            $('#right-panel').load(ref,function(){
                $("#right-panel").scrollTop(0);
                displaywindow.init(sel,ref)
            });
        });
        //HISTORY BUTTON
        $("#historylink").click(function() {
            $(this).blur();
            var ref="display.php?browse[Viewed+in+the+last+8+hours]=history&select=library";
            $('#right-panel').load(ref,function(){
                $("#right-panel").scrollTop(0);
                displaywindow.init('library',ref)
            });
        });
    }
};

var downloadcommon={
    init:function(dbase){
        common.init();
        var showid='';
        function briefShow($el) {
            clearTimeout(showid);
            $el.css('height','auto');
        }
        function briefHide($el) {
            clearTimeout(showid);
            $el.css('height','');
        }
        $('#addarticle-right .brief').mouseover(function(){
            var $t=$(this);
            showid=setTimeout(function(){
                briefShow($t)
                }, 400);
        }).mouseout(function(){
            briefHide($(this));
        });
        var scrpt='download_'+dbase;
        $('#download-reset').button({
            icons:{
                primary:'ui-icon-arrowreturnthick-1-w'
            }
        }).click(function(e){
            e.preventDefault();
            $('#download-form').resetForm();
        });
        $("#addarticle-right .pgdown").click(function() {
            var pgupoffset=$("#addarticle-right .pgup").offset();
            $("#addarticle-right").animate({
                scrollTop:pgupoffset.top
            },100);
        });
        $("#addarticle-right .pgup").click(function() {
            $("#addarticle-right").animate({
                scrollTop:0
            },100);
        });
        if(dbase!='pubmed'){
            $('#download-clear').button({
                icons:{
                    primary:'ui-icon-trash'
                }
            }).click(function(e){
                e.preventDefault();
                $('#addarticle-right').load('download_'+dbase+'.php?newsearch',function(){
                    window[scrpt].init()
                });
            });
            $('#download-form input[name="'+dbase+'_searchname"]').keydown(function(e) {
                if(e.which==13) {
                    e.preventDefault();
                    $('#download-save').click();
                }
            });
        }
        $('.navigation').click(function(e) {
            e.preventDefault();
            timeId=setTimeout(dooverlay,200);
            var ref=$(this).attr('href');
            $.getScript('wpad.php',function(){
                proxystr=FindProxyForURL('', 'www.pubmed.org');
                $.get(ref+'&proxystr='+proxystr,function(answer){
                    clearoverlay();
                    if(answer.substring(0,5)=='Error'){
                        $.jGrowl(answer,{
                            theme:'jgrowl-error',
                            life:6000
                        });
                        return false;
                    }
                    $('#addarticle-right').scrollTop(0).html(answer);
                    window[scrpt].init();
                }
                );
            });
        });
        //HOTKEYS
        $(document).unbind('keydown').bind('keydown', 'd', function(){
            if($('#addarticle-right .nextpage').is(':visible') && $('.ui-dialog:visible').length===0) $('#addarticle-right .nextpage').click();
        }).bind('keydown', 'a', function(){
            if($('#addarticle-right .prevpage').is(':visible') && $('.ui-dialog:visible').length===0) $('#addarticle-right .prevpage').click();
        });
    }
};

var download_pubmed={
    init:function(){
        downloadcommon.init('pubmed');
        $('#download-search').click(function(e) {
            e.preventDefault();
            timeId=setTimeout(dooverlay,1);
            var proxystr;
            $.getScript('wpad.php',function(){
                proxystr=FindProxyForURL('', 'www.crossref.org');
                $('#download-form').ajaxSubmit({
                    data: {
                        'proxystr': proxystr
                    },
                    success: function(answer){
                        if(answer.substring(0,5)=='Error'){
                            $.jGrowl(answer,{
                                theme:'jgrowl-error',
                                life:6000
                            });
                            clearoverlay();
                            return false;
                        }
                        var srch=$('#download-form').find('input[name="searchname"]').val();
                        srch=encodeURIComponent(srch);
                        srch=srch.replace(/([ #;&,.+*~\':"!^$[\]()=>|\/%])/g,'\\$1');
                        clearoverlay();
                        $('#addarticle-right').empty().html(answer);
                        download_pubmed.init();
                        $('#saved-search-pubmed-'+srch).next().next().text('0 hours ago');
                    }
                });
            });
        }).click(function(e){
            e.preventDefault();
        }).button({
            icons:{
                primary:'ui-icon-search'
            }
        });
        $('#download-clear').button({
            icons:{
                primary:'ui-icon-trash'
            }
        }).click(function(e){
            e.preventDefault();
            $('#addarticle-right').load('download.php?newsearch',function(){
                download_pubmed.init()
            });
        });
        $('#download-save').click(function(e) {
            e.preventDefault();
            var srch=$('#download-form').find('input[name="searchname"]').val();
            if(srch=='') return false;
            var srchid=encodeURIComponent(srch);
            srchid=srchid.replace(/([ #;&,.+*~\':"!^$[\]()=>|\/%])/g,'\\$1');
            $('#download-form').ajaxSubmit({
                data: {
                    save: '1'
                },
                success: function(){
                    if($('#saved-search-pubmed-'+srchid).length==0) {
                        $('#pubmed-container').append('<div class="pubmed"><div class="ui-state-highlight del-saved-search pubmed" style="float:right;margin:1px 0"><span class="ui-icon ui-icon-trash"></span></div>'
                            +'<span class="saved-search pubmed" id=""></span><br>&nbsp;<span>Never</span></div><div style="clear:both"></div>');
                        $('span.saved-search').removeClass('clicked');
                        $('#pubmed-container > div.pubmed:last > span.saved-search').attr('id','saved-search-pubmed-'+encodeURIComponent(srch)).text(srch).addClass('clicked');
                        addarticle.init(false);
                        $.jGrowl('Search saved.');
                    } else {
                        $('#saved-search-pubmed-'+srchid).next().next().text('Never');
                        $.jGrowl('Search saved.');
                    }
                }
            });
        }).button({
            icons:{
                primary:'ui-icon-disk'
            }
        });
        $('input.matcher').bind('keyup blur',function(){
            $('textarea.tagged_query').val('');
        });
        $('textarea.tagged_query').bind('keyup blur',function(){
            $('input.matcher').each(function(){
                $(this).val('')
            });
        });
        $("#addarticle-right div.titles").click(function() {
            var $this=$(this),$abstract=$this.parent().siblings("div.abstract_container"),$c=$(this).parent().siblings('div.save_container'),
            $firstcontainer=$this.parent().siblings('table.firstcontainer'),uid=$this.closest('div.items').attr('id').split('-').pop();
            if($firstcontainer.is(':visible')) {
                if($abstract.html()=='') {
                    if($('body').data('click')==true) return false;
                    $('body').data('click',true);
                    timeId=setTimeout(dooverlay,1000);
                    var proxystr;
                    $.getScript('wpad.php',function(){
                        proxystr=FindProxyForURL('', 'www.crossref.org');
                        $.get('fetch.php?id='+uid+'&proxystr='+proxystr,function(answer){
                            $('body').data('click',false);
                            clearoverlay();
                            if(answer.substring(0,5)=='Error'){
                                $.jGrowl(answer,{
                                    theme:'jgrowl-error',
                                    life:6000
                                });
                                return false;
                            }
                            $abstract.html(answer).show();
                            $firstcontainer.hide();
                            fetch.init();
                        });
                    });
                } else {
                    $abstract.show();
                    if($c.html()!='') $c.show();
                    $firstcontainer.hide();
                }
            } else {
                $abstract.hide();
                $c.hide();
                $firstcontainer.show();
            }
        });
        $("#addarticle-right div.flag").click(function() {
            var $this=$(this),uid=$this.closest('div.items').attr('id').substring(4),count=parseInt($('#pubmed-flagged-count').text());
            $.get('flagged.php?database=pubmed&uid='+uid,function(answer){
                if(answer=='added'){
                    $this.removeClass('ui-priority-secondary').addClass('ui-state-error-text');
                    $('#pubmed-flagged-count').text(count+1);
                } else
                if(answer=='removed'){
                    $this.removeClass('ui-state-error-text').addClass('ui-priority-secondary');
                    $('#pubmed-flagged-count').text(count-1);
                }
            });
        });
        $('#download-form textarea[name="tagged_query"]').keydown(function(e) {
            if(e.which==13) {
                e.preventDefault();
                $('#download-search').click();
            }
        });
        $('#download-form input[name="searchname"]').keydown(function(e) {
            if(e.which==13) {
                e.preventDefault();
                $('#download-save').click();
            }
        });
    }
};

var download_pmc={
    init:function(){
        downloadcommon.init('pmc');
        $('#download-search').click(function(e) {
            e.preventDefault();
            timeId=setTimeout(dooverlay,1);
            var proxystr;
            $.getScript('wpad.php',function(){
                proxystr=FindProxyForURL('', 'www.crossref.org');
                $('#download-form').ajaxSubmit({
                    data: {
                        'proxystr': proxystr
                    },
                    success: function(answer){
                        clearoverlay();
                        if(answer.substring(0,5)=='Error'){
                            $.jGrowl(answer,{
                                theme:'jgrowl-error',
                                life:6000
                            });
                            return false;
                        }
                        var srch=$('#download-form').find('input[name="pmc_searchname"]').val();
                        srch=encodeURIComponent(srch);
                        srch=srch.replace(/([ #;&,.+*~\':"!^$[\]()=>|\/%])/g,'\\$1');
                        $('#addarticle-right').empty().html(answer);
                        download_pmc.init();
                        $('#saved-search-pmc-'+srch).next().next().text('0 hours ago');
                    }
                });
            });
        }).click(function(e){
            e.preventDefault();
        }).button({
            icons:{
                primary:'ui-icon-search'
            }
        });
        $('#download-save').click(function(e) {
            e.preventDefault();
            var srch=$('#download-form').find('input[name="pmc_searchname"]').val();
            if(srch=='') return false;
            var srchid=encodeURIComponent(srch);
            srchid=srchid.replace(/([ #;&,.+*~\':"!^$[\]()=>|\/%])/g,'\\$1');
            $('#download-form').ajaxSubmit({
                data: {
                    save: '1'
                },
                success: function(){
                    if($('#saved-search-pmc-'+srchid).length==0) {
                        $('#pmc-container').append('<div class="pmc"><div class="ui-state-highlight del-saved-search pmc" style="float:right;margin:1px 0"><span class="ui-icon ui-icon-trash"></span></div>'
                            +'<span class="saved-search pmc" id=""></span><br>&nbsp;<span>Never</span></div><div style="clear:both"></div>');
                        $('span.saved-search').removeClass('clicked');
                        $('#pmc-container > div.pmc:last > span.saved-search').attr('id','saved-search-pmc-'+encodeURIComponent(srch)).text(srch).addClass('clicked');
                        addarticle.init(false);
                        $.jGrowl('Search saved.');
                    } else {
                        $('#saved-search-pmc-'+srchid).next().next().text('Never');
                        $.jGrowl('Search saved.');
                    }
                }
            });
        }).button({
            icons:{
                primary:'ui-icon-disk'
            }
        });
        $('input.matcher').bind('keyup blur',function(){
            $('textarea.pmc_tagged_query').val('');
        });
        $('textarea.pmc_tagged_query').bind('keyup blur',function(){
            $('input.matcher').each(function(){
                $(this).val('')
            });
        });
        $("#addarticle-right div.titles").click(function() {
            var $this=$(this),$abstract=$this.parent().siblings("div.abstract_container"),$c=$(this).parent().siblings('div.save_container'),
            $firstcontainer=$this.parent().siblings('table.firstcontainer'),uid=$this.closest('div.items').attr('id').split('-').pop(),
            pmcid=$this.closest('div.items').data('pmcid');
            if($firstcontainer.is(':visible')) {
                if($abstract.html()=='') {
                    if($('body').data('click')==true) return false;
                    $('body').data('click',true);
                    timeId=setTimeout(dooverlay,1000);
                    var proxystr;
                    $.getScript('wpad.php',function(){
                        proxystr=FindProxyForURL('', 'www.crossref.org');
                        $.get('fetch_pmc.php?id='+uid+'&pmcid='+pmcid+'&proxystr='+proxystr,function(answer){
                            $('body').data('click',false);
                            clearoverlay();
                            if(answer.substring(0,5)=='Error'){
                                $.jGrowl(answer,{
                                    theme:'jgrowl-error',
                                    life:6000
                                });
                                return false;
                            }
                            $abstract.html(answer).show();
                            $firstcontainer.hide();
                            fetch.init();
                        });
                    });
                } else {
                    $abstract.show();
                    if($c.html()!='') $c.show();
                    $firstcontainer.hide();
                }
            } else {
                $abstract.hide();
                $c.hide();
                $firstcontainer.show();
            }
            return false;
        });
        $("#addarticle-right div.flag").click(function() {
            var $this=$(this),uid=$this.closest('div.items').data('pmcid'),count=parseInt($('#pmc-flagged-count').text());
            $.get('flagged.php?database=pmc&uid='+uid,function(answer){
                if(answer=='added'){
                    $this.removeClass('ui-priority-secondary').addClass('ui-state-error-text');
                    $('#pmc-flagged-count').text(count+1);
                } else
                if(answer=='removed'){
                    $this.removeClass('ui-state-error-text').addClass('ui-priority-secondary');
                    $('#pmc-flagged-count').text(count-1);
                }
            });
        });
        $('#download-form textarea[name="pmc_tagged_query"]').keydown(function(e) {
            if(e.which==13) {
                e.preventDefault();
                $('#download-search').click();
            }
        });
    }
};

var download_nasa={
    init:function(){
        downloadcommon.init('nasa');
        $('#download-search').click(function(e) {
            e.preventDefault();
            timeId=setTimeout(dooverlay,1);
            var proxystr;
            $.getScript('wpad.php',function(){
                proxystr=FindProxyForURL('', 'www.arxiv.org');
                $('#download-form').ajaxSubmit({
                    data: {
                        'proxystr': proxystr
                    },
                    success: function(answer){
                        clearoverlay();
                        if(answer.substring(0,5)=='Error'){
                            $.jGrowl(answer,{
                                theme:'jgrowl-error',
                                life:6000
                            });
                            return false;
                        }
                        var srch=$('#download-form').find('input[name="nasa_searchname"]').val();
                        srch=encodeURIComponent(srch);
                        srch=srch.replace(/([ #;&,.+*~\':"!^$[\]()=>|\/%])/g,'\\$1');
                        $('#addarticle-right').empty().html(answer);
                        download_nasa.init();
                        $('#saved-search-nasaads-'+srch).next().next().text('0 hours ago');
                    }
                });
            });
        }).click(function(e){
            e.preventDefault();
        }).button({
            icons:{
                primary:'ui-icon-search'
            }
        });
        $('#download-save').click(function(e) {
            e.preventDefault();
            var srch=$('#download-form').find('input[name="nasa_searchname"]').val();
            if(srch=='') return false;
            var srchid=encodeURIComponent(srch);
            srchid=srchid.replace(/([ #;&,.+*~\':"!^$[\]()=>|\/%])/g,'\\$1');
            $('#download-form').ajaxSubmit({
                data: {
                    save: '1'
                },
                success: function(){
                    if($('#saved-search-nasaads-'+srchid).length==0) {
                        $('#nasaads-container').append('<div class="nasaads"><div class="ui-state-highlight del-saved-search nasaads" style="float:right;margin:1px 0"><span class="ui-icon ui-icon-trash"></span></div>'
                            +'<span class="saved-search nasaads" id=""></span><br>&nbsp;<span>Never</span></div><div style="clear:both"></div>');
                        $('span.saved-search').removeClass('clicked');
                        $('#nasaads-container > div.nasaads:last > span.saved-search').attr('id','saved-search-nasaads-'+encodeURIComponent(srch)).text(srch).addClass('clicked');
                        addarticle.init(false);
                        $.jGrowl('Search saved.');
                    } else {
                        $('#saved-search-nasaads-'+srchid).next().next().text('Never');
                        $.jGrowl('Search saved.');
                    }
                }
            });
        }).button({
            icons:{
                primary:'ui-icon-disk'
            }
        });
        $("#addarticle-right div.titles").click(function() {
            var $this=$(this),$abstract=$this.parent().siblings("div.abstract_container"),$c=$(this).parent().siblings('div.save_container'),
            $firstcontainer=$this.parent().siblings('table.firstcontainer');
            if($firstcontainer.is(':visible')) {
                $abstract.show();
                if($c.html()!='') $c.show();
                $firstcontainer.hide();
                fetch.init();
            } else {
                $abstract.hide();
                $c.hide();
                $firstcontainer.show();
            }
            return false;
        });
        $("#addarticle-right div.flag").click(function() {
            var $this=$(this),uid=$this.closest('div.items').data('uid'),count=parseInt($('#nasaads-flagged-count').text());
            $.get('flagged.php?database=nasaads&uid='+uid,function(answer){
                if(answer=='added'){
                    $this.removeClass('ui-priority-secondary').addClass('ui-state-error-text');
                    $('#nasaads-flagged-count').text(count+1);
                } else
                if(answer=='removed'){
                    $this.removeClass('ui-state-error-text').addClass('ui-priority-secondary');
                    $('#nasaads-flagged-count').text(count-1);
                }
            });
        });
        $('#download-form textarea').tipsy({
            fallback:'One search term per line.'
        });
    }
};

var download_arxiv={
    init:function(){
        downloadcommon.init('arxiv');
        $('#download-search').click(function(e) {
            e.preventDefault();
            timeId=setTimeout(dooverlay,1);
            var proxystr;
            $.getScript('wpad.php',function(){
                proxystr=FindProxyForURL('', 'www.arxiv.org');
                $('#download-form').ajaxSubmit({
                    data: {
                        'proxystr': proxystr
                    },
                    success: function(answer){
                        clearoverlay();
                        if(answer.substring(0,5)=='Error'){
                            $.jGrowl(answer,{
                                theme:'jgrowl-error',
                                life:6000
                            });
                            return false;
                        }
                        var srch=$('#download-form').find('input[name="arxiv_searchname"]').val();
                        srch=encodeURIComponent(srch);
                        srch=srch.replace(/([ #;&,.+*~\':"!^$[\]()=>|\/%])/g,'\\$1');
                        $('#addarticle-right').empty().html(answer);
                        download_arxiv.init();
                        $('#saved-search-arxiv-'+srch).next().next().text('0 hours ago');
                    }
                });
            });
        }).click(function(e){
            e.preventDefault();
        }).button({
            icons:{
                primary:'ui-icon-search'
            }
        });
        $('#download-save').click(function(e) {
            e.preventDefault();
            var srch=$('#download-form').find('input[name="arxiv_searchname"]').val();
            if(srch=='') return false;
            var srchid=encodeURIComponent(srch);
            srchid=srchid.replace(/([ #;&,.+*~\':"!^$[\]()=>|\/%])/g,'\\$1');
            $('#download-form').ajaxSubmit({
                data: {
                    save: '1'
                },
                success: function(){
                    if($('#saved-search-arxiv-'+srchid).length==0) {
                        $('#arxiv-container').append('<div class="arxiv"><div class="ui-state-highlight del-saved-search arxiv" style="float:right;margin:1px 0"><span class="ui-icon ui-icon-trash"></span></div>'
                            +'<span class="saved-search arxiv" id=""></span><br>&nbsp;<span>Never</span></div><div style="clear:both"></div>');
                        $('span.saved-search').removeClass('clicked');
                        $('#arxiv-container > div.arxiv:last > span.saved-search').attr('id','saved-search-arxiv-'+encodeURIComponent(srch)).text(srch).addClass('clicked');
                        addarticle.init(false);
                        $.jGrowl('Search saved.');
                    } else {
                        $('#saved-search-arxiv-'+srchid).next().next().text('Never');
                        $.jGrowl('Search saved.');
                    }
                }
            });
        }).button({
            icons:{
                primary:'ui-icon-disk'
            }
        });
        $("#addarticle-right div.titles").click(function() {
            var $this=$(this),$abstract=$this.parent().siblings("div.abstract_container"),$c=$(this).parent().siblings('div.save_container'),
            $firstcontainer=$this.parent().siblings('table.firstcontainer');
            if($firstcontainer.is(':visible')) {
                $abstract.show();
                if($c.html()!='') $c.show();
                $firstcontainer.hide();
                fetch.init();
            } else {
                $abstract.hide();
                $c.hide();
                $firstcontainer.show();
            }
            return false;
        });
        $("#addarticle-right div.flag").click(function() {
            var $this=$(this),uid=$this.closest('div.items').data('uid'),count=parseInt($('#arxiv-flagged-count').text());
            $.get('flagged.php?database=arxiv&uid='+uid,function(answer){
                if(answer=='added'){
                    $this.removeClass('ui-priority-secondary').addClass('ui-state-error-text');
                    $('#arxiv-flagged-count').text(count+1);
                } else
                if(answer=='removed'){
                    $this.removeClass('ui-state-error-text').addClass('ui-priority-secondary');
                    $('#arxiv-flagged-count').text(count-1);
                }
            });
        });
    }
};

var download_jstor={
    init:function(){
        downloadcommon.init('jstor');
        $('#download-search').click(function(e) {
            e.preventDefault();
            timeId=setTimeout(dooverlay,1);
            var proxystr;
            $.getScript('wpad.php',function(){
                proxystr=FindProxyForURL('', 'www.arxiv.org');
                $('#download-form').ajaxSubmit({
                    data: {
                        'proxystr': proxystr
                    },
                    success: function(answer){
                        clearoverlay();
                        if(answer.substring(0,5)=='Error'){
                            $.jGrowl(answer,{
                                theme:'jgrowl-error',
                                life:6000
                            });
                            return false;
                        }
                        var srch=$('#download-form').find('input[name="jstor_searchname"]').val();
                        srch=encodeURIComponent(srch);
                        srch=srch.replace(/([ #;&,.+*~\':"!^$[\]()=>|\/%])/g,'\\$1');
                        $('#addarticle-right').empty().html(answer);
                        download_jstor.init();
                        $('#saved-search-jstor-'+srch).next().next().text('0 hours ago');
                    }
                });
            });
        }).click(function(e){
            e.preventDefault();
        }).button({
            icons:{
                primary:'ui-icon-search'
            }
        });
        $('#download-save').click(function(e) {
            e.preventDefault();
            var srch=$('#download-form').find('input[name="jstor_searchname"]').val();
            if(srch=='') return false;
            var srchid=encodeURIComponent(srch);
            srchid=srchid.replace(/([ #;&,.+*~\':"!^$[\]()=>|\/%])/g,'\\$1');
            $('#download-form').ajaxSubmit({
                data: {
                    save: '1'
                },
                success: function(){
                    if($('#saved-search-jstor-'+srchid).length==0) {
                        $('#jstor-container').append('<div class="jstor"><div class="ui-state-highlight del-saved-search jstor" style="float:right;margin:1px 0"><span class="ui-icon ui-icon-trash"></span></div>'
                            +'<span class="saved-search jstor" id=""></span><br>&nbsp;<span>Never</span></div><div style="clear:both"></div>');
                        $('span.saved-search').removeClass('clicked');
                        $('#jstor-container > div.jstor:last > span.saved-search').attr('id','saved-search-jstor-'+encodeURIComponent(srch)).text(srch).addClass('clicked');
                        addarticle.init(false);
                        $.jGrowl('Search saved.');
                    } else {
                        $('#saved-search-jstor-'+srchid).next().next().text('Never');
                        $.jGrowl('Search saved.');
                    }
                }
            });
        }).button({
            icons:{
                primary:'ui-icon-disk'
            }
        });
        $("div.titles").click(function() {
            var $this=$(this),$abstract=$this.siblings("div.abstract_container"),$c=$(this).siblings('div.save_container'),
            $firstcontainer=$this.siblings('table.firstcontainer');
            if($firstcontainer.is(':visible')) {
                $abstract.show();
                if($c.html()!='') $c.show();
                $firstcontainer.hide();
                fetch.init();
            } else {
                $abstract.hide();
                $c.hide();
                $firstcontainer.show();
            }
            return false;
        });
    }
};

var download_highwire={
    init:function(){
        downloadcommon.init('highwire');
        $('#download-search').click(function(e) {
            e.preventDefault();
            timeId=setTimeout(dooverlay,1);
            var proxystr;
            $.getScript('wpad.php',function(){
                proxystr=FindProxyForURL('', 'www.crossref.org');
                $('#download-form').ajaxSubmit({
                    data: {
                        'proxystr': proxystr
                    },
                    success: function(answer){
                        clearoverlay();
                        if(answer.substring(0,5)=='Error'){
                            $.jGrowl(answer,{
                                theme:'jgrowl-error',
                                life:6000
                            });
                            return false;
                        }
                        var srch=$('#download-form').find('input[name="highwire_searchname"]').val();
                        srch=encodeURIComponent(srch);
                        srch=srch.replace(/([ #;&,.+*~\':"!^$[\]()=>|\/%])/g,'\\$1');
                        $('#addarticle-right').empty().html(answer);
                        download_highwire.init();
                        $('#saved-search-highwire-'+srch).next().next().text('0 hours ago');
                    }
                });
            });
        }).click(function(e){
            e.preventDefault();
        }).button({
            icons:{
                primary:'ui-icon-search'
            }
        });
        $('#download-save').click(function(e) {
            e.preventDefault();
            var srch=$('#download-form').find('input[name="highwire_searchname"]').val();
            if(srch=='') return false;
            var srchid=encodeURIComponent(srch);
            srchid=srchid.replace(/([ #;&,.+*~\':"!^$[\]()=>|\/%])/g,'\\$1');
            $('#download-form').ajaxSubmit({
                data: {
                    save: '1'
                },
                success: function(){
                    if($('#saved-search-highwire-'+srchid).length==0) {
                        $('#highwire-container').append('<div class="highwire"><div class="ui-state-highlight del-saved-search highwire" style="float:right;margin:1px 0"><span class="ui-icon ui-icon-trash"></span></div>'
                            +'<span class="saved-search highwire" id=""></span></div><div style="clear:both"></div>');
                        $('span.saved-search').removeClass('clicked');
                        $('#highwire-container > div.highwire:last > span.saved-search').attr('id','saved-search-highwire-'+encodeURIComponent(srch)).text(srch).addClass('clicked');
                        addarticle.init(false);
                        $.jGrowl('Search saved.');
                    } else {
                        $.jGrowl('Search saved.');
                    }
                }
            });
        }).button({
            icons:{
                primary:'ui-icon-disk'
            }
        });
        $("#addarticle-right div.titles").click(function() {
            var $this=$(this),$abstract=$this.parent().siblings("div.abstract_container"),$c=$(this).parent().siblings('div.save_container'),
            $firstcontainer=$this.parent().siblings('table.firstcontainer');
            if($firstcontainer.is(':visible')) {
                $abstract.show();
                if($c.html()!='') $c.show();
                $firstcontainer.hide();
                fetch.init();
            } else {
                $abstract.hide();
                $c.hide();
                $firstcontainer.show();
            }
            return false;
        });
    }
};

var download_ieee={
    init:function(){
        downloadcommon.init('ieee');
        $('#download-search').click(function(e) {
            e.preventDefault();
            $(this).button('disable');
            timeId=setTimeout(dooverlay,1);
            var proxystr;
            $.getScript('wpad.php',function(){
                proxystr=FindProxyForURL('', 'www.ieee.org');
                $('#download-form').ajaxSubmit({
                    data: {
                        'proxystr': proxystr
                    },
                    success: function(answer){
                        clearoverlay();
                        $('#download-search').button('enable');
                        if(answer.substring(0,5)=='Error'){
                            $.jGrowl(answer,{
                                theme:'jgrowl-error',
                                life:6000
                            });
                            return false;
                        }
                        $('#addarticle-right').empty().html(answer);
                        download_ieee.init();
                    }
                });
            });
        }).button({
            icons:{
                primary:'ui-icon-search'
            }
        });
        $('#download-save').click(function(e) {
            e.preventDefault();
            var srch=$('#download-form').find('input[name="ieee_searchname"]').val();
            if(srch=='') return false;
            var srchid=encodeURIComponent(srch);
            srchid=srchid.replace(/([ #;&,.+*~\':"!^$[\]()=>|\/%])/g,'\\$1');
            $('#download-form').ajaxSubmit({
                data: {
                    save: '1'
                },
                success: function(){
                    if($('#saved-search-ieee-'+srchid).length==0) {
                        $('#ieee-container').append('<div class="ieee"><div class="ui-state-highlight del-saved-search ieee" style="float:right;margin:1px 0"><span class="ui-icon ui-icon-trash"></span></div>'
                            +'<span class="saved-search ieee" id=""></span></div><div style="clear:both"></div>');
                        $('span.saved-search').removeClass('clicked');
                        $('#ieee-container > div.ieee:last > span.saved-search').attr('id','saved-search-ieee-'+encodeURIComponent(srch)).text(srch).addClass('clicked');
                        addarticle.init(false);
                        $.jGrowl('Search saved.');
                    } else {
                        $.jGrowl('Search saved.');
                    }
                }
            });
        }).button({
            icons:{
                primary:'ui-icon-disk'
            }
        });
        $("#addarticle-right div.titles").click(function() {
            var $this=$(this),$abstract=$this.parent().siblings("div.abstract_container"),$c=$(this).parent().siblings('div.save_container'),
            $firstcontainer=$this.parent().siblings('table.firstcontainer');
            if($firstcontainer.is(':visible')) {
                $abstract.show();
                if($c.html()!='') $c.show();
                $firstcontainer.hide();
                fetch.init();
            } else {
                $abstract.hide();
                $c.hide();
                $firstcontainer.show();
            }
            return false;
        });
        $('#download-form input[name="ieee_type"]').change(function() {
            if($(this).val()=='') {
                $('.ieee-searchin').each(function(){
                    $(this).find('option:first').text('Metadata');
                });
            } else {
                $('.ieee-searchin').each(function(){
                    $(this).find('option:first').text('Full Text');
                });
            }
        });
    }
};

var download_sciencedirect={
    init:function(){
    }
};

var fetch={
    init:function(){
        common.init();
        $('.save-item').unbind().click(function(e) {
            e.preventDefault();
            var $t=$(this),$f=$t.closest('.fetch-form'),$c=$t.closest('div.items').children('div.save_container');
            $t.button('option','disabled',true);
            $f.ajaxSubmit({
                success: function(answer){
                    $t.hide();
                    $c.show().html(answer);
                    uploadwindow.init();
                }
            });
        }).button({
            icons:{
                primary:'ui-icon-disk'
            }
        });
        $('.quick-save-item').unbind().click(function(e) {
            e.preventDefault();
            var $t=$(this),$f=$t.closest('.fetch-form');
            $t.button('option','disabled',true);
            $.getScript('wpad.php',function(){
                var proxystr=FindProxyForURL('', 'www.crossref.org');
                $f.ajaxSubmit({
                    data: {
                        form_sent: 1,
                        shelf: 1,
                        'proxystr': proxystr
                    },
                    dataType:'json',
                    success: function(answer){
                        if(answer['error']!=undefined) {
                            $.each(answer['error'], function(key, err) {
                                $.jGrowl(err,{
                                    theme:'jgrowl-error'
                                });
                            });
                        }
                        if(answer['message']!=undefined) {
                            $.each(answer['message'], function(key, mess) {
                                mess=mess.replace(/&lt;/g, '<');
                                mess=mess.replace(/&gt;/g, '>');
                                $.jGrowl(mess);
                            });
                            $t.closest('div.abstract_container').siblings('div.titles').css('color','#999');
                        }
                    }
                });
            });
        }).button({
            icons:{
                primary:'ui-icon-disk'
            }
        });
    }
};