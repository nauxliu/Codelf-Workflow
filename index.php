<?php

use Alfred\Workflow;

class CodeLfWorkflow
{
    private $query = null;
    private $workflow = null;
    private $translate_api = "http://fanyi.youdao.com/openapi.do?keyfrom=Codelf&key=2023743559&type=data&doctype=json&version=1.1&q=";

    public function __construct($query)
    {
        $this->query = $query;
        $this->workflow = new Workflow('me.naux.codelf');
    }

    public function run()
    {
        if(!$this->query){
            $this->workflow->result(array(
                'arg' => 'http://github.com/nauxliu',
                'title'        => "Search for '...'",
            ));
            $this->done();
        }

        if (!self::containChinese($this->query)) { 
            $this->searchFor($this->query);
            $this->done();
        }

        $trans_result = $this->translate($this->query);
        $result = [];

        array_walk_recursive($trans_result, function ($x) use (&$result) {
            foreach (explode(' ', $x) as $value) {
                if(CodeLfWorkflow::containChinese($value) || is_numeric($value)){
                    return;
                }
                $result[] = $value;
            }
        });

        foreach ($this->uniqueAndSort($result) as $item) {
            $this->searchFor($item);
        }

        $this->done();
    }

    private function searchFor($word){
        $this->workflow->result(array(
            'arg'          => 'http://unbug.github.io/codelf/#'. $this->query,
            'title'        => $word,
            'subtitle'     => "Search for '{$word}'",
        ));
    }

    private function uniqueAndSort($datas){
        $results = [];

        foreach ($datas as $item) {
            if(isset($results[$item])){
                $results[$item] += 1;
            }else{
                $results[$item] = 0;
            }
        }

        arsort($results);

        return array_unique(array_keys($results));
    }

    private function translate($str){
        $str = trim($str);
        $handle = fopen($this->translate_api . urlencode($str), 'r');
        $contents = '';

        while (!feof($handle)) {
            $contents .= fread($handle, 8192);
        }

        return json_decode($contents, true);
    }

    public static function containChinese($str){
        return preg_match("/[\x7f-\xff]/", $str);
    }

    private function done(){
        echo $this->workflow->toXML(); exit;
    }
 }
