// State
let currentExam = null;
let answers = {};
let isRevealed = false;

async function readApiResponse(response) {
    const raw = await response.text();
    if (!raw) return {};

    try {
        return JSON.parse(raw);
    } catch {
        throw new Error(response.ok
            ? 'เซิร์ฟเวอร์ตอบข้อมูลไม่ถูกต้อง'
            : `เซิร์ฟเวอร์ขัดข้อง (${response.status})`);
    }
}

// View Routing
function goHome() {
    document.getElementById('view-form').classList.remove('hidden');
    document.getElementById('view-exam').classList.add('hidden');
    currentExam = null;
    answers = {};
    isRevealed = false;
}

function toggleType() {
    const type = document.getElementById('examType').value;
    if (type === 'levels') {
        document.getElementById('levels-ui').classList.remove('hidden');
        document.getElementById('normal-count-ui').classList.add('hidden');
    } else {
        document.getElementById('levels-ui').classList.add('hidden');
        document.getElementById('normal-count-ui').classList.remove('hidden');
    }
}

function updateTotal() {
    const e = parseInt(document.getElementById('lvl-easy').value) || 0;
    const m = parseInt(document.getElementById('lvl-medium').value) || 0;
    const h = parseInt(document.getElementById('lvl-hard').value) || 0;
    const ex = parseInt(document.getElementById('lvl-expert').value) || 0;
    document.getElementById('total-levels').innerText = e + m + h + ex;
}

// Settings
async function openSettings() {
    document.getElementById('api-key-input').value = '';
    document.getElementById('api-key-input').placeholder = 'กำลังตรวจสอบ...';
    document.getElementById('settings-modal').classList.remove('hidden');
    try {
        const response = await fetch('ai-settings-api');
        const data = await readApiResponse(response);
        if (!response.ok) throw new Error(data.error || 'ตรวจสอบการตั้งค่าไม่ได้');
        document.getElementById('api-key-input').placeholder = data.configured ? 'บันทึก API Key แล้ว — กรอกใหม่เมื่อต้องการเปลี่ยน' : 'AIzaSy...';
    } catch { document.getElementById('api-key-input').placeholder = 'ตรวจสอบการตั้งค่าไม่ได้'; }
}

function closeSettings() {
    document.getElementById('settings-modal').classList.add('hidden');
}

async function saveSettings() {
    const key = document.getElementById('api-key-input').value.trim();
    if (!key) return alert('กรุณากรอก API Key');
    try {
        const response = await fetch('ai-settings-api', {method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({apiKey:key})});
        const data = await readApiResponse(response);
        if(!response.ok) throw new Error(data.error||'บันทึกไม่สำเร็จ');
        localStorage.removeItem('gemini_api_key'); closeSettings(); alert('บันทึก API Key ลงฐานข้อมูลแล้ว');
    } catch(error) { alert(error.message); }
}

