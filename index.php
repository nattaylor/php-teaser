<?php
/*
'authors' => 
            array
              ...
          'body' => string '<div id="copy"><p>NEWÂ­TOWN, Conn. â€” Adam LanÂ­za spent the last months of his life mostÂ­ly alone in his bedÂ­room. His winÂ­dows were covÂ­ered with trash bags. He was preÂ­occuÂ­pied with viÂ­oÂ­lent video games and deÂ­tails from some of the worst masÂ­sacres in AmerÂ­iÂ­can hisÂ­toÂ­ry. </p> <p>Mr. LanÂ­za reÂ­fused to speak even to his mothÂ­er, comÂ­muÂ­niÂ­catÂ­ing with her onÂ­ly by email, even though their bedÂ­rooms shared the same floor of their house on YoÂ­gananÂ­da Street.</p> <p>He would n'... (length=10803)
          'byline' => string '<div id="byline">By JOSEPH BERGER and MARC SANTORA</div>' (length=56)
          'firstPublishedTs' => int 1385410596
          'id' => float 1.0000000256755E+14
          'keywords' => 
            array
              ...
          'printEdition' => string 'The New York Times on the Web' (length=29)
          'printHeadline' => string 'Chilling Look at Newtown Killer, but No Motive ' (length=47)
          'printPubDate' => int 1385442000
          'pubDate' => int 1385442000
          'relatedAssets' => 
            array
              ...
          'section' => string 'nyregion' (length=8)
          'sectionDisplayName' => string 'N.Y. / Region' (length=13)
          'showTimestamp' => boolean true
          'summary' => string 'Almost a year after Adam Lanza killed 26 children and adults in a Newtown, Conn., elementary school, an investigative report shed new light on his internal life and complicated relationship with his mother.' (length=206)
          'tagline' => string 'Joseph Berger reported from Newtown, and Marc Santora from New York. Elizabeth Maker contributed reporting from Newtown, and Kristin Hussey from Bridgeport, Conn. ' (length=163)
          'tinyUrl' => string 'http://nyti.ms/18CiT1D' (length=22)
          'title' => string 'Chilling Look at Newtown Killer, but No Motive ' (length=47)
          'type' => string 'article' (length=7)
          'updatedDate' => int 1385430431
          'url' => string
*/
$api = "http://www.nytimes.com/chrome/backend/services/full.html?uri=http://platforms.nytimes.com/mobile/v1/json/skimmer/homepage.json";
$cache = 'homepage.json';
$html = "";

require("class.phpteaser.php");

//if(!file_exists($cache)){
if(file_exists($cache) && filemtime($cache) > time() - 60*60){
	$html = genHTML($cache);
} else {
	file_put_contents($cache,file_get_contents($api));
}

function genHTML($cache) {
	$news = json_decode(file_get_contents($cache),true);
	$html = '';
	foreach($news['assets'] as $article) {
		$summary = new Teaser();
		$content = $summary->createSummary($article['body'],'text',$article['title']);
		$html .= '<div class="article">';
		$html .= '<div class="article-section">'.$article['sectionDisplayName'].'</div>';
		$html .= '<h1 class="article-title"><a href="'.$article['url'].'" class="article-link">'.$article['title'].'</a></h1>';
		$html .= '<div class="article-summary">'.$article['summary'].'</div>';
		$html .= '<ul class="artile-list"><li class="article-item">'.implode('</li><li class="article-item">',$content).'</li></ul>';
		$html .= '</div>';
	}
	return $html;
}
?>

<!DOCTYPE HTML>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>News!</title>
	<script src="http://masonry.desandro.com/masonry.pkgd.min.js"></script>
	<script>
	document.addEventListener("DOMContentLoaded", masonryInit, false);
	window.addEventListener("resize", checkSize, false);
	var msnry = {};
	function masonryInit() {
		if(window.innerWidth > 480) {
			var container = document.querySelector('.wrap');
			msnry = new Masonry( container, {
				columnWidth: 0,
				itemSelector: '.article'
			});
		}
	}
	function checkSize() {
		if(window.innerWidth < 481) {
			msnry.destroy()
		} else {
			masonryInit();
		}
	}

	</script>
	<style>
body {
	font-family: "HelveticaNeue-Light", "Helvetica Neue Light", "Helvetica Neue", Helvetica, Arial, "Lucida Grande", sans-serif; 
	font-weight: 300;
}

.article {
	width:20%;
	box-sizing:border-box;
	padding:5px;
}

.article-section {
    text-transform: uppercase;
    letter-spacing: 1px;
    background-color: #efefef;
    padding: 2px;
    text-align: center;
}
.article-title {
    font-size: 1em;
    margin:0;
}
.article-link {
    text-decoration: none;
    color: #222;
}

.article-item {
    list-style: none;
    border-bottom: 3px solid #ccc;
    padding-top: 5px;
}

.article-item:last-child {
    border:0;
}
.artile-list {
    padding: 0;
    margin: 0;
}

@media (max-width : 480px) {
	.article {
		width:93%;
		margin:0 auto;
	}
}
	</style>
</head>

<body>
	<div class="wrap">
		<?php echo $html; ?>
	</div>
</body>
</html>
