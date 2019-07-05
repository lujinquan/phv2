/*产权统计*/
window.onload = function () {
var section_name = $('#userName').text();
var section_name_start = section_name.indexOf('(')+1;
var section_name_end = section_name.indexOf(')');
section_name = section_name.substring(section_name_start,section_name_end);
var time_span = $('#timeYear').val().split('-');
$('.time').text(time_span[0]+'年'+time_span[1]+'月');
};
$("#yueQuery").click(function() {
	var time_span = $('#timeYear').val().split('-');
	$('.time').text(time_span[0]+'年'+time_span[1]+'月');
	$(".DQueryType,#DOwnerTyp").text($("#OwnerTyp option:selected").text());
})