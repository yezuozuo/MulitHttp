<?php
/**
 * Created by PhpStorm.
 * User: zoco
 * Date: 16/8/18
 * Time: 16:02
 */

use GuzzleHttp\Client;
use GuzzleHttp\Pool;

require __DIR__.'/vendor/autoload.php';

class Request {
    private $totalPageCount;
    private $counter        = 1;
    private $concurrency    = 7;

    private $users = ['yezuozuo', 'appleboy', 'Aufree', 'lifesign', 'overtrue', 'zhengjinghua', 'NauxLiu'];

    protected $signature = 'test:multithreading-request';
    protected $description = 'Command description';

    public function handle() {
        $this->totalPageCount = count($this->users);

        $client = new Client();

        $requests = function ($total) use ($client) {
            foreach ($this->users as $key => $user) {

                $uri = 'https://api.github.com/users/' . $user;
                yield function() use ($client, $uri) {
                    return $client->getAsync($uri);
                };
            }
        };

        $pool = new Pool($client, $requests($this->totalPageCount), [
            'concurrency' => $this->concurrency,
            'fulfilled'   => function ($response, $index){

                $res = json_decode($response->getBody()->getContents());

                echo ("请求第 $index 个请求，用户 " . $this->users[$index] . " 的 Github ID 为：" .$res->id);

            },
            'rejected' => function ($reason, $index){
                echo "rejected";
            },
        ]);

        // 开始发送请求
        $promise = $pool->promise();
        $promise->wait();
        if ($this->counter < $this->totalPageCount){
            $this->counter++;
            return;
        }
    }
}

$test = new Request();
$test->handle();