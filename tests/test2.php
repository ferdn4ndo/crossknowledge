<?

include(__DIR__."/../request.php");
include(__DIR__."/../request.php.old");

$url = "https://jsonplaceholder.typicode.com/users/2";
$totalRequests = 10;
$operations = ['GET','PUT','POST','DELETE','PATCH'];

/*
 * TEST 2: GET REQUEST TO SAME URL WITH AND WITHOUT CACHE
 */
printf("TEST 2: GET REQUEST TO SAME URL (%s) WITH AND WITHOUT CACHE (%d times)<br>\n",$url,$totalRequests);
$durationsWithCache = [];
$durationsWithoutCache = [];
for ($i=0; $i < $totalRequests; $i++) { 
	#TEST 2.1: GET REQUEST TO SAME URL WITHOUT CACHE
	$withoutCacheStartTime = microtime(true);
	$Result = OldSimpleJsonRequest::get($url);
	$withoutCacheEndtime = microtime(true);
	$withoutCacheDuration = ($withoutCacheEndtime - $withoutCacheStartTime)*1000;
	$durationsWithoutCache[] = $withoutCacheDuration;
	#TEST 2.2: GET REQUEST TO SAME URL WITH CACHE
	$withCacheStartTime = microtime(true);
	$Result = SimpleJsonRequest::get($url);
	$withCacheEndtime = microtime(true);
	$withCacheDuration = ($withCacheEndtime - $withCacheStartTime)*1000;
	$durationsWithCache[] = $withCacheDuration;

	$percentualDiff = ($withCacheDuration > $withoutCacheDuration) ? sprintf("~%03.1f times greater",(($withCacheDuration/$withoutCacheDuration)-1)) : sprintf("~%03.1f times lower",(($withoutCacheDuration/$withCacheDuration)-1));
	printf("Iteration #%02d - Method: GET | CACHE <b>OFF</b> duration: %08.4f ms | CACHE <b>ON</b> duration: %08.4f ms | diff: %s with cache<br>\n",$i,$withoutCacheDuration,$withCacheDuration,$percentualDiff);
}
printf("Max duration - CACHE <b>OFF</b>: %08.4f ms | CACHE <b>ON</b>: %08.4f ms<br>\n",max($durationsWithoutCache),max($durationsWithCache));
printf("Min duration - CACHE <b>OFF</b>: %08.4f ms | CACHE <b>ON</b>: %08.4f ms<br>\n",min($durationsWithoutCache),min($durationsWithCache));
printf("Avg duration - CACHE <b>OFF</b>: %08.4f ms | CACHE <b>ON</b>: %08.4f ms<br>\n",array_sum($durationsWithoutCache)/$totalRequests,array_sum($durationsWithCache)/$totalRequests);
printf("Total duration - CACHE <b>OFF</b>: %08.4f ms | CACHE <b>ON</b>: %08.4f ms<br>\n",array_sum($durationsWithoutCache),array_sum($durationsWithCache));
