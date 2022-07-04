#! /bin/sh
PRO_NAME=planetary/think
CMD="php /www/wwwroot/planetary/think websocket 7999"
NUM=`ps aux | grep -w ${PRO_NAME} | grep -v grep |wc -l`
#少于1，重启进程
if [ "${NUM}" -lt "1" ];then
    $CMD
#大于1，杀掉所有进程，重启
elif [ "${NUM}" -gt "1" ];then
    killall -9 $PRO_NAME
    $CMD
fi