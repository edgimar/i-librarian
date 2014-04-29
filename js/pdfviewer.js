$.ajaxSetup({
    cache: false
});
var browser='';
if (/MSIE/.test(navigator.userAgent)){
    browser='msie';
}
if (browser!='msie') $('#pdf-viewer-img-div').clickNScroll({
    allowThrowing:false,
    acceleration:1
});
$('#save').button({
    icons: {
        primary: "ui-icon-suitcase"
    },
    text: false
}).click(function(){
    $('#save-container').dialog({
        autoOpen: true,
        modal: true,
        buttons: {
            'Save': function() {
                var frm=$('#save-container form').formSerialize();
                window.location.assign('downloadpdf.php?mode=download&file='+fileName+'&'+frm);
                $(this).dialog('destroy');
                return false;
            },
            'Close': function() {
                $(this).dialog('destroy');
            }
        },
        close: function(){
            $(this).dialog('destroy');
        }
    });
}).tipsy({
    gravity:'nw'
});
$('#size1').button({
    icons: {
        primary: "ui-icon-zoomin"
    }
}).tipsy({
    fade:true
});
$('#size2').button({
    icons: {
        primary: "ui-icon-arrowthickstop-1-w",
        secondary: "ui-icon-arrowthickstop-1-e"
    },
    text: false
}).tipsy({
    fade:true
});
$('#size3').button({
    icons: {
        primary: "ui-icon-document",
        secondary: "ui-icon-arrowthick-2-n-s"
    },
    text: false
}).tipsy({
    fade:true
});
$('#control-first').button({
    icons: {
        primary: "ui-icon-arrowthickstop-1-n"
    },
    text: false
}).tipsy({
    fade:true
});
$('#control-prev').button({
    icons: {
        primary: "ui-icon-arrowthick-1-n"
    },
    text: false
}).tipsy({
    fade:true
});
$('#control-next').button({
    icons: {
        primary: "ui-icon-arrowthick-1-s"
    },
    text: false
}).tipsy({
    fade:true
});
$('#control-last').button({
    icons: {
        primary: "ui-icon-arrowthickstop-1-s"
    },
    text: false
}).tipsy({
    fade:true
});
$('#pdf-viewer-copy-image').button({
    icons: {
        primary: "ui-icon-image"
    },
    text: false
}).click(function(){
    $('#image-to-copy').attr('src',$('#pdf-viewer-img').attr('src'));
    $('#image-src').val($('#pdf-viewer-img').attr('src'));
    $('#copy-image-container').dialog({
        autoOpen: true,
        modal: true,
        width: $(window).width()-40,
        height: $(window).height()-40,
        buttons: {
            'Copy': function() {
                $('#copy-image-container form').submit();
            },
            'Close': function() {
                $.Jcrop('#image-to-copy').destroy();
                $('.jcrop-holder').remove();
                $(this).dialog('destroy');
            }
        },
        open: function(){
            $('#image-to-copy').Jcrop({
                onSelect: function(c){
                    $('#x').val(c.x);
                    $('#y').val(c.y);
                    $('#w').val(c.w);
                    $('#h').val(c.h);
                }
            });
        },
        close: function(){
            $.Jcrop('#image-to-copy').destroy();
            $('.jcrop-holder').remove();
            $(this).dialog('destroy');
        }
    });
}).tipsy({
    fade:true
});
//INITIAL WINDOW SIZE
var wh=$(window).height(),toolbar=75,navw=160,ww=$('body').width();
if($('#pdf-viewer-controls').is(':hidden')) toolbar=0;
if(!navpanes) navw=0;
$('#pdf-viewer-div').height(wh-toolbar).width(ww);
$('#pdf-viewer-img-div').css('width','auto');
//WINDOW RESIZE
$(window).resize(function(){
    var wh=$(window).height(),toolbar=62,navw=160,ww=$('body').width();
    if($('#pdf-viewer-controls').is(':hidden')) toolbar=0;
    $('#pdf-viewer-div').height(wh-toolbar).width(ww);
    if(!navpanes) navw=0;
    if($('#navpane').is(':visible')) ww=ww-navw;
    $('#pdf-viewer-img-div').css('width','auto');
    var iw=$('#pdf-viewer-img').width(),riw=$('#pdf-viewer-img').data('riw'),piw=Math.round(100*iw/riw);
    $('#zoom').slider("value",piw);
    $('#zoom').next().text(piw+'%');
    var h=$('#pdf-viewer-img').height(),pos=$('#pdf-viewer-img').position();
    $('#highlight-container, #annotation-container').css({
        'width':iw,
        'height':h,
        'left':Math.max(pos.left,0)
    });
});
$('#toggle').button({
    icons: {
        primary: "ui-icon-bookmark"
    },
    text: false
}).click(function(){
    if($('#navpane').is(':visible')) {
        $('#navpane').hide();
        $('#pdf-viewer-img-div').css('width','auto');
    } else {
        $('#navpane').show();
        $('#pdf-viewer-img-div').css('width','auto');
        $('#pageprev-button').click();
    }
    var iw=$('#pdf-viewer-img').width(),riw=$('#pdf-viewer-img').data('riw'),piw=Math.round(100*iw/riw);
    $('#zoom').slider("value",piw);
    $('#zoom').next().text(piw+'%');
    var ih=$('#pdf-viewer-img').height(),pos=$('#pdf-viewer-img').position();
    $('#highlight-container, #annotation-container').css({
        'width':iw+'px',
        'height':ih+'px',
        'left':Math.max(pos.left,0)+'px'
    });
}).tipsy({
    gravity:'nw'
});
//ZOOM
$('#size1').click(function(){
    $('#pdf-viewer-img-div').scrollTop(0).scrollLeft(0);
    $('#pdf-viewer-img').css('width','auto').css('height','auto');
    $('#zoom').slider("value",100);
    $('#zoom').next().text('100%');
    var w=$('#pdf-viewer-img').width(),h=$('#pdf-viewer-img').height(),pos=$('#pdf-viewer-img').position();
    $('#highlight-container, #annotation-container').css({
        'width':w,
        'height':h,
        'left':Math.max(pos.left,0)
        });
});
$('#size2').click(function(){
    $('#pdf-viewer-img-div').scrollTop(0).scrollLeft(0);
    $('#pdf-viewer-img').css('width','99%').css('height','auto');
    var iw=$('#pdf-viewer-img').width(),riw=$('#pdf-viewer-img').data('riw'),piw=Math.round(100*iw/riw);
    $('#zoom').slider("value",piw);
    $('#zoom').next().text(piw+'%');
    var w=$('#pdf-viewer-img').width(),h=$('#pdf-viewer-img').height(),pos=$('#pdf-viewer-img').position();
    $('#highlight-container, #annotation-container').css({
        'width':w,
        'height':h,
        'left':Math.max(pos.left,0)
        });
});
$('#size3').click(function(){
    $('#pdf-viewer-img-div').css('overflow','hidden').scrollTop(0).scrollLeft(0);
    $('#pdf-viewer-img').css({
        'height':'99%',
        'width':'auto'
    });
    var iw=$('#pdf-viewer-img').width(),riw=$('#pdf-viewer-img').data('riw'),piw=Math.round(100*iw/riw);
    $('#zoom').slider("value",piw);
    $('#zoom').next().text(piw+'%');
    var ih=$('#pdf-viewer-img').height(),pos=$('#pdf-viewer-img').position();
    $('#highlight-container, #annotation-container').css({
        'width':iw+'px',
        'height':ih+'px',
        'left':Math.max(pos.left,0)+'px'
        });

    $('#pdf-viewer-img-div').css('overflow','auto');
});
$('#zoom').slider({
    min: 30,
    max: 150,
    value: 100,
    slide: function(e,ui) {
        var riw=$('#pdf-viewer-img').data('riw'),iw=Math.round(ui.value*riw/100);
        $('#pdf-viewer-img').css('width',iw).css('height','auto');
        $(this).next().text(ui.value+'%');
        var ih=$('#pdf-viewer-img').height(),pos=$('#pdf-viewer-img').position();
        $('#highlight-container, #annotation-container').css({
            'width':iw+'px',
            'height':ih+'px',
            'left':Math.max(pos.left,0)+'px'
            });
    }
});
//OPEN FIRST PAGE AND THUMBS
$('body').data('lock',1);
$('#pdf-viewer-loader').fadeIn(800);
$.get('viewpdf.php?renderpdf=1&file='+fileName+'&page='+pg, function(riw) {
    if(isNaN(riw)) {
        $.jGrowl(riw,{
            theme:'jgrowl-error'
        });
        $('body').data('lock',0);
        $('#pdf-viewer-loader').hide();
        return false;
    }
    $('#pdf-viewer-loader').hide();
    $('#pdf-viewer-img').attr('src','library/pngs/'+fileName+'.'+pg+'.png').show().data('pg',pg);
    $('#control-page').val(pg);
    $('body').data('lock',0);
    var iw=0.98*$('#pdf-viewer-img-div').width(),piw=Math.round(100*iw/riw);
    $('#pdf-viewer-img').data('riw',riw);
    console.log(iw+'/'+riw);
    $('#zoom').slider( "value" , piw );
    $('#zoom').next().text(piw+'%');
    if(navpanes) {
        $('#toggle').click();
    }
});
//LEFT NAVPANE
$('#pageprev-button').button({
    icons: {
        primary: "ui-icon-document"
    },
    text: false
}).click(function(){
    if($('#navpane').is(':hidden')) $('#toggle').click();
    $('#thumbs').show();
    $('#bookmarks, #annotations-left, #search-results').hide();
    $('#pdf-viewer-img-div').css('width','auto');
    $('#print-notes').button('disable');
    if($('#thumbs').find('div').length==0) {
        $.get('viewpdf.php?renderthumbs=1&file='+fileName, function(answer) {
            var thumbs='',i=1;
            while (i<=totalPages) {
                thumbs = thumbs+'<div style="padding:1px 0 5px 0">Page '+i+':<br><img class="pdf-viewer-thumbs thumb-'+i+'" src="library/pngs/'+fileName+'.t'+i+'.png" alt=""></div>';
                i++;
            }
            $('#thumbs').html(thumbs);
            var pg=$('#pdf-viewer-img').data('pg'),$thumb=$('#thumbs > div:eq('+(pg-1)+')');
            $thumb.css('background-color','#999fdd');
        });
    }
}).tipsy({
    gravity:'nw'
});
$('#bookmarks-button').button({
    icons: {
        primary: "ui-icon-tag"
    },
    text: false
}).click(function(){
    if($('#navpane').is(':hidden')) $('#toggle').click();
    $('#bookmarks').show();
    $('#thumbs, #annotations-left, #search-results').hide();
    $('#pdf-viewer-img-div').css('width','auto');
    $('#print-notes').button('disable');
    if($('#bookmarks').html()=='') {
        $('#bookmarks').html('<div style="padding:12px">Reading bookmarks.</div>');
        $.getJSON('viewpdf.php?renderbookmarks=1&file='+fileName, function(bookmarks) {
            if(bookmarks.length===0) {
                $('#bookmarks').html('<div style="padding:12px">No bookmarks.</div>');
                return false;
            }
            $('#bookmarks').empty();
            $.each(bookmarks, function(key, rows) {
                $('#bookmarks').append('<p class="bookmark" id="bookmark-'+key+'" data-page="'+rows.page+'">'+rows.title+'</p>');
                $('#bookmark-'+key).css('padding-left',6*rows.level+'px');
            });
            $('#bookmarks .bookmark').click(function(){
                $('.bookmark').css('background-color','');
                $(this).css('background-color','#aaafe6');
                if($(this).data('page')!=$('#pdf-viewer-img').data('pg')) fetch_page(fileName,$(this).data('page'));
            });
        });
    }
}).tipsy({
    fade:true
});
$('#notes-button').button({
    icons: {
        primary: "ui-icon-note"
    },
    text: false
}).click(function(){
    if($('#navpane').is(':hidden')) $('#toggle').click();
    $('#annotations-left').show();
    $('#bookmarks, #thumbs, #search-results').hide();
    $('#pdf-viewer-img-div').css('width','auto');
    $('#print-notes').button('enable');
    if($('#pdf-viewer-annotations').prop('checked')==false) $('#pdf-viewer-annotations').prop('checked',true).change();
    var usr='';
    if($('#pdf-viewer-others-annotations').prop('checked')==true) usr='&user=all';
    $.getJSON('annotate.php?fetch=1&type=annotation&filename='+fileName+'&page=all'+usr, function(answer) {
        $('#annotations-left p').remove();
        if(answer.length===0) $('#annotations-left').append('<p style="padding:0 6px">No notes.</p>');
        $.each(answer, function(key, rows) {
            var annot=rows.annotation,noteid='note-'+10*rows.top+'-'+10*rows.left;
            $('#annotations-left').append('<p class="annotation" id="annot-'+key+'" data-page="'+rows.page+'"><b>Page '+rows.page+', note '+rows.id+':</b> <span style="white-space:pre-wrap">'+annot+'</span></p>');
            $('#annot-'+key).data('noteid',noteid);
        });
        $('#annotations-left .annotation').click(function(){
            $('.annotation').css('background-color','');
            $(this).css('background-color','#aaafe6');
            if($(this).data('page')!=$('#pdf-viewer-img').data('pg')) fetch_page(fileName,$(this).data('page'));
        });
    });
    searchnotes.init();
}).tipsy({
    fade:true
});
$('#print-notes').button({
    icons: {
        primary: "ui-icon-print"
    },
    text: false,
    disabled: true
}).click(function(){
    if($('#annotations-left').html()!='') {
        $('.annotation').css('background-color','');
        w=window.open('','','width=800,height=400');
        w.document.write('<style type="text/css">@media print {#filter_notes {display:none}} @page {margin:0}</style>');
        w.document.write($('#annotations-left').html());
        w.print();
        //FOR IE
        w.document.close();
        //FOR OTHER BROWSERS
        w.close();
    }
}).tipsy({
    fade:true
});
$('#search-results-button').button({
    icons: {
        primary: "ui-icon-search"
    },
    text: false,
    disabled: true
}).click(function(){
    if($('#search-results .search-result').length>0) {
        $('#search-results').show();
        if($('#navpane').is(':hidden')) $('#toggle').click();
        $('#annotations-left, #bookmarks, #thumbs').hide();
        $('#print-notes').button('disable');
        $('#pdf-viewer-img-div').css('width','auto');
    }
}).tipsy({
    fade:true
});
//PAGE NAVIGATION
function fetch_page(file,pg,f) {
    $('body').data('lock',1);
    $('#pdf-viewer-img').data('pg',pg);
    var loader=function(){
        $('#pdf-viewer-loader').fadeIn(400)
        };
    var timeid=setTimeout(loader,500);
    $.get('viewpdf.php?renderpdf=1&file='+file+'&page='+pg, function(answer) {
        clearTimeout(timeid);
        $('#pdf-viewer-loader').stop(true,true).hide();
        $('#pdf-viewer-img').attr('src','library/pngs/'+file+'.'+pg+'.png');
        $('#pdf-viewer-img-div').scrollTop(0).scrollLeft(0);
        $('#control-page').val(pg);
        $('.pdfviewer-highlight').hide();
        $('.highlight-page-'+pg).fadeTo(0,0.25);
        $('body').data('lock',0);
        $('#thumbs > div').css('background-color','');
        if($('#thumbs > div').length>0) {
            var $thumb=$('#thumbs > div:eq('+(pg-1)+')'),
            thtop=$thumb.offset().top,
            thbottom=thtop+$thumb.height(),
            partop=$('#navpane').offset().top,
            parbottom=partop+$('#navpane').height();
            $thumb.css('background-color','#999fdd');
            if($('#thumbs').is(':visible') && (thtop-partop<0 || parbottom-thbottom<0)) {
                $('#navpane').animate({
                    scrollTop: $('#navpane').scrollTop()+thtop-((parbottom-partop)/2)
                },200);
            }
        }
        if ($('#pdf-viewer-annotations').prop('checked')==true) {
            var firstpressed=0,otherspressed=false,
            markerchecked=$('#pdf-viewer-marker').prop('checked'),
            notechecked=$('#pdf-viewer-note').prop('checked'),
            erasechecked=$('#pdf-viewer-marker-erase').prop('checked'),
            othersannotations=$('#pdf-viewer-others-annotations').prop('checked');
            $('#annotation-container').empty().unbind();
            if (markerchecked==true) firstpressed=1;
            if (notechecked==true) firstpressed=2;
            if (erasechecked==true) firstpressed=3;
            if (othersannotations==true) otherspressed=true;
            $('#pdf-viewer-annotations').trigger('change',[firstpressed, otherspressed]);
        }
        if(typeof f=='function') f();
    });
    $.get('bookmark.php?file='+file+'&page='+pg);
}
$('#control-first').click(function(){
    if($('body').data('lock')==1) return false;
    if($('#pdf-viewer-img').data('pg')==1) return false;
    fetch_page(fileName,1);
});
$('#control-prev').click(function(){
    if($('body').data('lock')==1) return false;
    var pg=$('#pdf-viewer-img').data('pg');
    if(pg==1) return false;
    pg=pg-1;
    pg=Math.max(pg,1);
    fetch_page(fileName,pg);
});
$('#control-next').click(function(){
    if($('body').data('lock')==1) return false;
    var pg=$('#pdf-viewer-img').data('pg');
    if(pg==totalPages) return false;
    pg=pg+1;
    pg=Math.min(pg,totalPages);
    fetch_page(fileName,pg);
});
$('#control-last').click(function(){
    if($('body').data('lock')==1) return false;
    if($('#pdf-viewer-img').data('pg')==totalPages) return false;
    fetch_page(fileName,totalPages);
});
$('#control-page').keydown(function(e){
    if($('body').data('lock')==1) return false;
    if(e.which!=13) return true;
    var pg=parseInt($(this).val());
    if(isNaN(pg) || pg<1 || pg > totalPages) {
        $(this).val('1');
        pg=1;
    }
    fetch_page(fileName,pg);
    return false;
}).focus(function(){
    this.select();
});
$('#thumbs').click(function(e){
    var $t=$(e.target),pg=$('#thumbs img').index($t)+1,currpg=$('#pdf-viewer-img').data('pg');
    if(!$t.is('img') || pg==currpg) return false;
    if($('body').data('lock')==1) return false;
    fetch_page(fileName,pg);
});
//SEARCH
$('#pdf-viewer-search').keydown(function(e){
    if(e.which!=13) return true;
    e.preventDefault();
    var st=$('#pdf-viewer-search').val();
    $('.pdfviewer-highlight').remove();
    if(st==''){
        $('#pdf-viewer-clear').click();
        return false;
    }
    $('body').data('lock',1);
    $.getJSON('searchpdf.php', {
        'search_term': st , 
        'file': fileName
    },function(answer){
        if(jQuery.isEmptyObject(answer)) {
            $.jGrowl('No Hits.');
            $('body').data('lock',0);
            return false;
        }
        if(answer['Error']!=undefined) {
            $.jGrowl(answer['Error'],{
                theme:'jgrowl-error'
            });
            $('body').data('lock',0);
            return false;
        }
        $('#search-results .search-result, #search-results .search-result-page').remove();
        $('#navpane').show();
        $('#pdf-viewer-img-div').css('width','auto');
        var i=0,positions=new Array(),pgs=[],w=$('#pdf-viewer-img').width(),h=$('#pdf-viewer-img').height(),pos=$('#pdf-viewer-img').position();
        $('#highlight-container, #annotation-container').css('left',Math.max(pos.left,0)).width(w).height(h);
        $.each(answer, function(key, rows) {
            pgs[i] = key;
            i=i+1;
            positions[key]=new Array();
            $('#search-results').append('<p class="search-result-page" style="font-weight:bold;padding:0 6px">Page '+key+':</p>');
            $.each(rows, function(key2, row) {
                $('#highlight-container').append('<div class="ui-corner-all pdfviewer-highlight highlight-page-'+key+'" id="highlight-page-'+key+'-row-'+key2+'">&nbsp;</div>');
                $('#highlight-page-'+key+'-row-'+key2).css({
                    'width': row.width +'%',
                    'height': row.height +'%',
                    'top': row.top +'%',
                    'left': row.left +'%'
                    });
                positions[key][key2]=row.top;
                $('#search-results').append('<p class="search-result" data-linksto="highlight-page-'+key+'-row-'+key2+'">'+row.text+'</p>');
            });
        });
        $('#search-results-button').button('enable').click();
        var openpg=Math.min.apply(Math,pgs);
        fetch_page(fileName,openpg,function(){
            searchresults.init();
            $("#search-results .search-result").eq(0).click();
        });
    });
}).focus(function(){
    this.select();
}).tipsy({
    fade:true
});
$('#pdf-viewer-clear').button({
    icons: {
        primary: "ui-icon-arrowreturnthick-1-w"
    },
    text: false
}).click(function(){
    $('#pdf-viewer-search').val('');
    $('.pdf-viewer-thumbs').removeClass('ui-state-disabled');
    $('.pdfviewer-highlight').remove();
    $('#highlight-container').css({
        'width':0,
        'height':0,
        'left':0
    });
    $('#search-results-button').button('disable');
    $('#search-results .search-result, #search-results .search-result-page').remove();
    $('#search-results').hide();
    $('#toggle').click();
}).tipsy({
    fade:true
});
$('#pdf-viewer-search-prev').button({
    icons: {
        primary: "ui-icon-search",
        secondary: "ui-icon-triangle-1-n"
    },
    text: false
}).click(function(){
    $('.search-result.shown').prevAll('.search-result').eq(0).click();
}).tipsy({
    fade:true
});
$('#pdf-viewer-search-next').button({
    icons: {
        primary: "ui-icon-search",
        secondary: "ui-icon-triangle-1-s"
    },
    text: false
}).click(function(){
    $('.search-result.shown').nextAll('.search-result').eq(0).click();
}).tipsy({
    fade:true
});
//ANNOTATIONS
$('#pdf-viewer-annotations').button({
    icons: {
        primary: "ui-icon-comment"
    },
    text: false
}).change(function(e,firstpressed,otherspressed){
    if($(this).is(':checked')) {
        var iw=h=$('#pdf-viewer-img').width(),h=$('#pdf-viewer-img').height(),pos=$('#pdf-viewer-img').position();
        $('#annotation-container').show().css({
            'width':iw,
            'height':h,
            'left':Math.max(pos.left,0)
            });
        $('#pdf-viewer-marker,#pdf-viewer-note,#pdf-viewer-marker-erase,#pdf-viewer-others-annotations').button('enable');
        $.getJSON('annotate.php?fetch=1&type=yellowmarker&filename='+fileName+'&page='+$('#pdf-viewer-img').data('pg'), function(answer) {
            $.each(answer, function(key, rows) {
                var markid='marker-'+10*rows.top+'-'+10*rows.left;
                $('#annotation-container').append('<div class="marker marker-yellow" id="'+markid+'" data-dbid="'+rows.id+'"></div>');
                $('#'+markid).css('top', rows.top+'%').css('left', rows.left+'%').css('width', rows.width+'%');
            });
        });
        $.getJSON('annotate.php?fetch=1&type=annotation&filename='+fileName+'&page='+$('#pdf-viewer-img').data('pg'), function(answer) {
            $.each(answer, function(key, rows) {
                var markid='note-'+10*rows.top+'-'+10*rows.left;
                $('#annotation-container').append('<div class="marker marker-note" id="'+markid+'" data-dbid="'+rows.id+'">'+rows.id+'</div>');
                $('#'+markid).css('top', rows.top+'%').css('left', rows.left+'%').data('annotation',rows.annotation);
            });
            var timeid='';
            $('.marker-note').hover(function(e) {
                function hoverNote(left,$t){
                    var annot='<span style="white-space:pre-wrap">'+$t.data('annotation')+'</span>',jgpos='top-right';
                    if(($(window).width()-left)<300) jgpos='top-left';
                    $("div.jGrowl-close").click();
                    $.jGrowl(annot,{
                        header: 'Note:', 
                        sticky: true, 
                        speed: 0, 
                        position: jgpos
                    });
                }
                var left=e.pageX,$t=$(this);
                timeid=setTimeout(function(){hoverNote(left,$t)},300);
            },function () {
                clearTimeout(timeid);
                timeid=setTimeout(function(){$("div.jGrowl-close").click()},300);
            });
            if (firstpressed==1) $('#pdf-viewer-marker').change();
            if (firstpressed==2) $('#pdf-viewer-note').change();
            if (firstpressed==3) $('#pdf-viewer-marker-erase').change();
            if (otherspressed==true) $('#pdf-viewer-others-annotations').change();
        });
    } else {
        $('#annotation-container').empty().hide();
        if ($('#pdf-viewer-marker').is(':checked')) $('#pdf-viewer-marker').prop('checked',false).change().button('refresh');
        if ($('#pdf-viewer-note').is(':checked')) $('#pdf-viewer-note').prop('checked',false).change().button('refresh');
        if ($('#pdf-viewer-marker-erase').is(':checked')) $('#pdf-viewer-marker-erase').prop('checked',false).change().button('refresh');
        if ($('#pdf-viewer-others-annotations').is(':checked')) $('#pdf-viewer-others-annotations').prop('checked',false).change().button('refresh');
        $('#pdf-viewer-marker,#pdf-viewer-note,#pdf-viewer-marker-erase,#pdf-viewer-others-annotations').button('disable');
    }
}).next().tipsy({
    fade:true
});
//YELLOW MARKER
$('#pdf-viewer-marker').button({
    icons: {
        primary: "ui-icon-pencil"
    },
    text: false,
    disabled: true
}).change(function(){
    if($(this).is(':checked')) {
        if ($('#pdf-viewer-note').is(':checked')) $('#pdf-viewer-note').prop('checked',false).change().button('refresh');
        if ($('#pdf-viewer-marker-erase').is(':checked')) $('#pdf-viewer-marker-erase').prop('checked',false).change().button('refresh');
        if (browser!='msie') $('#pdf-viewer-img-div').unbind().css('cursor','text');
        $('#pdf-viewer-img-div').css('cursor','text');
        $('#annotation-container').mouseenter(function(){
            $("#cursor > span").addClass('ui-icon-pencil').parent().show();
        }).mouseleave(function(){
            $("#cursor > span").removeClass('ui-icon-pencil').parent().hide();
        }).mousedown(function(e){
            var markstposX=e.pageX,
            markstposY=e.pageY,
            prntpos=$(this).offset(),
            posx=Math.round(1000*(e.pageX-prntpos.left)/$(this).width())/10,
            posy=Math.round(1000*(e.pageY-prntpos.top)/$(this).height()-5)/10,
            markid='marker-'+10*posy+'-'+10*posx;
            if($('#'+markid).length==1) return false;
            $(this).data('marker', {
                'markid': markid, 
                'markstposX': markstposX, 
                'markstposY': markstposY
            });
            $('<div class="marker marker-yellow" id="'+markid+'" data-dbid=""></div>').appendTo(this);
            $('#'+markid).css('top', posy+'%').css('left', posx+'%');
        }).mousemove(function(e){
            posx=16+e.pageX,
            posy=16+e.pageY;
            $('#cursor').css('top', posy+'px').css('left', posx+'px');
            if(!$(this).data('marker')) return false;
            var markstposX=$(this).data('marker').markstposX,
            markw=e.pageX-markstposX,
            markid=$(this).data('marker').markid;
            $('#'+markid).width(markw);
        }).mouseup(function(e){
            if(!$(this).data('marker')) return false;
            var prntpos=$(this).offset(),
            markstposX=$(this).data('marker').markstposX,
            markstposY=$(this).data('marker').markstposY,
            posx=Math.round(1000*(markstposX-prntpos.left)/$(this).width())/10,
            posy=Math.round(1000*(markstposY-prntpos.top)/$(this).height()-6)/10,
            markw=Math.round(1000*(e.pageX-markstposX)/$(this).width())/10,
            markid=$(this).data('marker').markid;
            $('#'+markid).width(markw+'%');
            $(this).data('marker', '');
            if(markw<1) {
                $('#'+markid).remove();
                return false;
            }
            $.get('annotate.php?save=1&type=yellowmarker&filename='+fileName+'&page='+$('#pdf-viewer-img').data('pg')+'&top='+posy+'&left='+posx+'&width='+markw, function(answer) {
                $('#'+markid).attr('data-dbid',answer);
                if(answer=='') {
                    $.jGrowl('Error during saving the mark!');
                    $('#'+markid).remove();
                }
            });
        });
    } else {
        if (browser!='msie') $('#pdf-viewer-img-div').clickNScroll({
            allowThrowing:false,
            acceleration:1
        });
        $('#pdf-viewer-img-div').css('cursor','pointer');
        $('#annotation-container').unbind();
    }
}).next().tipsy({
    fade:true
});
//PINNED NOTES
$('#pdf-viewer-note').button({
    icons: {
        primary: "ui-icon-pin-w"
    },
    text: false,
    disabled: true
}).change(function(){
    if($(this).is(':checked')) {
        $('.marker-note, .marker-note-others').unbind('hover');
        if ($('#pdf-viewer-marker').is(':checked')) $('#pdf-viewer-marker').prop('checked',false).change().button('refresh');
        if ($('#pdf-viewer-marker-erase').is(':checked')) $('#pdf-viewer-marker-erase').prop('checked',false).change().button('refresh');
        if (browser!='msie') $('#pdf-viewer-img-div').unbind();
        $('#pdf-viewer-img-div').css('cursor','default');
        $('#annotation-container').mouseenter(function(){
            $("#cursor > span").addClass('ui-icon-pin-w').parent().show();
        }).mouseleave(function(){
            $("#cursor > span").removeClass('ui-icon-pin-w').parent().hide();
        }).mousemove(function(e){
            posx=16+e.pageX,
            posy=16+e.pageY;
            $('#cursor').css('top', posy+'px').css('left', posx+'px');
        }).click(function(e){
            if($(e.target).hasClass('marker-note')){
                var annotation='',markid=$(e.target).attr('id'),dbid=$(e.target).data('dbid');
                if($('#jGrowl').find('#ta-'+markid).length==1) return false;
                if(dbid!='') annotation=$('#'+markid).data('annotation');
                $("div.jGrowl-close").click();
                var clstem='<span class="ui-icon ui-icon-disk ui-state-highlight"></span>';
                if (browser=='msie') clstem='Save';
                $.jGrowl('<textarea class="note-ta" id="ta-'+markid+'" wrap="hard">'+annotation+'</textarea>',
                {
                    header: 'Edit the note:', 
                    sticky: true, 
                    speed: 0, 
                    closeTemplate: clstem, 
                    closerTemplate: '<div>[ save all ]</div>',
                    close: function(el){
                        var $e=$(el),txt=$e.find('textarea').val();
                        $.get('annotate.php','edit=1&dbid='+dbid+'&annotation='+encodeURIComponent(txt),function(){
                            $('#'+markid).data('annotation',txt);
                            if($('#annotations-left').is(':visible')) $('#notes-button').click();
                        });
                    }
                });
            } else {
                var prntpos=$(this).offset(),
                posx=Math.round(1000*(e.pageX-prntpos.left)/$(this).width()-35)/10,
                posy=Math.round(1000*(e.pageY-prntpos.top)/$(this).height()-25)/10,
                markid='note-'+10*posy+'-'+10*posx;
                if($('#'+markid).length==1) return false;
                $('<div class="marker marker-note" id="'+markid+'"></div>').appendTo(this);
                $('#'+markid).css('top', posy+'%').css('left', posx+'%');
                $.get('annotate.php','save=1&type=annotation&filename='+fileName+'&page='+$('#pdf-viewer-img').data('pg')+'&top='+posy+'&left='+posx+'&annotation=',
                    function(answer) {
                        $('#'+markid).attr('data-dbid',answer).data('annotation','').text(answer);
                        if($('#annotations-left').is(':visible')) $('#notes-button').click();
                    });
                var clstem='<span class="ui-icon ui-icon-disk ui-state-highlight"></span>';
                if (browser=='msie') clstem='Save';
                $("div.jGrowl-close").click();
                $.jGrowl('<textarea class="note-ta" name="note-ta" id="ta-'+markid+'" wrap="hard"></textarea>',
                {
                    header: 'Add new note:', 
                    sticky: true, 
                    speed: 0, 
                    closeTemplate: clstem, 
                    closerTemplate: '<div>[ save all ]</div>',
                    close: function(el){
                        var $e=$(el),txt=$e.find('textarea').val(),dbid=$('#'+markid).data('dbid');
                        $.get('annotate.php','edit=1&dbid='+dbid+'&annotation='+encodeURIComponent(txt),function(){
                            $('#'+markid).data('annotation',txt);
                            if($('#annotations-left').is(':visible')) $('#notes-button').click();
                        });
                    }
                });
            }
        });
    } else {
        if (browser!='msie') $('#pdf-viewer-img-div').clickNScroll({
            allowThrowing:false,
            acceleration:1
        });
        $('#pdf-viewer-img-div').css('cursor','pointer');
        $('#annotation-container').unbind();
        $("div.jGrowl-close").click();
        $('.marker-note, .marker-note-others').hover(function (e) {
                function hoverNote(left,$t){
                    var annot='<span style="white-space:pre-wrap">'+$t.data('annotation')+'</span>',jgpos='top-right';
                    if(($(window).width()-left)<300) jgpos='top-left';
                    $("div.jGrowl-close").click();
                    $.jGrowl(annot,{
                        header: 'Note:', 
                        sticky: true, 
                        speed: 0, 
                        position: jgpos
                    });
                }
                var left=e.pageX,$t=$(this);
                timeid=setTimeout(function(){hoverNote(left,$t)},300);
            },function () {
                clearTimeout(timeid);
                timeid=setTimeout(function(){$("div.jGrowl-close").click()},300);
            });
        $('.marker-note-others').tipsy();
    }
}).next().tipsy({
    fade:true
});
//ERASE ANNOTATIONS
$('#pdf-viewer-marker-erase').button({
    icons: {
        primary: "ui-icon-trash"
    },
    text: false,
    disabled: true
}).change(function(){
    $(this).next().tipsy('hide');
    if($(this).is(':checked')) {
        if ($('#pdf-viewer-marker').is(':checked')) $('#pdf-viewer-marker').prop('checked',false).change().button('refresh');
        if ($('#pdf-viewer-note').is(':checked')) $('#pdf-viewer-note').prop('checked',false).change().button('refresh');
        if (browser!='msie') $('#pdf-viewer-img-div').unbind().css('cursor','default');
        $('#pdf-viewer-img-div').css('cursor','default');
        $('#pdf-viewer-delete-menu').show();
        $('#pdf-viewer-delete-menu > div').eq(0).click(function(){
            $('#pdf-viewer-delete-menu').hide();
            $('#annotation-container').mouseenter(function(){
            $("#cursor > span").addClass('ui-icon-trash').parent().show();
            }).mouseleave(function(){
                $("#cursor > span").removeClass('ui-icon-trash').parent().hide();
            }).mousemove(function(e){
                posx=16+e.pageX,
                posy=16+e.pageY;
                $('#cursor').css('top', posy+'px').css('left', posx+'px');
            }).click(function(e){
                var $this='',type='';
                if ($(e.target).hasClass('marker') && !$(e.target).hasClass('marker-yellow-others') && !$(e.target).hasClass('marker-note-others')) {
                    $this=$(e.target);
                } else {
                    return false;
                }
                if($this.hasClass('marker-yellow')) type='yellowmarker';
                if($this.hasClass('marker-note')) type='annotation';
                $.get('annotate.php?delete=1&dbid='+$this.data('dbid')+'&type='+type, function(answer) {
                    if(answer=='') {
                        $.jGrowl('Error during deleting the mark!');
                    } else {
                        $("div.jGrowl-close").click();
                        $this.remove();
                        if($('#annotations-left').is(':visible')) $('#notes-button').click();
                    }
                });
            });
        });
        $('#pdf-viewer-delete-menu > div').eq(1).click(function(){
            $('#confirm-container').html('<p><span class="ui-state-error-text"><span class="ui-icon ui-icon-alert" style="float:left;margin-right:10px"></span></span>Delete all markers?</p>')
            .dialog('option','buttons',{
                'Delete': function(){
                    $.get('annotate.php?delete=all&type=yellowmarker&filename='+fileName, function(answer) {
                        if(answer=='OK') {
                            $("div.jGrowl-close").click();
                            $('.marker-yellow').remove();
                            $('#pdf-viewer-marker-erase').prop('checked',false).change().button('refresh');
                        } else {
                            $.jGrowl('Error during deleting marks! '+answer);
                        }
                    });
                    $(this).dialog('close');
                    $('#pdf-viewer-delete-menu').hide();
                },
                'Close': function() {
                    $(this).dialog('close');
                }
            }).dialog('open');
        });
        $('#pdf-viewer-delete-menu > div').eq(2).click(function(){
            $('#confirm-container').html('<p><span class="ui-state-error-text"><span class="ui-icon ui-icon-alert" style="float:left;margin-right:10px"></span></span>Delete all notes?</p>')
            .dialog('option','buttons',{
                'Delete': function(){
                    $.get('annotate.php?delete=all&type=annotation&filename='+fileName, function(answer) {
                        if(answer=='OK') {
                            $("div.jGrowl-close").click();
                            $('.marker-note').remove();
                            if($('#annotations-left').is(':visible')) $('#notes-button').click();
                            $('#pdf-viewer-marker-erase').prop('checked',false).change().button('refresh');
                        } else {
                            $.jGrowl('Error during deleting notes! '+answer);
                        }
                    });
                    $(this).dialog('close');
                    $('#pdf-viewer-delete-menu').hide();
                },
                'Close': function() {
                    $(this).dialog('close');
                }
            }).dialog('open');
        });
        $('#pdf-viewer-delete-menu > div').eq(3).click(function(){
            $('#confirm-container').html('<p><span class="ui-state-error-text"><span class="ui-icon ui-icon-alert" style="float:left;margin-right:10px"></span></span>Delete all annotations?</p>')
            .dialog('option','buttons',{
                'Delete': function(){
                    $.get('annotate.php?delete=all&type=all&filename='+fileName, function(answer) {
                        if(answer=='OK') {
                            $("div.jGrowl-close").click();
                            $('.marker').remove();
                            if($('#annotations-left').is(':visible')) $('#notes-button').click();
                            $('#pdf-viewer-marker-erase').prop('checked',false).change().button('refresh');
                        } else {
                            $.jGrowl('Error during deleting annotations! '+answer);
                        }
                    });
                    $(this).dialog('close');
                    $('#pdf-viewer-delete-menu').hide();
                },
                'Close': function() {
                    $(this).dialog('close');
                }
            }).dialog('open');
        });
    } else {
        $('#pdf-viewer-delete-menu').hide();
        if (browser!='msie') $('#pdf-viewer-img-div').clickNScroll({
            allowThrowing:false,
            acceleration:1
        });
        $('#pdf-viewer-img-div').css('cursor','pointer');
        $('#annotation-container').unbind();
        $('#pdf-viewer-delete-menu > div').unbind();
    }
}).next().tipsy({
    fade:true
});
$('#confirm-container').dialog({
    autoOpen: false,
    modal: true
});
//OTHERS' ANNOTATIONS
$('#pdf-viewer-others-annotations').button({
    icons: {
        primary: "ui-icon-person"
    },
    text: false,
    disabled: true
}).change(function(){
    if($(this).is(':checked')) {
        $.getJSON('annotate.php?fetchothers=1&type=yellowmarker&filename='+fileName+'&page='+$('#pdf-viewer-img').data('pg'), function(answer) {
            $.each(answer, function(key, rows) {
                var markid='marker-'+10*rows.top+'-'+10*rows.left;
                $('#annotation-container').append('<div class="marker marker-yellow-others" id="'+markid+'" data-dbid="'+rows.id+'"></div>');
                $('#'+markid).css('top', rows.top+'%').css('left', rows.left+'%').css('width', rows.width+'%').attr('title',rows.user).tipsy();
            });
        });
        $.getJSON('annotate.php?fetchothers=1&type=annotation&filename='+fileName+'&page='+$('#pdf-viewer-img').data('pg'), function(answer) {
            $.each(answer, function(key, rows) {
                var markid='note-'+10*rows.top+'-'+10*rows.left;
                $('#annotation-container').append('<div class="marker marker-note-others" id="'+markid+'" data-dbid="'+rows.id+'">'+rows.id+'</div>');
                $('#'+markid).css('top', rows.top+'%').css('left', rows.left+'%').data('annotation',rows.annotation).attr('title',rows.user).tipsy();
            });
            if($('#pdf-viewer-note').prop('checked')==false) {
                $('.marker-note-others').hover(function (e) {
                function hoverNote(left,$t){
                    var annot='<span style="white-space:pre-wrap">'+$t.data('annotation')+'</span>',jgpos='top-right';
                    if(($(window).width()-left)<300) jgpos='top-left';
                    $("div.jGrowl-close").click();
                    $.jGrowl(annot,{
                        header: 'Note:', 
                        sticky: true, 
                        speed: 0, 
                        position: jgpos
                    });
                }
                var left=e.pageX,$t=$(this);
                timeid=setTimeout(function(){hoverNote(left,$t)},300);
            },function () {
                clearTimeout(timeid);
                timeid=setTimeout(function(){$("div.jGrowl-close").click()},300);
            });
            }
        });
    } else {
        $('#annotation-container .marker-yellow-others, #annotation-container .marker-note-others').remove();
    }
    if($('#annotations-left').is(':visible')) $('#notes-button').click();
}).next().tipsy({
    fade:true
});
//SEARCH IN NOTES
var searchnotes={
    init:function(){
        $("#filter_notes").keyup(function(){
            var str=$(this).val(), $container=$('#annotations-left > p');
            if(str!='') {
                qstr=str.replace(/([^a-zA-Z0-9])/g,'\\$1');
                var re=new RegExp(qstr, 'i');
                $container.hide().filter(function(){
                    return re.test($(this).children('span').text());
                }).show();
                var re2=new RegExp('\('+qstr+'\)', 'gi');
                $container.each(function(){
                    if($(this).is(':visible')) {
                        newstr=$(this).children('span').text().replace(re2,'<span style="background-color:#eea">$1</span>');
                        $(this).children('span').html(newstr);
                    }
                });
            } else {
                $container.show();
                $container.each(function(){
                    newstr=$(this).children('span').text();
                    $(this).children('span').text(newstr);
                });
            }
        }).focus(function(){
            $(this).val('');
            $('#annotations-left p').show();
            $('#annotations-left p').each(function(){
                newstr=$(this).children('span').text();
                $(this).children('span').text(newstr);
            });
        });
    }
};
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
        } else if($span.hasClass('ui-icon-check')) {
            $input.prop('checked',false);
            $span.removeClass('ui-icon-check').addClass('ui-icon-close');
        }
    }
});
//SEARCH RESULTS CLICK
var searchresults={
    init:function(){
        function clickResult($t,$target) {
            $('#highlight-container .pdfviewer-highlight')
                .css('box-shadow','').css('-webkit-box-shadow','');
            $target.css('box-shadow','0 0 4px 4px #000')
                .css('-webkit-box-shadow','0 0 4px 4px #000');
            $('#search-results .search-result').removeClass('shown').css('background-color','');
            $t.addClass('shown').css('background-color','#aaafe6');
            var pos=$target.position();
            $('#pdf-viewer-img-div').animate({
                scrollTop: -100+pos.top,
                scrollLeft: -100+pos.left
            }, 200);
            var off=$t.offset(),curr=$('#navpane').scrollTop(),
            bottom=$(window).height() - off.top + $t.height();
            if(off.top<100) $('#navpane').animate({
                scrollTop: curr-$(window).height()+200
            },1000);
            if(bottom<100) $('#navpane').animate({
                scrollTop: curr+$(window).height()-200
            },1000);
        }
        $("#search-results .search-result").click(function(){
            var $t=$(this),$target=$('#'+$(this).data('linksto')),
            targetarr=$(this).data('linksto').split('-'),
            targetpg=1*targetarr[2],pg=$('#pdf-viewer-img').data('pg');
            if(pg!=targetpg) {
                fetch_page(fileName,targetpg,function(){
                    clickResult($t,$target);
                });
            } else {
                clickResult($t,$target);
            }
        });
    }
};
//HOTKEYS
$(document).bind('keydown', 'd', function(){
    if($('.ui-dialog:visible').length==0) $('#control-next').click();
}).bind('keydown', 'e', function(){
    if($('.ui-dialog:visible').length==0) $('#control-prev').click();
}).bind('keydown', 'w', function(){
    $("iframe", top.document).contents().find('#items-left').focus().blur();
}).bind('keydown', 's', function(){
    $("iframe", top.document).contents().find('#items-left').focus().blur();
}).bind('keydown', 'q', function(){
    $("iframe", top.document).contents().find('#items-left').focus().blur();
});