function googleTranslateElementInit() {
    new google.translate.TranslateElement(
        {
            layout: google.translate.TranslateElement.InlineLayout.SIMPLE
        },
        'google_translate_element'
    );

}
function getCookie(name){
    var arr, reg = new RegExp("(^| )" + name + "=([^;]*)(;|$)");
    if (arr = document.cookie.match(reg))
        return unescape(arr[2]);
    else
        return null;
}
if(!getCookie('googtrans')){
    document.cookie="googtrans=/auto/zh-CN"
}


function getCookie(name){
    var arr, reg = new RegExp("(^| )" + name + "=([^;]*)(;|$)");
    if (arr = document.cookie.match(reg))
        return unescape(arr[2]);
    else
        return null;
}
var typegoo=getCookie('googtrans')
if(typegoo=='/auto/en'){
  localStorage.setItem("typegoog",0)
}else if(typegoo=='/auto/ja'){
  localStorage.setItem("typegoog",1)
}else if(typegoo=='/auto/zh-CN'){
  localStorage.setItem("typegoog",2)
}

// function setLanguageEN(){
//     document.cookie="googtrans=/auto/en"//设置当前语言，这里的语言缩写你看下cookie，我不确定
//     }
// function setLanguageCN(){
//     document.cookie="googtrans=/auto/zh-CN"//设置当前语言，这里的语言缩写你看下cookie，我不确定
// }
// function setLanguageTW(){
//     document.cookie="googtrans=/auto/zh-TW"//设置当前语言，这里的语言缩写你看下cookie，我不确定
//     }