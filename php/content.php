<?php
/**
 * Created by Radonirina.
 * Date: 17/03/2015
 * Time: 08:44
 */

function get_content_url($url) {
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);

    $data = curl_exec($ch);
    curl_close($ch);


    return $data;
}

function check_internet_connection($sCheckHost = 'www.google.com')
{
    return (bool) @fsockopen($sCheckHost, 80, $iErrno, $sErrStr, 5);
}

// check if data is not empty
if ( isset($_POST['data']) && $_POST['data'] != "https://www.facebook.com" ) {
    $url = $_POST['data'];
    $context = stream_context_create(array(
        'http' => array(
            'method' => 'POST',
            'header' => implode("\r\n", array(
                'Content-type: application/x-www-form-urlencoded',
                'Accept-Language: en-us,en;q=0.5', // optional
                'Accept-Charset: ISO-8859-1,utf-8;q=0.7,*;q=0.7' // optional
            )),
            'content' => http_build_query(array(
                'prev'  =>  '_t',
                'hl'    =>  'en',
                'ie'    =>  'UTF-8',
                'text'  =>  'hello',
                'sl'    =>  'en',
                'tl'    =>  'ru'
            ))
        )
    ));

    if ( check_internet_connection() ) {
        if ( $url != "http://www.facebook.com" ) {
            // get the url which will be extracted
            $html = @file_get_contents($url, FILE_TEXT, $context);


            // Some tags is uppercase (crazy)
            if ( preg_match("/(<\\/[A-Z]+)(.*?>)/e", $html) ) {
                // so, we should replace those tag into lowercase
                $htmlFormatted = preg_replace('/(<\/?\w+\s+[\w=\"\w+\"]+\s+\w+)(.*?>)/e', "strtolower('\\1') . '\\2'", $html);
            } else {
                $htmlFormatted = $html;
            }

            // new DOMDocument(): load html
            $doc = new DOMDocument();
            libxml_use_internal_errors(true);
            // load html
            @$doc->loadHTML($htmlFormatted);
            $metaTags = @get_meta_tags($url);
            libxml_clear_errors();

            // Make sure that html is not empty
            //if ( $html ) {
            // get element by its names
            $node = $doc->getElementsByTagName('title');
            $metas = $doc->getElementsByTagName('meta');
            $p = $doc->getElementsByTagName('p');
            $img = $doc->getElementsByTagName('img');
            $srcNotHttp;
            $rightClass = "";



            // loop over the metas
            // so we want to check if the given <meta> is an image
            for ( $im = 0; $im <= $metas->length; $im ++ ) {
                $metaImg = $metas->item($im);


                // if the <meta property="og:image"> OR <meta name="twitter:image">
                // We take the content: it will return the url of the image
                if ( isset($metaImg) && $metaImg->getAttribute('property') == 'og:image' || isset($metaImg) && $metaImg->getAttribute('name') == 'twitter:image' ) {
                    // add the image
                    echo '<button class="close">X</button><div class="img-content left"><img src="'.$metaImg->getAttribute('content').'" alt="" class="s-img" /></div>';
                    break;
                } else {
                    // if the document has not <meta property> OR <meta name>
                    // then we check the first image
                    // usually, it is the logo

                    if ( empty($metaImg) && is_object($img) && $img->item(0) ) {
                        $src = $img->item(0)->getAttribute('src');


                        // check if the url is valid one: http:// or https://
                        if (preg_match('/http:\\/\\/|https:\\/\\//', $src)) {
                            $srcNotHttp = $src;
                        } else  if ( preg_match('/^\/\w+/', $src) ) { // sometimes, we have url like this /{{IMG_PATH}}
                            // so we concatenate it with the url itself
                            $srcNotHttp = $url.$src;
                        } else { // if the url is only like the following format: //{{URL}}
                            // therefore, what we just need is to add http: before to render it like http://
                            $srcNotHttp = preg_replace('/\\/\\//', $src, 'http://');
                        }

                        if ( ! empty($src) || $src != "" ) {
                            list($width, $height) = getimagesize($srcNotHttp);
                            $classForImgSize = $width > 100 ? "l-img":"s-img";
                            $classForImgContainer = $width > 100 ? "":"left";
                            $rightClass = $width > 100 ? "full":"right";

                            // add the image
                            echo '<button class="close">X</button><div class="img-content '.$classForImgContainer.'"><img src="' . $srcNotHttp . '" alt="image" class="'.$classForImgSize.'"/></div>';
                        } else {
                            echo '<button class="close">X</button>';
                        }
                        // we stop the loop
                        break;
                    }
                }

            }


            // if title is not empty
            if ( ! empty($node->item(0)->nodeValue) ) {
                // We add the title
                // TODO: manage class
                echo '<div class="description '.$rightClass.'"><h2>'.$node->item(0)->nodeValue.'</h2>';
            }

            //die();

            // If <meta name="description"> OR <meta name="og:description"> OR <meta name="twitter:description"> exist
            if ( isset($metaTags['description']) || isset($metaTags['og:description']) ||isset($metaTags['twitter:description']) ) {
                // then we loop over the meta tags
                for ( $desc = 0; $desc <= $metas->length; $desc ++ ) {
                    $metaTag = $metas->item($desc);
                    if ( ! empty($metaTag) ) {
                        // if the meta exist, the we need to check if the name is equal to "Description" OR "description" OR "twitter:descrioption" OR "og:description"
                        if ( $metaTag->getAttribute('name') == "Description" || strpos($metaTag->getAttribute('name'), 'description') || $metaTag->getAttribute('name') == "description" || $metaTag->getAttribute('name') == "twitter:description" || $metaTag->getAttribute('name') == "og:description" && $metaTag->getAttribute('name') != null ) {
                            // then we add the description
                            echo '<p>'.$metaTag->getAttribute('content').'</p>';
                            break;
                        }
                    } else {
                        echo '<p>'.$metaTags['description'].'</p>';
                        break;
                    }

                }

            } else { // Some websites don't have a meta description, so, what we need to do is to check the first paragraph
                for ( $par = 0; $par <= $p->length; $par ++ ) {
                    $parag = $p->item($par);
                    // if paragraph has text ...
                    if ( ! empty($parag->nodeValue) ) {
                        // if the text length is greater than 100 ...
                        if ( strlen($parag->nodeValue) > 100 ) {
                            // We add the text to <p> element
                            echo '<p>'.$parag->nodeValue.'</p>';
                            // stop the loop
                            break;
                        }
                    }
                }
            }

            if ( isset($metaTags['application-name']) ) {
                echo '<p class="app-name">'.$metaTags['application-name'].'</p></div>';
            }


            //}
        } else {
            echo '<button class="close">X</button><div class="img-content left"><img src="https://www.facebook.com/images/fb_icon_325x325.png" alt="" class="s-img" /></div>';
            echo '<div class="description right"><h2>Welcome to Facebook - Log In, Sign Up or Learn More</h2>';
            echo '<p>Facebook is a social utility that connects people with friends and others who work, study and live around them. People use Facebook to keep up with...</p>';
            echo '</div>';
        }
    } else {
        echo "Please check you internet connexion";
    }


}