// Form Submit
async function handleGenerate(e) {
    e.preventDefault();
    const driveUrl = document.getElementById('driveUrl').value.trim();
    if (!driveUrl) {
        alert("กรุณาใส่ลิงก์ Google Drive (ต้องตั้งค่าการแชร์เป็น Anyone with the link)");
        return;
    }

    const type = document.getElementById('examType').value;
    let finalCount = parseInt(document.getElementById('qCount').value) || 10;
    let countsObj = null;

    if (type === 'levels') {
        countsObj = {
            easy: parseInt(document.getElementById('lvl-easy').value) || 0,
            medium: parseInt(document.getElementById('lvl-medium').value) || 0,
            hard: parseInt(document.getElementById('lvl-hard').value) || 0,
            expert: parseInt(document.getElementById('lvl-expert').value) || 0,
        };
        finalCount = Object.values(countsObj).reduce((a, b) => a + b, 0);
        if (finalCount === 0) {
            alert("กรุณาระบุจำนวนข้อสอบอย่างน้อย 1 ระดับ");
            return;
        }
    }

    const details = document.getElementById('details').value;
    const shuffle = document.getElementById('shuffle').checked;

    document.getElementById('loading-overlay').classList.remove('hidden');
    document.getElementById('loading-overlay').classList.add('flex');
    document.getElementById('btn-submit').disabled = true;

    try {
        const res = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                url: driveUrl,
                type: type,
                count: finalCount,
                counts: countsObj,
                details: details,
                shuffle: shuffle,
                apiKey: ''
            })
        });

        const data = await readApiResponse(res);
        if (!res.ok) throw new Error(data.error || 'เกิดข้อผิดพลาดจากเซิร์ฟเวอร์');
        if (!data.questions || data.questions.length === 0) throw new Error('AI ไม่สามารถสร้างข้อสอบได้ โปรดตรวจสอบเอกสารต้นฉบับ');

        currentExam = data.questions;
        answers = {};
        isRevealed = false;

        const generatedExam = {
            title: `แบบทดสอบจาก AI ${new Date().toLocaleDateString('th-TH')}`,
            subject: 'ทั่วไป',
            grade: 'ทุกระดับ',
            topic: details,
            generationMode: type,
            sourceUrl: driveUrl,
            questions: currentExam
        };
        const saveResponse = await fetch('exams-api', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(generatedExam)
        });
        const saveResult = await readApiResponse(saveResponse);
        if (!saveResponse.ok) {
            throw new Error(saveResult.error || 'ไม่สามารถบันทึกข้อสอบลงฐานข้อมูลได้');
        }
        const savedNotice = document.getElementById('exam-saved-notice');
        if (savedNotice) {
            savedNotice.textContent = `บันทึกข้อสอบ #${saveResult.examId} เรียบร้อยแล้ว กำลังเปิดคลังข้อสอบ...`;
            savedNotice.classList.remove('hidden');
        }
        window.location.href = `tests.php?created=${encodeURIComponent(saveResult.examId)}`;
        return;
        
        document.getElementById('exam-meta').innerHTML = `
            <span class="bg-pink-50 text-pink-600 font-bold text-[11px] px-2.5 py-1 rounded-md tracking-wide uppercase">ประเภท: ${type}</span>
            <span class="bg-[#f4f7fb] text-[#65738a] font-bold text-[11px] px-2.5 py-1 rounded-md tracking-wide">จำนวน: ${currentExam.length} ข้อ</span>
        `;
        
        renderExam();
        
        document.getElementById('view-form').classList.add('hidden');
        document.getElementById('view-exam').classList.remove('hidden');
        window.scrollTo({ top: 0, behavior: 'smooth' });

    } catch (error) {
        alert("Error: " + error.message);
    } finally {
        document.getElementById('loading-overlay').classList.add('hidden');
        document.getElementById('loading-overlay').classList.remove('flex');
        document.getElementById('btn-submit').disabled = false;
    }
}

// Exam Rendering
function renderExam() {
    const container = document.getElementById('questions-container');
    container.innerHTML = '';

    currentExam.forEach((q, qIndex) => {
        const isCorrect = answers[qIndex] === q.correctAnswerIndex;
        const qDiv = document.createElement('div');
        qDiv.className = "bg-white rounded-[20px] shadow-[0_4px_24px_rgba(15,42,83,0.03)] border border-[#e8ecf2] p-6";
        
        let html = `<div class="text-[16px] font-bold text-navy-950 mb-5 whitespace-pre-wrap leading-relaxed"><span class="font-black mr-2 text-pink-500">ข้อ ${qIndex + 1}.</span>${q.questionText}</div><div class="space-y-3">`;

        q.options.forEach((opt, oIndex) => {
            const isSelected = answers[qIndex] === oIndex;
            const isActualAnswer = q.correctAnswerIndex === oIndex;
            
            let optionClass = "flex items-center p-3.5 rounded-xl border-2 transition-all cursor-pointer ";
            let markerClass = "w-5 h-5 rounded-full border-2 mr-3.5 flex items-center justify-center shrink-0 transition-colors ";
            let markerInner = "";

            if (!isRevealed) {
                optionClass += isSelected ? "border-pink-500 bg-pink-50/50" : "border-[#e8ecf2] hover:border-pink-300 hover:bg-[#f8fafc]";
                markerClass += isSelected ? "border-pink-500" : "border-[#cbd5e1]";
                if (isSelected) markerInner = '<div class="w-2.5 h-2.5 bg-pink-500 rounded-full"></div>';
            } else {
                optionClass += " cursor-default ";
                if (isActualAnswer) {
                    optionClass += "border-[#22c55e] bg-[#f0fdf4] text-[#166534] font-bold";
                    markerClass += "border-[#22c55e] bg-[#22c55e] text-white";
                    markerInner = '<svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" /></svg>';
                } else if (isSelected && !isCorrect) {
                    optionClass += "border-[#ef4444] bg-[#fef2f2] text-[#b91c1c]";
                    markerClass += "border-[#ef4444] bg-[#ef4444] text-white";
                    markerInner = '<svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>';
                } else {
                    optionClass += "border-[#e8ecf2] opacity-50";
                    markerClass += "border-[#cbd5e1]";
                }
            }

            html += `<div class="${optionClass}" onclick="selectOption(${qIndex}, ${oIndex})"><div class="${markerClass}">${markerInner}</div><span class="flex-1 text-[14px] font-medium leading-snug ${isRevealed && isActualAnswer ? 'text-[#166534]' : 'text-navy-950'}">${opt}</span></div>`;
        });

        html += `</div>`;

        if (isRevealed && q.explanation) {
            html += `
            <div class="mt-5 p-4.5 bg-[#f8fafc] rounded-xl border border-[#e8ecf2]">
              <h4 class="flex items-center font-bold text-navy-950 mb-1.5 text-[14px]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1.5 text-[#65738a]" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" /></svg>
                คำอธิบายเฉลย
              </h4>
              <p class="text-[#65738a] text-[13px] font-medium whitespace-pre-wrap leading-relaxed ml-[26px]">${q.explanation}</p>
            </div>`;
        }

        qDiv.innerHTML = html;
        container.appendChild(qDiv);
    });

    // Hide/Show correct action buttons
    if (isRevealed) {
        document.getElementById('exam-actions').classList.add('hidden');
        document.getElementById('exam-actions').classList.remove('flex');
        
        document.getElementById('exam-actions-done').classList.remove('hidden');
        document.getElementById('exam-actions-done').classList.add('flex');
    } else {
        document.getElementById('exam-actions').classList.remove('hidden');
        document.getElementById('exam-actions').classList.add('flex');
        
        document.getElementById('exam-actions-done').classList.add('hidden');
        document.getElementById('exam-actions-done').classList.remove('flex');
        
        document.getElementById('exam-result').classList.add('hidden');
        document.getElementById('exam-view-mode').classList.add('hidden');
    }
}

