pid=`ps -ef | grep nginx | grep -v grep | awk '{print $2}'`
echo $pid
kill -9 $pid
rm -rf /tmp/redis_pool.sock
rm -rf /var/run/mysql_sock/mysql_user_pool.sock
/etc/init.d/nginx start
/usr/local/openresty.1.13/nginx/sbin/nginx -p ~/openresty-pool
