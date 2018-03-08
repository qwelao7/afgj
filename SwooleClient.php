<?php
$client = new swoole_client(SWOOLE_SOCK_TCP | SWOOLE_KEEP);
$client->connect('127.0.0.1', 9501, 1);

//$client->send(json_encode(['action'=>'demo','param'=>['aaa','b']]));
$isbn='9787506036597';
$donate_code='90600';
$library_id=1;
$qr_code="book0000002";
$client->send(json_encode(['action'=>'spider-book-by-isbn','param'=>[$isbn,$donate_code,$library_id,$qr_code]]));