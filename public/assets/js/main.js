/* =====================================================
   GLOBAL HELPERS
===================================================== */

function getActiveProject() {
    return localStorage.getItem("active_project");
}

function storeActiveProject(projectId) {
    if (projectId) {
        localStorage.setItem("active_project", projectId);
    }
}

/* =====================================================
   IMAGE POLLING
===================================================== */

function startPollingStatus(projectId, previewBox) {
    const replaced = new Set();
    let pollCount = 0;
    const MAX_POLLS = 120; // Stop after 120 attempts (6 minutes max)

    const poll = async () => {
        pollCount++;
        
        try {
            const res = await fetch(`/project/${projectId}/status`);
            const data = await res.json();

            if (!Array.isArray(data.jobs)) {
                console.warn('Status response missing jobs array', data);
                return;
            }

            // Count total and done jobs
            const totalJobs = data.jobs.length;
            const doneCount = data.jobs.filter(j => j.status === 'done').length;
            
            console.log(`[Poll ${pollCount}] Progress: ${doneCount}/${totalJobs} done (${data.progress || 0}%)`);

            data.jobs.forEach((job) => {
                if (job.status !== "done" || !job.result_url) return;
                if (replaced.has(job.token)) return;

                const doc = previewBox;
                const img = doc.querySelector(`img[data-image="${job.token}"]`);

                if (img) {
                    img.src = job.result_url;
                    img.removeAttribute("data-image");
                    replaced.add(job.token);
                    console.log(`✓ Updated image: ${job.token}`);
                }
            });

            // STOP CONDITIONS:
            // 1. All jobs are done
            // 2. Progress is 100
            // 3. Safety limit reached
            const allDone = doneCount === totalJobs;
            const progressComplete = data.progress >= 100;
            const maxRetriesReached = pollCount >= MAX_POLLS;

            if (allDone || progressComplete || maxRetriesReached) {
                console.log(`✅ Polling stopped: allDone=${allDone}, progressComplete=${progressComplete}, maxReached=${maxRetriesReached}`);
                return;
            }

            // Continue polling if not done
            setTimeout(poll, 3000);
        } catch (err) {
            console.error("Polling error:", err);
            
            if (pollCount < MAX_POLLS) {
                setTimeout(poll, 5000);
            } else {
                console.error("❌ Polling stopped: max retries reached");
            }
        }
    };

    poll();
}

/* =====================================================
   PARTICLES
===================================================== */

function initParticles() {
    if (!window.particlesJS) return;

    particlesJS("particles-js", {
        particles: {
            number: { value: 80, density: { enable: true, value_area: 800 } },
            color: { value: "#06b6d4" },
            shape: { type: "circle" },
            opacity: { value: 0.5, random: true },
            size: { value: 3, random: true },
            line_linked: {
                enable: true,
                distance: 150,
                color: "#06b6d4",
                opacity: 0.2,
                width: 1,
            },
            move: {
                enable: true,
                speed: 2,
                random: true,
                out_mode: "out",
            },
        },
        interactivity: {
            detect_on: "canvas",
            events: {
                onhover: { enable: true, mode: "grab" },
                onclick: { enable: true, mode: "push" },
                resize: true,
            },
        },
    });
}

/* =====================================================
   CHAT UI
===================================================== */

function appendMessage(role, text) {
    const chatMessages = document.getElementById("chatbot-messages");
    if (!chatMessages) return;

    const div = document.createElement("div");
    div.className =
        role === "user"
            ? "bg-cyan-600/40 rounded-lg p-3 text-sm text-white self-end"
            : "bg-gray-700/50 rounded-lg p-3 text-sm text-gray-200";

    div.innerText = text;
    chatMessages.appendChild(div);
    chatMessages.scrollTop = chatMessages.scrollHeight;
}

function renderChat(messages = []) {
    const chatMessages = document.getElementById("chatbot-messages");
    if (!chatMessages) return;

    chatMessages.innerHTML = "";

    if (!messages.length) {
        appendMessage(
            "assistant",
            "Hi! I'm your AI assistant. Tell me what you'd like to edit."
        );
        return;
    }

    messages.forEach((msg) => {
        appendMessage(msg.role, msg.content);
    });
}

