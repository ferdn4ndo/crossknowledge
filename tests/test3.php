<?

include(__DIR__."/../request.php");
include(__DIR__."/../request.php.old");

$url = "https://jsonplaceholder.typicode.com/users/2";
$totalRequests = 25;
$operations = ['GET','PUT','POST','DELETE','PATCH'];

/*
 * TEST 3: RANDOM REQUEST TO SAME URL WIT AND WITHOUT CACHE
 */
printf("TEST 3: RANDOM REQUEST TO SAME URL (%s) WITH AND WITHOUT CACHE (%d times)<br>\n",$url,$totalRequests);
$durations = [];
for ($i=0; $i < $totalRequests; $i++) { 
	#Generate a random operation from list
	$opIdx = mt_rand(0,count($operations)-1);
	#TEST 3.1: RANDOM REQUEST TO SAME URL WITHOUT CACHE
	$withoutCacheStartTime = microtime(true);
	switch ($operations[$opIdx]) {
		case 'GET': OldSimpleJsonRequest::get($url); break;
		case 'DELETE': OldSimpleJsonRequest::delete($url); break;
		case 'PUT': OldSimpleJsonRequest::put($url,null,["name"=>"morpheus","job"=>"zion resident"]); break;
		case 'PATCH': OldSimpleJsonRequest::patch($url,null,["name"=>"morpheus","job"=>"zion resident"]); break;
		case 'POST': OldSimpleJsonRequest::post($url,null,["name"=>"morpheus","job"=>"zion resident"]); break;
	}
	$withoutCacheEndtime = microtime(true);
	$withoutCacheDuration = ($withoutCacheEndtime - $withoutCacheStartTime)*1000;
	$durationsWithoutCache[] = $withoutCacheDuration;
	#TEST 3.2: RANDOM REQUEST TO SAME URL WITH CACHE
	$withCacheStartTime = microtime(true);
	switch ($operations[$opIdx]) {
		case 'GET': SimpleJsonRequest::get($url); break;
		case 'DELETE': SimpleJsonRequest::delete($url); break;
		case 'PUT': SimpleJsonRequest::put($url,null,["name"=>"morpheus","job"=>"zion resident"]); break;
		case 'PATCH': SimpleJsonRequest::patch($url,null,["name"=>"morpheus","job"=>"zion resident"]); break;
		case 'POST': SimpleJsonRequest::post($url,null,["name"=>"morpheus","job"=>"zion resident"]); break;
	}
	$withCacheEndtime = microtime(true);
	$withCacheDuration = ($withCacheEndtime - $withCacheStartTime)*1000;
	$durationsWithCache[] = $withCacheDuration;
	#Percentual difference
	$percentualDiff = ($withCacheDuration > $withoutCacheDuration) ? sprintf("~%03.1f times greater",(($withCacheDuration/$withoutCacheDuration)-1)) : sprintf("~%03.1f times lower",(($withoutCacheDuration/$withCacheDuration)-1));
	printf("Iteration #%02d - Method: %s | CACHE <b>OFF</b> duration: %08.4f ms | CACHE <b>ON</b> duration: %08.4f ms | diff: %s with cache<br>\n",$i,$operations[$opIdx],$withoutCacheDuration,$withCacheDuration,$percentualDiff);
}
printf("Max duration - CACHE <b>OFF</b>: %08.4f ms | CACHE <b>ON</b>: %08.4f ms<br>\n",max($durationsWithoutCache),max($durationsWithCache));
printf("Min duration - CACHE <b>OFF</b>: %08.4f ms | CACHE <b>ON</b>: %08.4f ms<br>\n",min($durationsWithoutCache),min($durationsWithCache));
printf("Avg duration - CACHE <b>OFF</b>: %08.4f ms | CACHE <b>ON</b>: %08.4f ms<br>\n",array_sum($durationsWithoutCache)/$totalRequests,array_sum($durationsWithCache)/$totalRequests);
printf("Total duration - CACHE <b>OFF</b>: %08.4f ms | CACHE <b>ON</b>: %08.4f ms<br>\n",array_sum($durationsWithoutCache),array_sum($durationsWithCache));
