"use strict";

const studioState = {
    difficulty: 'medium',
    source: 'drive',
    storedFile: '',
    originalName: '',
    questions: [],
};

const studioEndpoints = window.AI_STUDIO_ENDPOINTS;
const studioEscape = value => String(value ?? '').replace(/[&<>"']/g, char => ({
    '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
})[char]);

function studioSetActive(selector, activeElement) {
    document.querySelectorAll(selector).forEach(element => element.classList.remove('is-active'));
    activeElement.classList.add('is-active');
}

function studioUpdateTime() {
    const countInput = document.getElementById('studio-count');
    let count = Math.max(1, Math.min(50, Number.parseInt(countInput.value, 10) || 1));
    countInput.value = count;
    const minutesPerQuestion = studioState.difficulty === 'easy' ? 1.5 : studioState.difficulty === 'hard' ? 2.5 : 2;
    const minutes = Math.ceil(count * minutesPerQuestion);
    document.getElementById('studio-time').textContent = `${minutes} นาที (~${minutesPerQuestion} นาที/ข้อ)`;
    document.querySelectorAll('.studio-count').forEach(button => {
        button.classList.toggle('is-active', Number(button.dataset.count) === count);
    });
}

document.querySelectorAll('.studio-choice').forEach(button => button.addEventListener('click', () => {
    studioState.difficulty = button.dataset.value;
    studioSetActive('.studio-choice', button);
    studioUpdateTime();
}));

document.querySelectorAll('.studio-count').forEach(button => button.addEventListener('click', () => {
    document.getElementById('studio-count').value = button.dataset.count;
    studioUpdateTime();
}));
document.getElementById('studio-count').addEventListener('input', studioUpdateTime);

document.querySelectorAll('.studio-source').forEach(button => button.addEventListener('click', () => {
    studioState.source = button.dataset.source;
    studioSetActive('.studio-source', button);
    document.getElementById('source-drive').classList.toggle('hidden', studioState.source !== 'drive');
    document.getElementById('source-upload').classList.toggle('hidden', studioState.source !== 'upload');
}));

async function studioUploadFile(file) {
    if (!file) return;
    const label = document.getElementById('studio-file-label');
    const status = document.getElementById('studio-upload-status');
    label.textContent = `กำลังอัปโหลด ${file.name}...`;
    status.classList.add('hidden');
    const form = new FormData();
    form.append('document', file);
    try {
        const response = await fetch(studioEndpoints.upload, { method: 'POST', body: form });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'อัปโหลดไม่สำเร็จ');
        studioState.storedFile = data.storedFile;
        studioState.originalName = data.originalName;
        label.textContent = data.originalName;
        status.textContent = `อัปโหลดและเก็บบน Server แล้ว (${(data.size / 1024 / 1024).toFixed(2)} MB)`;
        status.classList.remove('hidden');
    } catch (error) {
        studioState.storedFile = '';
        studioState.originalName = '';
        label.textContent = 'เลือกไฟล์หรือลากมาวาง';
        alert(error.message);
    }
}

const fileInput = document.getElementById('studio-file');
const dropzone = document.getElementById('studio-dropzone');
fileInput.addEventListener('change', () => studioUploadFile(fileInput.files[0]));
['dragenter', 'dragover'].forEach(eventName => dropzone.addEventListener(eventName, event => {
    event.preventDefault();
    dropzone.classList.add('border-[#2563eb]', 'bg-[#eff6ff]');
}));
['dragleave', 'drop'].forEach(eventName => dropzone.addEventListener(eventName, event => {
    event.preventDefault();
    dropzone.classList.remove('border-[#2563eb]', 'bg-[#eff6ff]');
}));
dropzone.addEventListener('drop', event => studioUploadFile(event.dataTransfer.files[0]));

async function studioKeyStatus() {
    try {
        const response = await fetch(studioEndpoints.settings, { cache: 'no-store' });
        return await response.json();
    } catch (_) {
        return { configured: false };
    }
}

window.openStudioSettings = async function () {
    const modal = document.getElementById('studio-settings');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    const status = await studioKeyStatus();
    document.getElementById('studio-key-status').textContent = status.configured
        ? `ตั้งค่าแล้ว ${status.maskedKey || ''}` : 'ยังไม่ได้ตั้งค่า API Key';
};

window.closeStudioSettings = function () {
    const modal = document.getElementById('studio-settings');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
};

window.saveStudioSettings = async function () {
    const input = document.getElementById('studio-key');
    const apiKey = input.value.trim();
    if (!apiKey) return alert('กรุณากรอก Gemini API Key');
    try {
        const response = await fetch(studioEndpoints.settings, {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ apiKey })
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'บันทึกไม่สำเร็จ');
        input.value = '';
        closeStudioSettings();
    } catch (error) {
        alert(error.message);
    }
};