function initChat() {
    const sendBtn = document.getElementById("chatbot-send");
    const input = document.getElementById("chatbot-input");
    const previewBox = document.getElementById("website-preview");
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    if (!sendBtn || !input) return;

    const sendMessage = async () => {
        const message = input.value.trim();
        if (!message) return;

        const projectId = getActiveProject();
        if (!projectId) {
            alert("No active project");
            return;
        }

        appendMessage("user", message);
        input.value = "";

        try {
            const res = await fetch(`/project/${projectId}/chat-edit`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrf,
                },
                body: JSON.stringify({ message }),
            });

            if (!res.ok) {
                throw new Error("Server error");
            }

            const data = await res.json();

            if (data.reply) {
                appendMessage("assistant", data.reply);
            }

            if (data.updated_html && previewBox) {
    previewBox.srcdoc = data.updated_html;
}

        } catch (err) {
            console.error("Chat error:", err);
            appendMessage("assistant", "Something went wrong. Please try again.");
        }
    };

    sendBtn.addEventListener("click", sendMessage);

    // Optional: allow Enter key to send message
    input.addEventListener("keypress", (e) => {
        if (e.key === "Enter") {
            e.preventDefault();
            sendMessage();
        }
    });
}


/* =====================================================
   MENU + CHAT TOGGLES
===================================================== */

function initMenu() {
    document
        .getElementById("mobile-menu-toggle")
        ?.addEventListener("click", () => {
            document.getElementById("mobile-menu")?.classList.toggle("hidden");
        });

    document.getElementById("chatbot-toggle")?.addEventListener("click", () => {
        document.getElementById("chatbot-panel")?.classList.toggle("hidden");
    });

    document.getElementById("chatbot-close")?.addEventListener("click", () => {
        document.getElementById("chatbot-panel")?.classList.add("hidden");
    });

    document.getElementById("edit-btn")?.addEventListener("click", () => {
    enableLiveEdit();
});
}





/* =====================================================
   INIT
===================================================== */

document.addEventListener("DOMContentLoaded", () => {
    initMenu();
    initChat();
    initParticles();
    initGenerateWebsite();
});

function initGenerateWebsite() {
    const generateBtn = document.getElementById("generate-btn");
    const promptInput = document.getElementById("website-prompt");
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    if (!generateBtn) return;

    generateBtn.addEventListener("click", async () => {
        const prompt = promptInput?.value?.trim();

        if (!prompt) {
            alert("Please enter a prompt");
            return;
        }

        generateBtn.disabled = true;
        generateBtn.innerText = "Generating...";

        try {
            const res = await fetch("/generate-site", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": csrf,
                },
                body: JSON.stringify({ prompt }),
            });

            if (!res.ok) throw new Error("Server error");

            const data = await res.json();

            if (!data.success) {
                alert("Generation failed.");
                return;
            }

            // STORE PROJECT
            // Store the active project in memory + localStorage
function setActiveProject(projectId, projectSlug) {
    window.currentProjectId = projectId;
    window.currentProjectSlug = projectSlug;

    localStorage.setItem("project_id", projectId);
    localStorage.setItem("project_slug", projectSlug);
}

// Example usage after generation
setActiveProject(data.project_id, data.slug);

// Save project after generation
storeActiveProject(data.project_id);

localStorage.setItem("project_id", data.project_id);
localStorage.setItem("project_slug", data.slug);

// Set globally
window.currentProjectId = data.project_id;
window.currentProjectSlug = data.slug;


// Restore on page reload
document.addEventListener("DOMContentLoaded", () => {
    const storedId = localStorage.getItem("project_id");
    const storedSlug = localStorage.getItem("project_slug");

    if (storedId && storedSlug) {
        window.currentProjectId = storedId;
        window.currentProjectSlug = storedSlug;
    }
});

            // SHOW PREVIEW UI
            document.getElementById('preview-wrapper').classList.remove('hidden');
            document.getElementById('preview-container').classList.remove('hidden');

            // LOAD HTML INTO IFRAME
            const iframe = document.getElementById("website-preview");
            iframe.srcdoc = data.html;

            // START IMAGE POLLING
            startPollingStatus(data.project_id, iframe.contentDocument);

        } catch (err) {
            console.error("Generation error:", err);
            alert("Network error. Please try again.");
        } finally {
            generateBtn.disabled = false;
            generateBtn.innerText = "Generate Website";
        }
    });
}

// To enable editing on the iframe
const iframe = document.getElementById("website-preview");


