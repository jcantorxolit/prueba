'use strict';

app
  .filter('htmlToPlaintext', function () {
      return function (text) {
          return String(text).replace(/<[^>]+>/gm, '');
      };
  }
);

app.filter('trim', function () {
    return function(value) {
        if(!angular.isString(value)) {
            return value;
        }  
        return value.replace(/^\s+|\s+$/g, ''); // you could use .trim, but it's not going to work in IE<9
    };
});

app.filter('nl2br', function($sce){

    return function(msg,is_xhtml) { 
  
        var is_xhtml = is_xhtml || true;
  
        var breakTag = (is_xhtml) ? '<br />' : '<br>';
  
        var msg = (msg + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, '$1'+ breakTag +'$2');
  
        return $sce.trustAsHtml(msg);
    }
  
});