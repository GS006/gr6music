(function(){
	var dbg=false,vcke=getCookieConsent("video"),text={youtube:"",vimeo:""};
	window.video_iframes=[];
	document.addEventListener("DOMContentLoaded",function(){
		var videomarcogs,wall,video_platform,video_src,video_id,video_w,video_h;
		var bYoutube=getCookieConsent("youtube"),bVimeo=getCookieConsent("vimeo");
		dbg&&console.log('YouTube='+bYoutube+' Vimeo='+bVimeo);
		// Indexes of all video frames
		var nYT="",nVM="";
		videomarcogs=document.getElementsByTagName('iframe');
		for(var i=0,max=window.frames.length-1;i<=max;i+=1){
			video_src=videomarcogs[i].src;
			if(video_src.match(/youtube|vimeo/)==null){
				continue;
			}
			if(!bYoutube&&video_src.match(/vimeo/)==null){
				nYT+=(nYT=="")?""+i:";"+i;
			}
			if(!bVimeo&&video_src.match(/youtube/)==null){
				nVM+=(nVM=="")?""+i:";"+i;
			}
		}
		dbg&&console.log('YouTube frame indexes='+nYT+' Vimeo frames='+nVM);
		if(nYT!=""||nVM!="")
		for(var i=0,nSkipped=0,max=window.frames.length-1;i<=max;i+=1){
			videomarcogs=document.getElementsByTagName('iframe')[nSkipped];
			video_w=videomarcogs.getAttribute('width');
			if(video_w.indexOf("%")<0)video_w+="px";
			video_h=videomarcogs.getAttribute('height');
			if(video_h.indexOf("%")<0)video_h+="px";
			video_src=videomarcogs.src;
			video_iframes.push(videomarcogs);
			wall=document.createElement('article');
			dbg&&console.log('video src='+video_src);
			// Only proccess video iframes [youtube|vimeo]
			if(video_src.match(/youtube|vimeo/)==null){
				dbg&&console.log('Skip non-video frame['+i+']');
				nSkipped++;
				continue;
			}
			video_platform=video_src.match(/vimeo/)==null?'youtube':'vimeo';
			video_id=video_src.match(/(embed|video)\/([^?\s]*)/)[2];
			wall.setAttribute('class','video-wall');
			wall.setAttribute('data-videotype',video_platform);
			wall.setAttribute('data-indexes',(video_platform=='youtube')?nYT:nVM);
			wall.setAttribute('id','vidframe'+i);
			if(video_w&&video_h){
				wall.setAttribute('style','width:'+video_w+';height:100%');
			}
			// Only process video iframes if not already authorized
			if((video_platform=="youtube"&&(bYoutube||nYT==""))||(video_platform=="vimeo"&&(bVimeo||nVM==""))){
				dbg&&console.log('Skip '+video_platform+' frame['+i+']');
				nSkipped++;
				continue;
			}
			dbg&&console.log('Process '+video_platform+' iframe['+i+']');
			wall.innerHTML=text[video_platform].replace(/\%id\%/g,video_id);
			videomarcogs.parentNode.replaceChild(wall,videomarcogs);
			document.querySelectorAll('.video-wall button')[i-nSkipped].addEventListener('click',function(){
				var videomarcogs=this.parentNode,vtype=videomarcogs.getAttribute('data-videotype'),vframes=videomarcogs.getAttribute('data-indexes').split(';');
				dbg&&console.log('Indexes of '+vtype+' frames='+videomarcogs.getAttribute('data-indexes'));
				for(var i,n=0;n<vframes.length;n++){
					i=vframes[n];
					dbg&&console.log(vtype+' iframe['+i+'] wall deactivated');
					videomarcogs=document.getElementById('vidframe'+i);
					video_iframes[i].src=video_iframes[i].src.replace(/www\.youtube\.com/,'www.youtube-nocookie.com');
					videomarcogs.parentNode.replaceChild(video_iframes[i],videomarcogs);
				}
				setCookieConsent(vtype,true);
			},false);
		}
	});
})();
