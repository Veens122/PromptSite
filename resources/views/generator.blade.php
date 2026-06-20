<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>AI Website Builder</title>
    <!-- Tailwind CDN (dev) - in production compile Tailwind -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-slate-900 text-gray-100 min-h-screen">

    <div class="max-w-5xl mx-auto p-6">
        <h1 class="text-3xl font-bold mb-4">AI Website Builder</h1>

        <textarea id="website-prompt" class="w-full min-h-[140px] p-4 rounded-lg bg-gray-800 border border-gray-700"
            placeholder="Describe the website (pages, theme, colors, fonts, sections)"></textarea>

        <div class="flex gap-4 mt-4">
            <button id="generate-btn" class="px-6 py-3 bg-cyan-400 text-black rounded-lg font-semibold">Generate
                Website</button>
            <button id="open-projects" class="px-4 py-3 bg-gray-700 rounded-lg">My Projects</button>
        </div>

        <div id="progress-area" class="mt-6 hidden">
            <div id="status-text" class="mb-2 text-sm text-gray-300">Starting...</div>
            <div id="image-jobs" class="space-y-2"></div>
        </div>

        <div id="preview-area" class="mt-8 hidden">
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-xl font-semibold">Preview</h2>
                <div class="flex gap-2">
                    <button id="open-in-iframe" class="px-3 py-1 bg-gray-700 rounded">Open</button>
                    <button id="download-btn" class="px-3 py-1 bg-green-500 rounded">Download ZIP</button>
                </div>
            </div>

            <div id="preview-frame" class="border border-gray-700 rounded overflow-hidden" style="height:560px;">
                <iframe id="preview-iframe" srcdoc="" class="w-full h-full"></iframe>
            </div>

            <div class="mt-4">
                <h3 class="text-lg">Chat with AI (conversational edits)</h3>
                <div class="flex gap-2 mt-2">
                    <input id="chat-input" class="flex-1 p-2 rounded bg-gray-800 border border-gray-700"
                        placeholder="Ask: change hero color to #facc15" />
                    <button id="chat-send" class="px-4 py-2 bg-indigo-500 rounded">Send</button>
                </div>
                <div id="chat-log" class="mt-3 max-h-40 overflow-auto text-sm text-gray-300"></div>
            </div>
        </div>
    </div>

    <script>
        const API_BASE = '/api'; // adjust if your API prefix differs
        const csrftoken = document.querySelector('meta[name="csrf-token"]').content;

        document.getElementById('generate-btn').addEventListener('click', async () => {
            const prompt = document.getElementById('website-prompt').value.trim();
            if (!prompt) return alert('Please enter a prompt.');

            document.getElementById('progress-area').classList.remove('hidden');
            document.getElementById('status-text').innerText = 'Sending prompt to AI...';
            document.getElementById('image-jobs').innerHTML = '';

            const res = await fetch(API_BASE + '/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrftoken
                },
                body: JSON.stringify({
                    prompt
                })
            });
            const data = await res.json();
            if (!res.ok) {
                document.getElementById('status-text').innerText = 'Error: ' + (data.error || res.statusText);
                return;
            }

            const projectId = data.project_id;
            // show initial html (with tokens)
            document.getElementById('preview-area').classList.remove('hidden');
            document.getElementById('preview-iframe').srcdoc = data.html;

            document.getElementById('status-text').innerText = 'Images queued. Generating images...';
            // start polling for job statuses
            pollStatus(projectId);
            // save current project id to window for chat edits
            window._currentProjectId = projectId;
        });

        // poll status repeatedly
        async function pollStatus(projectId) {
            const jobsContainer = document.getElementById('image-jobs');

            const interval = setInterval(async () => {
                const resp = await fetch(API_BASE + '/project/' + projectId + '/status');
                const json = await resp.json();
                // update status text
                document.getElementById('status-text').innerText = 'Project status: ' + (json.project
                    .status || 'unknown');

                // update jobs list
                jobsContainer.innerHTML = '';
                json.jobs.forEach(job => {
                    const el = document.createElement('div');
                    el.className = 'flex items-center justify-between bg-gray-800 p-2 rounded';
                    el.innerHTML = `<div class="flex items-center gap-3"><div class="w-10 h-10 bg-gray-700 rounded overflow-hidden ${job.status==='done' ? '' : 'animate-pulse'}">
        ${job.result_url ? '<img src="'+job.result_url+'" class="w-full h-full object-cover">' : ''}
        </div><div>
          <div class="text-sm font-medium">${job.token}</div>
          <div class="text-xs text-gray-400">${job.status}${job.error ? ' — ' + job.error : ''}</div>
        </div></div>`;
                    jobsContainer.appendChild(el);
                });

                // if project html updated (images inserted), refresh preview iframe content
                if (json.project.html) {
                    document.getElementById('preview-iframe').srcdoc = json.project.html;
                }

                if (json.project.status === 'ready' || json.project.status === 'failed') {
                    clearInterval(interval);
                    document.getElementById('status-text').innerText = 'Finished: ' + json.project.status;
                }
            }, 2500);
        }

        // Chat / conversational edits
        document.getElementById('chat-send').addEventListener('click', async () => {
            const msg = document.getElementById('chat-input').value.trim();
            if (!msg) return;
            const projectId = window._currentProjectId;
            if (!projectId) return alert('No project loaded.');

            addChatLog('You: ' + msg);
            document.getElementById('chat-input').value = '';

            // send edit request
            const res = await fetch(API_BASE + '/project/' + projectId + '/edit', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrftoken
                },
                body: JSON.stringify({
                    message: msg
                })
            });
            const data = await res.json();
            if (!res.ok) {
                addChatLog('AI Error: ' + (data.error || res.statusText));
                return;
            }
            addChatLog('AI: Made edits. Regenerating images (if any)...');
            // poll status again for new images
            pollStatus(projectId);
        });

        function addChatLog(text) {
            const c = document.getElementById('chat-log');
            const el = document.createElement('div');
            el.innerText = text;
            c.appendChild(el);
            c.scrollTop = c.scrollHeight;
        }
    </script>
</body>

</html>
