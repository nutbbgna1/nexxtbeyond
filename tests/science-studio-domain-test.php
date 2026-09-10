<?php
declare(strict_types=1);
require_once __DIR__.'/../science-studio/lib/domain.php';
require_once __DIR__.'/../science-studio/lib/store.php';
require_once __DIR__.'/../science-studio/lib/gemini.php';

function check(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
function node(string $title): array { return ['title'=>$title,'objective'=>'เข้าใจ '.$title]; }

$project=sciNewProject([
    'title'=>'เคมีพื้นฐาน','branches'=>['chemistry'],'grade'=>'ม.4','foundation'=>'เริ่มต้น',
    'topic'=>'โครงสร้างอะตอม','goal'=>'อธิบายองค์ประกอบของอะตอมได้','difficulty'=>'ปานกลาง',
    'sessionMinutes'=>30,'questionsPerEp'=>3,
]);
check(sciNext($project)['kind']==='chapters','งานแรกต้องสร้างบท');
sciApply($project,sciNext($project),['items'=>[node('อะตอม')]]);
check(sciNext($project)['kind']==='episodes','ต้องสร้าง EP หลังบท');
sciApply($project,sciNext($project),['items'=>[node('อนุภาคมูลฐาน')]]);
check(sciNext($project)['kind']==='parts','ต้องสร้าง Part หลัง EP');
sciApply($project,sciNext($project),['items'=>[['title'=>'โปรตอน','objective'=>'อธิบายโปรตอนได้','minutes'=>60]]]);
check(sciNext($project)===null && sciStage($project)==='รอตรวจโครง','ต้องรอครูตรวจโครง');
$project['outlineApproved']=true;
$task=sciNext($project); check($task['kind']==='content','ต้องสร้างเนื้อหาหลังยืนยันโครง');
check(sciSplit($project,$task) && count($project['chapters'][0]['episodes'][0]['parts'])===2,'ต้องแบ่ง Part ที่เกิน Token');

foreach ($project['chapters'][0]['episodes'][0]['parts'] as $index=>$part) {
    $task=sciNext($project);
    sciApply($project,$task,['sections'=>[['heading'=>'แนวคิด','body'=>'เนื้อหาวิทยาศาสตร์']],'example'=>'ตัวอย่าง',
        'exercise'=>'โจทย์','hint'=>'คำใบ้','solution'=>'เฉลย','summary'=>'สรุป']);
    $project['chapters'][0]['episodes'][0]['parts'][$index]['approved']=true;
}
$task=sciNext($project); check($task['kind']==='quiz' && $task['count']===2,'ต้องกระจายข้อสอบตาม Part');
$partId=$project['chapters'][0]['episodes'][0]['parts'][$task['p']]['id'];
sciApply($project,$task,['questions'=>array_map(static fn($i)=>[
    'question'=>'คำถาม '.$i,'options'=>['ก','ข','ค','ง'],'answer'=>0,'explanation'=>'เพราะ ก ถูก',
    'partId'=>$partId,'objective'=>'ตรวจความเข้าใจ'],range(1,2))]);
$task=sciNext($project); check($task['kind']==='quiz' && $task['count']===1 && $task['p']===1,'ต้องสร้างข้อสอบของ Part ถัดไป');
$partId=$project['chapters'][0]['episodes'][0]['parts'][$task['p']]['id'];
sciApply($project,$task,['questions'=>[['question'=>'คำถาม 3','options'=>['ก','ข','ค','ง'],'answer'=>0,
    'explanation'=>'เพราะ ก ถูก','partId'=>$partId,'objective'=>'ตรวจความเข้าใจ']]]);
check(sciStage($project)==='พร้อมเรียน','หลักสูตรที่ครบต้องพร้อมเรียน');

$schedule=sciSchedule($project,['start'=>'2026-09-09','days'=>[3,5],'minutes'=>30,'deadline'=>'2026-09-09']);
check(count($schedule['items'])>=4,'แผนต้องมี Part ทบทวน และข้อสอบ');
check($schedule['overdue']===true,'ต้องเตือนเมื่อเรียนไม่ทันเป้าหมาย');
check(count(array_unique(array_column($schedule['items'],'date')))>1,'เนื้อหายาวต้องแบ่งข้ามวัน');

$db=new PDO('sqlite::memory:');
$store=new ScienceStore($db,1); $row=$store->create(sciNewProject([
    'title'=>'ฟิสิกส์','branches'=>['physics'],'grade'=>'ม.ต้น','foundation'=>'เริ่มต้น','topic'=>'แรง',
    'goal'=>'อธิบายแรง','difficulty'=>'พื้นฐาน','sessionMinutes'=>30,'questionsPerEp'=>3,
]));
$running=$store->state($row['id'],'running');
$claimed=$store->claim($row['id']);
check($claimed!==null && $store->claim($row['id'])===null,'lease ต้องให้หนึ่ง worker เท่านั้น');
$claimed['data']['history'][]=['status'=>'success'];
$saved=$store->finish($claimed,$claimed['data'],true);
check($saved['revision']===1 && $saved['run_state']==='paused','ต้องบันทึกผลและหยุดคิวได้');

$queue=$store->create(sciNewProject([
    'title'=>'ชีววิทยา','branches'=>['biology'],'grade'=>'ม.ต้น','foundation'=>'เริ่มต้น','topic'=>'เซลล์',
    'goal'=>'อธิบายเซลล์','difficulty'=>'พื้นฐาน','sessionMinutes'=>30,'questionsPerEp'=>3,
]));
$store->state($queue['id'],'running');
$queue=sciRun($store,$queue['id'],static fn($data,$task)=>[
    'data'=>['items'=>[node('โครงสร้างเซลล์')]],'model'=>'mock','usage'=>['totalTokenCount'=>100],
]);
check(count($queue['data']['chapters'])===1 && $queue['run_state']==='running','หนึ่ง worker ต้องบันทึกหนึ่งงานแล้วคิวทำต่อ');
$queue=sciRun($store,$queue['id'],static function() { throw new RuntimeException('จำลองข้อผิดพลาด'); });
check($queue['run_state']==='paused' && $queue['data']['error']==='จำลองข้อผิดพลาด','งานล้มเหลวต้องหยุดโดยเก็บผลก่อนหน้า');

echo "Science Studio domain tests passed\n";
