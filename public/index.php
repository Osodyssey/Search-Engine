<!doctype html>
<html lang="fa" dir="rtl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <script src="https://cdn.tailwindcss.com"></script>
  <title>جستجوگر oodi-os</title>
</head>
<body class="bg-gray-50 text-gray-800">
  <div class="max-w-3xl mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4">موتور جستجوی oodi-os</h1>
    <div class="flex gap-2">
      <input id="q" type="text" placeholder="جستجو..." class="flex-1 p-3 rounded-md border" />
      <button id="go" class="px-4 py-3 bg-blue-600 text-white rounded-md">جستجو</button>
    </div>
    <div id="results" class="mt-6 space-y-4"></div>
  </div>
  <script>
    async function doSearch(q) {
      const res = await fetch('/api/search.php?q=' + encodeURIComponent(q));
      if (!res.ok) {
        document.getElementById('results').innerHTML = '<p class="text-red-500">خطا در تماس با سرویس جستجو</p>';
        return;
      }
      const data = await res.json();
      const box = document.getElementById('results');
      box.innerHTML = '';
      if (!data.length) { box.innerHTML = '<p class="text-gray-500">نتیجه‌ای پیدا نشد.</p>'; return; }
      for (const r of data) {
        const el = document.createElement('div');
        el.className = 'p-4 bg-white rounded-md shadow-sm';
        el.innerHTML = `<a href="${r.url}" class="text-blue-600 font-semibold" target="_blank" rel="noopener noreferrer">${r.title || r.url}</a>
                        <p class="text-sm mt-2 text-gray-600">${r.snippet}</p>
                        <p class="text-xs text-gray-400 mt-2">${r.url}</p>`;
        box.appendChild(el);
      }
    }
    document.getElementById('go').addEventListener('click', async () => {
      const q = document.getElementById('q').value.trim();
      if (!q) return;
      await doSearch(q);
    });
    document.getElementById('q').addEventListener('keydown', async (e) => {
      if (e.key === 'Enter') {
        const q = document.getElementById('q').value.trim();
        if (!q) return;
        await doSearch(q);
      }
    });
  </script>
</body>
</html>
