# CrossKnowledge
## Technical challenge (PHP)

Based on [this instructions](https://gist.github.com/pxotox/3d694aee5fc5bc3e8ebe6cdc908c6d5a)

# Challenges

## 1. Cache function

Original text:

> Implement a function (on the same module - `request.php`) to cache requests preventing unecessary calls. You may use [this Redis module](https://github.com/phpredis/phpredis) as a cache service.
>
> **Note**: You may use any PHP version, including PHP7+.

### Considerations

Taking a look at `request.php` file, I've made a few considerations:

* The given class seems to be a RESTful interface built only on static methods, thus it's not intended for instantiation with data retention. This implies on external ways for storing earlier used requests, which is the [definition of cache](https://en.wikipedia.org/wiki/Cache_(computing)). Finally, this implies on external data access (like using databases or APIs) or direct file access. The right way to choose among the options would be doing benchmarks, as it's arguably to defend only one as seen in [[1]](https://stackoverflow.com/questions/12693042/direct-mysql-query-or-cached-file-which-is-faster), [[2]](https://stackoverflow.com/questions/4071349/is-it-faster-to-allow-php-to-parse-a-large-file-than-calling-data-from-a-mysql-d), [[3]](https://stackoverflow.com/questions/6853482/flat-file-vs-database-speed) and [[4]](https://stackoverflow.com/questions/8003454/is-it-faster-or-better-to-use-mysql-instead-of-text-files-or-file-names-for-orde). I have chosen direct file access for this one to maintain the scope of this challenge.

* By choosing direct file access, I took two rules based on the answers of links above:
    1. Break the cached requests on different files - as the response length may vary (it's not defined) and smaller files usually have faster access;
    2. Implement a filename that holds the whole index identifier for that cache, in order to use a single command (file_exists) to check if there's a cache for that particular request and to use [GLOB](https://www.php.net/manual/pt_BR/function.glob.php) when cache handling functions (like filtering the cached requests using index criterias).

* I'm going to cache **only the results of the `GET`** method, as this is the only method (based on [REST definition](https://restfulapi.net/)) that doesn't affect the server-side database. Perhaps an application could provide some version control over the data for caching porposes also on other methods, but it's out of the scope of this challenge.

* I'm assuming that the pair URL+Parameters contains the unique identifier for the record that is being manipulated through the REST interface, and from now on I'm assuming it as the `ID`.

* Whenever a `PUT`, `PATCH` or `DELETE` request is made on an `ID`, the `GET` cached response of that same record should be deleted (if it exists), as the next `GET` request on that record will (most likely) not result on the same response. Another approach would making a subsequent `GET` request on the same `ID` to update the cached `GET` response, but I don't think it's necessary as the record may not be used soon and it would be cached anyway at next `GET`.

* The `POST` request has no effect on the cache system, as it result is usually different (presuming it returns the unique `ID` of the created record) and it usually doesn't have an identifier in the request URL nor its parameters (as it'll be created after the request is executed). Perhaps if the application return the whole created record (simulating a `GET` request) we could cache it too, but again it's out of the current scope.

* I had to add a second parameter (boolean TRUE) to the `json_decode` inside `get` function in order to transform the result into an associative array to write to the file, and not an object (as returned without this). From the [manual](https://www.php.net/manual/en/function.json-decode.php):

	> Parameters
	>
	> json - The json string being decoded.
	> [assoc] - When TRUE, returned objects will be converted into associative arrays.

* The cached requests will be handled inside `cached/` folder, which must have write privileges for the user:group where PHP is running.

* The caching functions will be all private (as there'd be no reason for their to be public, as the cache files should be always handled from the request functions), and are listed below:
	* `checkCached`: this function check if a request was already cached. It simply runs a `file_exists` built-in function over the `convertFilePath` result of the URL+Parameters pair and return the boolean result, indicating if it's cached (TRUE) or not (FALSE).

	* `readCache`: this function read the result of a previous cached request. It simply runs a `file_get_contents` built-in function over the `convertFilePath` result of the URL+Parameters pair and return the file contents. This may cause an E_WARNING if the file is not found, but as this function should be always run after a truly return of the `checkCached`one, it shouldn't be a problem.

	* `writeCache`: this function writes the result of a request to the cache. It simply runs a `file_put_contents` built-in function over the `convertFilePath` result of the URL+Parameters pair, writing the result of the `makeRequest` function to the file.

	* `removeCached`: this function removes a cached result of a request. It simply runs an `unlink` built-in function over the `convertFilePath` result of the URL+Parameters pair.

	* `convertFilePath`: this function converts the pair (URL+Parameters) into a cache filepath. It basically creates the full URL and replace anything out of ranges A-Z, a-z and 0-9 with a hyphen ('-'). This may imply in a problem if the characters that are used to identify the record in the URL (or in the parameters) are outside of the given ranges. Anyway, as it's not defined on the challenge, I'll leave it like this, but it's easy to change it later.

* Future improvements may include:
	* Removing unused cached requests after a while (expiration) for space saving (and even performance stats over file operations).
	* Checking whenever the cache data is outdated, for example a cached `GET` at `/api/users/` that contains all users (including user with ID 2) will *NOT* be updated after a `DELETE` request over `/api/users/2`, which would have deleted user with ID 2.

* In order to test the caching system I created a `tests/` directory that has three different benchmarks, as described below.

### Tests for Caching Function

All the tests were targeted to [this link](https://jsonplaceholder.typicode.com/users/2), which is a freely distributed fake data REST API for Testing and Prototyping. More info can be found at [JSONPlaceholder](https://jsonplaceholder.typicode.com/) page. I have also found [this one](https://reqres.in/api/users/2) which is hosted and distributed freely by [ReqRes](https://reqres.in/).

##### 1. `test1.php`

This test aims to check the response time for `GET` requests with caching enabled. It also runs a `PUT`/`POST`/`PATCH`/`DELETE` after 5 consecutive `GET` requests in order to check if cache is updated (by removing the previous cached version). A random execution returned the following:

	TEST 1: DIRECT ACCESS TO THE SAME URL (https://reqres.in/api/users/2)
	Doing PUT at 'https://reqres.in/api/users/2'... PUT done!
	Iteration #00 - data is present - duration: 122.3261 ms
	Iteration #01 - data is present - duration: 000.1130 ms
	Iteration #02 - data is present - duration: 000.0808 ms
	Iteration #03 - data is present - duration: 000.0770 ms
	Iteration #04 - data is present - duration: 000.0770 ms
	Doing DELETE at 'https://reqres.in/api/users/2'... DELETE done!
	Iteration #05 - data is present - duration: 063.0901 ms
	Iteration #06 - data is present - duration: 000.0448 ms
	Iteration #07 - data is present - duration: 000.0310 ms
	Iteration #08 - data is present - duration: 000.0291 ms
	Iteration #09 - data is present - duration: 000.0298 ms
	Doing DELETE at 'https://reqres.in/api/users/2'... DELETE done!
	Iteration #10 - data is present - duration: 118.3679 ms
	Iteration #11 - data is present - duration: 000.2031 ms
	Iteration #12 - data is present - duration: 000.1490 ms
	Iteration #13 - data is present - duration: 000.1109 ms
	Iteration #14 - data is present - duration: 000.0770 ms
	Doing PUT at 'https://reqres.in/api/users/2'... PUT done!
	Iteration #15 - data is present - duration: 119.0090 ms
	Iteration #16 - data is present - duration: 000.3018 ms
	Iteration #17 - data is present - duration: 000.1318 ms
	Iteration #18 - data is present - duration: 000.0801 ms
	Iteration #19 - data is present - duration: 000.0761 ms
	Doing PATCH at 'https://reqres.in/api/users/2'... PATCH done!
	Iteration #20 - data is present - duration: 124.6879 ms
	Iteration #21 - data is present - duration: 000.1969 ms
	Iteration #22 - data is present - duration: 000.2460 ms
	Iteration #23 - data is present - duration: 000.2720 ms
	Iteration #24 - data is present - duration: 000.2029 ms
	Doing DELETE at 'https://reqres.in/api/users/2'... DELETE done!
	Iteration #25 - data is present - duration: 124.3582 ms
	Iteration #26 - data is present - duration: 000.0491 ms
	Iteration #27 - data is present - duration: 000.0319 ms
	Iteration #28 - data is present - duration: 000.0389 ms
	Iteration #29 - data is present - duration: 000.0470 ms
	Max duration: 124.6879 ms
	Min duration: 000.0291 ms
	Avg duration: 022.4845 ms

As we can see, after a `PUT`/`POST`/`PATCH`/`DELETE` request the response for GET is a lot higher, which indicates it has been downloaded again. The following `GET` requests have a minimum response time (always lower than 1ms) as the result was cached again. This validates the test.

##### 2. `test2.php`

This test aims to compare the difference in response time for `GET` requests with and without caching. A random execution returned the following:

	TEST 2: GET REQUEST TO SAME URL (https://reqres.in/api/users/2) WITH AND WITHOUT CACHE (10 times)
	Iteration #00 - Method: GET | CACHE OFF duration: 069.3800 ms | CACHE ON duration: 000.1099 ms | diff: ~631.2 times lower with cache
	Iteration #01 - Method: GET | CACHE OFF duration: 070.3280 ms | CACHE ON duration: 000.1690 ms | diff: ~416.0 times lower with cache
	Iteration #02 - Method: GET | CACHE OFF duration: 072.9210 ms | CACHE ON duration: 000.1731 ms | diff: ~421.3 times lower with cache
	Iteration #03 - Method: GET | CACHE OFF duration: 067.6901 ms | CACHE ON duration: 000.2000 ms | diff: ~338.4 times lower with cache
	Iteration #04 - Method: GET | CACHE OFF duration: 066.3559 ms | CACHE ON duration: 000.1600 ms | diff: ~414.8 times lower with cache
	Iteration #05 - Method: GET | CACHE OFF duration: 069.5281 ms | CACHE ON duration: 000.1361 ms | diff: ~510.7 times lower with cache
	Iteration #06 - Method: GET | CACHE OFF duration: 074.8742 ms | CACHE ON duration: 000.1578 ms | diff: ~474.4 times lower with cache
	Iteration #07 - Method: GET | CACHE OFF duration: 065.2320 ms | CACHE ON duration: 000.1528 ms | diff: ~426.8 times lower with cache
	Iteration #08 - Method: GET | CACHE OFF duration: 066.9699 ms | CACHE ON duration: 000.2089 ms | diff: ~320.7 times lower with cache
	Iteration #09 - Method: GET | CACHE OFF duration: 070.9691 ms | CACHE ON duration: 000.1938 ms | diff: ~366.1 times lower with cache
	Max duration - CACHE OFF: 074.8742 ms | CACHE ON: 000.2089 ms
	Min duration - CACHE OFF: 065.2320 ms | CACHE ON: 000.1099 ms
	Avg duration - CACHE OFF: 069.4248 ms | CACHE ON: 000.1662 ms
	Total duration - CACHE OFF: 694.2484 ms | CACHE ON: 001.6615 ms

As we can see, the total execution time for 10 `GET` requests without cache is almost 700ms, while with cache enabled (and considering that the URL was already cached) the total response time was below 2ms, a value about 350 times lower. This validates the test.

##### 3. `test3.php`

This test aims to compare the difference in response time for random method requests with and without caching. A random execution returned the following:

	TEST 3: RANDOM REQUEST TO SAME URL (https://jsonplaceholder.typicode.com/users/2) WITH AND WITHOUT CACHE (25 times)
	Iteration #00 - Method: POST | CACHE OFF duration: 649.5082 ms | CACHE ON duration: 702.2390 ms | diff: ~0.1 times greater with cache
	Iteration #01 - Method: PUT | CACHE OFF duration: 697.9880 ms | CACHE ON duration: 653.0139 ms | diff: ~0.1 times lower with cache
	Iteration #02 - Method: PATCH | CACHE OFF duration: 635.0021 ms | CACHE ON duration: 657.2900 ms | diff: ~0.0 times greater with cache
	Iteration #03 - Method: PUT | CACHE OFF duration: 668.2131 ms | CACHE ON duration: 610.2471 ms | diff: ~0.1 times lower with cache
	Iteration #04 - Method: PUT | CACHE OFF duration: 609.0269 ms | CACHE ON duration: 680.6419 ms | diff: ~0.1 times greater with cache
	Iteration #05 - Method: PATCH | CACHE OFF duration: 639.6358 ms | CACHE ON duration: 683.4631 ms | diff: ~0.1 times greater with cache
	Iteration #06 - Method: DELETE | CACHE OFF duration: 720.9291 ms | CACHE ON duration: 636.5111 ms | diff: ~0.1 times lower with cache
	Iteration #07 - Method: POST | CACHE OFF duration: 630.8670 ms | CACHE ON duration: 647.2580 ms | diff: ~0.0 times greater with cache
	Iteration #08 - Method: GET | CACHE OFF duration: 538.7101 ms | CACHE ON duration: 1035.7521 ms | diff: ~0.9 times greater with cache
	Iteration #09 - Method: DELETE | CACHE OFF duration: 585.2189 ms | CACHE ON duration: 707.6881 ms | diff: ~0.2 times greater with cache
	Iteration #10 - Method: DELETE | CACHE OFF duration: 733.5739 ms | CACHE ON duration: 648.4880 ms | diff: ~0.1 times lower with cache
	Iteration #11 - Method: GET | CACHE OFF duration: 527.8730 ms | CACHE ON duration: 614.5759 ms | diff: ~0.2 times greater with cache
	Iteration #12 - Method: GET | CACHE OFF duration: 593.0569 ms | CACHE ON duration: 000.5822 ms | diff: ~1017.6 times lower with cache
	Iteration #13 - Method: POST | CACHE OFF duration: 716.6460 ms | CACHE ON duration: 577.9829 ms | diff: ~0.2 times lower with cache
	Iteration #14 - Method: POST | CACHE OFF duration: 670.0759 ms | CACHE ON duration: 596.6761 ms | diff: ~0.1 times lower with cache
	Iteration #15 - Method: DELETE | CACHE OFF duration: 604.3820 ms | CACHE ON duration: 653.4309 ms | diff: ~0.1 times greater with cache
	Iteration #16 - Method: GET | CACHE OFF duration: 525.9151 ms | CACHE ON duration: 520.8831 ms | diff: ~0.0 times lower with cache
	Iteration #17 - Method: GET | CACHE OFF duration: 15801.5070 ms | CACHE ON duration: 000.1309 ms | diff: ~120720.9 times lower with cache
	Iteration #18 - Method: GET | CACHE OFF duration: 535.1038 ms | CACHE ON duration: 000.0961 ms | diff: ~5568.2 times lower with cache
	Iteration #19 - Method: GET | CACHE OFF duration: 533.4759 ms | CACHE ON duration: 000.1280 ms | diff: ~4165.8 times lower with cache
	Iteration #20 - Method: POST | CACHE OFF duration: 667.2211 ms | CACHE ON duration: 16037.5390 ms | diff: ~23.0 times greater with cache
	Iteration #21 - Method: POST | CACHE OFF duration: 634.4860 ms | CACHE ON duration: 637.9881 ms | diff: ~0.0 times greater with cache
	Iteration #22 - Method: DELETE | CACHE OFF duration: 589.0219 ms | CACHE ON duration: 605.3500 ms | diff: ~0.0 times greater with cache
	Iteration #23 - Method: PUT | CACHE OFF duration: 671.7300 ms | CACHE ON duration: 751.2591 ms | diff: ~0.1 times greater with cache
	Iteration #24 - Method: PATCH | CACHE OFF duration: 599.1092 ms | CACHE ON duration: 627.8431 ms | diff: ~0.0 times greater with cache
	Max duration - CACHE OFF: 15801.5070 ms | CACHE ON: 16037.5390 ms
	Min duration - CACHE OFF: 525.9151 ms | CACHE ON: 000.0961 ms
	Avg duration - CACHE OFF: 1231.1311 ms | CACHE ON: 1171.4823 ms
	Total duration - CACHE OFF: 30778.2772 ms | CACHE ON: 29287.0579 ms

As expected, for data-changing operations (`PUT`/`POST`/`PATCH`/`DELETE`) the response time doesn't change significantly (the #20 iteration was probably affected by another running program or internet oscillation and was the main reason for the small difference on total duration). It only affects subsequent `GET` requests (as seen in iterations #12, #17, #18 and #19). This also validates the test.

## 2. Date formatting

Original text:

> Implement a JavaScript code (on the same file - `date-format.html`) that replaces the date value of all elements (that have `js-date-format` class) with the value of the time passed from now (`new Date()`). Use the following format:
> * 1 second ago OR X seconds ago
> * 1 minute ago OR X minutes ago
> * 1 hour ago OR X hours ago
> * Date in ISO format (original format)
> 
> Example:
> 
> ![Working example](https://i.ibb.co/LnQF3yx/example.gif)
> 
> **Note**: You should use ecmascript 6 features but may not use any framework or add any dependency.

So, the first though is to use [jQuery](https://jquery.com/) for familiarity, however the text says "(...) *but **may not** use any framework or add any dependency*", which would be the case. Anyway, using DOM for selectors manipulation is pretty much straightforward too.

Taking a look at the code and running the page in a browser shows that it runs up to 100 iterations appending the following code to the body at each iteration:

**NOTE**: For the tests and examples below I changed the total iterations limit value to 10 for faster prototyping.

	<div class="js-date-format">2019-05-13T19:51:47.403Z</div>

The goal is to convert the content of the div to the elapsed time from now (basically convert the div content to a valid [Date](https://developer.mozilla.org/pt-BR/docs/Web/JavaScript/Reference/Global_Objects/Date) element and subtract it from the current timestamp), displaying the result int the correct range (seconds, minutes or hours).

First thing is to define the trigger for the data update. It'd be a lot easier if we could just inject a function calling before the div appending to the body (and perhaps even faster), but there's a note on the JS code that says:

	// This will create elements for testing every second
	// Don't change this code if possible

That said, I'm going to try a different approach. I found out that [MutationObserver](https://developer.mozilla.org/en-US/docs/Web/API/MutationObserver) could do the trick. It complies with [DOM standarsd](https://dom.spec.whatwg.org/#interface-mutationobserver) and is compatible with modern browsers, which is satisfied by the ES6 requirement.

So basically I have built a MutationObserver that triggers only on `childList` changes (like append a new element as the original code does), and then it checks if it was an element with `js-date-format` class. If so, it calls an `updateDates` function that iterates through each `js-date-format` div and updates its content.

Due to the nature of the calculation (and to avoid rounding errors) the function first check for a `data-original-value` attribute on the div. If it's not defined, then it means that the element content is still in ISO format, and therefore must be stored on `data-original-value` to be used for the calculation. We then call a function to make the calculation and retrieve it in user friendly way (as requested), that is exposed as the element content.

Another consideration to take is that I'm going to use semicolons on every single line instruction, because even if it's considered optional in ES6 (as there's an automatic semicolon insertion), it still cause improper syntax evaluation as seen in [1](https://stackoverflow.com/questions/34950322/use-of-semicolons-in-es6), [2](https://stackoverflow.com/questions/16664824/no-semicolon-before-is-causing-error-in-javascript) and [3](https://stackoverflow.com/questions/2846283/what-are-the-rules-for-javascripts-automatic-semicolon-insertion-asi).

I had to create two functions for this task:
	* `updateDates`: This function aims to iterate through every 'js-date-format' class element and update its content to the elapsed time from it's creation (that is stored on data-original-value attribute)
	* `displayMillisDiff`: This function is used to convert a milliseconds value (from the calculation of elapsed time) to a more user-friendly value, on a scale that varies from seconds to hours (like 10 seconds ago, 1 minute ago, 2 hours ago, etc). It expects one parameter (the milliseconds value) and returns the string containing the user-friendly interval.

After running the script (with the original 1000ms interval) the result was:

	9 seconds ago
	8 seconds ago
	7 seconds ago
	6 seconds ago
	5 seconds ago
	4 seconds ago
	3 seconds ago
	2 seconds ago
	1 second ago
	0 second ago

And the HTML code of the DIVs was:

	<div class="js-date-format" data-original-value="2019-05-13T22:55:38.569Z">9 seconds ago</div>
	<div class="js-date-format" data-original-value="2019-05-13T22:55:39.507Z">8 seconds ago</div>
	<div class="js-date-format" data-original-value="2019-05-13T22:55:40.507Z">7 seconds ago</div>
	<div class="js-date-format" data-original-value="2019-05-13T22:55:41.506Z">6 seconds ago</div>
	<div class="js-date-format" data-original-value="2019-05-13T22:55:42.507Z">5 seconds ago</div>
	<div class="js-date-format" data-original-value="2019-05-13T22:55:43.507Z">4 seconds ago</div>
	<div class="js-date-format" data-original-value="2019-05-13T22:55:44.507Z">3 seconds ago</div>
	<div class="js-date-format" data-original-value="2019-05-13T22:55:45.507Z">2 seconds ago</div>
	<div class="js-date-format" data-original-value="2019-05-13T22:55:46.507Z">1 second ago</div>
	<div class="js-date-format" data-original-value="2019-05-13T22:55:47.508Z">0 second ago</div>

But hence the original iteration has ended it won't update these values ever again. The challenge statement doesn't say anything about continuous update, but a simple approach for that would be to insert a single line timer that has an interval of 1 second (which is the smaller time scale we're using). It'd look like:

	var timer = setInterval(updateDates, 1000); // call every 1000 milliseconds

In fact, if this approach is valid, we could suppress the whole MutationObserver part, as it would automatically check for every `js-date-format` div at every second. Perhaps the interval should be reduced to avoid small glitches that would display the ISO time (like if both timers are out of sync, and after the original timer insert the DIV there'll be a number of milliseconds before the next update function call).

## 3. Apply style

Original text:
> Implement the CSS code to make the component on `component.html` look like the desired mockup below.
> 
> Mockup:
> 
> ![Desired mockup](https://i.ibb.co/Brh3jXQ/mockup.png)
>	
>**Note #1**: You should use new CSS features and add classes as you need, but try not to change the HTML structure.
>
>**Note #2**: We recommend you try using [BEM](http://getbem.com/introduction/).



During the development, I had the following considerations:

1. The icon and the percentage at the top left header of the article was somewhat painfully to vertical align. The major problem was that the percentage is not enclosed by a `span`, `p` or `div` tag. Instead, it's at the same level as the icon image. This implies in putting an outer style (that will affect both text and the image) and then an inner class for the image, that has to compensate any attribute that was set to the text and affects the image.

2. The linear gradient over the image would be a lot easier (and also A LOT more responsive) if the image source was set as a background for the div. Doing that way the gradient overlay would go into the same class as the background image and would also allow some responsive behavior on different screen sizes (using background-repeat and background-position attributes). The way I made it for the challenge is absolutely not responsive (absolute positioning with hardcoded positions) and was done using a pseudo `::after` element, which also implies on a usage restriction to modern browsers only. A simple change on the body padding breaks it.

3. Minor but to be noted: it was my very first experience with [BEM](http://getbem.com/introduction/) methodology. It's a bit confusing when you're really used to code with Bootstrap - as I was. I recommend a fast read (and to use for reference and doubts) [this article](https://www.smashingmagazine.com/2014/07/bem-methodology-for-small-projects/) and a more hands-on approach on [BEM by Example](https://seesparkbox.com/foundry/bem_by_example). I was particularly fascinated by the possibilities with [i-bem](https://en.bem.info/technologies/classic/i-bem/), a JS BEM library that runs with [jQuery](https://jquery.com/).

4. I freely decided to justify the text inside the footer. It's subjective but I find it a stylish way of presenting a paragraph and nowhere in the challenge was said that I should be restricted to what was presented, but instead "(...) *the CSS code to make the component [...] **look like** the desired mockup*". It still look like for me.

5. I looked up for the font. As Roboto was already referenced in the code, I did assume it for the whole body. I haven't considered a fallback system font as it wasn't a challenge request and I'm assuming that the [Google Fonts repository](https://fonts.google.com/) will be always up. 

Using [BEM](http://getbem.com/introduction/) methodology, I did break the styles into Blocks, Elements and Modifiers, as described below:

* Blocks:
	* `b-body`: Main body block class, just add padding in order to make it easier to see the component.
	* `b-article`: Block that holds the whole component. Here we set just the outer width, font family and font size.
	* `b-header`: Block class for the header, where we set its height and padding.
	* `b-footer`: Block class for the footer. Here we set the padding.

* Elements:
	* `b-header__title`: An element class for the header title, where we set its font weight (to a bolder one).
	* `b-header__legend`: An element class for the header legend, where we set the font weight to a smaller on.
	* `b-header__left`: An element class that stores the left side of the header.
	* `b-header__right`: An element class that stores the right side of the header.
	* `b-header__icon`: An element class to style the icon image (vertical align it to center the text and set its height.
	* `b-article__cover`: According to BEM methodology, we created a MODIFIER class to style the cover image (to set its width.
	* `b-article__button`: Element class of the button inside the article. I haven't considered it a block (as most of the examples does) due to the absolute positioning with pixel coordinates. This makes this class have meaning only being part of the article (can't exist by itself), which makes it an element by definition.
	* `b-article__body`: Element class of the body of the article. It holds the cover image and the button.
	* `b-article__body::after`: Pseudo class of the body of the article. It was the only solution I found to make a gradient over the image element of the HTML. If it was a div with the cover as background-image it'd be easier and responsive (which definitely isn't right now: try changing b-body padding and see what happens). Even though, I tried to make it as much browser-compatible as possible.
	* `b-footer_text`: Element class for the footer text. Here we set the text font-weight (to a light one) and also (not required and self decision) justify its content text.

* Modifiers:
	* `b-article_bg-gray`: A modifier class for background color and border styling on the article.
	* `b-article__button_white`: A modifier class for background and foreground color and border styling on the article.
	* `b-article__button_white:hover`: Event class for mouse hover over the button. It wasn't requested, but I think it was expected.

**NOTE**: As a [BEM convention](https://www.smashingmagazine.com/2014/07/bem-methodology-for-small-projects/) I did create every class using the Block/Element/Modifier methodology instead of using direct tag selector and multi-level class chaining.

The final `component.html` rendering (at right) compared to the request mockup (at left) are shown on image below:

![Result comparison](https://i.imgur.com/iZD0ek5.png)

## 4. Question

Original text:

> Send us your answer for the following question:
> 
> What are the main HTTP verbs used in REST applications and what are they meant for?
> 

These are the HTTP verbs used in REST applications:

* `GET`: meant to retrieve single or multiple elements data. For example, a `GET` on `/users/` will most likely retrieve a list of users and some (or all) of their data, while a `GET` on `/users/23` is most likely going to retrieve just the data of the user with ID 23. 
In my experience, I don't see a unique pattern on URL contruction: while some use the singular `/user/` on both single and multiple requests, others prefer to use `/users/` for both or even use `/user/{id}` for single and `/users/` for multiple. What is common in this case is to send the ID as a query (URL) parameter and empty request content.

* `POST`: meant to create an element. For example, a `POST` on `/users/` will most likely create a new user, with the data being served in the request content. Usually a JSON single/multi level associative array.

* `PUT`/`PATCH`: both are meant to update an element.  However the main definition of `PUT` request is to update the whole body content only if the record already exists. Otherwise it'll create a new one with the given data. It makes the `PUT` request idempotent as several unfiltered clicks on submit by the user on a form won't make duplicate records, for instance. Also from definition, `PATCH` would then be used for partial update on existing records. 
In my experience projects usually adopt `PUT` for update operations - passing the ID(s) over the query and the fields names and data on the body, - while `POST` is left for creating new ones - with fields names and data on the body and no query parameters - due to the indexing on databases.

* `DELETE`: as the most self-explanatory request, it's meant to remove a single or multiple record. In my experience it is usually sent with ID(s) on the query and empty content.