/**
 * JavaScript required by the turmultiplechoice question type.
 *
 * @package    qtype
 * @subpackage turmultiplechoice
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


M.qtype_turmultiplechoice = M.qtype_turmultiplechoice || {};

M.qtype_turmultiplechoice.init = function (Y, questiondiv, quiet, autoplay) {

    if (!$(document.body).hasClass('turmultiplechoice')) {
        $(document.body).addClass('turmultiplechoice');
    }
	
	vFact_AllowHighLight = false;
	vFact_HighLightColor = '';
	vFact_SentenceColor = '';
	vFact_HighlightMode = null;
	
	var playing;
	var isPlaying;
    var initialplaythroughcomplete = false;
    var current = 0;
    var audio = $('#audiodiv');
    var playlist = $(questiondiv);
    var tracks = playlist.find('.content .formulation .audioplay');
	
	
	setTimeout(function() {
		vFact_HTML5Player.setEventHandler_OnChangePlaylistStatus(test); 
		function test(newPlaylistStatus) {
			 if (newPlaylistStatus == 1){
				 playing.addClass('playing');
				 isPlaying = true;
			 } else if (newPlaylistStatus == 0){
				 playing.removeClass('playing');
				  isPlaying = false;
				 nextQuestion();
			 }
		 }
	 }, 1500);
	
    if (!quiet && autoplay == 1) {
        var playing = $(playlist.find('.audioplay')[current]);
		if (playing.data('src') == undefined){
			setTimeout(function() {
				playing.addClass('playing');
				vFact_playsectionEXT('qtext');
   			}, 2000);
		} else {
			playing.addClass('playing');
			audio[0].play();
		}
    }
	
	function nextQuestion(){
		$('.audioplay').removeClass('playing');
        if (current != tracks.length - 1 && !initialplaythroughcomplete) {
            setTimeout(function() {
                current++;
                playing = $(playlist.find('.audioplay')[current]);
				if (playing.data('src') == undefined){
					vFact_playsection(playing.data('qid'));
				} else {
					playing.addClass('playing');
					audio[0].src = $(playlist.find('.audioplay')[current]).attr('data-src');
					audio[0].load();
					audio[0].play();
				}
            }, 1100);
        } else {
            initialplaythroughcomplete = true;
        }
	}
	
    audio[0].addEventListener('ended',function(e){
		nextQuestion();
    });

    $('.audioplay').click(function(e) {
		$('*').removeClass('playing');
		initialplaythroughcomplete = true;
		
		if (isPlaying){
			vFact_dostop();	
			isPlaying = false;
			return;
		}

        if ($(this).hasClass('playing')) {
            audio.trigger('pause');
            $(this).removeClass('playing');
        } else {
            audio.trigger('pause');
			$(this).removeClass('playing');
            $('.audioplay').removeClass('playing');
			
			if ($(this).data('src') == undefined) {
				var questionId = $(this).attr("data-qid");
				if (questionId == '0'){
					playing = $(this);
					vFact_playsectionEXT('qtext');
				} else {
					playing = $(this);
					  setTimeout(function() {
						vFact_playsection(questionId);
					  }, 550);
				}
			} else {
				audio[0].src = $(this).data('src');
				audio[0].load();
				audio[0].play();
				$(this).addClass('playing');
			}
        }
    });
};
