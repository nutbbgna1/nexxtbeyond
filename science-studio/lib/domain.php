<?php
declare(strict_types=1);

function sciId(): string { return bin2hex(random_bytes(12)); }
function sciText($value, int $max = 1000): string {
    if (!is_string($value) || trim($value) === '' || mb_strlen($value) > $max) {
        throw new InvalidArgumentException('ข้อความว่างหรือยาวเกินกำหนด');
    }
    return trim($value);
}
function sciInt($value, int $min, int $max): int {
    if (filter_var($value, FILTER_VALIDATE_INT) === false || (int)$value < $min || (int)$value > $max) {
        throw new InvalidArgumentException("จำนวนต้องอยู่ระหว่าง $min และ $max");
    }
    return (int)$value;
}
function sciList($value, int $min, int $max): array {
    if (!is_array($value) || array_values($value) !== $value || count($value) < $min || count($value) > $max) {
        throw new InvalidArgumentException('จำนวนรายการไม่ถูกต้อง');
    }
    return $value;
}
function sciBranches(): array {
    return ['physics'=>'ฟิสิกส์', 'chemistry'=>'เคมี', 'biology'=>'ชีววิทยา', 'earth'=>'โลกและอวกาศ', 'integrated'=>'วิทยาศาสตร์บูรณาการ'];
}
function sciConfig(array $in): array {
    $branches = sciList($in['branches'] ?? null, 1, 5);
    foreach ($branches as $b) if (!is_string($b) || !isset(sciBranches()[$b])) throw new InvalidArgumentException('กรุณาเลือกแขนงวิชา');
    return [
        'title'=>sciText($in['title'] ?? '', 200), 'branches'=>array_values(array_unique($branches)),
        'grade'=>sciText($in['grade'] ?? '', 100), 'foundation'=>sciText($in['foundation'] ?? '', 300),
        'topic'=>sciText($in['topic'] ?? '', 1500), 'goal'=>sciText($in['goal'] ?? '', 1000),
        'difficulty'=>sciText($in['difficulty'] ?? 'ปานกลาง', 80),
        'sessionMinutes'=>sciInt($in['sessionMinutes'] ?? 45, 30, 90),
        'questionsPerEp'=>sciInt($in['questionsPerEp'] ?? 6, 3, 15),
        'generateImages'=>($in['generateImages'] ?? '') === '1' || ($in['generateImages'] ?? false) === true,
    ];
}
function sciNewProject(array $config): array {
    return ['config'=>sciConfig($config), 'chapters'=>[], 'outlineApproved'=>false,
        'history'=>[], 'error'=>null, 'schedule'=>null, 'completed'=>[], 'attempts'=>[]];
}
function sciNode(array $raw, string $kind): array {
    $node = ['id'=>sciId(), 'title'=>sciText($raw['title'] ?? '', 180),
        'objective'=>sciText($raw['objective'] ?? '', 800)];
    if ($kind === 'chapter') $node['episodes'] = [];
    if ($kind === 'episode') { $node['parts'] = []; $node['questions'] = []; }
    if ($kind === 'part') {
        $node['minutes'] = sciInt($raw['minutes'] ?? 45, 30, 90);
        $node['content'] = null; $node['approved'] = false; $node['splitDepth'] = 0;
    }
    return $node;
}
function sciNext(array $p): ?array {
    if (!$p['chapters']) return ['kind'=>'chapters', 'label'=>'วางโครงบทเรียน'];
    foreach ($p['chapters'] as $ci=>$c) {
        if (!$c['episodes']) return ['kind'=>'episodes', 'c'=>$ci, 'label'=>'วาง EP: '.$c['title']];
        foreach ($c['episodes'] as $ei=>$e) {
            if (!$e['parts']) return ['kind'=>'parts', 'c'=>$ci, 'e'=>$ei, 'label'=>'แบ่ง Part: '.$e['title']];
        }
    }
    if (!$p['outlineApproved']) return null;
    foreach ($p['chapters'] as $ci=>$c) foreach ($c['episodes'] as $ei=>$e) {
        foreach ($e['parts'] as $pi=>$part) if (!$part['content']) {
            return ['kind'=>'content', 'c'=>$ci, 'e'=>$ei, 'p'=>$pi, 'label'=>$e['title'].' / '.$part['title']];
        }
    }
    foreach ($p['chapters'] as $ci=>$c) foreach ($c['episodes'] as $ei=>$e) {
        $approved = count(array_filter($e['parts'], static fn($part)=>$part['approved'])) === count($e['parts']);
        if ($approved && count($e['questions']) < $p['config']['questionsPerEp']) {
            $total=$p['config']['questionsPerEp']; $partCount=count($e['parts']);
            $base=intdiv($total,$partCount); $extra=$total%$partCount;
            foreach ($e['parts'] as $pi=>$part) {
                $quota=$base+($pi<$extra?1:0);
                $actual=count(array_filter($e['questions'],static fn($q)=>$q['partId']===$part['id']));
                if ($actual<$quota) return ['kind'=>'quiz','c'=>$ci,'e'=>$ei,'p'=>$pi,
                    'count'=>min(3,$quota-$actual),'label'=>'ข้อสอบ: '.$e['title'].' / '.$part['title']];
            }
        }
    }
    return null;
}
function sciStage(array $p): string {
    if (!$p['chapters']) return 'ยังไม่มีโครงบท';
    foreach ($p['chapters'] as $c) {
        if (!$c['episodes']) return 'กำลังวางโครง';
        foreach ($c['episodes'] as $e) if (!$e['parts']) return 'กำลังวางโครง';
    }
    if (!$p['outlineApproved']) return 'รอตรวจโครง';
    foreach ($p['chapters'] as $c) foreach ($c['episodes'] as $e) foreach ($e['parts'] as $part) if (!$part['content']) return 'กำลังสร้างเนื้อหา';
    foreach ($p['chapters'] as $c) foreach ($c['episodes'] as $e) foreach ($e['parts'] as $part) if (!$part['approved']) return 'รอตรวจเนื้อหา';
    foreach ($p['chapters'] as $c) foreach ($c['episodes'] as $e) if (count($e['questions']) < $p['config']['questionsPerEp']) return 'กำลังสร้างข้อสอบ';
    return 'พร้อมเรียน';
}
function sciContent(array $raw): array {
    $sections=[];
    foreach (sciList($raw['sections'] ?? null, 1, 8) as $s) {
        $sections[]=['heading'=>sciText($s['heading'] ?? '', 180),'body'=>sciText($s['body'] ?? '', 10000)];
    }
    $imagePrompt=trim((string)($raw['imagePrompt']??''));
    if (mb_strlen($imagePrompt)>4000) throw new InvalidArgumentException('คำสั่งภาพยาวเกินกำหนด');
    $illustration=is_array($raw['illustration']??null)?$raw['illustration']:null;
    if ($illustration && !preg_match('#^/uploads/ai-images/[0-9]{4}/[0-9]{2}/[a-f0-9]{32}\.(png|jpg|webp|svg)$#',(string)($illustration['url']??''))) $illustration=null;
    $imageError=trim((string)($raw['imageError']??''));
    if (mb_strlen($imageError)>1000) $imageError=mb_substr($imageError,0,1000);
    return ['sections'=>$sections, 'example'=>sciText($raw['example'] ?? '', 8000),
        'exercise'=>sciText($raw['exercise'] ?? '', 3000), 'hint'=>sciText($raw['hint'] ?? '', 1000),
        'solution'=>sciText($raw['solution'] ?? '', 5000), 'summary'=>sciText($raw['summary'] ?? '', 1200),
        'imagePrompt'=>$imagePrompt, 'illustration'=>$illustration, 'imageError'=>$imageError];
}
function sciQuestions(array $raw, array $e, int $count, string $expectedPartId): array {
    $questions=sciList($raw['questions'] ?? null, $count, $count); $seen=[];
    foreach ($e['questions'] as $q) $seen[mb_strtolower(preg_replace('/\s+/u', '', $q['question']))]=true;
    $ids=array_column($e['parts'], 'id'); $out=[];
    foreach ($questions as $q) {
        $question=sciText($q['question'] ?? '', 3000);
        $signature=mb_strtolower(preg_replace('/\s+/u', '', $question));
        if (isset($seen[$signature])) throw new InvalidArgumentException('พบข้อสอบซ้ำ กรุณาสร้างชุดย่อยนี้ใหม่');
        $seen[$signature]=true;
        $options=array_map(static fn($s)=>sciText($s, 1000), sciList($q['options'] ?? null, 4, 4));
        if (count(array_unique($options)) !== 4) throw new InvalidArgumentException('ตัวเลือกข้อสอบซ้ำกัน');
        if (!in_array($q['partId'] ?? '', $ids, true) || $q['partId'] !== $expectedPartId) {
            throw new InvalidArgumentException('ข้อสอบอ้างอิง Part ไม่ตรงกับงานย่อย');
        }
        $out[]=['id'=>sciId(), 'question'=>$question, 'options'=>$options,
            'answer'=>sciInt($q['answer'] ?? -1, 0, 3), 'explanation'=>sciText($q['explanation'] ?? '', 3000),
            'partId'=>$q['partId'], 'objective'=>sciText($q['objective'] ?? '', 800)];
    }
    return $out;
}
function sciApply(array &$p, array $task, array $result): void {
    switch ($task['kind']) {
        case 'chapters':
            $p['chapters']=array_map(static fn($n)=>sciNode($n,'chapter'), sciList($result['items'] ?? null,1,6)); break;
        case 'episodes':
            $p['chapters'][$task['c']]['episodes']=array_map(static fn($n)=>sciNode($n,'episode'),sciList($result['items'] ?? null,1,4)); break;
        case 'parts':
            $p['chapters'][$task['c']]['episodes'][$task['e']]['parts']=array_map(static fn($n)=>sciNode($n,'part'),sciList($result['items'] ?? null,1,4)); break;
        case 'content':
            $p['chapters'][$task['c']]['episodes'][$task['e']]['parts'][$task['p']]['content']=sciContent($result); break;
        case 'quiz':
            $e=&$p['chapters'][$task['c']]['episodes'][$task['e']];
            $expectedPartId=$e['parts'][$task['p']]['id'];
            $e['questions']=array_merge($e['questions'],sciQuestions($result,$e,$task['count'],$expectedPartId)); break;
    }
    $p['error']=null;
}
function sciSplit(array &$p, array $task): bool {
    if ($task['kind'] !== 'content') return false;
    $parts=&$p['chapters'][$task['c']]['episodes'][$task['e']]['parts']; $old=$parts[$task['p']];
    if ($old['splitDepth'] >= 2 || count($parts) >= 24) return false;
    $children=[];
    foreach (['แนวคิดและตัวอย่างพื้นฐาน','การประยุกต์และฝึกทำ'] as $label) {
        $child=sciNode(['title'=>mb_substr($old['title'],0,100).' / '.$label,
            'objective'=>mb_substr($old['objective'],0,600).' เน้น'.$label,
            'minutes'=>max(30,min(90,(int)$old['minutes']))],'part');
        $child['splitDepth']=$old['splitDepth']+1; $children[]=$child;
    }
    array_splice($parts,$task['p'],1,$children); $p['schedule']=null;
    return true;
}
function sciFindPart(array $p, string $id): array {
    foreach ($p['chapters'] as $ci=>$c) foreach ($c['episodes'] as $ei=>$e) foreach ($e['parts'] as $pi=>$part) {
        if ($part['id']===$id) return [$ci,$ei,$pi];
    }
    throw new InvalidArgumentException('ไม่พบ Part');
}
function sciOutline(array &$p, array $chapters): void {
    if ($p['outlineApproved']) throw new InvalidArgumentException('โครงนี้เริ่มสร้างเนื้อหาแล้ว กรุณาสร้างหลักสูตรใหม่เมื่อต้องการเปลี่ยนโครง');
    $out=[]; $allIds=[];
    foreach (sciList($chapters,1,6) as $c) {
        $chapter=sciNode($c,'chapter');
        foreach (sciList($c['episodes'] ?? [],1,4) as $e) {
            $ep=sciNode($e,'episode');
            foreach (sciList($e['parts'] ?? [],1,4) as $part) $ep['parts'][]=sciNode($part,'part');
            $chapter['episodes'][]=$ep;
        }
        $out[]=$chapter;
    }
    $p['chapters']=$out; $p['schedule']=null; $p['error']=null;
}
function sciSchedule(array $p, array $settings): array {
    $start=DateTimeImmutable::createFromFormat('!Y-m-d', (string)($settings['start'] ?? ''));
    if (!$start || $start->format('Y-m-d') !== ($settings['start'] ?? '')) throw new InvalidArgumentException('วันเริ่มไม่ถูกต้อง');
    $days=sciList($settings['days'] ?? null,1,7); $days=array_values(array_unique(array_map(static fn($n)=>sciInt($n,1,7),$days)));
    $budget=sciInt($settings['minutes'] ?? 45,15,180); $cursor=$start; $remaining=$budget; $items=[];
    $advance=static function() use (&$cursor,&$remaining,$budget,$days): void {
        do { $cursor=$cursor->modify('+1 day'); } while (!in_array((int)$cursor->format('N'),$days,true));
        $remaining=$budget;
    };
    while (!in_array((int)$cursor->format('N'),$days,true)) $cursor=$cursor->modify('+1 day');
    $append=static function(string $id,string $label,int $minutes,string $kind) use (&$items,&$remaining,&$cursor,$advance): void {
        $segment=1;
        while ($minutes>0) {
            if ($remaining===0) $advance();
            $duration=min($remaining,$minutes);
            $items[]=['id'=>$id,'label'=>$label,'date'=>$cursor->format('Y-m-d'),'minutes'=>$duration,'kind'=>$kind,'segment'=>$segment++];
            $remaining-=$duration; $minutes-=$duration;
        }
    };
    foreach ($p['chapters'] as $c) foreach ($c['episodes'] as $e) {
        foreach ($e['parts'] as $part) $append($part['id'],$e['title'].' / '.$part['title'],$part['minutes'],'lesson');
        $append($e['id'],$e['title'].' / ทบทวน',10,'review');
        $append($e['id'],$e['title'].' / แบบทดสอบ',$p['config']['questionsPerEp']*2,'quiz');
    }
    if (!$items) throw new InvalidArgumentException('กรุณาสร้างโครง EP และ Part ก่อนจัดแผนเรียน');
    $deadline=$settings['deadline'] ?? '';
    if ($deadline !== '') {
        $d=DateTimeImmutable::createFromFormat('!Y-m-d',$deadline);
        if (!$d || $d->format('Y-m-d')!==$deadline || $d<$start) throw new InvalidArgumentException('วันเป้าหมายต้องไม่น้อยกว่าวันเริ่ม');
    }
    return ['settings'=>['start'=>$settings['start'],'days'=>$days,'minutes'=>$budget,'deadline'=>$deadline],
        'items'=>$items,'end'=>$cursor->format('Y-m-d'),'overdue'=>$deadline!=='' && $cursor->format('Y-m-d')>$deadline];
}
