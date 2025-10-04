<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
  <title>Deep Search OODI — جستجوگر</title>
  <style>
    /* Extra small tweaks to mimic screenshot: centered floating chat box */
    body { background: #0f1724; color: #e6eef8; height:100vh; display:flex; align-items:center; justify-content:center; }
    .glass { background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(255,255,255,0.01)); border: 1px solid rgba(255,255,255,0.04); backdrop-filter: blur(6px); box-shadow: 0 10px 40px rgba(2,6,23,0.6); }
    .input-area:focus-within { box-shadow: 0 6px 24px rgba(30,64,175,0.2); transform: translateY(-1px); }
  </style>
</head>
<body>
  <div class="w-full max-w-4xl px-4">
    <div class="text-center mb-6">
      <img src="" alt="" style="width:36px;height:36px;display:inline-block;vertical-align:middle;margin-left:8px;">
      <h1 class="text-2xl font-semibold inline-block">چطور می‌توانم به شما کمک کنم؟</h1>
    </div>

    <div class="glass rounded-2xl p-6 mx-auto" style="max-width:900px;">
      <div class="input-area flex items-center gap-4">
        <input id="q" class="flex-1 bg-transparent border border-transparent placeholder:text-gray-400 text-white p-4 rounded-xl" placeholder="Message DeepSeek" />
        <div class="flex gap-2">
          <button id="btnSearch" class="bg-blue-600 px-4 py-2 rounded-xl">جستجو</button>
        </div>
      </div>

      <div id="chips" class="mt-4 flex gap-2"></div>

      <div id="results" class="mt-6 space-y-4"></div>
    </div>
  </div>

<script>
async function search(q) {
  const res = await fetch('/api/search.php?q=' + encodeURIComponent(q));
  if (!res.ok) return [];
  return await res.json();
}

function makeResult(r) {
  const el = document.createElement('div');
  el.className = 'p-4 bg-[#091225] border border-white/3 rounded-xl';
  el.innerHTML = `<a href="${r.url}" target="_blank" class="text-blue-400 font-semibold">${r.title || r.url}</a>
                  <p class="text-sm text-gray-300 mt-2">${r.snippet}</p>
                  <p class="text-xs text-gray-400 mt-2">${r.url}</p>`;
  return el;
}

document.getElementById('btnSearch').addEventListener('click', async () => {
  const q = document.getElementById('q').value.trim();
  if (!q) return;
  document.getElementById('results').innerHTML = '<p class="text-gray-400">در حال جستجو...</p>';
  const data = await search(q);
  const box = document.getElementById('results'); box.innerHTML = '';
  if (!data || data.length === 0) { box.innerHTML = '<p class="text-gray-400">نتیجه‌ای پیدا نشد.</p>'; return; }
  data.forEach(r => box.appendChild(makeResult(r)));
});

document.getElementById('q').addEventListener('keydown', async (e) => {
  if (e.key === 'Enter') { document.getElementById('btnSearch').click(); }
});
</script>
</body>
</html>
