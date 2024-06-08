'use strict';

var comunicator = function($http, $log) {
    var logger = $log;
    var token = sess;

    function decrypt(message, json) {
        var raw = JSON3.parse(message);
        if(json !== null && json !== undefined && !json){
            raw = message;
        }
        
        var info = Base64.decode(raw);
        var data = Aes.Ctr.decrypt(info, token, 256);
        
        return data;
    }

    function encrypt(message) {
        
        var info = Aes.Ctr.encrypt(message, sess, 256);
        var data = Base64.encode(info);

        return data;
    }

    return {
        // get all the ideas
        request: function(info) {

            var data = encrypt(info);

            return data;
        },
        response: function(info) {

            var data = decrypt(info);

            return data;
        },
        encode: function(info) {

            var data = Base64.encode(info);

            return data;
        },
        decode: function(info) {

            var data = Base64.decode(info);

            return data;
        }
    };
};
