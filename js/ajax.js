document.addEventListener('DOMContentLoaded', function() {
    var xhr = new XMLHttpRequest();
    var textAreaTag;
    var val;
    var urlMatch = /(http|ftp|https):\/\/[\w-]+(\.[\w-]+)+([\w.,@?^=%&amp;:\/~+#-]*[\w@?^=%&amp;\/~+#-])?/;
    var xmlRequestTimeout;
    var result = document.querySelector('.result');
    var loader = document.querySelector('.loader');
    var linkTo = document.querySelector('.linkTo');
    var close;

    textAreaTag = document.getElementById('post');

    textAreaTag.addEventListener('keyup', function() {
        val = this.value;

        var matchedUrl = val.replace(urlMatch, function(url) {
                if ( typeof result.children[0] !== "undefined" ) {
                    hide(loader);
                } else {
                    if ( url ) {
                        show(loader);
                        linkTo.href = url;
                        clearTimeout(xmlRequestTimeout);
                        xhr.open('POST', "php/content.php");
                        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xhr.addEventListener('readystatechange', function() {
                            if ( xhr.readyState === 4 && xhr.status === 200 ) {
                                if ( xhr.responseText.length <= 0 ) {
                                    result.innerHTML = "";
                                    hide(linkTo);
                                } else {
                                    linkTo.style.display = "block";
                                    result.innerHTML = xhr.responseText;
                                    hide(loader);
                                    if ( typeof result.children[1] !== "undefined" && typeof result.children[1].children[0] !== "undefined" ) {
                                        if ( result.children[1].children[0].className == 'l-img' ) {
                                            result.className = "result clearfix n-p-t";
                                        } else if ( result.children[1].children[0].className == 's-img' ) {
                                            result.className = "result clearfix";
                                        }
                                    } else {
                                        result.className = "result clearfix padding";
                                    }
                                    close = document.querySelector('.close');
                                    closeAction(linkTo, close, url);
                                }
                                xhr.abort();
                            }
                        });
                        xhr.send("data=" + url);
                        xmlRequestTimeout = setTimeout(function() {
                            linkTo.style.display = "block";
                            result.innerHTML = '<button class="close">X</button><p>It seems that the page that you\'re trying to load is not available. Please, try later</p>';
                            result.className = "result clearfix padding";
                            hide(loader);
                            close = document.querySelector('.close');
                            closeAction(linkTo, close, url);
                            return false;
                        }, 12e4);
                    }
                }
            });

    }, false);


    function hide(el) {
        if ( el ) {
            el.style.display = "none";
        }
    }

    function show(el) {
        //var disp = el.currentStyle || window.getComputedStyle(el, "");
        if ( el ) {
            el.style.display = 'block';
        }
    }

    function closeAction(el, element, url) {
        el.addEventListener('mouseover', function() {
            show(element);
        }, false);
        el.addEventListener('mouseout', function() {
            hide(element);
        }, false);
        el.addEventListener("click", function(e) {
            e.preventDefault();
            if (e.target.className == "close" ) {
                hide(el);
                for ( var i = 0; i <= result.children.length; i ++) {
                    result.innerHTML = "";
                    result.className = "result clearfix";
                }
            } else {
                window.open(url, "_blank");
            }
        }, false);
    }

}, false);
