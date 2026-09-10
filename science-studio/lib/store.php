<?php
declare(strict_types=1);
require_once __DIR__.'/domain.php';

final class ScienceStore {
    private PDO $db;
    private int $owner;
    public function __construct(PDO $db, int $owner) {
        $this->db=$db; $this->owner=$owner;
        $db->exec("CREATE TABLE IF NOT EXISTS science_projects (
            id VARCHAR(24) PRIMARY KEY, owner_id INTEGER NOT NULL, data LONGTEXT NOT NULL,
            run_state VARCHAR(20) NOT NULL DEFAULT 'paused', revision INTEGER NOT NULL DEFAULT 0,
            lease VARCHAR(24) NULL, lease_until BIGINT NOT NULL DEFAULT 0, updated_at BIGINT NOT NULL
        )");
    }
    public function get(string $id): array {
        $q=$this->db->prepare('SELECT * FROM science_projects WHERE id=? AND owner_id=?');
        $q->execute([$id,$this->owner]); $row=$q->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new InvalidArgumentException('ไม่พบหลักสูตรหรือไม่มีสิทธิ์เข้าถึง');
        $row['data']=json_decode($row['data'],true,512,JSON_THROW_ON_ERROR);
        $row['revision']=(int)$row['revision'];
        $row['busy']=(int)$row['lease_until']>time(); $row['stage']=sciStage($row['data']);
        return $row;
    }
    public function all(): array {
        $q=$this->db->prepare('SELECT id FROM science_projects WHERE owner_id=? ORDER BY updated_at DESC');
        $q->execute([$this->owner]);
        return array_map(function($id) { $p=$this->get($id); return ['id'=>$id,'title'=>$p['data']['config']['title'],
            'branches'=>$p['data']['config']['branches'],'stage'=>$p['stage'],'updated_at'=>$p['updated_at']]; },$q->fetchAll(PDO::FETCH_COLUMN));
    }
    public function create(array $p): array {
        $id=sciId(); $q=$this->db->prepare('INSERT INTO science_projects(id,owner_id,data,updated_at) VALUES(?,?,?,?)');
        $q->execute([$id,$this->owner,json_encode($p,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),time()]);
        return $this->get($id);
    }
    public function save(array $row, array $p): array {
        $q=$this->db->prepare('UPDATE science_projects SET data=?,revision=revision+1,updated_at=? WHERE id=? AND owner_id=? AND revision=? AND lease_until<=?');
        $q->execute([json_encode($p,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),time(),$row['id'],$this->owner,$row['revision'],time()]);
        if ($q->rowCount()!==1) throw new RuntimeException('ข้อมูลเปลี่ยนจากหน้าต่างอื่นหรือกำลังสร้าง กรุณาโหลดหลักสูตรอีกครั้ง');
        return $this->get($row['id']);
    }
    public function state(string $id, string $state): array {
        if (!in_array($state,['running','paused'],true)) throw new InvalidArgumentException('สถานะไม่ถูกต้อง');
        $this->get($id);
        $q=$this->db->prepare('UPDATE science_projects SET run_state=?,updated_at=? WHERE id=? AND owner_id=?');
        $q->execute([$state,time(),$id,$this->owner]); return $this->get($id);
    }
    public function claim(string $id): ?array {
        $token=sciId();
        $q=$this->db->prepare("UPDATE science_projects SET lease=?,lease_until=? WHERE id=? AND owner_id=? AND run_state='running' AND lease_until<=?");
        $q->execute([$token,time()+300,$id,$this->owner,time()]);
        return $q->rowCount()===1 ? $this->get($id) : null;
    }
    public function finish(array $row, array $p, bool $pause): array {
        $sql='UPDATE science_projects SET data=?,revision=revision+1,updated_at=?,lease=NULL,lease_until=0'.($pause?",run_state='paused'":'').' WHERE id=? AND owner_id=? AND lease=?';
        $q=$this->db->prepare($sql);
        $q->execute([json_encode($p,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR),time(),$row['id'],$this->owner,$row['lease']]);
        if ($q->rowCount()!==1) throw new RuntimeException('งานหมดเวลาล็อก กรุณาโหลดข้อมูลใหม่');
        return $this->get($row['id']);
    }
}
