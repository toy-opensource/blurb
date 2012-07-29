/****************************************************************
* file: 	plugins/custom-blurb/js/templates/rollover.js
* author:	toy
* date:		02sep11
* re:		js custom story rollover template
*
*
* rev history
* [toy 08sep11]		orig version
* 
*****************************************************************/
var $J = jQuery.noConflict();

$J(document).ready(function() {		
	$J(".cb-image-").hover(
		function() {
			$J(this).parent().next(".cb-overlay").slideToggle("slow");
		},
		function() {
			$J(this).parent().next(".cb-overlay").slideToggle("slow");
		});
	$J(".cb-overlay").hover(
		function() {
			$J(this).stop(true, true).css("display","block");
		},
		function() {
			$J(this).css("display","none");
		});
});
