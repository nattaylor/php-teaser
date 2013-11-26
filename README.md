#php-teaser#

Summarize text or articles into a few bullet points

##Usage###
Basically create an instance of `Teaser()` then pass it either a URL or a text/title pair, and it will return a summary as an array of sentences.

    //Ultra-simple Example
    $teaser = new Teaser();
    $teaser->createSummary("http://www.business2community.com/cloud-computing/confused-saas-paas-iaas-0687173","url"));

##Notes##
- Is there a lot more to do? Yes.  Does it basically work? Yes.
- I tried to carefully document the class, but it needs more detail.  This is coming soon.
- (Obviously) This relies on the source text having some good sentences that summarize it.  Without that, our summary will suck.
- Based on https://github.com/xiaoxu193/PyTeaser based on http://www.textteaser.com/
- What would make this a lot better?  Tweaking the scoring, duh!