function selectOption(qIndex, oIndex) {
    if (isRevealed) return;
    answers[qIndex] = oIndex;
    renderExam();
}

function submitExam() {
    if (Object.keys(answers).length < currentExam.length) {
        if (!confirm('คุณยังทำข้อสอบไม่ครบทุกข้อ ต้องการส่งคำตอบใช่หรือไม่?')) return;
    }
    
    let correct = 0;
    currentExam.forEach((q, index) => {
        if (answers[index] === q.correctAnswerIndex) correct++;
    });

    document.getElementById('score-display').innerText = `${correct} / ${currentExam.length}`;
    document.getElementById('exam-result').classList.remove('hidden');
    
    isRevealed = true;
    renderExam();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function showAnswersOnly() {
    if (!confirm('คุณต้องการเปิดดูเฉลยทั้งหมดโดยไม่บันทึกคะแนนใช่หรือไม่?')) return;
    
    document.getElementById('exam-view-mode').classList.remove('hidden');
    isRevealed = true;
    renderExam();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function loadPdfLibrary() {
    if (window.html2pdf) return Promise.resolve();
    return new Promise((resolve, reject) => {
        const existing = document.querySelector('script[data-html2pdf]');
        if (existing) {
            existing.addEventListener('load', resolve, { once: true });
            existing.addEventListener('error', reject, { once: true });
            return;
        }
        const script = document.createElement('script');
        script.src = 'https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js';
        script.dataset.html2pdf = 'true';
        script.onload = resolve;
        script.onerror = () => reject(new Error('โหลดระบบสร้าง PDF ไม่สำเร็จ'));
        document.head.appendChild(script);
    });
}

async function downloadPDF() {
    try {
        await loadPdfLibrary();
    } catch (error) {
        alert(error.message);
        return;
    }
    const element = document.getElementById('view-exam');
    const opt = {
        margin:       10,
        filename:     'AI_Exam_NextBeyond.pdf',
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true, logging: false },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };

    // Temporarily hide buttons and top-bar during capture
    const examActions = document.getElementById('exam-actions');
    const examActionsDone = document.getElementById('exam-actions-done');
    const backBtn = element.querySelector('button[onclick="goHome()"]');
    
    if (examActions) examActions.style.display = 'none';
    if (examActionsDone) examActionsDone.style.display = 'none';
    if (backBtn) backBtn.style.display = 'none';

    // Show loading indicator or change button text
    const oldTitle = document.title;
    document.title = "Generating PDF...";

    html2pdf().set(opt).from(element).save().then(() => {
        // Restore elements
        if (examActions) examActions.style.display = '';
        if (examActionsDone) examActionsDone.style.display = '';
        if (backBtn) backBtn.style.display = '';
        document.title = oldTitle;
    });
}
