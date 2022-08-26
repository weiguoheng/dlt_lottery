<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;


class TestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:start {type=get}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->{$this->argument('type')}();
    }
    private function pull()
    {
        $data = file_get_contents("https://webapi.sporttery.cn/gateway/lottery/getHistoryPageListV1.qry?gameNo=85&provinceId=0&pageSize=3000&isVerify=1&pageNo=1");
        $data = file_put_contents("numHistory.csv", $data);
        echo 'success';
    }
    // 获取概率
    private function print($type='dump')
    {
        $data = file_get_contents("numHistory.csv");
        $frontPool = [];
        $backendPool = [];
        foreach(json_decode($data)->value->list as $item) {
            $numArr = explode(" ", $item->lotteryDrawResult);
            foreach($numArr as $key=>$num) {
                $num = intval($num);
                if($key<5) {
                    if(isset($frontPool[$num])) {
                        $frontPool[$num]++;
                    } else {
                        $frontPool[$num] = 1;
                    }    
                } else {
                    if(isset($backendPool[$num])) {
                        $backendPool[$num]++;
                    } else {
                        $backendPool[$num] = 1;
                    }    
                }
            }
        }
        ksort($frontPool);
        ksort($backendPool);
        if($type=='dump'){
            dump($frontPool);
            dump($backendPool);    
        } else {
            return [$frontPool,$backendPool];
        }
    }
    // 随机获取选号
    private function get()
    {
        list($front,$back) = $this->print('get');
        // 获取概率最低的一组号码
        $frontNum = $this->getNum($front, 5);
        $backNum = $this->getNum($back, 2);
        echo '出现概率最低的一组号码'; echo PHP_EOL;
        dump($frontNum. ' '.$backNum);
        echo '根据以往概率的一组号码'; echo PHP_EOL;
        $frontRandNum = $this->getRand($front, 5);
        $backRandNum = $this->getRand($back, 2);
        dump($frontRandNum. ' '.$backRandNum);
    }
    private function getNum($data, $length=5)
    {
        $total = array_sum($data);
        $probability = [];
        foreach ($data as $key => $value) {
            $probability[] = ['n'=>$key, 'p'=>(round(($value*100)/$total, 10))];
        }
        // 获取出现概率最低的号码
        $lowNum = [];
        foreach($probability as $num) {
            $lowNum[] = $num['p'];
        }
        array_multisort($lowNum, SORT_ASC, $probability);
        $lowProbabilityData = array_slice($probability, 0, $length);
        $lowProbabilityNumArr = [];
        foreach($lowProbabilityData as $item) {
            $lowProbabilityNumArr[] = $item['n']<10?'0'.$item['n']:$item['n'];
        }
        sort($lowProbabilityNumArr);
        return(implode(" ", $lowProbabilityNumArr));
    }
    
    private function getRand($data, $length=5)
    {
        $total = array_sum($data);
        $randNum = mt_rand(0,$total-1);
        $pool = [];
        $num = [];
        foreach($data as $k=>$v) {
            for($j=0;$j<$v;$j++) {
                $pool[] = $k;
            }
        }
        for($a=0;$a<$length;$a++) {
            $theNum = $pool[array_rand($pool, 1)];
            $num[] = $theNum<10?'0'.$theNum:$theNum;
            foreach($pool as $key=>$numItem) {
                if($numItem == $theNum) {
                    unset($pool[$key]);
                }
            }
        }
        
        return implode(" ", $num);
    }
}