// Live editing
function enableLiveEdit() {
    const iframe = document.getElementById("website-preview");

    if (!iframe || !iframe.contentDocument) {
        alert("Preview not ready");
        return;
    }

    const doc = iframe.contentDocument;

    // Enable editing
    doc.body.contentEditable = true;
    doc.body.style.outline = "2px dashed #06b6d4";

    // Enable image click replace
    doc.querySelectorAll("img").forEach(img => {
        img.style.cursor = "pointer";

        img.onclick = () => {
            const newUrl = prompt("Enter new image URL:");
            if (newUrl) img.src = newUrl;
        };
    });

    // Show save button
    document.getElementById("save-edit-btn")?.classList.remove("hidden");

    console.log("Edit mode ON");
}


// Save edited HTML
document.getElementById("save-edit-btn")?.addEventListener("click", async () => {
    const iframe = document.getElementById("website-preview");

    if (!iframe || !iframe.contentDocument) {
        alert("Nothing to save");
        return;
    }

    const doc = iframe.contentDocument;

    // TURN OFF EDIT MODE
    doc.body.contentEditable = false;
    doc.body.style.outline = "none";

    // REMOVE IMAGE EDIT EVENTS
    doc.querySelectorAll("img").forEach(img => {
        img.style.cursor = "default";
        img.onclick = null;
    });

    // CLEAN EDIT ATTRIBUTES 
    doc.querySelectorAll('[contenteditable]').forEach(el => {
        el.removeAttribute('contenteditable');
    });

    const html = doc.documentElement.outerHTML;

    const projectId = window.currentProjectId || localStorage.getItem('project_id');

    if (!projectId) {
        alert("Project not found");
        return;
    }

    try {
    const res = await fetch(`/project/${projectId}/update-html`, {
        method: "PUT",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify({ html })
    });

    const data = await res.json();
    console.log("SAVE RESPONSE:", data);

    if (data.success) {

        alert("Saved successfully!");

        document.getElementById("save-edit-btn")?.classList.add("hidden");

        // disable edit mode
        const iframe = document.getElementById("website-preview");
        if (iframe?.contentDocument) {
            iframe.contentDocument.body.contentEditable = false;
            iframe.contentDocument.body.style.outline = "none";
        }

        // redirect to preview
        const projectSlug =
            window.currentProjectSlug ||
            localStorage.getItem('project_slug');

        if (projectSlug) {
            window.location.href = `/preview/${projectSlug}`;
        } else {
            console.error("Project slug not found");
        }

    } else {
        alert("Save failed from server");
    }

} catch (err) {
    console.error("SAVE ERROR:", err);
    alert("Failed to save changes");
}


const doc = iframe.contentDocument;

// Disable editing BEFORE saving
doc.body.contentEditable = false;
doc.body.style.outline = "none";

// Clean attributes
doc.querySelectorAll('[contenteditable]').forEach(el => {
    el.removeAttribute('contenteditable');
});

// Preview btn
document.getElementById('preview-btn')?.addEventListener('click', () => {
    const projectSlug = window.currentProjectSlug;

    if (!projectSlug) {
        alert("Generate a site first");
        return;
    }

    window.open(`/preview/${projectSlug}`, '_blank');
});




// Publish
async function publishProject(projectId) {
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    const res = await fetch(`/project/${projectId}/publish`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json'
        }
    });

    const data = await res.json();

    if (data.success) {

        alert('Website published successfully!');

        if (data.url) {
            window.open(data.url, '_blank');
        }

        const publishedLink = document.getElementById('published-link');

        if (publishedLink) {
            publishedLink.href = data.url;
            publishedLink.innerText = "View Published Site";
        }

    } else {
        alert(data.message || 'Failed to publish website.');
    }
}

// Publish btn click
document.getElementById('publish-btn')?.addEventListener('click', async () => {

    const projectId =
        window.currentProjectId ||
        localStorage.getItem('project_id');

    if (!projectId) {
        alert("Generate a site first");
        return;
    }

    try {
        await publishProject(projectId);
    } catch (error) {
        console.error('Publish Error:', error);
        alert('Something went wrong while publishing.');
    }
});
// Enable editing
function enableEdit(html) {
    document.getElementById('website-preview').outerHTML =
        `<textarea id="editor" class="w-full h-96">${html}</textarea>`;
}

// Download project
document.getElementById('download-btn')?.addEventListener('click', () => {
    const projectId = window.currentProjectId || localStorage.getItem('project_id');

    if (!projectId) {
        alert("No project found");
        return;
    }

    // Trigger download
    window.open(`/project/${projectId}/download`, '_blank');
});

