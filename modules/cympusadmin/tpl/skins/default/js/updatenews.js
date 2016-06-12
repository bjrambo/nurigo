jQuery(function($){
  // 위젯이 여러개일수도 있기 때문에, 배열로 구분한다.
  var parent_obj = new Array();
  var parent_height = new Array();
  var child_obj = new Array();
  var child_height = new Array();
  var clone_obj = new Array();
  var scroll_mount = new Array();
  var scroll_count = new Array();
  
  // 위치를 초기화 시키는 함수
  function setTopValue(e){
    child_obj[e].css("top",0);
    clone_obj[e].css("top", child_height[e]);
  }
  
  // 스크롤 시키는 함수
  function UpdateNewsScroll(e){
    if(scroll_mount[e]) scroll_count[e]++ ;
    if(child_obj[e].position().top <= child_height[e]*-1) setTopValue(e);
    
    child_obj[e].css("top",child_obj[e].position().top - scroll_mount[e]);
    clone_obj[e].css("top",clone_obj[e].position().top - scroll_mount[e]);
    if(scroll_count[e] >= parent_height[e]){
      scroll_count[e] = 0;
      setTimeout(function(){ UpdateNewsScroll(e) }, 1500);
    }
    else{
      setTimeout(function(){ UpdateNewsScroll(e) }, 40);
    }
  }
  
  // 시작 함수.
  function UpdateNewsSetting(){
    $(".updatenews .updatenews_obj").each(function(e){
      scroll_mount[e] = 1;
      scroll_count[e] = 0;
      parent_obj[e] = $(this);
      parent_obj[e].hover(function(){
        scroll_mount[e] = 0;
      },function(){
        scroll_mount[e] = 1;
      });
      parent_height[e] = parent_obj[e].height();
      
      child_obj[e] = parent_obj[e].find("div.updatenews_box");
      child_height[e] = child_obj[e].height();
      
      clone_obj[e] = child_obj[e].clone().appendTo(parent_obj[e]);
      
      setTopValue(e);
      setTimeout(function(){ UpdateNewsScroll(e); }, 1500);
    });
  }
  
  // 시작 함수 실행
  UpdateNewsSetting();
});
