<?php
/**
	* Create a summary from long text blocks
	*
	* php-teaser is based on the original TextTeaser project written in Scala by Mojojolo and PyTeaser by xiaoxu193. It's completely re-written in PHP.
	* The aim of php-teaser is is to take any news article and extract a brief summary from it.
	*
	* php-teaser requires php-readability
	*
	*/
class Teaser {
	var $stopWords = array("-", " ", ",", ".", "a", "e", "i", "o", "u", "t", "about", "above", "above", "across", "after", "afterwards", "again", "against", "all", "almost", "alone", "along", "already", "also", "although", "always", "am", "among", "amongst", "amoungst", "amount", "an", "and", "another", "any", "anyhow", "anyone", "anything", "anyway", "anywhere", "are", "around", "as", "at", "back", "be", "became", "because", "become", "becomes", "becoming", "been", "before", "beforehand", "behind", "being", "below", "beside", "besides", "between", "beyond", "both", "bottom", "but", "by", "call", "can", "cannot", "can't", "co", "con", "could", "couldn't", "de", "describe", "detail", "did", "do", "done", "down", "due", "during", "each", "eg", "eight", "either", "eleven", "else", "elsewhere", "empty", "enough", "etc", "even", "ever", "every", "everyone", "everything", "everywhere", "except", "few", "fifteen", "fifty", "fill", "find", "fire", "first", "five", "for", "former", "formerly", "forty", "found", "four", "from", "front", "full", "further", "get", "give", "go", "got", "had", "has", "hasnt", "have", "he", "hence", "her", "here", "hereafter", "hereby", "herein", "hereupon", "hers", "herself", "him", "himself", "his", "how", "however", "hundred", "i", "ie", "if", "in", "inc", "indeed", "into", "is", "it", "its", "it's", "itself", "just", "keep", "last", "latter", "latterly", "least", "less", "like", "ltd", "made", "make", "many", "may", "me", "meanwhile", "might", "mill", "mine", "more", "moreover", "most", "mostly", "move", "much", "must", "my", "myself", "name", "namely", "neither", "never", "nevertheless", "new", "next", "nine", "no", "nobody", "none", "noone", "nor", "not", "nothing", "now", "nowhere", "of", "off", "often", "on", "once", "one", "only", "onto", "or", "other", "others", "otherwise", "our", "ours", "ourselves", "out", "over", "own", "part", "people", "per", "perhaps", "please", "put", "rather", "re", "said", "same", "see", "seem", "seemed", "seeming", "seems", "several", "she", "should", "show", "side", "since", "sincere", "six", "sixty", "so", "some", "somehow", "someone", "something", "sometime", "sometimes", "somewhere", "still", "such", "take", "ten", "than", "that", "the", "their", "them", "themselves", "then", "thence", "there", "thereafter", "thereby", "therefore", "therein", "thereupon", "these", "they", "thickv", "thin", "third", "this", "those", "though", "three", "through", "throughout", "thru", "thus", "to", "together", "too", "top", "toward", "towards", "twelve", "twenty", "two", "un", "under", "until", "up", "upon", "us", "use", "very", "via", "want", "was", "we", "well", "were", "what", "whatever", "when", "whence", "whenever", "where", "whereafter", "whereas", "whereby", "wherein", "whereupon", "wherever", "whether", "which", "while", "whither", "who", "whoever", "whole", "whom", "whose", "why", "will", "with", "within", "without", "would", "yet", "you", "your", "yours", "yourself", "yourselves", "the", "reuters", "news", "monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday", "mon", "tue", "wed", "thu", "fri", "sat", "sun", "rappler", "rapplercom", "inquirer", "yahoo", "home", "sports", "1", "10", "2012", "sa", "says", "tweet", "pm", "home", "homepage", "sports", "section", "newsinfo", "stories", "story", "photo", "2013", "na", "ng", "ang", "year", "years", "percent", "ko", "ako", "yung", "yun", "2", "3", "4", "5", "6", "7", "8", "9", "0", "time", "january", "february", "march", "april", "may", "june", "july", "august", "september", "october", "november", "december", "philippine", "government", "police", "manila");
	var $ideal = 20.0;

	/**
	* Summarize some text, optionally by extracting an article from a URL
	*
	* <p>Longer description</p>
	*
	* @param String $text Accepts a block or text or a URL
	* @param String $type The type of text given
	* @param Int $count The number of sentences to return (optional)
	* @param String $title The title (optional)
	* @return Array The resulting sentences
	*/
	function createSummary($text, $type ,$title="", $count = 3) {
		#Go grab the article from the URL
		if($type == 'url') {
			$articleObj = $this->getArticle($text);
			$text = $articleObj["content"];
			$title = $articleObj["title"];
		}
		
		//Prepare for scoring by parsing $text and $title
		$text = $this->cleanText($text);
		$sentences = $this->splitSentences($text);
		$keys =  $this->computeKeywords($text);
		$titleWords = $this->splitWords($title);

		//Score setences, and return the top $count sentences
		$ranks = $this->computeScore($sentences, $titleWords, $keys);

		arsort($ranks);
		$ranks = array_slice($ranks,0,$count);
		$summaries = array();
		foreach($ranks as $sentence=>$rank) {
			$summaries[]=$sentence;
		}
		//return array($summaries,$ranks,$keys,$titleWords);
		return $summaries;
	}

