<?php
declare (strict_types = 1);

namespace app\middleware;

use think\facade\Log;
use think\Request;

class RequestLog
{
    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return Response
     */
    public function handle(\think\Request $request, \Closure $next)
    {
	    $requestInfo = [
		    'ip' => $request->ip(),
		    'method' => $request->method(),
		    'host' => $request->host(),
		    'uri' => $request->url(),
	    ];
	    $logInfo = [
		    "{$requestInfo['ip']} {$requestInfo['method']} {$requestInfo['host']}{$requestInfo['uri']}",
		    '[ HEADER ] ' . var_export($request->header(), true),
		    '[ PARAM ] ' . var_export($request->param(), true),
		    '---------------------------------------------------------------',
	    ];
	    $logInfo = implode(PHP_EOL, $logInfo) . PHP_EOL;
	    Log::record($logInfo, 'request');
		
	    return $next($request);
    }
}
