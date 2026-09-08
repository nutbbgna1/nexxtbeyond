const socketUrl = process.argv[2];
if (!socketUrl) throw new Error("DevTools WebSocket URL is required");

const ws = new WebSocket(socketUrl);
let nextId = 0;
const pending = new Map();
const send = (method, params = {}) => new Promise((resolve, reject) => {
  const id = ++nextId;
  pending.set(id, { resolve, reject });
  ws.send(JSON.stringify({ id, method, params }));
});
ws.onmessage = event => {
  const message = JSON.parse(event.data);
  if (!message.id || !pending.has(message.id)) return;
  const task = pending.get(message.id);
  pending.delete(message.id);
  message.error ? task.reject(new Error(message.error.message)) : task.resolve(message.result);
};

await new Promise((resolve, reject) => {
  ws.onopen = resolve;
  ws.onerror = reject;
});
await send("Runtime.enable");
await send("Page.enable");
const now = new Date().toISOString();
const seed = `
  localStorage.setItem('nb_user_role', 'student');
  localStorage.setItem('nb_user', JSON.stringify({id:'student_a',email:'a@example.com',name:'Student A'}));
  localStorage.setItem('nb_exams', JSON.stringify([
    {id:'exam_a',title:'คะแนนของบัญชี A',type:'quiz',status:'active',isPublished:true,questions:[]},
    {id:'exam_b',title:'คะแนนของบัญชี B',type:'quiz',status:'active',isPublished:true,questions:[]}
  ]));
  localStorage.setItem('nb_test_attempts', JSON.stringify([
    {id:'attempt_a',examId:'exam_a',userId:'student_a',score:88,correctCount:7,totalQuestions:8,timeSpentSeconds:125,completedAt:'${now}'},
    {id:'attempt_b',examId:'exam_b',userId:'student_b',score:25,correctCount:1,totalQuestions:4,timeSpentSeconds:50,completedAt:'${now}'}
  ]));
`;
await send("Runtime.evaluate", { expression: seed });
await send("Page.reload", { ignoreCache: true });
await new Promise(resolve => setTimeout(resolve, 1200));
const evaluated = await send("Runtime.evaluate", {
  expression: `JSON.stringify({
    text: document.body.innerText,
    historyCount: document.querySelector('[data-history-count]')?.textContent,
    historyVisible: !document.getElementById('test-history-container')?.classList.contains('hidden'),
    hasScoresMenu: !!document.querySelector('a[href*="view=history"]')
  })`,
  returnByValue: true
});
const state = JSON.parse(evaluated.result.value);
const checks = {
  ownScoreVisible: state.text.includes("คะแนนของบัญชี A") && state.text.includes("88%"),
  otherAccountHidden: !state.text.includes("คะแนนของบัญชี B"),
  historyCountCorrect: state.historyCount === "1",
  historyTabVisible: state.historyVisible,
  signedInMenuVisible: state.hasScoresMenu
};
for (const [name, passed] of Object.entries(checks)) console.log(`${passed ? "PASS" : "FAIL"} ${name}`);

await send("Page.navigate", { url: "http://127.0.0.1:8765/auth.php" });
await new Promise(resolve => setTimeout(resolve, 800));
await send("Runtime.evaluate", {
  expression: `(async () => {
    const bytes = new TextEncoder().encode('TestPass1');
    const digest = await crypto.subtle.digest('SHA-256', bytes);
    const passwordHash = [...new Uint8Array(digest)].map(byte => byte.toString(16).padStart(2, '0')).join('');
    localStorage.setItem('nb_users', JSON.stringify([{id:'student_login',email:'login@example.com',name:'Login Student',role:'student',passwordHash}]));
    document.getElementById('login-email').value = 'login@example.com';
    document.getElementById('login-password').value = 'TestPass1';
    document.querySelector('[data-action="login"]').click();
  })()`,
  awaitPromise: true
});
await new Promise(resolve => setTimeout(resolve, 900));
const loginResult = await send("Runtime.evaluate", {
  expression: `JSON.stringify({path:location.pathname,user:JSON.parse(localStorage.getItem('nb_user') || 'null')})`,
  returnByValue: true
});
const loginState = JSON.parse(loginResult.result.value);
const loginPassed = loginState.path.endsWith('/index.php') && loginState.user?.id === 'student_login';
console.log(`${loginPassed ? "PASS" : "FAIL"} registered account login`);
ws.close();
if (Object.values(checks).some(passed => !passed) || !loginPassed) process.exitCode = 1;
