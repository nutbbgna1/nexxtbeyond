<?php
declare(strict_types=1);

function ensureRoadmapSchema(PDO $pdo): void
{
    static $ready = false;
    if ($ready) return;

    $pdo->exec("CREATE TABLE IF NOT EXISTS roadmap_tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        stage ENUM('m1','m4','tcas') NOT NULL DEFAULT 'tcas',
        title VARCHAR(255) NOT NULL,
        subject VARCHAR(100) NOT NULL DEFAULT 'ทั่วไป',
        category VARCHAR(100) NOT NULL DEFAULT 'ทั่วไป',
        due_date DATE NULL,
        points_reward INT NOT NULL DEFAULT 10,
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_by INT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_roadmap_stage_active (stage, is_active, sort_order),
        CONSTRAINT fk_roadmap_creator FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS roadmap_task_progress (
        id INT AUTO_INCREMENT PRIMARY KEY,
        task_id INT NOT NULL,
        user_id INT NOT NULL,
        is_completed TINYINT(1) NOT NULL DEFAULT 0,
        completed_at DATETIME NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_roadmap_task_user (task_id, user_id),
        INDEX idx_roadmap_progress_user (user_id, is_completed),
        CONSTRAINT fk_roadmap_progress_task FOREIGN KEY (task_id) REFERENCES roadmap_tasks(id) ON DELETE CASCADE,
        CONSTRAINT fk_roadmap_progress_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    seedRoadmapTemplates($pdo);
    $ready = true;
}

function seedRoadmapTemplates(PDO $pdo): void
{
    $seedKey = 'roadmap_templates_seeded_v1';
    try {
        $stmt = $pdo->prepare('SELECT setting_value FROM system_settings WHERE setting_key = ? LIMIT 1');
        $stmt->execute([$seedKey]);
        if ($stmt->fetchColumn() !== false) return;
    } catch (PDOException $error) {
        if ($error->getCode() !== '42S02') throw $error;
        if ((int)$pdo->query('SELECT COUNT(*) FROM roadmap_tasks')->fetchColumn() > 0) return;
    }

    $templates = [
        ['tcas','สรุปสูตรฟิสิกส์ ม.ปลาย เรื่องกลศาสตร์และคลื่นแม่เหล็กไฟฟ้า','ฟิสิกส์','สรุปเนื้อหา',3,15],
        ['tcas','ฝึกทำโจทย์ Mock Exam TPAT1 พาร์ทเชาวน์ปัญญา','TPAT1','ทำข้อสอบ',5,20],
        ['tcas','ทบทวนคำศัพท์ TGAT1 English Vocabulary 500 คำแรก','TGAT1','ภาษาอังกฤษ',7,10],
        ['tcas','จำลองสอบจับเวลา A-Level เคมี พันธะเคมีและสมดุลกรด-เบส','เคมี','ทำข้อสอบ',10,25],
        ['tcas','ฝึกเขียน Essay ภาษาอังกฤษ 250 คำเพื่อเตรียมยื่น Portfolio','ภาษาอังกฤษ','Portfolio',14,20],
        ['m4','สรุปฟังก์ชันกำลังสองและเรขาคณิตวิเคราะห์ ม.4 เทอม 1','คณิตศาสตร์','สรุปเนื้อหา',2,15],
        ['m4','ทดสอบความรู้โครงสร้างเซลล์และกล้องจุลทรรศน์','ชีววิทยา','ทำข้อสอบ',6,15],
        ['m4','ทำแบบฝึกหัดกฎการเคลื่อนที่ของนิวตัน 20 ข้อ','ฟิสิกส์','แบบฝึกหัด',9,20],
        ['m1','ทบทวนสมการเชิงเส้นตัวแปรเดียวและโจทย์ปัญหาร้อยละ','คณิตศาสตร์','สรุปเนื้อหา',1,10],
        ['m1','ฝึกอ่าน Short Passages & Reading Comprehension','ภาษาอังกฤษ','ภาษาอังกฤษ',4,15],
        ['m1','จำลองข้อสอบวิทยาศาสตร์เรื่องสารบริสุทธิ์และสารผสม','วิทยาศาสตร์','ทำข้อสอบ',8,20],
    ];
    if ((int)$pdo->query('SELECT COUNT(*) FROM roadmap_tasks')->fetchColumn() === 0) {
        $insert = $pdo->prepare('INSERT INTO roadmap_tasks (stage,title,subject,category,due_date,points_reward,sort_order,is_active) VALUES (?,?,?,?,?,?,?,1)');
        foreach ($templates as $index => $template) {
            [$stage,$title,$subject,$category,$days,$points] = $template;
            $insert->execute([$stage,$title,$subject,$category,date('Y-m-d', strtotime("+$days days")),$points,$index + 1]);
        }
    }
    try {
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, '1') ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $stmt->execute([$seedKey]);
    } catch (PDOException $error) {
        if ($error->getCode() !== '42S02') throw $error;
    }
}

function roadmapStageLabels(): array
{
    return [
        'tcas' => ['title' => 'ม.6 / TCAS มหาวิทยาลัย', 'badge' => 'TCAS & A-Level', 'description' => 'ทบทวนวิชาสามัญ TGAT, TPAT และเตรียม Portfolio สำหรับเข้ามหาวิทยาลัย'],
        'm4' => ['title' => 'ม.4 - ม.5 มัธยมปลาย', 'badge' => 'มัธยมปลาย', 'description' => 'ปรับพื้นฐานวิทย์ คณิต และภาษา พร้อมเก็บคะแนนในโรงเรียน'],
        'm1' => ['title' => 'ม.1 - ม.3 มัธยมต้น', 'badge' => 'มัธยมต้น', 'description' => 'ปูพื้นฐานวิชาหลักและเตรียมสอบเข้าในระดับถัดไป'],
    ];
}

function validRoadmapStage(string $stage): string
{
    return array_key_exists($stage, roadmapStageLabels()) ? $stage : 'tcas';
}

function getStudentRoadmap(PDO $pdo, int $userId, string $stage): array
{
    ensureRoadmapSchema($pdo);
    $stage = validRoadmapStage($stage);
    $stmt = $pdo->prepare(
        'SELECT t.*, COALESCE(p.is_completed, 0) AS is_completed, p.completed_at
         FROM roadmap_tasks t
         LEFT JOIN roadmap_task_progress p ON p.task_id = t.id AND p.user_id = :user_id
         WHERE t.stage = :stage AND t.is_active = 1
         ORDER BY t.sort_order, t.due_date IS NULL, t.due_date, t.id'
    );
    $stmt->execute([':user_id' => $userId, ':stage' => $stage]);
    return $stmt->fetchAll();
}

function roadmapProgress(array $tasks): array
{
    $total = count($tasks);
    $completed = count(array_filter($tasks, static fn(array $task): bool => (bool)$task['is_completed']));
    return ['total' => $total, 'completed' => $completed, 'percent' => $total ? (int)round($completed * 100 / $total) : 0];
}
