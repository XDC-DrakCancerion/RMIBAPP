(() => {
  const USER_KEY = 'rmib_user';
  const PACK_KEY = 'rmib_test_pack';
  const SESSION_PREFIX = 'rmib_offline_session_';
  const GROUPS = ['A','B','C','D','E','F','G','H','I'];

  const el = (id) => document.getElementById(id);
  const sectionNoUser = el('sectionNoUser');
  const sectionNoPack = el('sectionNoPack');
  const sectionHome = el('sectionHome');
  const sectionTest = el('sectionTest');
  const sectionResult = el('sectionResult');
  const netBadge = el('netBadge');
  const userInfo = el('userInfo');
  const groupTitle = el('groupTitle');
  const testInfo = el('testInfo');
  const groupNav = el('groupNav');
  const jobsBody = el('jobsBody');
  const errorBox = el('errorBox');
  const fav1 = el('fav1');
  const fav2 = el('fav2');
  const fav3 = el('fav3');
  const resultTop3 = el('resultTop3');
  const resultBody = el('resultBody');
  const resultTotal = el('resultTotal');
  const syncStatus = el('syncStatus');
  const btnDownloadPack = el('btnDownloadPack');
  const packStatus = el('packStatus');
  const btnStart = el('btnStart');
  const btnResume = el('btnResume');
  const btnReset = el('btnReset');
  const btnPrev = el('btnPrev');
  const btnNext = el('btnNext');
  const btnBackHome = el('btnBackHome');
  const btnResultHome = el('btnResultHome');
  const btnSync = el('btnSync');
  const btnOpenResult = el('btnOpenResult');

  let user = null;
  let pack = null;
  let session = null;
  let jobMap = {};
  let categoryMap = {};

  function setBadge() {
    const online = navigator.onLine;
    netBadge.textContent = online ? 'Online' : 'Offline';
    netBadge.classList.toggle('online', online);
    netBadge.classList.toggle('offline', !online);
  }

  function showOnly(section) {
    [sectionNoUser, sectionNoPack, sectionHome, sectionTest, sectionResult].forEach((s) => {
      if (!s) return;
      s.classList.toggle('hidden', s !== section);
    });
  }

  function loadUser() {
    try {
      const raw = localStorage.getItem(USER_KEY);
      if (!raw) return null;
      return JSON.parse(raw);
    } catch {
      return null;
    }
  }

  function loadPack() {
    try {
      const raw = localStorage.getItem(PACK_KEY);
      if (!raw) return null;
      return JSON.parse(raw);
    } catch {
      return null;
    }
  }

  function savePack(data) {
    try { localStorage.setItem(PACK_KEY, JSON.stringify(data)); } catch {}
  }

  function sessionKey() {
    if (!user || !user.id_pengguna) return null;
    return SESSION_PREFIX + user.id_pengguna;
  }

  function loadSession() {
    const key = sessionKey();
    if (!key) return null;
    try {
      const raw = localStorage.getItem(key);
      if (!raw) return null;
      return JSON.parse(raw);
    } catch {
      return null;
    }
  }

  function saveSession() {
    const key = sessionKey();
    if (!key || !session) return;
    session.updated_at = new Date().toISOString();
    try { localStorage.setItem(key, JSON.stringify(session)); } catch {}
  }

  function resetSession() {
    const key = sessionKey();
    if (!key) return;
    localStorage.removeItem(key);
    session = null;
  }

  function buildMaps() {
    jobMap = {};
    categoryMap = {};
    const cats = (pack && pack.data && pack.data.categories) || [];
    cats.forEach((c) => {
      categoryMap[c.id_kategori] = c;
    });

    const groups = (pack && pack.data && pack.data.groups) || {};
    Object.keys(groups).forEach((g) => {
      const byJk = groups[g] || {};
      ['L','P'].forEach((jk) => {
        const list = byJk[jk] || [];
        list.forEach((job) => {
          jobMap[job.id_pekerjaan] = { id_kategori: job.id_kategori, kelompok: g };
        });
      });
    });
  }

  function groupJobs(code) {
    const groups = (pack && pack.data && pack.data.groups) || {};
    const byJk = groups[code] || {};
    const jk = (user && user.jenis_kelamin) || 'L';
    return byJk[jk] || byJk['L'] || [];
  }

  function hasValidPack() {
    return pack && pack.data && pack.data.groups;
  }

  function setError(msg) {
    if (!errorBox) return;
    if (!msg) {
      errorBox.classList.add('hidden');
      errorBox.textContent = '';
      return;
    }
    errorBox.textContent = msg;
    errorBox.classList.remove('hidden');
  }

  function renderHome() {
    if (!userInfo) return;
    const jkLabel = user.jenis_kelamin === 'P' ? 'Perempuan' : 'Laki-laki';
    userInfo.textContent = `Nama: ${user.nama || '-'} | JK: ${jkLabel}`;

    const hasSession = !!session;
    btnResume.disabled = !hasSession;
    if (hasSession) {
      btnResume.textContent = (session.status === 'completed' || session.status === 'synced')
        ? 'Lihat Hasil'
        : 'Lanjutkan Tes';
    }
    showOnly(sectionHome);
  }

  function renderGroupNav() {
    if (!groupNav) return;
    groupNav.innerHTML = '';
    GROUPS.forEach((g) => {
      const btn = document.createElement('button');
      btn.textContent = g;
      btn.className = g === session.current_group ? 'active' : '';
      const done = session.answers && session.answers[g] && Object.keys(session.answers[g]).length >= 12;
      if (done) btn.classList.add('done');
      btn.addEventListener('click', () => {
        session.current_group = g;
        saveSession();
        renderTest();
      });
      groupNav.appendChild(btn);
    });
  }

  function renderJobs() {
    const code = session.current_group;
    const jobs = groupJobs(code);
    groupTitle.textContent = code;
    testInfo.textContent = `Kelompok ${code} - isi peringkat 1 sampai 12`;
    jobsBody.innerHTML = '';

    jobs.forEach((job, idx) => {
      const tr = document.createElement('tr');

      const tdNo = document.createElement('td');
      tdNo.textContent = String(idx + 1);

      const tdName = document.createElement('td');
      tdName.textContent = job.nama_pekerjaan || '-';

      const tdRank = document.createElement('td');
      const select = document.createElement('select');
      select.className = 'input';
      const optEmpty = document.createElement('option');
      optEmpty.value = '';
      optEmpty.textContent = 'Pilih';
      select.appendChild(optEmpty);
      for (let i = 1; i <= 12; i++) {
        const opt = document.createElement('option');
        opt.value = String(i);
        opt.textContent = String(i);
        select.appendChild(opt);
      }

      const answers = (session.answers && session.answers[code]) || {};
      const val = answers[job.id_pekerjaan];
      if (val) select.value = String(val);

      select.addEventListener('change', () => {
        if (!session.answers) session.answers = {};
        if (!session.answers[code]) session.answers[code] = {};
        const v = parseInt(select.value, 10);
        if (Number.isNaN(v)) {
          delete session.answers[code][job.id_pekerjaan];
        } else {
          session.answers[code][job.id_pekerjaan] = v;
        }
        saveSession();
      });

      tdRank.appendChild(select);
      tr.appendChild(tdNo);
      tr.appendChild(tdName);
      tr.appendChild(tdRank);
      jobsBody.appendChild(tr);
    });
  }

  function validateCurrentGroup() {
    const code = session.current_group;
    const jobs = groupJobs(code);
    const answers = (session.answers && session.answers[code]) || {};
    if (jobs.length < 12) return 'Paket pekerjaan belum lengkap.';

    for (const job of jobs) {
      const val = answers[job.id_pekerjaan];
      if (!val || val < 1 || val > 12) {
        return 'Semua pekerjaan harus diberi peringkat 1 sampai 12.';
      }
    }
    return '';
  }

  function renderTest() {
    if (!session) return;
    renderGroupNav();
    renderJobs();
    fav1.value = (session.fav && session.fav.fav1) || '';
    fav2.value = (session.fav && session.fav.fav2) || '';
    fav3.value = (session.fav && session.fav.fav3) || '';
    setError('');
    showOnly(sectionTest);
  }

  function computeResult() {
    const totals = {};
    Object.keys(categoryMap).forEach((id) => { totals[id] = 0; });

    const answersByGroup = session.answers || {};
    Object.keys(answersByGroup).forEach((g) => {
      const answers = answersByGroup[g];
      Object.keys(answers).forEach((jobId) => {
        const meta = jobMap[jobId];
        if (!meta || !meta.id_kategori) return;
        const rank = parseInt(answers[jobId], 10);
        if (!Number.isNaN(rank)) {
          const key = String(meta.id_kategori);
          totals[key] = (totals[key] || 0) + rank;
        }
      });
    });

    const entries = Object.keys(totals).map((id) => ({
      id: parseInt(id, 10),
      total: totals[id]
    }));

    entries.sort((a, b) => a.total - b.total);
    const rankings = {};
    entries.forEach((e, idx) => { rankings[e.id] = idx + 1; });

    const top3 = entries.slice(0, 3).map((e) => e.id);

    session.result = { totals, rankings, top3 };
  }

  function renderResult() {
    if (!session || !session.result) return;
    resultTop3.innerHTML = '';
    resultBody.innerHTML = '';

    const totals = session.result.totals || {};
    const rankings = session.result.rankings || {};
    const top3 = session.result.top3 || [];

    top3.forEach((id, idx) => {
      const c = categoryMap[id] || {};
      const label = `${c.nama_kategori || '-'} (${c.kd_kategori || '-'})`;
      const box = document.createElement('div');
      box.className = 'card-mini';
      box.innerHTML = `<div><b>${idx + 1}. ${label}</b></div><div class="muted">${c.deskripsi_kategori || '-'}</div>`;
      resultTop3.appendChild(box);
    });

    Object.keys(totals).forEach((id) => {
      const c = categoryMap[id] || {};
      const label = `${c.nama_kategori || '-'} (${c.kd_kategori || '-'})`;
      const tr = document.createElement('tr');
      tr.innerHTML = `<td>${label}</td><td>${totals[id] || 0}</td><td>${rankings[id] || '-'}</td>`;
      resultBody.appendChild(tr);
    });

    const sum = Object.keys(totals).reduce((acc, id) => acc + (totals[id] || 0), 0);
    resultTotal.textContent = `Total skor: ${sum} (validasi: 702)`;

    if (session.status === 'synced') {
      btnSync.disabled = true;
      btnOpenResult.classList.remove('hidden');
      syncStatus.textContent = 'Sudah tersinkron.';
    } else {
      btnSync.disabled = !navigator.onLine;
      btnOpenResult.classList.add('hidden');
      syncStatus.textContent = navigator.onLine ? '' : 'Offline: sinkronkan saat online.';
    }

    showOnly(sectionResult);
  }

  function finishTest() {
    session.status = 'completed';
    computeResult();
    saveSession();
    renderResult();
  }

  function collectFav() {
    if (!session.fav) session.fav = {};
    session.fav.fav1 = (fav1.value || '').trim();
    session.fav.fav2 = (fav2.value || '').trim();
    session.fav.fav3 = (fav3.value || '').trim();
  }

  function toPayload() {
    const answers = {};
    GROUPS.forEach((g) => {
      const map = (session.answers && session.answers[g]) || {};
      answers[g] = Object.keys(map).map((jobId) => ({
        id_pekerjaan: parseInt(jobId, 10),
        peringkat: parseInt(map[jobId], 10)
      }));
    });

    return {
      local_id: session.local_id,
      client_started_at: session.created_at,
      client_finished_at: session.updated_at,
      fav: session.fav || {},
      answers
    };
  }

  async function syncNow() {
    if (!navigator.onLine) {
      syncStatus.textContent = 'Offline: tidak bisa sinkron.';
      return;
    }

    syncStatus.textContent = 'Menyinkronkan...';
    btnSync.disabled = true;
    try {
      const resp = await fetch('../peserta/api_sync.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'include',
        body: JSON.stringify(toPayload())
      });
      const text = await resp.text();
      let data;
      try {
        data = JSON.parse(text);
      } catch {
        throw new Error('Sesi login habis. Silakan login ulang.');
      }
      if (!resp.ok || !data.ok) throw new Error(data.message || 'Gagal sinkron.');

      session.status = 'synced';
      session.server_sesi_id = data.id_sesi || null;
      saveSession();
      syncStatus.textContent = 'Sinkron berhasil.';
      btnOpenResult.classList.remove('hidden');
      renderResult();
    } catch (e) {
      btnSync.disabled = false;
      syncStatus.textContent = 'Gagal sinkron: ' + e.message;
    }
  }

  function startNewSession() {
    session = {
      local_id: (crypto && crypto.randomUUID) ? crypto.randomUUID() : String(Date.now()),
      created_at: new Date().toISOString(),
      updated_at: new Date().toISOString(),
      status: 'in_progress',
      current_group: 'A',
      answers: {},
      fav: { fav1: '', fav2: '', fav3: '' }
    };
    saveSession();
    renderTest();
  }

  function init() {
    setBadge();
    window.addEventListener('online', () => { setBadge(); renderResult(); });
    window.addEventListener('offline', () => { setBadge(); renderResult(); });

    user = loadUser();
    if (!user || !user.id_pengguna) {
      showOnly(sectionNoUser);
      return;
    }

    pack = loadPack();
    if (!pack || !hasValidPack()) {
      showOnly(sectionNoPack);
    } else {
      buildMaps();
      session = loadSession();
      renderHome();
    }

    if (btnDownloadPack) {
      btnDownloadPack.addEventListener('click', async () => {
        if (!navigator.onLine) {
          packStatus.textContent = 'Offline: sambungkan internet.';
          return;
        }
        packStatus.textContent = 'Mengunduh paket...';
        try {
          const resp = await fetch('../peserta/api_test_pack.php', { credentials: 'include' });
          const text = await resp.text();
          let data;
          try {
            data = JSON.parse(text);
          } catch {
            throw new Error('Sesi login habis. Silakan login ulang.');
          }
          if (!resp.ok || !data.ok) throw new Error(data.message || 'Gagal memuat paket.');
          data.cached_at = new Date().toISOString();
          savePack(data);
          pack = data;
          buildMaps();
          packStatus.textContent = 'Paket tes tersimpan.';
          session = loadSession();
          renderHome();
        } catch (e) {
          packStatus.textContent = 'Gagal: ' + e.message;
        }
      });
    }

    if (btnStart) btnStart.addEventListener('click', startNewSession);
    if (btnResume) btnResume.addEventListener('click', () => {
      if (!session) return;
      if (session.status === 'completed' || session.status === 'synced') {
        renderResult();
        return;
      }
      renderTest();
    });
    if (btnReset) btnReset.addEventListener('click', () => {
      if (confirm('Reset tes offline? Data lokal akan dihapus.')) {
        resetSession();
        session = null;
        renderHome();
      }
    });

    if (btnPrev) btnPrev.addEventListener('click', () => {
      const idx = GROUPS.indexOf(session.current_group);
      const prev = GROUPS[idx - 1];
      if (!prev) return;
      session.current_group = prev;
      saveSession();
      renderTest();
    });

    if (btnNext) btnNext.addEventListener('click', () => {
      collectFav();
      const err = validateCurrentGroup();
      if (err) {
        setError(err);
        return;
      }
      setError('');
      const idx = GROUPS.indexOf(session.current_group);
      const next = GROUPS[idx + 1];
      if (next) {
        session.current_group = next;
        saveSession();
        renderTest();
      } else {
        finishTest();
      }
    });

    if (btnBackHome) btnBackHome.addEventListener('click', () => renderHome());
    if (btnResultHome) btnResultHome.addEventListener('click', () => renderHome());
    if (btnSync) btnSync.addEventListener('click', syncNow);

    [fav1, fav2, fav3].forEach((f) => {
      f.addEventListener('input', () => {
        if (!session) return;
        collectFav();
        saveSession();
      });
    });
  }

  init();
})();
