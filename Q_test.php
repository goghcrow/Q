<?php
namespace Xiaofeng;
error_reporting(E_ALL);

require __DIR__ . "/Q.php";

$R = [
    ["id"=>1, "name"=>"a"],
    ["id"=>2, "name"=>"b1"],
    ["id"=>2, "name"=>"b2"],
    ["id"=>3, "name"=>"c"],
];

$S = [
    ["id"=>1, "sex"=>1, "name"=>"sa"],
    ["id"=>2, "sex"=>0, "name"=>"sb"],
    ["id"=>2, "sex"=>1, "name"=>"sc"],
];

// Traversable
// $table = Q::from($R)->setAlias("a")->setKeyColumn("id")->join(Q::from($S, "id", "b"));
// foreach($table as $row) {
//     var_export($row);
// }

// invoke -> ArrayIterator
// $table = Q::from($R)->setAlias("a")->setKeyColumn("id")->join(Q::from($S, "id", "b"));
// $tableArr = $table();
// var_export($tableArr[0]);
// echo count($tableArr);

// any
// echo Q::from($R)->setAlias("a")->setKeyColumn("id")->join(Q::from($S, "id", "b"))->any(function($r) {
//     return $r["sex"] === 1;
// });

// all
// echo Q::from($R)->setAlias("a")->setKeyColumn("id")->join(Q::from($S, "id", "b"))->all(function($r) {
//     return $r["sex"] === 1;
// });

// where
// echo Q::from($R)->setAlias("a")->setKeyColumn("id")->join(Q::from($S, "id", "b"))
//    ->where(function($r, $join) {
//     return $r["sex"] === 0;
// });

// select
// echo Q::from($R)->setAlias("a")->setKeyColumn("id")->join(Q::from($S, "id", "b"))->select(["a.id"=>"id", "a.name", "b.sex"]);

// auto setKeyColumn
// $q = Q::from($R)->setAlias("a")->setKeyColumn("id")->join(Q::from($S, "id", "b"));
// echo $q->getKeyColumn() . PHP_EOL;
// echo $q->select(["a.id"=>"id", "a.name"=>"name", "sex"])->getKeyColumn() . PHP_EOL;

// orderby
// echo Q::from($R)->setAlias("a")->setKeyColumn("id")->join(Q::from($S, "id", "b"))->orderBy(function($a, $b) {
//     return ($a["b.name"] === $b["b.name"] ? 0 : ($a["b.name"] < $b["b.name"] ? -1 : 1));
// });

// limit
// echo Q::from($R)->setAlias("a")->setKeyColumn("id")->join(Q::from($S, "id", "b"))->orderBy(function($a, $b) {
//     return ($a["b.name"] === $b["b.name"] ? 0 : ($a["b.name"] < $b["b.name"] ? -1 : 1));
// })->limit(2);


// echo Q::from($R)->setKeyColumn("id")->join(Q::from($S, "id"));
// echo Q::from($R)->setAlias("a")->setKeyColumn("id")->join(Q::from($S, "id", "b"));
// echo Q::from($R)->setAlias("a")->setKeyColumn("id")->leftJoin(Q::from($S, "id", "b"));
// echo Q::from($R)->setAlias("a")->setKeyColumn("id")->rightJoin(Q::from($S, "id", "b"));
