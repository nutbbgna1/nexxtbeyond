<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit(1);
require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../science-studio/lib/store.php';

$owner=(int)$pdo->query("SELECT id FROM users WHERE role='admin' AND is_active=1 ORDER BY id LIMIT 1")->fetchColumn();
if (!$owner) throw new RuntimeException('No active admin');
$store=new ScienceStore($pdo,$owner);
$mode=$argv[1]??'';
if ($mode==='delete') {
    $id=$argv[2]??''; $row=$store->get($id);
    if ($row['data']['config']['title']!=='__Science Studio Browser Test__') throw new RuntimeException('Not a browser fixture');
    $q=$pdo->prepare('DELETE FROM science_projects WHERE id=? AND owner_id=?'); $q->execute([$id,$owner]);
    echo "fixture deleted\n"; exit;
}
if ($mode!=='create') throw new InvalidArgumentException('Use create or delete ID');
$p=sciNewProject(['title'=>'__Science Studio Browser Test__','branches'=>['chemistry'],'grade'=>'ม.4',
    'foundation'=>'เริ่มต้น','topic'=>'อะตอม','goal'=>'เข้าใจอนุภาคมูลฐาน','difficulty'=>'พื้นฐาน',
    'sessionMinutes'=>30,'questionsPerEp'=>3]);
sciOutline($p,[['title'=>'โครงสร้างอะตอม','objective'=>'อธิบายองค์ประกอบของอะตอม','episodes'=>[
    ['title'=>'อนุภาคมูลฐาน','objective'=>'เปรียบเทียบโปรตอน นิวตรอน และอิเล็กตรอน','parts'=>[
        ['title'=>'โปรตอนและเลขอะตอม','objective'=>'เชื่อมโยงจำนวนโปรตอนกับเลขอะตอม','minutes'=>30],
    ]],
]]]);
$p['outlineApproved']=true;
$task=sciNext($p);
sciApply($p,$task,['sections'=>[
    ['heading'=>'โปรตอนคืออะไร','body'=>'โปรตอนเป็นอนุภาคที่มีประจุบวกและอยู่ในนิวเคลียสของอะตอม'],
    ['heading'=>'เลขอะตอม','body'=>'เลขอะตอมเท่ากับจำนวนโปรตอนในนิวเคลียสและใช้ระบุชนิดของธาตุ'],
],'example'=>'ธาตุคาร์บอนมีเลขอะตอม 6 จึงมีโปรตอน 6 ตัว','exercise'=>'อะตอมที่มีโปรตอน 8 ตัวคือธาตุใด',
    'hint'=>'ดูเลขอะตอมในตารางธาตุ','solution'=>'เลขอะตอม 8 คือออกซิเจน','summary'=>'จำนวนโปรตอนกำหนดเลขอะตอมและชนิดของธาตุ']);
$p['chapters'][0]['episodes'][0]['parts'][0]['approved']=true;
$partId=$p['chapters'][0]['episodes'][0]['parts'][0]['id'];
sciApply($p,sciNext($p),['questions'=>array_map(static fn($i)=>[
    'question'=>'ข้อใดอธิบายความสัมพันธ์ระหว่างโปรตอนและเลขอะตอมได้ถูกต้อง '.$i,
    'options'=>['เลขอะตอมเท่ากับจำนวนโปรตอน','เลขอะตอมเท่ากับจำนวนนิวตรอน','โปรตอนไม่มีประจุ','โปรตอนอยู่นอกนิวเคลียส'],
    'answer'=>0,'explanation'=>'เลขอะตอมของธาตุกำหนดจากจำนวนโปรตอน','partId'=>$partId,'objective'=>'เชื่อมโยงโปรตอนกับเลขอะตอม',
],range(1,3))]);
$p['schedule']=sciSchedule($p,['start'=>'2026-09-09','days'=>[3,5],'minutes'=>30,'deadline'=>'2026-09-11']);
echo $store->create($p)['id']."\n";