	/** Extract article from a page using php-readability */
	function getArticle($url) {
		require 'class.Readability.php';

		$html = file_get_contents($url);
		$html_input_charset = 'utf-8';

		$Readability = new Readability($html, $html_input_charset); // default charset is utf-8
		$ReadabilityData = $Readability->getContent();
		$results = array(
			'title' => $ReadabilityData['title'],
			'content' => $ReadabilityData['content']
		);

		return $results;
	}

	/**
	* Score sentences
	*
	* @param Array $sentences
	* @param Array $titleWords
	* @param Array $keywords
	* @return Array Resulting scores for each $sentence
	*/
	function computeScore($sentences, $titleWords, $keywords) {
		$senCount = count($sentences);
		$ranks = array();
		$count = 0;
		foreach($sentences as $sentence) {
			$words = $this->splitWords($sentence);
			$titleFeature = $this->computeTitleScore($titleWords, $words);
			$sentenceLength = $this->computeLengthScore($words);
			$sentencePosition = $this->computeSentencePositionScore($count+1, $senCount);
			$sbsFeature = $this->sbs($words, $keywords);
			$dbsFeature = $this->dbs($words, $keywords);
			$frequency = ($sbsFeature + $dbsFeature) / 2.0 * 10.0;

			#weighted average of scores from four categories
			$totalScore = ($titleFeature*1.5 + $frequency*2.0 + $sentenceLength*1.0 + $sentencePosition*1.0)/4.0;
			$ranks[$sentence] = $totalScore;
			$count++;
			$result = array(
				"the_sentence" => $sentence,
				"title" => $titleFeature,
				"sentence" => $sentencePosition,
				"sbs" => $sbsFeature,
				"dbs" => $dbsFeature,
			);
			//var_dump($result);
		}
		return $ranks;
	}

	/**
	* Score a sentence based on the presences of a keyword
	*
	* @param Array $words All the words in the sentence
	* @param Array $keywords All of the keyword and frequency tuples
	* @return Int score for the sentence representent by $words
	*/
	function sbs($words, $keywords) {
		$score = 0.0;
		if (count($words) == 0) {
			return 0;
		}
		foreach($words as $word=>$kscore) {
			if(in_array($words[$word],array_keys($keywords))) {
				$score+=$keywords[$words[$word]];
			}
		}
		return (1.0 / abs(count($words)) * $score)/10.0;
	}

	/**
	* Score a sentence based on its proximity to other keywords
	*
	* @param Array $words All the words in the sentence
	* @param Array $keywords All of the keyword and frequency tuples
	* @return Int score for the sentence representent by $words
	*/
	function dbs($words, $keywords) {
		$score = 0.0;
		$first = array();
		$second = array();
		$count = 0;

		foreach($words as $word) {
			if(in_array($word,array_keys($keywords))) {
				$kscore = $keywords[$word];
				if (count($first)==0) {
					$first = array($count, $kscore);
				} else {
					$second = $first;
					$first = array($count, $kscore);
					$dif = $first[0] - $second[0];
					$score += ($first[1]*$second[1]) / (pow($dif,2));
				}
			}
			$count++;
		}

		$k = count(array_diff(array_keys($keywords),$words))+1;
		$score = (1/($k*($k+1.0))*$score);
		return $score;
	}

	/**
	* Split text into words
	*
	* @param String $text The Text to split
	* @return Array The resulting word list
	*/
	function splitWords($text) {
		return preg_split('/((^\p{P}+)|(\p{P}*\s+\p{P}*)|(\p{P}+$))/', $text, -1, PREG_SPLIT_NO_EMPTY);
	}

	/**
	* Compute top 10 keywords based on frequency, elininating stopwords
	* 
	* @param String $text Text source from which to compute keywords
	* @return Array Scored keywords where each item is array('keyword',score)
	*/
	function computeKeywords($text) {
		$text = $this->splitWords($text);
		$numWords = count($text);
		//Remove stop words
		$text = array_diff($text,$this->stopWords);

		//Compute frequency of words
		$freq = array();
		foreach($text as $word) {
			if(!isset($freq[$word])) {
				$freq[$word]=0;
			}
			$freq[$word]+=1;
		}
		$minSize = min(10,count($freq));
		arsort($freq);
		$keywords = array_slice($freq,0,$minSize);

		//Improve sorting a little bit
		foreach($keywords as $keyword => $score) {
			$articleScore = $score*1.0 / $numWords;
			$keywords[$keyword] = $articleScore*1.5+1;
		}
		arsort($keywords);
		return $keywords;
	}

