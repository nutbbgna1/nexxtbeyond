/* Science Studio: each queue request persists exactly one generation job. */
'use strict';
(() => {
  const $ = selector => document.querySelector(selector);
  const $$ = selector => [...document.querySelectorAll(selector)];
  const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const icon = name => `<i data-lucide="${name}"></i>`;
  const csrf = $('meta[name="csrf-token"]').content;
  const branches = {physics:'ฟิสิกส์',chemistry:'เคมี',biology:'ชีววิทยา',earth:'โลกและอวกาศ',integrated:'วิทยาศาสตร์บูรณาการ'};
  let current=null, activeTab='outline', partId=null, episodeId=null, pumping=false, editorDraft=null;
  let apiReady=false, noticeTimer, quizResult=null;
  const drawIcons = () => window.lucide?.createIcons();
  const delay = ms => new Promise(resolve => setTimeout(resolve,ms));
  function notify(message,error=false) {
    clearTimeout(noticeTimer); const n=$('#notice'); n.hidden=false; n.textContent=message; n.classList.toggle('error',error);
    noticeTimer=setTimeout(()=>{n.hidden=true;},8000);
  }
  async function request(action,body=null) {
    const res=await fetch(body===null?`api.php?action=${encodeURIComponent(action)}`:'api.php',body===null?{cache:'no-store'}:{
      method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':csrf},body:JSON.stringify({action,...body})});
    let data; try { data=await res.json(); } catch { throw new Error('เซิร์ฟเวอร์ตอบกลับไม่สมบูรณ์ กรุณาโหลดหลักสูตรใหม่'); }
    if (!res.ok) throw new Error(data.error || `เกิดข้อผิดพลาด (${res.status})`);
    return data;
  }
  async function getProject(id) {
    const res=await fetch(`api.php?action=get&id=${encodeURIComponent(id)}`,{cache:'no-store'});
    const data=await res.json(); if (!res.ok) throw new Error(data.error); return data.project;
  }
  async function mutate(action,body={}) {
    const {project}=await request(action,{id:current.id,revision:current.revision,...body});
    current=project; render(); await loadList(); return project;
  }
  function episodes() { return current?.data.chapters.flatMap(c=>c.episodes.map(e=>({...e,chapter:c.title}))) ?? []; }
  function parts() { return episodes().flatMap(e=>e.parts.map(p=>({...p,episode:e.title,episodeId:e.id}))); }
  function canEdit() { return current && !current.busy && current.run_state!=='running'; }
  function empty(title,text,extra='') { return `<div class="empty">${icon('notebook-pen')}<h3>${esc(title)}</h3><p>${esc(text)}</p>${extra}</div>`; }
  function status(part) { return `<span class="status ${part.approved?'ready':part.content?'review':''}">${part.approved?'ผ่านตรวจ':part.content?'รอตรวจ':'รอสร้าง'}</span>`; }
  function illustration(content,part) {
    if (content?.illustration?.url) {
      const caption=content.illustration.status==='fallback'?'ภาพประกอบสำรองจากระบบ · ใช้แทนภาพ AI เมื่อโควตาไม่พร้อม':'ภาพร่างจาก AI · กรุณาตรวจความถูกต้องก่อนเผยแพร่';
      return `<figure class="lesson-illustration"><img src="${esc(content.illustration.url)}" alt="ภาพประกอบ ${esc(part.title)}" loading="lazy"><figcaption>${caption}</figcaption></figure>`;
    }
    if (content?.imageError) return `<div class="image-alert">${icon('image-off')}<div><strong>ยังสร้างภาพประกอบไม่ได้</strong><small>${esc(content.imageError)}</small></div></div>`;
    if (content?.imagePrompt) return `<div class="image-alert">${icon('image')}<div><strong>มีคำสั่งภาพพร้อมแล้ว</strong><small>กดปุ่มสร้างภาพประกอบใหม่เพื่อสร้างรูปกำกับ Part นี้</small></div></div>`;
    return '';
  }
  async function loadList() {
    const data=await request('list'); $('#project-count').textContent=data.projects.length;
    $('#projects').innerHTML=data.projects.length?data.projects.map(p=>`<button class="project-link ${current?.id===p.id?'active':''}" data-project="${p.id}">${esc(p.title)}<small>${esc(p.stage)}</small></button>`).join(''):'<p class="muted" style="font-size:12px;padding:12px">ยังไม่มีหลักสูตร</p>';
  }
  async function openProject(id) {
    if (pumping && current?.id!==id) { notify('กรุณาหยุดคิวก่อนเปลี่ยนหลักสูตร',true); return; }
    current=await getProject(id); partId=null; episodeId=null; quizResult=null;
    location.hash=current.id; $('#create-view').hidden=true; $('#project-view').hidden=false;
    $('#sidebar').classList.remove('open'); render(); await loadList();
  }
  function render() {
    if (!current) return;
    const p=current.data, eps=episodes(), ps=parts(), done=ps.filter(x=>x.content).length;
    $('#project-title').textContent=p.config.title;
    $('#project-branches').textContent=p.config.branches.map(b=>branches[b]).join(' / ');
    $('#project-info').textContent=`${p.config.grade} · ${p.config.difficulty} · ${p.config.sessionMinutes} นาที / Part`;
    $('#project-summary').innerHTML=[[p.chapters.length,'บทเรียน'],[eps.length,'EP'],[ps.length,'Part'],[`${done}/${ps.length}`,'สร้างเนื้อหาแล้ว']].map(([n,t])=>`<div class="metric"><strong>${n}</strong><small>${t}</small></div>`).join('');
    const staleModelError=['ไม่พบโมเดล','ไม่พบโมเดล Gemini ที่รองรับ'].includes(p.error)&&apiReady;
    $('#queue-title').textContent=staleModelError?'พร้อมลองเชื่อมต่อโมเดลอีกครั้ง':p.error || current.stage;
    const last=p.history.at(-1);
    $('#queue-detail').textContent=current.busy?'กำลังประมวลผลงานปัจจุบัน':current.run_state==='running'?'คิวพร้อมทำงานต่อ':last?`ล่าสุด: ${last.task}`:'พร้อมวางโครงบทเรียน';
    $('#approve-outline').hidden=current.stage!=='รอตรวจโครง';
    $('#approve-outline').disabled=!canEdit();
    $('#step-once').hidden=pumping;
    $('#step-once').disabled=['รอตรวจโครง','พร้อมเรียน'].includes(current.stage) || !apiReady || current.busy;
    $('#step-once span').textContent=!p.chapters.length?'สร้าง 1 งาน':current.stage==='รอตรวจเนื้อหา'?'สร้างข้อสอบ 1 งาน':'ทำ 1 งาน';
    $('#run-queue').hidden=pumping;
    $('#run-queue').disabled=['รอตรวจโครง','พร้อมเรียน'].includes(current.stage) || !apiReady;
    $('#run-queue span').textContent=!p.chapters.length?'เริ่มสร้างโครง':current.stage==='รอตรวจเนื้อหา'?'สร้างข้อสอบที่ผ่านตรวจ':'ทำต่อ';
    $('#pause-queue').hidden=current.run_state!=='running' && !current.busy;
    $('#pause-queue').disabled=current.run_state==='paused';
    $$('.tabs button').forEach(b=>b.setAttribute('aria-selected',String(b.dataset.tab===activeTab)));
    ({outline:renderOutline,learn:renderLearn,quiz:renderQuiz,plan:renderPlan,jobs:renderJobs}[activeTab])(); drawIcons();
  }
  function renderOutline() {
    const p=current.data;
    $('#tab-content').innerHTML=`<div class="section-toolbar"><div><h2>โครงสร้างการเรียนรู้</h2><p>${esc(p.config.goal)}</p></div><button data-action="edit-outline" ${!canEdit()||p.outlineApproved?'disabled':''}>${icon('pencil')}แก้ไขโครง</button></div>`+
      (!p.chapters.length?empty('เริ่มต้นด้วยโครงบทเรียน','Gemini จะจัดลำดับบท EP และ Part ตามเป้าหมายของหลักสูตร'):p.chapters.map((c,ci)=>`<section class="chapter"><div class="chapter-head"><span class="chapter-number">${String(ci+1).padStart(2,'0')}</span><div><h2>${esc(c.title)}</h2><p>${esc(c.objective)}</p></div></div>${!c.episodes.length?'<p class="muted">รอวาง EP</p>':c.episodes.map((e,ei)=>`<details class="episode" open><summary><span class="ep-number">EP ${ei+1}</span><span>${esc(e.title)}</span><span class="tag">${e.parts.length} Part</span></summary>${!e.parts.length?'<div class="part-row muted">รอแบ่ง Part</div>':e.parts.map((part,pi)=>`<div class="part-row"><span class="muted">${pi+1}.</span><span class="part-title">${esc(part.title)}</span><small>${part.minutes} นาที</small>${status(part)}<button data-action="read" data-id="${part.id}" ${!part.content?'disabled':''}>${icon('arrow-up-right')}เปิด</button></div>`).join('')}</details>`).join('')}</section>`).join(''));
  }
  function renderLearn() {
    const ps=parts(); if (!ps.length) { $('#tab-content').innerHTML=empty('ยังไม่มีเนื้อหาเรียน','สร้างและยืนยันโครงบทก่อนเริ่มสร้างเนื้อหา'); return; }
    let part=ps.find(p=>p.id===partId) || ps.find(p=>p.content) || ps[0]; partId=part.id;
    const content=part.content;
    $('#tab-content').innerHTML=`<div class="reading-layout"><nav class="reading-nav" aria-label="เลือก Part">${episodes().map((e,i)=>`<h3>EP ${i+1} · ${esc(e.title)}</h3>${e.parts.map((p,pi)=>`<button class="${p.id===partId?'selected':''}" data-action="read" data-id="${p.id}">${icon(current.data.completed.includes(p.id)?'circle-check':'circle')}<span>${pi+1}. ${esc(p.title)}</span></button>`).join('')}`).join('')}</nav><article class="reading-pane"><div class="eyebrow">${esc(part.episode)} · ${part.minutes} นาที</div><h2>${esc(part.title)}</h2><p class="objective">${esc(part.objective)}</p>${!content?empty('Part นี้ยังไม่มีเนื้อหา','กดทำต่อเพื่อสร้างเนื้อหาตามลำดับ'): `${status(part)}<div class="reading-actions"><button data-action="edit-part" data-id="${part.id}" ${canEdit()?'':'disabled'}>${icon('pencil')}แก้ไข</button><button data-action="approve-part" data-id="${part.id}" ${!canEdit()||part.approved?'disabled':''}>${icon('check')}ผ่านการตรวจ</button><button class="icon" data-action="generate-image" data-id="${part.id}" title="สร้างภาพประกอบใหม่" aria-label="สร้างภาพประกอบใหม่" ${canEdit()?'':'disabled'}>${icon('image-plus')}</button><button class="icon" data-action="regenerate" data-id="${part.id}" title="สร้าง Part นี้ใหม่" aria-label="สร้าง Part นี้ใหม่" ${canEdit()?'':'disabled'}>${icon('rotate-cw')}</button><button class="icon" data-action="print" title="พิมพ์เนื้อหา" aria-label="พิมพ์เนื้อหา">${icon('printer')}</button></div>${illustration(content,part)}${content.sections.map(s=>`<section class="lesson-section"><h3>${esc(s.heading)}</h3><p class="prose">${esc(s.body)}</p></section>`).join('')}<section class="lesson-section"><h3>ตัวอย่างและวิธีคิด</h3><p class="prose">${esc(content.example)}</p></section><section class="exercise"><h3>ลองทำด้วยตนเอง</h3><p class="prose">${esc(content.exercise)}</p><details><summary>คำใบ้</summary><p class="prose">${esc(content.hint)}</p></details><details><summary>เฉลยและวิธีทำ</summary><p class="prose">${esc(content.solution)}</p></details></section><section class="lesson-section"><h3>สรุปสิ่งที่ได้เรียนรู้</h3><p class="prose">${esc(content.summary)}</p></section><div class="reading-actions"><button class="primary" data-action="complete-part" data-id="${part.id}" ${!canEdit()||!part.approved||current.data.completed.includes(part.id)?'disabled':''}>${icon('check-check')}${current.data.completed.includes(part.id)?'เรียนจบแล้ว':'เรียนจบ Part นี้'}</button><button data-action="next-part">Part ถัดไป ${icon('arrow-right')}</button></div>`}</article></div>`;
  }
  function renderQuiz() {
    const eps=episodes().filter(e=>e.questions.length===current.data.config.questionsPerEp);
    if (!eps.length) { $('#tab-content').innerHTML=empty('ข้อสอบยังไม่พร้อม','ตรวจเนื้อหาแต่ละ Part แล้วกดสร้างข้อสอบที่ผ่านตรวจ'); return; }
    const ep=eps.find(e=>e.id===episodeId)||eps[0]; episodeId=ep.id;
    const result=quizResult?.episodeId===ep.id?quizResult:null;
    $('#tab-content').innerHTML=`<label class="quiz-select">เลือก EP<select id="quiz-episode">${eps.map(e=>`<option value="${e.id}" ${e.id===ep.id?'selected':''}>${esc(e.title)} (${e.questions.length} ข้อ)</option>`).join('')}</select></label>${result?`<div class="score"><strong>${result.score} / ${result.total}</strong><p>คะแนนทดลองทำ · ${result.review.length?'ทบทวน '+result.review.map(id=>esc(parts().find(p=>p.id===id)?.title||'')).join(', '):'ตอบถูกครบทุกข้อ'}</p></div>`:''}<form id="quiz-form">${ep.questions.map((q,i)=>`<section class="question"><h3>${i+1}. ${esc(q.question)}</h3>${q.options.map((op,oi)=>`<label><input type="radio" name="${q.id}" value="${oi}" ${result?'disabled':''}><span>${String.fromCharCode(65+oi)}. ${esc(op)}</span></label>`).join('')}${result?`<div class="answer">คำตอบ: ${String.fromCharCode(65+q.answer)}\n${esc(q.explanation)}</div>`:''}</section>`).join('')}<div class="reading-actions">${!result?`<button type="submit" class="primary" ${canEdit()?'':'disabled'}>${icon('check')}ส่งคำตอบ</button>`:'<button type="button" data-action="retry-quiz">ทดลองทำอีกครั้ง</button>'}</div></form>`;
  }
  const dateLabel = value => new Date(value+'T12:00:00').toLocaleDateString('th-TH',{day:'numeric',month:'short'});
  function renderPlan() {
    const plan=current.data.schedule, s=plan?.settings||{start:new Date().toLocaleDateString('en-CA'),days:[1,3,5],minutes:45,deadline:''};
    $('#tab-content').innerHTML=`<div class="section-toolbar"><div><h2>แผนการเรียนรายสัปดาห์</h2><p>${plan?`คาดว่าจะเรียนจบ ${dateLabel(plan.end)}`:'จัดเวลาเรียน ฝึกทำ และทบทวนตาม EP'}</p></div><button class="icon" data-action="print" title="พิมพ์แผนเรียน" aria-label="พิมพ์แผนเรียน">${icon('printer')}</button></div><form id="plan-form" class="plan-settings"><label>วันเริ่มเรียน<input type="date" name="start" value="${esc(s.start)}" required></label><label>นาทีต่อวัน<input type="number" name="minutes" min="15" max="180" value="${s.minutes}" required></label><label>วันเป้าหมาย (ไม่บังคับ)<input type="date" name="deadline" value="${esc(s.deadline)}"></label><div class="day-picker">${['จ.','อ.','พ.','พฤ.','ศ.','ส.','อา.'].map((label,i)=>`<label><input type="checkbox" name="days" value="${i+1}" ${s.days.includes(i+1)?'checked':''}>${label}</label>`).join('')}</div><div><button type="submit" class="primary" ${!canEdit()?'disabled':''}>${icon('calendar-days')}${plan?'จัดแผนใหม่':'สร้างแผนการเรียน'}</button></div></form>${plan?.overdue?'<p class="plan-warning">เวลาเรียนที่เลือกไม่พอกับวันเป้าหมาย กรุณาเพิ่มวันหรือเวลาเรียน หรือขยายวันเป้าหมาย</p>':''}<div id="schedule-items"></div>`;
    if (!plan) return;
    let week=-1;
    $('#schedule-items').innerHTML=plan.items.map(item=>{
      const w=Math.floor((Date.parse(item.date+'T12:00:00')-Date.parse(s.start+'T12:00:00'))/604800000)+1;
      const heading=w!==week?`<h3 class="plan-week">สัปดาห์ที่ ${w}</h3>`:''; week=w;
      return `${heading}<div class="plan-row"><span>${dateLabel(item.date)}</span><div>${esc(item.label)} ${item.segment>1?`(ช่วง ${item.segment})`:''}<small class="muted">${current.data.completed.includes(item.id)?' · เรียนจบแล้ว':''}</small></div><span class="muted">${item.minutes} นาที</span></div>`;
    }).join('');
  }
  function renderJobs() {
    const history=current.data.history;
    $('#tab-content').innerHTML=`<div class="section-toolbar"><div><h2>ประวัติงานล่าสุด</h2><p>${current.data.model?esc(current.data.model.name.replace('models/','')):'ยังไม่ได้เลือกโมเดล'} · ${history.length} งาน</p></div></div>`+(history.length?[...history].reverse().map(job=>`<div class="job"><span class="${job.status}">${({success:'สำเร็จ',failed:'ล้มเหลว',split:'แบ่ง Part',retrying:'กำลังแก้ไข'})[job.status]||esc(job.status)}</span><div>${esc(job.task)}<small>${esc(job.message||job.model||'')}</small></div><small>${new Date(job.at).toLocaleTimeString('th-TH')}<br>${job.usage?`${job.usage.totalTokenCount??job.usage.promptTokenCount??0} tokens`:''}</small></div>`).join(''):empty('ยังไม่มีประวัติ','งานแต่ละส่วนจะบันทึกผลเมื่อสร้างเสร็จ'));
  }
  async function pump() {
    if (pumping) return; pumping=true; render();
    const id=current.id; let connectionRetries=0;
    try {
      while (current?.id===id && current.run_state==='running') {
        let data;
        try { data=await request('step',{id}); connectionRetries=0; }
        catch(error) {
          if (++connectionRetries>5) throw error;
          notify(`การเชื่อมต่อสะดุด ระบบกำลังต่อใหม่ (${connectionRetries}/5)`);
          await delay(Math.min(15000,1500*connectionRetries)); continue;
        }
        current=data.project; render();
        if (current.data.error) notify(current.data.error,true);
        const last=current.data.history.at(-1);
        await delay(last?.status==='retrying'?(last.retryAfter||1500):(current.busy?2500:400));
      }
    } catch (error) {
      notify(error.message,true);
      try { await request('pause',{id}); current=await getProject(id); } catch { /* Reopening the course recovers the durable lease. */ }
    } finally { pumping=false; render(); await loadList(); }
  }
  async function start() {
    $('#run-queue').disabled=true;
    try { const data=await request('start',{id:current.id}); current=data.project; await pump(); }
    catch(error) {notify(error.message,true);render();}
  }
  async function stepOnce() {
    $('#step-once').disabled=true;
    try {
      let data=await request('start',{id:current.id});
      current=data.project; render();
      data=await request('step',{id:current.id});
      current=data.project;
      if (current.run_state==='running') current=(await request('pause',{id:current.id})).project;
      render();
      if (current.data.error) notify(current.data.error,true);
      else notify('ทำงานย่อยนี้เสร็จแล้ว');
    } catch(error) {
      notify(error.message,true);
      try { await request('pause',{id:current.id}); current=await getProject(current.id); } catch { /* Best effort pause after a single-step error. */ }
      render();
    }
  }
  function editOutline() {
    editorDraft=structuredClone(current.data.chapters);
    if (!editorDraft.length) editorDraft=[blankChapter()];
    $('#editor-title').textContent='แก้ไขบท / EP / Part'; renderOutlineEditor(); $('#editor-dialog').showModal();
  }
  const blankPart=()=>({title:'Part ใหม่',objective:'เป้าหมายของ Part',minutes:45});
  const blankEpisode=()=>({title:'EP ใหม่',objective:'เป้าหมายของ EP',parts:[blankPart()]});
  const blankChapter=()=>({title:'บทใหม่',objective:'เป้าหมายของบท',episodes:[blankEpisode()]});
  function controls(path) {return `<div class="edit-controls">${[['up','arrow-up','เลื่อนขึ้น'],['down','arrow-down','เลื่อนลง'],['remove','trash-2','ลบรายการ']].map(([act,ico,title])=>`<button type="button" data-edit="${act}" data-path="${path}" title="${title}" aria-label="${title}">${icon(ico)}</button>`).join('')}</div>`;}
  function renderOutlineEditor() {
    $('#editor-content').innerHTML=`<form id="outline-form">${editorDraft.map((c,ci)=>`<section class="edit-chapter"><div class="edit-title-row"><input aria-label="ชื่อบท ${ci+1}" data-field="${ci}.title" value="${esc(c.title)}" maxlength="180" required>${controls(String(ci))}</div><label>เป้าหมายบท<textarea data-field="${ci}.objective" maxlength="800" required>${esc(c.objective)}</textarea></label>${c.episodes.map((e,ei)=>`<div class="edit-episode"><div class="edit-title-row"><input aria-label="ชื่อ EP ${ei+1}" data-field="${ci}.episodes.${ei}.title" value="${esc(e.title)}" required maxlength="180">${controls(`${ci}.episodes.${ei}`)}</div><label>เป้าหมาย EP<textarea data-field="${ci}.episodes.${ei}.objective" maxlength="800" required>${esc(e.objective)}</textarea></label>${e.parts.map((p,pi)=>`<div class="edit-part"><label>ชื่อ Part<input data-field="${ci}.episodes.${ei}.parts.${pi}.title" value="${esc(p.title)}" required maxlength="180"></label><label>นาที<input type="number" min="5" max="60" data-field="${ci}.episodes.${ei}.parts.${pi}.minutes" value="${p.minutes}" required></label>${controls(`${ci}.episodes.${ei}.parts.${pi}`)}<label class="part-objective">เป้าหมาย Part<textarea data-field="${ci}.episodes.${ei}.parts.${pi}.objective" maxlength="800" required>${esc(p.objective)}</textarea></label></div>`).join('')}<button type="button" class="edit-add" data-edit="add-part" data-path="${ci}.episodes.${ei}">${icon('plus')}เพิ่ม Part</button></div>`).join('')}<button type="button" class="edit-add" data-edit="add-episode" data-path="${ci}">${icon('plus')}เพิ่ม EP</button></section>`).join('')}<button type="button" class="edit-add" data-edit="add-chapter" data-path="">${icon('plus')}เพิ่มบท</button><footer><button type="submit" class="primary">บันทึกโครง</button></footer></form>`;
    $('#editor-content').querySelectorAll('input[type="number"][data-field$=".minutes"]').forEach(input=>{input.min='30';input.max='90';});
    drawIcons();
  }
  function editPart(id) {
    const part=parts().find(p=>p.id===id); partId=id;
    if (!part?.content) return;
    $('#editor-title').textContent=part.title;
    const c=part.content;
    $('#editor-content').innerHTML=`<form id="content-form" class="content-editor">${c.sections.map((s,i)=>`<label>หัวข้อ ${i+1}<input name="heading-${i}" value="${esc(s.heading)}" required maxlength="180"></label><label>เนื้อหา<textarea name="body-${i}" rows="6" required maxlength="10000">${esc(s.body)}</textarea></label>`).join('')}${[['example','ตัวอย่างและวิธีคิด',8000],['exercise','แบบฝึกหัด',3000],['hint','คำใบ้',1000],['solution','เฉลย',5000],['summary','สรุป',1200],['imagePrompt','คำสั่งสร้างภาพประกอบ',4000]].map(([key,label,max])=>`<label>${label}<textarea name="${key}" required maxlength="${max}">${esc(c[key])}</textarea></label>`).join('')}<p class="muted">เมื่อแก้เนื้อหา ต้องตรวจรับและสร้างข้อสอบของ EP นี้ใหม่</p><footer><button class="primary" type="submit">บันทึกเนื้อหา</button></footer></form>`;
    $('#editor-dialog').showModal(); drawIcons();
  }
  document.addEventListener('input',e=>{
    if (!e.target.dataset.field) return; const path=e.target.dataset.field.split('.'),key=path.pop();
    const parent=path.reduce((obj,k)=>obj[k],editorDraft); parent[key]=key==='minutes'?Number(e.target.value):e.target.value;
  });
  document.addEventListener('click',async e=>{
    const button=e.target.closest('button'); if (!button) return;
    try {
      if (button.dataset.project) return await openProject(button.dataset.project);
      if (button.dataset.tab) {activeTab=button.dataset.tab;render();return;}
      if (button.classList.contains('close-dialog')) {button.closest('dialog').close();return;}
      if (button.dataset.edit) {
        const action=button.dataset.edit, path=button.dataset.path.split('.').filter(Boolean);
        if (action==='add-chapter') editorDraft.push(blankChapter());
        else if (action==='add-episode') path.reduce((o,k)=>o[k],editorDraft).episodes.push(blankEpisode());
        else if (action==='add-part') path.reduce((o,k)=>o[k],editorDraft).parts.push(blankPart());
        else {
        const i=Number(path.pop()),arr=path.reduce((o,k)=>o[k],editorDraft);
          if (action==='remove') {if (arr.length<=1) return notify('ต้องมีอย่างน้อยหนึ่งรายการ',true);arr.splice(i,1);}
          else {const next=i+(action==='up'?-1:1);if(next>=0&&next<arr.length)[arr[i],arr[next]]=[arr[next],arr[i]];}
        }
        renderOutlineEditor(); return;
      }
      switch(button.dataset.action) {
        case 'edit-outline': editOutline(); break;
        case 'read':partId=button.dataset.id;activeTab='learn';render();break;
        case 'edit-part':editPart(button.dataset.id);break;
        case 'approve-part':await mutate('approvePart',{partId:button.dataset.id});notify('บันทึกผลการตรวจแล้ว');break;
        case 'regenerate':
          if(confirm('สร้าง Part นี้ใหม่และยกเลิกข้อสอบเดิมของ EP นี้?')) await mutate('regenerate',{partId:button.dataset.id});break;
        case 'generate-image': {
          const part=parts().find(p=>p.id===button.dataset.id);
          const prompt=window.prompt('แก้คำสั่งภาพก่อนสร้างใหม่',part?.content?.imagePrompt||'');
          if(prompt!==null&&prompt.trim()){notify('กำลังสร้างภาพประกอบ...');await mutate('image',{partId:button.dataset.id,prompt:prompt.trim()});notify('สร้างภาพร่างใหม่แล้ว กรุณาตรวจความถูกต้อง');}
          break;
        }
        case 'complete-part':await mutate('completePart',{partId:button.dataset.id});break;
        case 'next-part':{const ps=parts(),index=ps.findIndex(p=>p.id===partId);if(index<ps.length-1){partId=ps[index+1].id;render();}else notify('ถึง Part สุดท้ายแล้ว');break;}
        case 'print':window.print();break;
        case 'retry-quiz':quizResult=null;render();break;
      }
    } catch(error) {notify(error.message,true);}
  });
  document.addEventListener('submit',async e=>{
    const form=e.target; if (!['create-form','settings-form','outline-form','content-form','plan-form','quiz-form'].includes(form.id)) return;
    e.preventDefault(); const submit=form.querySelector('[type=submit]'); if(submit)submit.disabled=true;
    try {
      const fd=new FormData(form);
      switch(form.id) {
        case 'create-form': {
          const config=Object.fromEntries(fd); config.branches=fd.getAll('branches');
          config.sessionMinutes=Number(config.sessionMinutes);config.questionsPerEp=Number(config.questionsPerEp);
          const {project}=await request('create',{config});await openProject(project.id);notify('สร้างหลักสูตรแล้ว กดเริ่มสร้างโครงเมื่อพร้อม');break;
        }
        case 'settings-form':await request('settings',{key:$('#api-key').value.trim()});$('#api-key').value='';$('#settings-dialog').close();await loadStatus();notify('บันทึก API Key แล้ว');if(current)render();break;
        case 'outline-form':await mutate('outline',{chapters:editorDraft});$('#editor-dialog').close();notify('บันทึกโครงแล้ว');break;
        case 'content-form': {
          const part=parts().find(p=>p.id===partId),content={};
          content.sections=part.content.sections.map((_,i)=>({heading:fd.get(`heading-${i}`),body:fd.get(`body-${i}`)}));
          for(const key of ['example','exercise','hint','solution','summary','imagePrompt'])content[key]=fd.get(key);
          await mutate('part',{partId,content});$('#editor-dialog').close();break;
        }
        case 'plan-form':await mutate('schedule',{settings:{start:fd.get('start'),deadline:fd.get('deadline'),minutes:Number(fd.get('minutes')),days:fd.getAll('days').map(Number)}});notify('บันทึกแผนการเรียนแล้ว');break;
        case 'quiz-form': {
          const answers=Object.fromEntries([...fd].map(([id,a])=>[id,Number(a)]));
          if(Object.keys(answers).length<current.data.config.questionsPerEp && !confirm('ยังตอบไม่ครบ ต้องการส่งคำตอบหรือไม่?'))break;
          await mutate('attempt',{episodeId,answers});quizResult=current.data.attempts.at(-1);render();break;
        }
      }
    } catch(error) {notify(error.message,true);} finally {if(submit)submit.disabled=false;}
  });
  document.addEventListener('change',e=>{if(e.target.id==='quiz-episode'){episodeId=e.target.value;quizResult=null;render();}});
  $('#run-queue').addEventListener('click',start);
  $('#step-once').addEventListener('click',stepOnce);
  $('#pause-queue').addEventListener('click',async()=>{try{current=(await request('pause',{id:current.id})).project;render();notify('หยุดคิวแล้ว งานปัจจุบันจะบันทึกเมื่อเสร็จ');}catch(e){notify(e.message,true);}});
  $('#approve-outline').addEventListener('click',async()=>{try{await mutate('approveOutline');notify('ยืนยันโครงแล้ว กดทำต่อเพื่อสร้างเนื้อหา');}catch(e){notify(e.message,true);}});
  $('#refresh-project').addEventListener('click',()=>openProject(current.id).catch(e=>notify(e.message,true)));
  $('#new-project').addEventListener('click',()=>{
    if(pumping){notify('กรุณาหยุดคิวก่อนสร้างหลักสูตรใหม่',true);return;}
    current=null;location.hash='';$('#project-view').hidden=true;$('#create-view').hidden=false;$('#sidebar').classList.remove('open');loadList().catch(e=>notify(e.message,true));
  });
  $('#menu-toggle').addEventListener('click',()=>$('#sidebar').classList.toggle('open'));
  $('#settings-open').addEventListener('click',()=>$('#settings-dialog').showModal());
  async function loadStatus() {
    const data=await request('status'); apiReady=data.configured;
    $('#key-dot').classList.toggle('ready',apiReady);
    $('#key-status').textContent=(apiReady?'บันทึก credential แล้ว ระบบจะตรวจโมเดลเมื่อเริ่มสร้าง':'ยังไม่ได้ตั้งค่า API Key')+(data.canConfigure?'':' · ติดต่อแอดมินเพื่อเปลี่ยน Key');
    $('#api-key').disabled=!data.canConfigure;$('#settings-form button[type=submit]').disabled=!data.canConfigure;
  }
  async function init() {drawIcons();await Promise.all([loadStatus(),loadList()]);if(/^[a-f0-9]{24}$/.test(location.hash.slice(1)))await openProject(location.hash.slice(1));}
  init().catch(e=>notify(e.message,true));
})();
