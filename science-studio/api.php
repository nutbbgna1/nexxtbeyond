<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__.'/../admin/includes/access.php';
require_once __DIR__.'/../includes/ai-settings.php';
require_once __DIR__.'/lib/store.php';
require_once __DIR__.'/lib/gemini.php';
require_once __DIR__.'/../includes/ai-image-service.php';

function sciReply(array $data,int $status=200): void {
    http_response_code($status); echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR); exit;
}
function sciPartImagePrompt(array $data, array $task, array $result): string {
    $prompt=trim((string)($result['imagePrompt']??''));
    if ($prompt!=='') return $prompt;
    if (($task['kind']??'')!=='content') return '';
    $part=$data['chapters'][$task['c']]['episodes'][$task['e']]['parts'][$task['p']]??null;
    if (!is_array($part)) return '';
    $title=trim((string)($part['title']??''));
    $objective=trim((string)($part['objective']??''));
    return trim('Clean accurate educational science diagram for Thai grade level. Topic: '.$title.'. Learning objective: '.$objective.'. Show the main scientific structure or process clearly, with simple visual hierarchy and no decorative background.');
}
function sciFallbackIllustration(array $data, array $task, string $prompt, string $reason): array {
    $part=$data['chapters'][$task['c']]['episodes'][$task['e']]['parts'][$task['p']]??[];
    $title=trim((string)($part['title']??'ภาพประกอบวิทยาศาสตร์'));
    $objective=trim((string)($part['objective']??''));
    $topic=mb_strtolower($title.' '.$objective.' '.$prompt);
    $safeTitle=htmlspecialchars($title,ENT_QUOTES,'UTF-8');
    $safeObjective=htmlspecialchars(mb_substr($objective,0,95),ENT_QUOTES,'UTF-8');
    $shape='<circle cx="330" cy="210" r="30" fill="#2563eb"/><circle cx="330" cy="210" r="14" fill="#f97316"/><ellipse cx="330" cy="210" rx="170" ry="55" fill="none" stroke="#60a5fa" stroke-width="3"/><ellipse cx="330" cy="210" rx="118" ry="118" fill="none" stroke="#a7f3d0" stroke-width="3"/><ellipse cx="330" cy="210" rx="55" ry="170" fill="none" stroke="#f9a8d4" stroke-width="3"/><circle cx="500" cy="210" r="10" fill="#0f172a"/><circle cx="330" cy="92" r="10" fill="#0f172a"/><circle cx="275" cy="360" r="10" fill="#0f172a"/><text x="42" y="305" class="label">nucleus</text><text x="430" y="116" class="label">energy levels</text>';
    if (str_contains($topic,'ไอออน') || str_contains($topic,'ถ่ายโอน')) {
        $shape='<circle cx="180" cy="215" r="64" fill="#dbeafe" stroke="#2563eb" stroke-width="3"/><circle cx="480" cy="215" r="64" fill="#fee2e2" stroke="#ef4444" stroke-width="3"/><path d="M260 215 C320 150 370 150 420 215" fill="none" stroke="#0f766e" stroke-width="6" marker-end="url(#arrow)"/><circle cx="300" cy="170" r="9" fill="#0f172a"/><text x="145" y="220" class="ion">Na</text><text x="450" y="220" class="ion">Cl</text><text x="270" y="126" class="label">electron transfer</text>';
    } elseif (str_contains($topic,'โควาเลนต์') || str_contains($topic,'ใช้') || str_contains($topic,'ร่วมกัน')) {
        $shape='<circle cx="245" cy="220" r="70" fill="#ecfeff" stroke="#0891b2" stroke-width="3"/><circle cx="415" cy="220" r="70" fill="#fef3c7" stroke="#d97706" stroke-width="3"/><circle cx="318" cy="205" r="10" fill="#0f172a"/><circle cx="342" cy="235" r="10" fill="#0f172a"/><text x="270" y="125" class="label">shared electron pair</text><text x="217" y="226" class="ion">A</text><text x="391" y="226" class="ion">B</text>';
    } elseif (str_contains($topic,'รูปร่าง') || str_contains($topic,'vsepr') || str_contains($topic,'โมเลกุล')) {
        $shape='<circle cx="330" cy="220" r="34" fill="#2563eb"/><circle cx="210" cy="140" r="26" fill="#f97316"/><circle cx="450" cy="140" r="26" fill="#f97316"/><circle cx="330" cy="355" r="26" fill="#f97316"/><line x1="330" y1="220" x2="210" y2="140" stroke="#64748b" stroke-width="8"/><line x1="330" y1="220" x2="450" y2="140" stroke="#64748b" stroke-width="8"/><line x1="330" y1="220" x2="330" y2="355" stroke="#64748b" stroke-width="8"/><text x="235" y="82" class="label">molecular geometry</text>';
    }
    $svg='<svg xmlns="http://www.w3.org/2000/svg" width="960" height="560" viewBox="0 0 660 420"><defs><marker id="arrow" markerWidth="10" markerHeight="10" refX="8" refY="3" orient="auto"><path d="M0,0 L0,6 L9,3 z" fill="#0f766e"/></marker><style>.title{font:700 22px sans-serif;fill:#0f172a}.sub{font:500 13px sans-serif;fill:#64748b}.label{font:600 15px sans-serif;fill:#334155}.ion{font:700 26px sans-serif;fill:#0f172a}</style></defs><rect width="660" height="420" rx="18" fill="#f8fafc"/><rect x="24" y="24" width="612" height="372" rx="14" fill="#fff" stroke="#dbe4ee"/><text x="42" y="62" class="title">'.$safeTitle.'</text><text x="42" y="88" class="sub">'.$safeObjective.'</text>'.$shape.'<text x="42" y="374" class="sub">ภาพประกอบสำรองจากระบบ เนื่องจากโควตาสร้างภาพ AI ไม่พร้อมใช้งาน</text></svg>';
    $relativeDir='uploads/ai-images/'.date('Y/m');
    $absoluteDir=dirname(__DIR__).'/'.$relativeDir;
    if (!is_dir($absoluteDir) && !mkdir($absoluteDir,0755,true) && !is_dir($absoluteDir)) throw new RuntimeException('สร้างโฟลเดอร์เก็บภาพไม่ได้');
    $filename=bin2hex(random_bytes(16)).'.svg';
    if (file_put_contents($absoluteDir.'/'.$filename,$svg,LOCK_EX)===false) throw new RuntimeException('บันทึกภาพสำรองไม่ได้');
    return ['url'=>'/'.$relativeDir.'/'.$filename,'mimeType'=>'image/svg+xml','bytes'=>strlen($svg),'model'=>'local-science-diagram','prompt'=>$prompt,'status'=>'fallback','error'=>$reason];
}
try {
    $store=new ScienceStore($pdo,(int)$consoleUser['id']);
    if ($_SERVER['REQUEST_METHOD']==='GET') {
        $action=$_GET['action']??'list';
        if ($action==='status') sciReply(['configured'=>aiSettingsGetKey($pdo)!=='','canConfigure'=>$consoleUser['role']==='admin','branches'=>sciBranches()]);
        if ($action==='get') sciReply(['project'=>$store->get((string)($_GET['id']??''))]);
        if ($action==='list') sciReply(['projects'=>$store->all()]);
        sciReply(['error'=>'ไม่พบคำสั่ง'],404);
    }
    if ($_SERVER['REQUEST_METHOD']!=='POST') sciReply(['error'=>'Method not allowed'],405);
    if (!hash_equals($_SESSION['science_csrf']??'',(string)($_SERVER['HTTP_X_CSRF_TOKEN']??'')) || empty($_SESSION['science_csrf'])) sciReply(['error'=>'กรุณาโหลดหน้าใหม่เพื่อยืนยันเซสชัน'],403);
    if ((int)($_SERVER['CONTENT_LENGTH']??0)>2*1024*1024) sciReply(['error'=>'ข้อมูลใหญ่เกินกำหนด'],413);
    $body=json_decode(file_get_contents('php://input'),true,512,JSON_THROW_ON_ERROR);
    if (!is_array($body)) throw new InvalidArgumentException('ข้อมูลไม่ถูกต้อง');
    $action=$body['action']??'';
    if ($action==='settings') {
        if ($consoleUser['role']!=='admin') sciReply(['error'=>'เฉพาะแอดมินที่เปลี่ยน API Key ได้'],403);
        $key=sciText($body['key']??'',250); aiSettingsEnsure($pdo);
        $stmt=$pdo->prepare("INSERT INTO system_settings(setting_key,setting_value) VALUES('gemini_api_key',?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
        $stmt->execute([aiSettingsEncrypt($key)]); sciReply(['success'=>true]);
    }
    if ($action==='create') sciReply(['project'=>$store->create(sciNewProject($body['config']??[]))],201);
    $id=sciText($body['id']??'',24); $row=$store->get($id); $p=$row['data'];
    if ($action==='pause') sciReply(['project'=>$store->state($id,'paused')]);
    if ($action==='start') {
        if ($row['busy']) sciReply(['project'=>$store->state($id,'running')]);
        if (!sciNext($p)) throw new InvalidArgumentException('กรุณาตรวจโครงหรือเนื้อหาก่อนเริ่มงานถัดไป');
        $key=aiSettingsGetKey($pdo); if ($key==='') throw new InvalidArgumentException('กรุณาตั้งค่า Gemini API Key');
        session_write_close();
        $models=sciModels($key);
        $p['model']=sciWorkingModel($key,$models); $p['error']=null; $p['recoveryAttempts']=[]; $store->save($row,$p);
        sciReply(['project'=>$store->state($id,'running')]);
    }
    if ($action==='step') {
        $key=aiSettingsGetKey($pdo); session_write_close(); ignore_user_abort(true); set_time_limit(240);
        if ($key==='') throw new InvalidArgumentException('กรุณาตั้งค่า Gemini API Key');
        sciReply(['project'=>sciRun($store,$id,static function($data,$task) use($key) {
            if (empty($data['model'])) throw new RuntimeException('กรุณากดทำต่อเพื่อเลือกโมเดลใหม่');
            $result=sciGenerate($data,$task,$key,$data['model']);
            if ($task['kind']==='content' && !empty($data['config']['generateImages'])) {
                $result['data']['imagePrompt']=sciPartImagePrompt($data,$task,$result['data']);
                try {
                    $result['data']['illustration']=AiImageService::generate($key,$result['data']['imagePrompt'],[
                        'subject'=>'วิทยาศาสตร์','grade'=>$data['config']['grade'],'style'=>'accurate clean educational textbook illustration']);
                } catch (Throwable $imageError) {
                    $result['data']['illustration']=sciFallbackIllustration($data,$task,$result['data']['imagePrompt'],$imageError->getMessage());
                    $result['data']['imageError']='ใช้ภาพประกอบสำรอง เพราะโควตาสร้างภาพ AI เต็ม';
                }
            }
            return $result;
        })]);
    }
    if ($row['busy'] || $row['run_state']==='running') throw new InvalidArgumentException('กรุณาหยุดคิวและรอให้งานปัจจุบันเสร็จก่อนแก้ไข');
    if (($body['revision']??null)!==$row['revision']) throw new RuntimeException('ข้อมูลเปลี่ยนไปแล้ว กรุณาโหลดหลักสูตรอีกครั้ง');
    switch ($action) {
        case 'outline': sciOutline($p,$body['chapters']??[]); break;
        case 'approveOutline':
            if (sciStage($p)!=='รอตรวจโครง') throw new InvalidArgumentException('โครงยังไม่ครบหรืออนุมัติแล้ว');
            $p['outlineApproved']=true; break;
        case 'part':
            [$ci,$ei,$pi]=sciFindPart($p,(string)($body['partId']??''));
            $part=&$p['chapters'][$ci]['episodes'][$ei]['parts'][$pi];
            $incoming=$body['content']??[];
            if (is_array($incoming) && empty($incoming['illustration']) && !empty($part['content']['illustration'])) $incoming['illustration']=$part['content']['illustration'];
            if (is_array($incoming) && empty($incoming['imagePrompt']) && !empty($part['content']['imagePrompt'])) $incoming['imagePrompt']=$part['content']['imagePrompt'];
            $part['content']=sciContent($incoming); $part['approved']=false;
            $p['chapters'][$ci]['episodes'][$ei]['questions']=[];
            $p['completed']=array_values(array_diff($p['completed'],[$part['id']])); $p['attempts']=[];
            break;
        case 'image':
            [$ci,$ei,$pi]=sciFindPart($p,(string)($body['partId']??''));
            $part=&$p['chapters'][$ci]['episodes'][$ei]['parts'][$pi];
            if (!$part['content']) throw new InvalidArgumentException('ยังไม่มีเนื้อหาสำหรับสร้างภาพ');
            $prompt=trim((string)($body['prompt']??$part['content']['imagePrompt']??''));
            $key=aiSettingsGetKey($pdo); if ($key==='') throw new InvalidArgumentException('กรุณาตั้งค่า Gemini API Key');
            $part['content']['imagePrompt']=$prompt;
            try {
                $part['content']['illustration']=AiImageService::generate($key,$prompt,['subject'=>'วิทยาศาสตร์','grade'=>$p['config']['grade']]);
                $part['content']['imageError']='';
            } catch (Throwable $imageError) {
                $part['content']['illustration']=sciFallbackIllustration($p,['kind'=>'content','c'=>$ci,'e'=>$ei,'p'=>$pi],$prompt,$imageError->getMessage());
                $part['content']['imageError']='ใช้ภาพประกอบสำรอง เพราะโควตาสร้างภาพ AI เต็ม';
            }
            $part['approved']=false;
            break;
        case 'approvePart':
            [$ci,$ei,$pi]=sciFindPart($p,(string)($body['partId']??''));
            if (!$p['chapters'][$ci]['episodes'][$ei]['parts'][$pi]['content']) throw new InvalidArgumentException('ยังไม่มีเนื้อหา');
            $p['chapters'][$ci]['episodes'][$ei]['parts'][$pi]['approved']=true; break;
        case 'regenerate':
            [$ci,$ei,$pi]=sciFindPart($p,(string)($body['partId']??''));
            $part=&$p['chapters'][$ci]['episodes'][$ei]['parts'][$pi];
            $part['content']=null; $part['approved']=false;
            $p['chapters'][$ci]['episodes'][$ei]['questions']=[];
            $p['completed']=array_values(array_diff($p['completed'],[$part['id']])); $p['attempts']=[]; $p['error']=null;
            break;
        case 'schedule': $p['schedule']=sciSchedule($p,$body['settings']??[]); break;
        case 'completePart':
            [$ci,$ei,$pi]=sciFindPart($p,(string)($body['partId']??''));
            if (!$p['chapters'][$ci]['episodes'][$ei]['parts'][$pi]['approved']) throw new InvalidArgumentException('Part นี้ยังไม่ผ่านการตรวจ');
            $p['completed']=array_values(array_unique(array_merge($p['completed'],[$body['partId']]))); break;
        case 'attempt':
            $episode=null;
            foreach ($p['chapters'] as $c) foreach ($c['episodes'] as $e) if ($e['id']===($body['episodeId']??'')) $episode=$e;
            if (!$episode || count($episode['questions'])!==$p['config']['questionsPerEp']) throw new InvalidArgumentException('ข้อสอบยังไม่ครบ');
            $answers=$body['answers']??[]; if (!is_array($answers)) throw new InvalidArgumentException('คำตอบไม่ถูกต้อง');
            $score=0; $review=[];
            foreach ($episode['questions'] as $q) {
                $a=$answers[$q['id']]??null;
                if ($a!==null) $a=sciInt($a,0,3);
                if ($a===$q['answer']) $score++; else $review[]=$q['partId'];
            }
            $p['attempts'][]=['at'=>date(DATE_ATOM),'episodeId'=>$episode['id'],'score'=>$score,'total'=>count($episode['questions']),'review'=>array_values(array_unique($review))];
            $p['attempts']=array_slice($p['attempts'],-100); break;
        default: sciReply(['error'=>'ไม่พบคำสั่ง'],404);
    }
    sciReply(['project'=>$store->save($row,$p)]);
} catch (InvalidArgumentException | JsonException $error) {
    sciReply(['error'=>$error instanceof JsonException?'ข้อมูล JSON ไม่ถูกต้อง':$error->getMessage()],422);
} catch (Throwable $error) {
    error_log('Science Studio: '.$error->getMessage());
    sciReply(['error'=>$error instanceof PDOException?'บันทึกข้อมูลไม่ได้ กรุณาตรวจการเชื่อมต่อฐานข้อมูล':$error->getMessage()],500);
}