function studioRenderPreview(meta) {
    const difficultyLabels = { easy: 'ปูพื้นฐาน', medium: 'สนามจริง', hard: 'ห้องพิเศษ / Gifted' };
    document.getElementById('studio-meta').innerHTML = [meta.subject, meta.grade, difficultyLabels[studioState.difficulty], `${studioState.questions.length} ข้อ`]
        .map(text => `<span class="px-2.5 py-1 bg-[#eff6ff] text-[#1d4ed8] rounded text-xs font-bold">${studioEscape(text)}</span>`).join('');
    document.getElementById('studio-questions').innerHTML = studioState.questions.map((question, index) => {
        const options = Array.isArray(question.options) ? question.options : [];
        return `<article class="bg-white border border-[#dce4ef] rounded-lg p-6 max-[640px]:p-4">
          <div class="flex gap-3"><span class="shrink-0 w-8 h-8 grid place-items-center bg-[#eef2ff] text-[#4338ca] rounded font-bold">${index + 1}</span><p class="pt-1 whitespace-pre-wrap font-bold leading-relaxed">${studioEscape(question.questionText)}</p></div>
          <div class="mt-5 grid grid-cols-2 max-[700px]:grid-cols-1 gap-2">${options.map((option, optionIndex) => `<div class="p-3 border rounded-md text-sm ${optionIndex === Number(question.correctAnswerIndex) ? 'border-[#86efac] bg-[#f0fdf4] text-[#166534] font-bold' : 'border-[#e2e8f0]'}">${studioEscape(option)}</div>`).join('')}</div>
          <div class="mt-4 p-3 bg-[#f8fafc] border border-[#e2e8f0] rounded-md text-sm text-[#475569]"><strong class="text-navy-950">คำอธิบาย:</strong> ${studioEscape(question.explanation || 'ไม่มีคำอธิบาย')}</div>
        </article>`;
    }).join('');
    document.getElementById('studio-form').classList.add('hidden');
    document.getElementById('studio-preview').classList.remove('hidden');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

document.getElementById('studio-generate-form').addEventListener('submit', async event => {
    event.preventDefault();
    const subject = document.getElementById('studio-subject').value.trim();
    const grade = document.getElementById('studio-grade').value;
    const topic = document.getElementById('studio-topic').value.trim();
    const count = Number.parseInt(document.getElementById('studio-count').value, 10);
    const driveUrl = document.getElementById('studio-drive-url').value.trim();
    if (!subject) return alert('กรุณาเลือกวิชาเรียน');
    if (studioState.source === 'drive' && !driveUrl) return alert('กรุณาใส่ลิงก์ Google Drive');
    if (studioState.source === 'upload' && !studioState.storedFile) return alert('กรุณาอัปโหลดเอกสารให้เสร็จก่อน');
    const key = await studioKeyStatus();
    if (!key.configured) { alert('กรุณาตั้งค่า Gemini API Key ก่อนเริ่มสร้างข้อสอบ'); openStudioSettings(); return; }

    const type = document.querySelector('input[name="studio-type"]:checked').value;
    const difficultyText = { easy: 'ระดับปูพื้นฐาน', medium: 'ระดับสนามจริง', hard: 'ระดับห้องพิเศษหรือ Gifted' }[studioState.difficulty];
    const details = [`วิชา ${subject}`, `ระดับชั้น ${grade}`, difficultyText, topic ? `เน้นหัวข้อ ${topic}` : 'ครอบคลุมทุกหัวข้อในเอกสาร'].join(', ');
    const loading = document.getElementById('studio-loading');
    loading.classList.remove('hidden');
    loading.classList.add('flex');
    try {
        const response = await fetch(studioEndpoints.generate, {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({
                url: studioState.source === 'drive' ? driveUrl : '',
                storedFile: studioState.source === 'upload' ? studioState.storedFile : '',
                type, count, difficulty: studioState.difficulty, details,
                shuffle: document.getElementById('studio-shuffle').checked,
                useServerKey: true,
            })
        });
        const data = await response.json();
        if (!response.ok) throw new Error(data.error || 'สร้างข้อสอบไม่สำเร็จ');
        if (!Array.isArray(data.questions) || !data.questions.length) throw new Error('AI ไม่ได้ส่งข้อสอบกลับมา');
        studioState.questions = data.questions;
        studioState.meta = { subject, grade, topic, count, type, driveUrl };
        studioRenderPreview(studioState.meta);
    } catch (error) {
        alert(error.message);
    } finally {
        loading.classList.add('hidden');
        loading.classList.remove('flex');
    }
});

document.getElementById('studio-back').addEventListener('click', () => {
    document.getElementById('studio-preview').classList.add('hidden');
    document.getElementById('studio-form').classList.remove('hidden');
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

document.getElementById('studio-save').addEventListener('click', async () => {
    if (!studioState.questions.length) return;
    const button = document.getElementById('studio-save');
    const meta = studioState.meta;
    button.disabled = true;
    button.textContent = 'กำลังบันทึก...';
    try {
        const sourceUrl = studioState.source === 'drive' ? meta.driveUrl : `/assets/uploads/ai-exam/${studioState.storedFile}`;
        const response = await fetch(studioEndpoints.save, {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({
                questions: studioState.questions,
                title: `แบบทดสอบ${meta.subject}จาก AI ${new Date().toLocaleDateString('th-TH')}`,
                subject: meta.subject, grade: meta.grade, topic: meta.topic,
                difficulty: studioState.difficulty, generationMode: meta.type, sourceUrl,
            })
        });
        const data = await response.json();
        if (!response.ok || !data.examId) throw new Error(data.error || 'บันทึกข้อสอบไม่สำเร็จ');
        document.getElementById('studio-save-status').innerHTML = `<strong class="text-[#15803d]">บันทึกเรียบร้อยแล้ว</strong> รหัสข้อสอบ #${studioEscape(data.examId)}`;
        button.textContent = 'เปิดในคลังข้อสอบ';
        button.disabled = false;
        button.onclick = () => { window.location.href = `tests.php?created=${encodeURIComponent(data.examId)}`; };
    } catch (error) {
        alert(error.message);
        button.disabled = false;
        button.textContent = 'บันทึกเข้าคลังข้อสอบ';
    }
});

studioUpdateTime();
