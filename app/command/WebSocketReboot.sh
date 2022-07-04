#! /bin/sh
PRO_NAME=planetary/think
CMD="php /www/wwwroot/planetary/think websocket 7999"
PID=`pgrep -f "${PRO_NAME}"`
#杀掉所有进程，重启
if [ -n $PID ];then
    #echo $PID
    kill -9 $PID
fi
$CMD