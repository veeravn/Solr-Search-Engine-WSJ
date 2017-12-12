'use strict';
var stopWords = "";
$(function() {
    $.get('stopwords.txt', function(data) {
       stopWords = data;                   
    });
    var URL_PREFIX = "http://localhost:8983/solr/wsjSearchEngine/suggest?q=";
    var URL_SUFFIX = "&wt=json";
    $("#q").autocomplete({
        source : function(request, response) {
            var lastword = $("#q").val().toLowerCase().split(" ").pop(-1);
            var URL = URL_PREFIX + lastword + URL_SUFFIX;
            $.get({
                url : URL,
                success : function(data) {
                    var lastword = $("#q").val().toLowerCase().split(" ").pop(-1);
                    var suggestions = data.suggest.suggest[lastword].suggestions;
                    suggestions = $.map(suggestions, function (value, index) {
                        var prefix = "";
                        var query = $("#q").val();
                        var queries = query.split(" ");
                        if (queries.length > 1) {
                            var lastIndex = query.lastIndexOf(" ");
                            prefix = query.substring(0, lastIndex + 1).toLowerCase();
                        }
                        if (prefix == "" && isStopWord(value.term)) {
                            return null;
                        }
                        if (!/^[0-9a-zA-Z]+$/.test(value.term)) {
                            return null;
                        }
                        return prefix + value.term;
                    });
                    response(suggestions.slice(0, 5));
                },
                dataType : 'jsonp',
                jsonp : 'json.wrf'
            });
        },
    minLength : 1
    });
});
function isStopWord(word)
{
    var regex = new RegExp("\\b"+word+"\\b","i");
    return stopWords.search(regex) < 0 ? false : true;
}
function getSnippets(query, fileName, resNum) {
    var ary = query.split(" ");
    var count = 0;
    var max = 0;
    var finalSnippet = "";
    var filePath = "WSJ/" + fileName;
    var htmlElements;
    var pos = 0;
    var wd = "";
    var start = 0;
    var end = 0;
    var post1;
    var pre;
    $.get( filePath, function( data ) {
        var tmp = document.createElement("DIV");
        tmp.innerHTML = data;
        var pElem = tmp.getElementsByTagName("p");
        var snippets = "";
        for (var i=0; i < pElem.length; i++) {
            snippets += pElem[i].innerText + " ";
        }
        //snippets = tmp.innerText || tmp.innerText || "";
        if(snippets != "") {
            var lower = snippets.toLowerCase();
            $.each(ary, function( index, value ) {
               value = value.toLowerCase();
                if(lower.indexOf(value) != -1) {
                    count++;
                }
            });
            if(max < count) {
                finalSnippet = snippets;
                max = count;
            }
            else if(max == count && count > 0 ) {
                if(finalSnippet.length < snippets.length) {
                    finalSnippet = snippets;
                    max = count;
                }
            }
            count = 0;
            $.each(ary, function( index, value ) {
                finalSnippet = finalSnippet.trim();
                finalSnippet = finalSnippet.replace(/\s+/g, ' ').trim();
                pos = getIndex(snippets, value, false);
                if(pos >= 0) {
                    return false;
                }
            });
            if(pos>80) {
                start = pos - 80;
            }
            else {
                start = 0;
            }
            end = start + 160;
            if(finalSnippet.length < end) {
                end = finalSnippet.length - 1;
                post1 = "";
            }
            else {
                post1 = "...";
            }
            if(finalSnippet.length > 160) {
                if(start > 0)
                    pre = "...";
                else
                    pre = "";
                finalSnippet = pre + finalSnippet.substr(start, end-start+1) + post1;
                finalSnippet = finalSnippet.trim();
                finalSnippet = finalSnippet.replace(/\s+/g, ' ').trim();
            }
            if(finalSnippet.length == 0) {
                finalSnippet = "No snippet could be found";
            }
        }
        
        var elements = document.getElementsByName("snippets");
        console.log("resNum: " + resNum);
        elements.item(resNum).innerText = finalSnippet;
        console.log(finalSnippet);
    }, "html");
    return finalSnippet;
    
}
function getIndex(searchStr, str, caseSensitive) {
    searchStr = searchStr.trim();
    searchStr = searchStr.replace(/\s+/g, ' ').trim();
    var searchStrLen = str.length;
    var startIndex = 0;
    var index;
    if (searchStrLen == 0) {
        return [];
    }
    if (!caseSensitive) {
        str = str.toLowerCase();
        searchStr = searchStr.toLowerCase();
    }
    while ((index = searchStr.indexOf(str, startIndex)) > -1) {
        var start = 0;
        var end = 0;
        var temp = searchStr.substr(index,searchStrLen+1);
        if(index > 80) {
            start = index - 80;
        }
        else {
            start = 0;
        }
        end = start + 160;
        var substring = searchStr.substr(start, end);
        var check = /^[a-zA-Z0-9- ]*$/.test(substring);
        if (check) {
            startIndex = index + searchStrLen;
        } else if(str+" " !== temp) {
            startIndex = index + searchStrLen;
        } else {
            return index;
        }
    }
}
