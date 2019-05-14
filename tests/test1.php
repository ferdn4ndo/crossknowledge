<?

include(__DIR__."/../request.php");

/*
 * TEST 1: DIRECT ACCESS TO THE SAME URL
 */

$url = "https://jsonplaceholder.typicode.com/users/2";
printf("TEST 1: DIRECT ACCESS TO THE SAME URL (%s)<br>\n",$url);
$totalRequests = 30;
$durations = [];
$operations = ['PUT','POST','DELETE','PATCH'];
$loopStartTime = microtime(true);
for ($i=0; $i < $totalRequests; $i++) { 
	# At every 5th request
	if($i % 5 == 0){
		$opIdx = mt_rand(0,count($operations)-1);
		printf("Doing %s at '%s'... ",$operations[$opIdx],$url);
		switch ($operations[$opIdx]) {
			case 'DELETE': SimpleJsonRequest::delete($url); break;
			case 'PUT': SimpleJsonRequest::put($url,null,["name"=>"morpheus","job"=>"zion resident"]); break;
			case 'PATCH': SimpleJsonRequest::patch($url,null,["name"=>"morpheus","job"=>"zion resident"]); break;
			case 'POST': SimpleJsonRequest::post($url,null,["name"=>"morpheus","job"=>"zion resident"]); break;
		}
		printf("%s done!<br>\n",$operations[$opIdx]);
	}
	$iterationStartTime = microtime(true);
	$Result = SimpleJsonRequest::get($url);
	$iterationEndtime = microtime(true);
	$iterationDuration = ($iterationEndtime - $iterationStartTime)*1000;
	$durations[] = $iterationDuration;
	printf("Iteration #%02d - data is %s - duration: %08.4f ms<br>\n",$i,(isset($Result['data'])?'present':'<b>NOT</b> present'),$iterationDuration);
}
$loopEndTime = microtime(true);
printf("Max duration: %08.4f ms<br>\n",max($durations));
printf("Min duration: %08.4f ms<br>\n",min($durations));
printf("Avg duration: %08.4f ms<br>\n",(array_sum($durations)/count($durations)));