	/**
	* Split text into sentences with regex
	*
	* <p>Borrowed from http://stackoverflow.com/questions/5032210/php-sentence-boundaries-detection</p>
	* 
	* @param String $text Text to split
	* @return Array Sentences
	*/
	function splitSentences($text) {
		$re = '/# Split sentences on whitespace between them.
		    (?<=                # Begin positive lookbehind.
		      [.!?]             # Either an end of sentence punct,
		    | [.!?][\'"”]        # or end of sentence punct and quote.
		    )                   # End positive lookbehind.
		    (?<!                # Begin negative lookbehind.
		      Mr\.              # Skip either "Mr."
		    | Mrs\.             # or "Mrs.",
		    | Ms\.              # or "Ms.",
		    | Jr\.              # or "Jr.",
		    | Dr\.              # or "Dr.",
		    | Prof\.            # or "Prof.",
		    | Sr\.              # or "Sr.",
		    | T\.V\.A\.         # or "T.V.A.",
		    | [A-Z]\.           # or Middle Initial
		                        # or... (you get the idea).
		    )                   # End negative lookbehind.
		    \s+                 # Split on whitespace between sentences.
		    /x';

		$sentences = preg_split($re, $text, -1, PREG_SPLIT_NO_EMPTY);
		return $sentences;
	}

	/**
	* Score a sentence based on its length
	*
	* @param Array $sentence
	* @return Int score
	*/
	function computeLengthScore($sentence) {
		return 1 - abs($this->ideal - count($sentence)) / $this->ideal;
	}

	/**
	* Score a sentence based on the title
	*
	* @param String $sentence
	* @param String $title
	* @return Int score
	*/
	function computeTitleScore($title, $sentence) {
		$title = array_diff($title,$this->stopWords);
		$score = 0.0;
		foreach($sentence as $word) {
			if (!in_array($word,$this->stopWords) && in_array($word,$title)) {
				$score+=1.0;
			}
		}
		//var_dump($score/count($title));
		return $score/count($title);
	}

	/**
	* Different sentence positions indicate different probability of being an important sentence
	*
	* @param Int $i Sentence position in $text
	* @param Int $size Length of sentence
	* @return Float sentence position score
	*/
	function computeSentencePositionScore($i, $size) {
		$normalized =  $i*1.0 / $size;
		if ($normalized > 0 and $normalized <= 0.1) {
			return 0.17;
		} elseif($normalized > 0.1 and $normalized <= 0.2) {
			return 0.23;
		} elseif($normalized > 0.2 and $normalized <= 0.3) {
			return 0.14;
		} elseif($normalized > 0.3 and $normalized <= 0.4) {
			return 0.08;
		} elseif($normalized > 0.4 and $normalized <= 0.5) {
			return 0.05;
		} elseif($normalized > 0.5 and $normalized <= 0.6) {
			return 0.04;
		} elseif($normalized > 0.6 and $normalized <= 0.7) {
			return 0.06;
		} elseif($normalized > 0.7 and $normalized <= 0.8) {
			return 0.04;
		} elseif($normalized > 0.8 and $normalized <= 0.9) {
			return 0.04;
		} elseif($normalized > 0.9 and $normalized <= 1.0) {
			return 0.15;
		} else {
			return 0;
		}
	}

	/** Remove unwanted tags etc from text */
	function cleanText($text) {
		$text = preg_replace('#(<script).*?(</script>)#si','',$text);
		$text = preg_replace('#(<style).*?(</style>)#si','',$text);
		$text = preg_replace('~>\s+<~', '><', $text);
		$text = trim(preg_replace('/\s+/', ' ', $text));
		$text = strip_tags($text);
		
		return $text;
	}
}
//$teaser = new Teaser();
//$text = "This would seem like child's play though Mr. X when you add the mobile universe to the fray. The two leading app store providers, Apple and Google, have to their credit 700,000 apps each. That is a universe several hundred times the size of traditional, above the line media for each app store. Identifying the exact app to piggyback on to reach out to the consumer is a challenge quite unlike any. And that is only one half of the decision. Inventory on the mobile extends to websites browsed through the device as well.  This should explain why unlike the traditional media, where the media team would typically ink individual contracts with specific channels or publications on behalf of the advertiser, the mobile space needs a partner to break through this universe in a way. Enter the mobile ad networks.  The way the ecosystem works in the mobile space is like this: Aggregators consolidate ad inventory (various apps and space therein as well as the mobile websites) from multiple publishers and offer that to ad networks such as Google's AdMob and Apple's iAds as well as independent networks such as Komli, Vserv, InMobi, Tyroo etc. These networks work like the supply side platforms (SSPs) in the digital advertising space. Advertising and media agencies work with the advertisers to define their mobile campaigns and buy space via these networks to reach out to the consumer. Effectively, these networks act as intermediaries, eliminating the need for advertisers and ad agencies to go directly to publishers or aggregators for buying space.";
//var_dump($teaser->createSummary($text,"text","mobile networks"));
//var_dump($teaser->createSummary("http://www.businessinsider.com/apples-acquisition-of-primesense-may-be-for-tv-2013-11","url"));

//var_dump($teaser->createSummary("http://www.business2community.com/cloud-computing/confused-saas-paas-iaas-0687173","url"));